<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use equal\orm\Domain;

list($params, $providers) = announce([
    'description'   => 'Advanced search for Consumptions: returns a collection of Consumptions according to extra paramaters.',
    'extends'       => 'core_model_collect',
    'params'        => [
        // inherited params
        'entity' =>  [
            'description'       => 'Full name (including namespace) of the class to look into.',
            'type'              => 'string',
            'default'           => 'lodging\sale\booking\Consumption'
        ],
        'date_from' => [
            'type'              => 'date',
            'description'       => "Date interval lower limit.",
            'default'           => strtotime('-7 days')
        ],
        'date_to' => [
            'type'              => 'date',
            'description'       => 'Date interval Upper limit.',
            'default'           => strtotime('+7 days')
        ],
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'lodging\identity\Center',
            'description'       => "The center to which the booking relates to."
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => [ 'context', 'orm' ]
]);

/**
 * @var \equal\php\Context          $context
 * @var \equal\orm\ObjectManager    $orm
 */
list($context, $orm) = [ $providers['context'], $providers['orm'] ];

/*
    Add conditions to the domain to consider advanced parameters
*/
$domain = $params['domain'];

if(isset($params['center_id'])) {
    // add contraint on center_id
    $domain = Domain::conditionAdd($domain, ['center_id', '=', $params['center_id']]);
}

if(isset($params['date_from'])) {
    // add contraint on date_from
    $domain = Domain::conditionAdd($domain, ['date', '>=', $params['date_from']]);
}

if(isset($params['date_to'])) {
    // add contraint on date_to
    $domain = Domain::conditionAdd($domain, ['date', '<=', $params['date_to']]);
}

$params['domain'] = $domain;

$result = eQual::run('get', 'model_collect', $params, true);

$context->httpResponse()
        ->body($result)
        ->send();