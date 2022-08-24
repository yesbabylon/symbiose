<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\BankStatement;
use finance\accounting\AccountingEntry;

use lodging\finance\accounting\AccountingJournal;

use lodging\identity\CenterOffice;

list($params, $providers) = announce([
    'description'   => "Export a zip archive containing all reconciled bank statements for importing in an external accounting software.",
    'params'        => [
        'center_office_id' => [
            'type'              => 'many2one',
            'foreign_object'    => CenterOffice::getType(),
            'description'       => 'Management Group to which the center belongs.',
            'required'          => true
        ],
    ],
    'access' => [
        'visibility'        => 'public',
        'groups'            => ['sale.default.user'],
    ],
    'response'      => [
        // 'content-type'        => 'application/zip',
        'content-type'        => 'text/plain',
        // 'content-disposition' => 'attachment; filename="export.zip"',
        'charset'             => 'utf-8',
        'accept-origin'       => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);

list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];


// 1) entries related to invoices
/*

Postulats
* l'origine des fichiers n'a pas d'importance
* les noms de fichiers peuvent avoir de l'importance
* les fichiers peuvent regrouper des lignes issues de différents centres
* les imports COMPTA se font par centre de gestion : il faut un export par centre de gestion

$columns = [
    'TDBK'
]

*/


// find journals for given center_office
$office = CenterOffice::id($params['center_office_id'])
            ->read([
                'accounting_journals_ids',
                'centers_ids' => [
                    'analytic_section_id' => [
                        'code'
                    ]
                ]
            ])
            ->first();

if(!$office) {
    throw new Exception("unknown_center_office", QN_ERROR_UNKNOWN_OBJECT);
}

$entries = AccountingEntry::search([ ['is_exported', '=', false], ['has_invoice', '=', true], ['journal_id', 'in', $office['accounting_journals_ids']] ])
            ->read([
                'invoice_id' => [
                    'name'
                ],
                'journal_id' => [
                    'code'
                ]
            ])
            ->get();

$result = [];
foreach($entries as $entry) {
    $result[] = $entry;
}


$context->httpResponse()
        ->body($result)
        ->send();