<?php
use documents\Document;

[$params, $providers] = eQual::announce([
    'description'   => 'Stream document (supports HTTP range).',
    'params'        => [
        'hash' => [
            'type'      => 'string',
            'required'  => true
        ]
    ],
    'access' => [
        'visibility' => 'public'
    ],
    'response' => [
        'accept-origin' => '*'
    ],
    'providers' => ['context', 'orm', 'auth']
]);

/**
 * @var \equal\php\Context  $context
 * @var \equal\auth\AuthenticationManager
 */
['context' => $context, 'auth' => $auth] = $providers;

$user_id = $auth->userId();
$auth->su();

$collection = Document::search(['hash', '=', $params['hash']]);
$document = $collection->read(['public'])->first();

if(!$document) {
    throw new Exception("document_unknown", QN_ERROR_UNKNOWN_OBJECT);
}

if(!$document['public']) {
    $auth->su($user_id);
}

$document = $collection->read(['name', 'data', 'type', 'size'])->first();

$data = $document['data'];
$size = $document['size'] ?? strlen($data);
$type = $document['type'] ?? 'application/octet-stream';

$start = 0;
$end = $size - 1;
$status = 200;

// range support
$http_range = $context->httpRequest()->header('Range');
if($http_range) {

    [$unit, $range] = explode('=', $http_range, 2);

    if($unit === 'bytes') {

        [$rStart, $rEnd] = array_pad(explode('-', $range), 2, null);

        if($rStart === '') {
            $start = max(0, $size - (int) $rEnd);
            $end   = $size - 1;
        }
        else {
            $start = (int) $rStart;
            $end   = $rEnd !== null ? (int) $rEnd : $end;
        }

        if($start > $end || $end >= $size) {
            $context->httpResponse()
                ->status(416)
                ->header('Content-Range', "bytes */$size")
                ->send();
            return;
        }

        $status = 206;
    }
}

$length = $end - $start + 1;

$response = $context->httpResponse()
    ->status($status)
    ->header('Content-Type', $type)
    ->header('Accept-Ranges', 'bytes')
    ->header('Content-Length', $length);

if($status === 206) {
    $response->header('Content-Range', "bytes $start-$end/$size");
}

$response
    ->body(substr($data, $start, $length), true)
    ->send();
