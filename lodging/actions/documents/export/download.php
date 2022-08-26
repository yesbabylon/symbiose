<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\documents\Export;

list($params, $providers) = announce([
    'description'   => 'Return raw data (with original MIME) of an export document identified by its ID.',
    'params'        => [
        'id' => [
            'type'              => 'many2one',
            'foreign_object'    => Export::getType(),
            'description'       => 'Management Group to which the center belongs.',
            'required'          => true
        ],
    ],
    'access' => [
        'visibility'        => 'public'
    ],
    'response'      => [
        'accept-origin' => '*',
        'content-type'  => 'application/zip'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

list($context, $om, $auth) = [ $providers['context'], $providers['orm'], $providers['auth'] ];

// retrieve targeted exeport
$export = Export::id($params['id'])->read(['name', 'data', 'type'])->first();

if(!$export) {
    throw new Exception("document_unknown", QN_ERROR_UNKNOWN_OBJECT);
}

// mark as donwloaded
Export::id($params['id'])->update(['is_exported' => true]);

$context->httpResponse()
        ->header('Content-Type', $export['type'])
        ->header('Content-Disposition', 'attachment; filename="'.$export['name'].'.zip"')
        ->body($export['data'], true)
        ->send();