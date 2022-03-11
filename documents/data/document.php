<?php
/*
    This file is part of the Resipedia project <http://www.github.com/cedricfrancoys/resipedia>
    Some Rights Reserved, Cedric Francoys, 2018, Yegen
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
use documents\Document;

list($params, $providers) = announce([
    'description'   => 'Return raw data (with original MIME) of a document identified by given hash.',
    'params'        => [
        'hash' =>  [
            'description'   => 'Unique identifier of the resource.',
            'type'          => 'string',
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'public',		// 'public' (default) or 'private' (can be invoked by CLI only)
        'groups'            => ['documents.default.user'],// list of groups ids or names granted 
    ],
    'response'      => [
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

list($context, $om, $auth) = [ $providers['context'], $providers['orm'], $providers['auth'] ];

$user_id = $auth->userId();

// documents can be public : swith to root user to bypass any permission check
$auth->su();

// search for documents matching given hash code (should be only one match)
$collection = Document::search(['hash', '=', $params['hash']]);
$document = $collection->read(['public'])->first();

if(!$document) {
    throw new Exception("document_unknown", QN_ERROR_UNKNOWN_OBJECT);
}

// if document is not public, switch back to original user: regular permission checks will apply
if(!$document['public']) {
    $auth->su($user_id);
}

$document =  $collection->read(['name', 'data', 'type', 'size'])->first();

$context->httpResponse()
        ->header('Content-Type', $document['type'])
        ->body($document['data'], true)
        ->send();