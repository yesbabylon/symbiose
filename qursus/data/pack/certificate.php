<?php
use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;

use qursus\UserAccess;
use qursus\Course;
use qursus\Module;

list($params, $providers) = eQual::announce([
    'description'   => "Returns a fully loaded JSON formatted single module.",
    'params'        => [
        'id' =>  [
            'description'   => 'Pack identifier (id field).',
            'type'          => 'integer',
            'required'      => true
        ],
        'mode' =>  [
            'description'   => 'Rendering mode (pdf or html).',
            'type'          => 'string',
            'default'       => 'pdf'
        ],
        'lang' =>  [
            'description'   => 'Language requested for multilang values.',
            'type'          => 'string',
            'default'       => constant('DEFAULT_LANG')
        ]
    ],
    'response'      => [
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm) = [ $providers['context'], $providers['orm']];

/*
    Retrieve current user id
*/

if(!isset($_COOKIE) || !isset($_COOKIE["wp_lms_user"]) || !is_numeric($_COOKIE["wp_lms_user"])) {
  throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
}

$user_id = (int) $_COOKIE["wp_lms_user"];

if($user_id <= 0) {
  throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
}


// default to empty image for QR code
$installment_qr_url = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII=";


/*
  Retrieve info related to user : completeness and unique alpha code
*/
$access_ids = UserAccess::search([ ['course_id', '=', $params['id']], ['user_id', '=', $user_id] ])->ids();

if(!count($access_ids)) {
  throw new Exception('missing_status', QN_ERROR_INVALID_PARAM);
}

$access = UserAccess::ids($access_ids)->read(['code_alpha', 'is_complete', 'modified'])->first();

if(!$access) {
  throw new Exception('missing_status', QN_ERROR_INVALID_PARAM);
}

if(!$access['is_complete']) {
  throw new Exception('cert_not_available', QN_ERROR_NOT_ALLOWED);
}

/*
  Retrieve Pack details
*/

$pack = Pack::id($params['id'])->read(['title', 'subtitle', 'modules_ids'])->first();

if(!$pack) {
  throw new Exception('unavailable_pack', QN_ERROR_UNKNOWN);
}


/*
  Retrieve User personal details from WP table
*/

$db = $orm->getDB();
$res = $db->sendQuery("SELECT meta_key, meta_value FROM `wp_usermeta` WHERE `user_id` = {$user_id} and ( meta_key = 'first_name' OR meta_key = 'last_name');");
$user = [
  'id'        => $user_id,
  'firstname' => '',
  'lastname'  => ''
];


while ($row = $db->fetchArray($res)) {
  if($row['meta_key'] == 'first_name') {
    $user['firstname'] = $row['meta_value'];
  }
  else if($row['meta_key'] == 'last_name') {
    $user['lastname'] = $row['meta_value'];
  }
}


try {
  $result = Builder::create()
  ->data('https://www.help2protect.info/cert/'.$access['code_alpha'])
  ->margin(0)
  ->build();

  $installment_qr_url = $result->getDataUri();
}
catch(Exception $e) {

}

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>help2protect</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

<style>
<?php

  if($params['mode'] == 'pdf') {
    echo qursus_pack_certificate_get_style_pdf();
  }
  else {
    echo qursus_pack_certificate_get_style_html();
  }

?>
</style>

  </head>
<body>


<div class="cert-container">

  <div class="cert-stamp-container">
    <div class="stamp-background"></div>
    <div class="stamp-content">
      <div class="title">Course Certificate</div>
      <div class="stamp"><img src="https://www.help2protect.info/app/cert_stamp.svg" width="250" height="250"></div>
    </div>
  </div>

  <div class="title-container">Help2Protect.info</div>

  <div class="details-container">
    <div class="date"><?php echo date('j F Y', $access['modified']); ?></div>
    <div class="name"><?php echo $user['firstname'].' '.strtoupper($user['lastname']); ?></div>
    <div class="success-header">Has successfully completed</div>
    <div class="success-body"><?php echo str_replace(' - ', '<br />', $pack['subtitle']); ?></div>
    <div class="success-footer">An online non-credit course of <?php echo count($pack['modules_ids']); ?> module(s) delivered through Help2Protect.info</div>
  </div>

  <div class="signatures-container">
    <div class="sig sig-1">
      <div class="img"></div>
      <div class="txt">Catherine Piana</div>
    </div>
    <div class="sig sig-2">
      <div class="img"></div>
      <div class="txt">Hugo Lüke</div>
    </div>
  </div>

  <div class="directors-container">
    Managing directors of Help2Protect SRL
  </div>


  <div class="code-container">
    <div class="qr-code">
      <img src="<?php echo $installment_qr_url; ?>" width= "150" height="150" />
    </div>
    <div class="notice">
    Verify at <a href="https://www.help2protect.info/cert/<?php echo $access['code_alpha']; ?>">help2protect.info/cert/<?php echo $access['code_alpha']; ?></a><br />
    Help2Protect has confirmed the identity of this individual and his participation in the course by generating this personalised QR code.
    </div>

  </div>

</div>

</body>
</html>
<?php
$html = ob_get_clean();

// instantiate and use the dompdf class
$options = new DompdfOptions();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$http_context = stream_context_create([
	'ssl' => [
		'verify_peer'       => FALSE,
		'verify_peer_name'  => FALSE,
		'allow_self_signed' => TRUE
	]
]);
$dompdf->setHttpContext($http_context);

$dompdf->setPaper('A4', 'landscape');
$dompdf->loadHtml((string) $html);
$dompdf->render();

// get generated PDF raw binary
$output = $dompdf->output();


if($params['mode'] == 'pdf') {
    $context->httpResponse()
    ->header('Content-Type', 'application/pdf')
    // ->header('Content-Disposition', 'attachment; filename="document.pdf"')
    ->header('Content-Disposition', 'inline; filename="document.pdf"')
    ->body($output)
    ->send();

}
else {
    $context->httpResponse()
    ->header('Content-Type', 'text/html')
    ->body($html)
    ->send();
}




function qursus_pack_certificate_get_style_pdf() {
  return "
    html {
      margin: 0;
      padding: 0;
      border: 0;
      width: 100%;
    }

    body {
      position: relative;
      padding: 0;
      border: 0;
      width: 100%;
    }

    .cert-container {
      background-image: url('https://www.help2protect.info/app/cert_background.png');
      background-size: contain;
      border: solid 1px #155991;
      border-radius: 10px;
      width: 970px;
      margin: auto;
      margin-top: 70px;
      height: 645px;
      font-family: Montserrat;
    }

    .cert-stamp-container {
      position: absolute;
      width: 310px;
      height: 400px;
      top: 0px;
      right: 110px;
      z-index: 1;
    }

    .cert-stamp-container .stamp-background {
      position: absolute;
      width: 310px;
      height: 400px;
      background-color: rgba(230, 230, 230, 0.7);
      z-index: 1;
      border-bottom-left-radius: 15px;
      border-bottom-right-radius: 15px;
      border: solid 6px rgba(100,100,100,0.2);
      border-top: 0px;
      border-left: 0px;
    }

    .cert-stamp-container .stamp-content {
      position: absolute;
      width: 310px;
      height: 400px;
      z-index: 2;
      /* border: solid 1px red; */
    }

    .cert-stamp-container .stamp-content .title {
      position: absolute;
      height: 30px;
      width: 310px;
      text-transform: uppercase;
      color: #155991;
      font-weight: 700;
      top: 80px;
      left: 0px;
      font-size: 24px;
      text-align: center;
      /* border: solid 1px green; */
    }

    .cert-stamp-container .stamp-content .stamp {
      position: absolute;
      top: 130px;
      left: 30px;
      width: 250px;
      height: 250px;
    }


    .title-container {
      position: absolute;
      top: 140px;
      left: 160px;
      height: 52px;
      width: 100%;
      font-size: 52px;
      font-weight: 700;
      line-height: 52px;
      color: #155991;
      /* border: solid 1px blue; */
    }

    .details-container {
      position: absolute;
      top: 250px;
      left: 160px;
      height: 300px;
      width: 520px;
      /* border: solid 1px blue; */
    }

    .details-container .date {
      position: absolute;
      top: 0px;
      font-size: 13px;
      font-weight: 500;
      /*border: solid 1px blue;*/
    }

    .details-container .name {
      position: absolute;
      top: 30px;
      font-size: 32px;
      font-weight: 600;
      /*border: solid 1px blue;*/
    }

    .details-container .success-header {
      position: absolute;
      top: 110px;
      font-size: 13px;
      font-weight: 500;
      /*border: solid 1px blue;*/
    }

    .details-container .success-body {
      position: absolute;
      top: 160px;
      width: 500px;
      font-size: 26px;
      line-height: 20px;
      font-weight: 600;
      /*border: solid 1px blue;*/
    }

    .details-container .success-footer {
      position: absolute;
      top: 240px;
      font-size: 13px;
      font-weight: 500;
      width: 350px;
      /* border: solid 1px blue; */
    }

    .signatures-container {
      position: absolute;
      top: 560px;
      left: 160px;
      height: 100px;
      width: 500px;
      /* border: solid 1px blue;  */
    }

    .signatures-container .sig {
      margin-top: 10px;
      display: inline-block;
      width: 150px;
      height: 100px;
      /* border: solid 1px green;  */
    }

    .signatures-container .sig .img {
      display: block;
    }

    .signatures-container .sig-1 .img {
      margin-top: 10px;
      background-image: url('https://www.help2protect.info/app/cert_sig_1.png');
      background-size: contain;
      background-repeat: no-repeat;
      width: 100px;
      height: 70px;
    }

    .signatures-container .sig-2 .img {
      background-image: url('https://www.help2protect.info/app/cert_sig_2.png');
      background-size: contain;
      background-repeat: no-repeat;
      width: 110px;
      height: 70px;
    }

    .signatures-container .sig .txt {
      display: block;
      font-size: 13px;
      font-weight: 500;
    }

    .signatures-container .sig-1 .txt {
      margin-top: -10px;
    }

    .directors-container {
      position: absolute;
      font-size: 13px;
      font-weight: 500;
      top: 650px;
      left: 160px;
      width: 400px;
      height: 30px;
      /* border: solid 1px red; */
    }

    .code-container {
      position: absolute;
      top: 480px;
      left: 710px;
      width: 320px;
      height: 210px;
      /* border: solid 1px red; */
    }

    .code-container .qr-code {
      /* border: solid 1px blue; */
      height: 160px;
      text-align: center;
    }

    .code-container .qr-code img {
      width: 120px;
      height: 120px;
    }

    .code-container .notice {
      font-size: 9px;
      font-weight: 500;
    }
  ";
}


function qursus_pack_certificate_get_style_html() {
  return "
    .cert-container {
      position: relative;
      background-image: url('https://www.help2protect.info/app/cert_background.png');
      background-size: contain;
      border: solid 1px #155991;
      border-radius: 10px;
      width: 970px;
      margin: auto;
      margin-top: 70px;
      height: 645px;
      font-family: Montserrat;
      overflow: hidden;
    }

    .cert-stamp-container {
      position: absolute;
      width: 310px;
      height: 400px;
      top: -40px;
      right: 30px;
      z-index: 1;
    }

    .cert-stamp-container .stamp-background {
      position: absolute;
      width: 310px;
      height: 400px;
      background-color: rgba(230, 230, 230, 0.7);
      z-index: 1;
      border-bottom-left-radius: 15px;
      border-bottom-right-radius: 15px;
      border: solid 6px rgba(100,100,100,0.2);
      border-top: 0px;
      border-left: 0px;
    }

    .cert-stamp-container .stamp-content {
      position: absolute;
      width: 310px;
      height: 400px;
      z-index: 2;
      /* border: solid 1px red; */
    }

    .cert-stamp-container .stamp-content .title {
      position: absolute;
      height: 30px;
      width: 310px;
      text-transform: uppercase;
      color: #155991;
      font-weight: 700;
      top: 80px;
      left: 0px;
      font-size: 24px;
      text-align: center;
      /* border: solid 1px green; */
    }

    .cert-stamp-container .stamp-content .stamp {
      position: absolute;
      top: 130px;
      left: 30px;
      width: 250px;
      height: 250px;
    }


    .title-container {
      position: absolute;
      top: 80px;
      left: 80px;
      height: 52px;
      width: 100%;
      font-size: 52px;
      font-weight: 700;
      line-height: 52px;
      color: #155991;
      /* border: solid 1px blue; */
    }

    .details-container {
      position: absolute;
      top: 190px;
      left: 80px;
      height: 300px;
      width: 520px;
      /* border: solid 1px blue; */
    }

    .details-container .date {
      position: absolute;
      top: 0px;
      font-size: 13px;
      font-weight: 500;
      /*border: solid 1px blue;*/
    }

    .details-container .name {
      position: absolute;
      top: 30px;
      font-size: 32px;
      font-weight: 600;
      /*border: solid 1px blue;*/
    }

    .details-container .success-header {
      position: absolute;
      top: 110px;
      font-size: 13px;
      font-weight: 500;
      /*border: solid 1px blue;*/
    }

    .details-container .success-body {
      position: absolute;
      top: 160px;
      width: 500px;
      font-size: 26px;
      line-height: 28px;
      font-weight: 600;
      /*border: solid 1px blue;*/
    }

    .details-container .success-footer {
      position: absolute;
      top: 240px;
      font-size: 13px;
      font-weight: 500;
      width: 350px;
      /* border: solid 1px blue; */
    }

    .signatures-container {
      position: absolute;
      top: 500px;
      left: 80px;
      height: 100px;
      width: 500px;
      /* border: solid 1px blue;  */
    }

    .signatures-container .sig {
      margin-top: 10px;
      display: inline-block;
      width: 150px;
      height: 100px;
      /* border: solid 1px green;  */
    }

    .signatures-container .sig .img {
      display: block;
    }

    .signatures-container .sig-1 .img {
      margin-top: 10px;
      background-image: url('https://www.help2protect.info/app/cert_sig_1.png');
      background-size: contain;
      background-repeat: no-repeat;
      width: 100px;
      height: 70px;
    }

    .signatures-container .sig-2 .img {
      background-image: url('https://www.help2protect.info/app/cert_sig_2.png');
      background-size: contain;
      background-repeat: no-repeat;
      width: 110px;
      height: 70px;
    }

    .signatures-container .sig .txt {
      display: block;
      font-size: 13px;
      font-weight: 500;
    }

    .signatures-container .sig-1 .txt {
      margin-top: -10px;
    }

    .directors-container {
      position: absolute;
      font-size: 13px;
      font-weight: 500;
      top: 600px;
      left: 80px;
      width: 400px;
      height: 30px;
      /* border: solid 1px red; */
    }

    .code-container {
      position: absolute;
      top: 420px;
      right: 25px;
      width: 320px;
      height: 210px;
      /* border: solid 1px red; */
    }

    .code-container .qr-code {
      /* border: solid 1px blue; */
      height: 160px;
      text-align: center;
    }

    .code-container .qr-code img {
      width: 120px;
      height: 120px;
    }

    .code-container .notice {
      font-size: 9px;
      font-weight: 500;
    }
  ";
}