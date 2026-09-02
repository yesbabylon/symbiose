<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;
use sale\serviceaccount\Report;

[$params, $providers] = eQual::announce([
    'description' => 'Generate a PDF view of a service account report.',
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
        ],
        'filename' => [
            'description' => 'Name given to the generated PDF file.',
            'type'        => 'string'
        ]
    ],
    'access' => [
        'visibility' => 'protected',
        'groups'     => ['sale.default.users']
    ],
    'response' => [
        'accept-origin' => '*',
        'content-type'  => 'application/pdf'
    ],
    'providers' => ['context']
]);

/** @var \equal\php\Context $context */
['context' => $context] = $providers;

$report = Report::id($params['id'])
    ->read(['id', 'name'])
    ->first();

if(empty($report)) {
    throw new Exception('unknown_report', EQ_ERROR_UNKNOWN_OBJECT);
}

try {
    $html = (string) eQual::run('get', 'sale_serviceaccount_Report_render-html', [
        'id'      => $params['id'],
        'details' => $params['details'],
        'logs'    => $params['logs'],
        'view_id' => $params['view_id']
    ]);

    $options = new DompdfOptions();
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->render();

    $canvas = $dompdf->getCanvas();
    $font = $dompdf->getFontMetrics()->getFont('helvetica', 'regular');
    $canvas->page_text(750, 32, 'page {PAGE_NUM} / {PAGE_COUNT}', $font, 10, [0, 0, 0]);

    $output = $dompdf->output();
}
catch(Exception $e) {
    trigger_error('APP::unable to generate PDF Report - '.$e->getMessage(), EQ_REPORT_ERROR);
    throw new Exception('report_generation_failed', EQ_ERROR_UNKNOWN);
}

$filename = $params['filename'] ?? $report['name'] ?? 'report';
$filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename);
$filename = trim($filename, '.-');

if($filename === '') {
    $filename = 'report';
}

$context->httpResponse()
    ->header('Content-Disposition', 'inline; filename="'.$filename.'.pdf"')
    ->body($output)
    ->send();
