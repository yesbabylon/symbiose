<?php
use equal\html\HtmlTemplate;
use core\User;
use learn\Pack;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Send an invite to satisfaction survey.",
    'params' 		=>	[
        'course_id' => [
            'description'   => 'Identifier of the pack the user just finished.',
            'type'          => 'integer',
            'required'      => true
        ],
        'user_id' => [
            'description'   => 'Identifier of the user to invite.',
            'type'          => 'integer',
            'required'      => true
        ]
    ],
    'constants'     => ['ROOT_APP_URL'],
    'response'      => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context', 'orm', 'spool', 'auth']
]);


// initalise local vars with inputs
list($orm, $context, $spool, $auth) = [ $providers['orm'], $providers['context'], $providers['spool'], $providers['auth'] ];


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

$pack = Pack::ids($params['course_id'])->read(['title'])->first();
$user = [];

$db = $orm->getDb();
$res = $db->getRecords(['wp_users'], ['user_email'], NULL, [ [ ['ID', '=', $user_id] ] ], 'ID', [], 0, 1);
while ($row = $db->fetchArray($res)) {
    $users[] = $row;
}

if(count($users)) {
    $user = array_pop($users);
}

try {


    // subject of the email should be defined in the template, as a <var> tag holding a 'title' attribute
    $subject = '';
    // read template according to user requested language
    $file = "packages/learn/views/mail_survey_invite.html";
    if(!($html = @file_get_contents($file, FILE_TEXT))) {
        throw new Exception("missing_template", QN_ERROR_INVALID_CONFIG);
    }

    // define template `var` nodes parsing callbacks
    $template = new HtmlTemplate($html, [
        'subject'		=>	function ($params, $attributes) use (&$subject) {
                                $subject = $attributes['title'];
                                return '';
                            },
        'pack_name'		=>	function ($params, $attributes) {
                                return $params['pack']['title'];
                            },
        'survey_url'	=>	function ($params, $attributes) {
                                $url = constant('ROOT_APP_URL')."/survey";
                                return "<a href=\"$url\">{$attributes['title']}</a>";
                            },
        'origin'        =>  function ($params, $attributes) {
                                return constant('EMAIL_SMTP_ACCOUNT_DISPLAYNAME');
                            },
        'abuse'         =>  function($params, $attributes) {
                                return "<a href=\"mailto:".constant('EMAIL_SMTP_ABUSE_EMAIL')."\">".constant('EMAIL_SMTP_ABUSE_EMAIL')."</a>";
                            }
        ],
        [
            'pack'  => $pack
        ]
    );

    // parse template as html
    $body = $template->getHtml();

    // switch to root user
    $auth->su();

    // send message
    $spool->queue($subject, $body, $user['user_email']);

    // immediate send
    $spool->run();

}
catch(Exception $e) {
    // for security reasons, in case of error no details are relayed to client
    trigger_error("ORM::{$e->getMessage()}", QN_REPORT_ERROR);
}

$context->httpResponse()
        ->body([])
        ->send();