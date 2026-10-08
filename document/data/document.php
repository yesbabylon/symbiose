<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use document\document\Document;

list($params, $providers) = announce([
    'description'   => 'Return raw data (with original MIME) of a document identified by given hash.',
    'params'        => [
        'hash' =>  [
            'description'   => 'Unique identifier of the resource.',
            'type'          => 'string',
            'required'      => true
        ],
        'disposition' => [
            'type'          => 'string',
            'selection'     => [
                'inline',
                'attachment'
            ],
            'default'       => 'inline'
        ]
    ],
    'access' => [
        'visibility'        => 'public'
    ],
    'response'      => [
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

list($context, $om, $auth) = [ $providers['context'], $providers['orm'], $providers['auth'] ];

$user_id = $auth->userId();

// public documents must be located before applying user-specific access checks
$auth->su();

// search for documents matching given hash code (should be only one match)
$collection = Document::search(['hash', '=', $params['hash']]);
$document = $collection->read(['document_visibility'])->first();

if(!$document) {
    throw new Exception("document_unknown", QN_ERROR_UNKNOWN_OBJECT);
}

// authenticated visibility levels rely on regular ORM access checks
if(($document['document_visibility'] ?? 'organization') !== 'public') {
    $auth->su($user_id);
}

$document = $collection->read(['name', 'data', 'content_type', 'extension'])->first();

$content_type = $document['content_type'] ?? 'application/octet-stream';
$filename = trim((string) ($document['name'] ?? ''));
if(pathinfo($filename, PATHINFO_EXTENSION) === '') {
    $extension = ltrim(trim((string) ($document['extension'] ?? '')), '.');
    if($extension !== '') {
        $filename = rtrim($filename, '.') . '.' . $extension;
    }
}

$context->httpResponse()
        ->header('Content-Disposition', $params['disposition'] . '; filename="' . $filename . '"')
        ->header('Content-Type', $content_type)
        ->body($document['data'], true)
        ->send();
