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
    'providers'     => ['context', 'orm'] 
]);

list($context, $om) = [ $providers['context'], $providers['orm'] ];
// documents are public : we d'ont use collections to bypass any permission check
$document = Document::search(['hash', '=', $params['hash']])->read(['name', 'data', 'content_type', 'size'])->first();

if(!$document) {
    throw new Exception("wrong identifier '{$params['hash']}'", QN_ERROR_UNKNOWN_OBJECT);
}

$context->httpResponse()
        ->header('Content-Type', $document['content_type'])
        ->body($document['data'], true)
        ->send();