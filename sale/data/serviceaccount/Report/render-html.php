<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use sale\serviceaccount\Report;

[$params, $providers] = eQual::announce([
    'description' => 'Generate an HTML view of a service account report.',
    'params'      => [
        'id' => [
            'description'    => 'Identifier of the report to render.',
            'type'           => 'many2one',
            'foreign_object' => 'sale\serviceaccount\Report',
            'required'       => true
        ],
        'details' => [
            'description' => 'Display the details of time entries.',
            'type'        => 'boolean',
            'default'     => true
        ],
        'logs' => [
            'description' => 'Display point calculation logs.',
            'type'        => 'boolean',
            'default'     => false
        ],
        'view_id' => [
            'description' => 'View id of the template to use.',
            'type'        => 'string',
            'default'     => 'print.default'
        ]
    ],
    'access' => [
        'visibility' => 'protected',
        'groups'     => ['sale.default.users']
    ],
    'response' => [
        'accept-origin' => '*',
        'content-type'  => 'text/html',
        'charset'       => 'utf-8'
    ],
    'providers' => ['context']
]);

/** @var \equal\php\Context $context */
['context' => $context] = $providers;

$report = Report::id($params['id'])
    ->read(['id'])
    ->first();

if(empty($report)) {
    throw new Exception('unknown_report', EQ_ERROR_UNKNOWN_OBJECT);
}

$html = Report::generateHtml($params['id'], [
    'show_details' => $params['details'],
    'show_logs'    => $params['logs'],
    'view_id'      => $params['view_id']
]);

if($html === null) {
    throw new Exception('report_rendering_failed', EQ_ERROR_UNKNOWN);
}

$context->httpResponse()
    ->body($html)
    ->send();
