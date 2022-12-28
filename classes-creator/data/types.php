
<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use documents\Document;
use equal\data\DataAdapter;

list($params, $providers) = announce([
    'description'   => 'Return raw data (with original MIME) of a document identified by given hash.',
    'params'        => [
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

$type = ['boolean', 'integer', 'float', 'string', 'time', 'date', 'datetime', 'array', 'many2one', 'one2many', 'many2many', 'html', 'binary', 'alias', 'computed'];





$context->httpResponse()
        ->body($type)
        ->send();
