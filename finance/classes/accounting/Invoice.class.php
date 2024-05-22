<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace finance\accounting;

use core\setting\Setting;
use equal\orm\Model;
use inventory\Product;

class Invoice extends Model {

    protected static $invoice_editable_fields = ['payment_status'];

    public static function getName() {
        return 'Invoice';
    }

    public static function getDescription() {
        return 'An invoice is a legal document issued by a seller and given to a buyer, that relates to a sale and is part of the accounting system.';
    }

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'alias',
                'alias'             => 'invoice_number'
            ],

            'reference' => [
                'type'              => 'string',
                'description'       => 'Note or comments to be addressed to the customer.',
                'help'              => 'This is an arbitrary text field (to be added at the top of invoices), such as customer reference or any comments to be addressed to the customer.'
            ],

            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organisation',
                'description'       => 'The organisation that emitted/received the invoice.',
                'default'           => 1
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'proforma',             // draft invoice (no number yet)
                    'invoice',              // final invoice (with unique number and accounting entries)
                    'cancelled'             // the invoice has been cancelled (through reversing entries)
                ],
                'default'           => 'proforma'
            ],

            'invoice_type' => [
                'type'              => 'string',
                'selection'         => [
                    'invoice',
                    'credit_note'
                ],
                'default'           => 'invoice'
            ],

            'invoice_purpose' => [
                'type'              => 'string',
                'selection'         => [
                    'sell',
                    'buy'
                ],
                'default'          => 'sell'
            ],

            'invoice_number' => [
                'type'              => 'string',
                'description'       => 'Number of the invoice, according to organization logic.',
                'required'          => true
            ],

            'reversed_invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'description'       => 'Credit note that was created for cancelling the invoice, if any.',
                'visible'           => ['status', '=', 'cancelled']
            ],

            'payment_status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',          // non-paid, payment terms delay running
                    'overdue',          // non-paid, and payment terms delay is over
                    'debit_balance',    // partially paid: buyer still has to pay something
                    'credit_balance',   // fully paid and a reimbursement to buyer is required
                    'balanced'          // fully paid and balanced
                ],
                'visible'           => ['status', '=', 'invoice'],
                'default'           => 'pending'
            ],

            'payment_reference' => [
                'type'              => 'string',
                'description'       => 'Message for identifying payments related to the invoice.'
            ],

            'emission_date' => [
                'type'              => 'datetime',
                'description'       => 'Emission date of the invoice.',
                'default'           => time(),
                'dependencies'      => ['invoice_number']
            ],

            'due_date' => [
                'type'              => 'date',
                'description'       => 'Deadline for the payment is expected.',
                'default'           => strtotime('+1 month')
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price of the invoice.',
                'function'          => 'calcTotal',
                'store'             => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Final tax-included invoiced amount.',
                'function'          => 'calcPrice',
                'store'             => true
            ],

            'invoice_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\InvoiceLine',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Detailed lines of the invoice.',
                'ondetach'          => 'delete',
                'dependencies'      => ['total', 'price']
            ],

            'invoice_line_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\InvoiceLineGroup',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Groups of lines of the invoice.',
                'ondetach'          => 'delete',
                'dependencies'      => ['total', 'price']
            ],

            'accounting_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\AccountingEntry',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Accounting entries relating to the lines of the invoice.',
                'ondetach'          => 'delete'
            ]

        ];
    }

    public static function getWorkflow() {
        return [
            'proforma' => [
                'description' => 'Draft invoice that is being completed.',
                'icon' => 'edit',
                'transitions' => [
                    'invoice' => [
                        'description' => 'Invoice the drafted proforma.',
                        'status' => 'invoice',
                    ],
                    'cancel' => [
                        'description' => 'Cancel the invoice.',
                        'status' => 'cancelled',
                    ],
                ],
            ],
            'invoice' => [
                'description' => 'Invoice can no longer be modified.',
                'icon' => 'receipt_long',
                'transitions' => [
                    'cancel' => [
                        'description' => 'Cancel the invoice.',
                        'status' => 'cancelled',
                    ],
                ],
            ],
            'cancelled' => [
                'description' => 'The invoice was cancelled.',
                'icon' => 'cancel',
                'transitions' => [
                ],
            ],
        ];
    }

    public static function calcTotal($self): array {
        $result = [];
        $self->read(['invoice_lines_ids' => ['total']]);
        foreach($self as $id => $invoice) {
            $result[$id] = array_reduce($invoice['invoice_lines_ids']->get(true), function ($c, $a) {
                return $c + $a['total'];
            }, 0.0);
        }

        return $result;
    }

    public static function calcPrice($self): array {
        $result = [];
        $self->read(['invoice_lines_ids' => ['price']]);
        $currency_decimal_precision = Setting::get_value('core', 'locale', 'currency.decimal_precision', 2);
        foreach($self as $id => $invoice) {
            $price = array_reduce($invoice['invoice_lines_ids']->get(true), function ($c, $a) {
                return $c + $a['price'];
            }, 0.0);

            $result[$id] = round($price, $currency_decimal_precision);
        }

        return $result;
    }

    /**
     * Check whether an object can be updated, and perform some additional operations if necessary.
     * This method can be overridden to define a more precise set of tests.
     *
     * @param  \equal\orm\ObjectManager   $om         ObjectManager instance.
     * @param  array                      $oids       List of objects identifiers.
     * @param  array                      $values     Associative array holding the new values to be assigned.
     * @param  string                     $lang       Language in which multilang fields are being updated.
     * @return array                      Returns an associative array mapping fields with their error messages. En empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang = 'en') {
        $res = $om->read(self::getType(), $oids, ['status']);

        if($res > 0) {
            foreach($res as $oid => $odata) {
                if($odata['status'] == 'invoice') {
                    if(!isset($values['status']) || !in_array($values['status'], ['invoice', 'cancelled'])) {
                        // only allow editable fields
                        if( count(array_diff(array_keys($values), get_called_class()::$invoice_editable_fields)) ) {
                            return ['status' => ['non_editable' => 'Invoice can only be updated while its status is proforma.']];
                        }
                    }
                }
            }
        }
        return parent::canupdate($om, $oids, $values, $lang);
    }

    /**
     * Check whether the invoice can be deleted.
     *
     * @param  \equal\orm\ObjectManager    $om         ObjectManager instance.
     * @param  array                       $oids       List of objects identifiers.
     * @return array                       Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be deleted.
     */
    public static function candelete($om, $oids) {
        $res = $om->read(get_called_class(), $oids, ['status']);

        if($res > 0) {
            foreach($res as $oid => $odata) {
                if($odata['status'] != 'proforma') {
                    return ['status' => ['non_removable' => 'Invoice can only be deleted while its status is proforma.']];
                }
            }
        }
        return parent::candelete($om, $oids);
    }
}
