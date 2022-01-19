<?php
/*
    This file is part of the Resipedia project <http://www.github.com/cedricfrancoys/resipedia>
    Some Rights Reserved, Cedric Francoys, 2018, Yegen
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
use documents\Document;

list($params, $providers) = announce([
    'description'   => 'Returns a list of entites according to given domain (filter), start offset, limit and order.',
    'params'        => [
        'hash' =>  [
            'description'   => 'Unique identifier of the resource.',
            'type'          => 'string', 
            'required'      => true
        ]
    ],
    'response'      => [
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth'] 
]);

list($context, $om, $auth) = [ $providers['context'], $providers['orm'], $providers['auth'] ];

$user_id = $auth->userId();
echo($user_id);
// swith to root user
$auth->su();

// documents are public : we d'ont use collections to bypass any permission check
$document = Document::search(['hash', '=', $params['hash']])->read(['public'])->first();

if(!$document) {
    throw new Exception("document_unknown", QN_ERROR_UNKNOWN_OBJECT);
}
var_dump($document);

if(!$document['public']) {
    $auth->su($user_id);
    echo('not public');
}
$user_id = $auth->userId();
echo($user_id);
die();
$document =  Document::search(['hash', '=', $params['hash']])->read(['name', 'data', 'type', 'size'])->first();

$context->httpResponse()
        ->header('Content-Type', $document['type'])
        ->body($document['data'], true)
        ->send();