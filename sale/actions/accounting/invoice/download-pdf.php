 <?php

use sale\accounting\invoice\Invoice;

list($params, $providers) = announce([
    'description'   => 'Download pdf of given invoice.',
    'params'        => [
        'id' => [
            'description' => 'Identifier of the targeted invoice.',
            'type'        => 'integer',
            'min'         => 1,
            'required'    => true
        ],

        'mode' =>  [
            'description' => 'Mode in which document has to be rendered: grouped (default) or detailed.',
            'type'        => 'string',
            'selection'   => ['grouped', 'detailed'],
            'default'     => 'grouped'
        ]
    ],
    'access'        => [
        'visibility' => 'protected',
        'groups'     => ['sale.default.users'],
    ],
    'response'      => [
        'accept-origin' => '*',
        'content-type'  => 'application/pdf'
    ],
    'providers'     => ['context']
]);

/** @var \equal\php\Context $context */
$context = $providers['context'];

$invoice = Invoice::id($params['id'])
    ->read(['id'])
    ->first();

if(empty($invoice)) {
    throw new Exception('invoice_unknown', QN_ERROR_UNKNOWN_OBJECT);
}

$output = eQual::run('get', 'sale_accounting_invoice_render-pdf', [
    'id'   => $params['id'],
    'mode' => $params['mode']
]);

$context->httpResponse()
        ->header('Content-Disposition', 'inline; filename="invoice.pdf"')
        ->body($output)
        ->send();
