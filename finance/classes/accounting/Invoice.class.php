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

    public static function getWorkflow(): array {
        return [
            'proforma' => [
                'transitions' => [
                    'invoice' => [
                        'description' => 'Update the invoice status based on the `invoice` field.',
                        'help'        => 'The `invoice` field is set by a dedicated controller that manages invoice approval requests.',
                        'status'	  => 'invoice',
                        'onbefore'    => 'onbeforeInvoice',
                        'onafter'     => 'onafterInvoice'
                    ]
                ]
            ],
            'invoice' => [
                'transitions' => [
                    'cancel'  => [
                        'description' => 'Set the invoice status as cancelled.',
                        'status'	  => 'cancelled'
                    ]
                ]
            ]
        ];
    }

    public static function onbeforeInvoice($self) {
        $self->read(['id', 'emission_date', 'invoice_number', 'status']);
        foreach($self as $id => $invoice) {
            Invoice::ids($id)
                ->update([
                    'invoice_number' => null,
                    'emission_date'  => time()
                ]);

            try {
                $invoices_accounting_entries = self::_generateAccountingEntries($self);

                // create new entries objects
                foreach($invoices_accounting_entries as $accounting_entries) {
                    foreach($accounting_entries as $entry) {
                        AccountingEntry::create($entry);
                    }
                }
            } catch(\Exception $e) {
                trigger_error("PHP::unable to create invoice accounting entries: {$e->getMessage()}", QN_REPORT_ERROR);
            }

        }
    }

    public static function onafterInvoice($self) {
        // force computing the invoice number
        $self->read(['invoice_number']);
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

    /**
     * Generate the accounting entries according to the invoice lines.
     *
     * @return array  Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be deleted.
     * @throws \Exception
     */
    public static function _generateAccountingEntries($self): array {
        // TODO: Handle generateAccountingEntries for "buy" purpose invoices (from purchase package)

        $result = [];
        $self->read(['status', 'invoice_type', 'organisation_id', 'invoice_lines_ids']);

        // retrieve specific accounts numbers
        $account_sales = Setting::get_value('finance', 'invoice', 'account.sales', 'not_found');
        $account_sales_taxes = Setting::get_value('finance', 'invoice', 'account.sales_taxes', 'not_found');
        $account_trade_debtors = Setting::get_value('finance', 'invoice', 'account.trade_debtors', 'not_found');

        $account_sales = AccountChartLine::search(['code', '=', $account_sales])->read(['id'])->first();
        $account_sales_taxes = AccountChartLine::search(['code', '=', $account_sales_taxes])->read(['id'])->first();
        $account_trade_debtors = AccountChartLine::search(['code', '=', $account_trade_debtors])->read(['id'])->first();

        if(!isset($account_sales, $account_sales_taxes, $account_trade_debtors)) {
            // a mandatory value could not be retrieved
            trigger_error('QN_DEBUG_ORM::missing mandatory account', QN_REPORT_ERROR);
            return [];
        }

        foreach($self as $id => $invoice) {
            if(
                $invoice['status'] != 'invoice'
                && $invoice['invoice_purpose'] == 'sell' // TODO: Handle "buy" purpose invoices
            ) {
                continue;
            }

            // default downpayment product to null
            $downpayment_product_id = 0;

            // retrieve downpayment product
            $downpayment_sku = Setting::get_value('sale', 'invoice', 'downpayment.sku.'.$invoice['organisation_id']);
            if($downpayment_sku) {
                $products_ids = Product::search(['sku', '=', $downpayment_sku])->ids();
                if($products_ids) {
                    $downpayment_product_id = reset($products_ids);
                }
            }

            $accounting_entries = [];

            // fetch invoice lines
            $lines = InvoiceLine::ids($invoice['invoice_lines_ids'])
                ->read([
                    'name',
                    'description',
                    'product_id',
                    'qty',
                    'total',
                    'price',
                    'price_id' => ['accounting_rule_id' => ['accounting_rule_line_ids']]
                ])
                ->get();

            if(!empty($lines)) {
                $debit_vat_sum = 0.0;
                $credit_vat_sum = 0.0;
                $prices_sum = 0.0;
                $downpayments_sum = 0.0;

                foreach($lines as $lid => $line) {
                    $vat_amount = abs($line['price']) - abs($line['total']);
                    // line refers to a downpayment
                    // (by convention qty is always negative for installments: this allows to distinguish installment invoices from balance invoice)
                    if($line['product_id'] == $downpayment_product_id && $line['qty'] < 0) {
                        // sum up downpayments (VAT incl. price)
                        $downpayments_sum += abs($line['price']);
                        // if some VAT is due, deduct the sum accordingly
                        $debit_vat_sum += $vat_amount;
                        // create a debit line with the product, on account "sales"
                        $debit = abs($line['total']);
                        $credit = 0.0;
                        $accounting_entries[] = [
                            'name'              => $line['name'],
                            'has_invoice'       => true,
                            'invoice_id'        => $id,
                            'invoice_line_id'   => $lid,
                            'account_id'        => $account_sales['id'],
                            'debit'             => ($invoice['invoice_type'] == 'invoice')?$debit:$credit,
                            'credit'            => ($invoice['invoice_type'] == 'invoice')?$credit:$debit
                        ];
                    }
                    // line is a regular product line
                    else {
                        // sum up VAT amounts
                        $credit_vat_sum += $vat_amount;
                        // sum up sale prices (VAT incl. price)
                        $prices_sum += $line['price'];
                        $rule_lines = [];
                        // handle installment invoice
                        if($line['product_id'] == $downpayment_product_id) {
                            // generate virtual rule for downpayment with account "sales"
                            $rule_lines = [
                                ['account_id' => $account_sales['id'], 'share' => 1.0]
                            ];
                        }
                        else if(isset($line['price_id.accounting_rule_id.accounting_rule_line_ids'])) {
                            // for products, retrieve all lines of accounting rule
                            $rule_lines = AccountingRuleLine::ids($line['price_id']['accounting_rule_id']['accounting_rule_line_ids'])
                                ->read(['account_id', 'share'])
                                ->get();
                        }
                        foreach($rule_lines as $rline) {
                            if(isset($rline['account_id']) && isset($rline['share'])) {
                                // create a credit line with product name, on the account related by the product (VAT excl. price)
                                $debit = 0.0;
                                $credit = round($line['total'] * $rline['share'], 2);
                                $accounting_entries[] = [
                                    'name'              => $line['name'],
                                    'has_invoice'       => true,
                                    'invoice_id'        => $id,
                                    'invoice_line_id'   => $lid,
                                    'account_id'        => $rline['account_id'],
                                    'debit'             => ($invoice['invoice_type'] == 'invoice')?$debit:$credit,
                                    'credit'            => ($invoice['invoice_type'] == 'invoice')?$credit:$debit
                                ];
                            }
                        }
                    }
                }

                // create a credit line on account "taxes to pay"
                if($credit_vat_sum > 0) {
                    $debit = 0.0;
                    $credit = round($credit_vat_sum, 2);
                    // assign with handling of reversing entries
                    $accounting_entries[] = [
                        'name'          => 'taxes TVA à payer',
                        'has_invoice'   => true,
                        'invoice_id'    => $id,
                        'account_id'    => $account_sales_taxes['id'],
                        'debit'         => ($invoice['invoice_type'] == 'invoice')?$debit:$credit,
                        'credit'        => ($invoice['invoice_type'] == 'invoice')?$credit:$debit
                    ];
                }

                // create a debit line on account "taxes to pay"
                if($debit_vat_sum > 0) {
                    $debit = round($debit_vat_sum, 2);
                    $credit = 0.0;
                    // assign with handling of reversing entries
                    $accounting_entries[] = [
                        'name'          => 'VAT taxes to pay',
                        'has_invoice'   => true,
                        'invoice_id'    => $id,
                        'account_id'    => $account_sales_taxes['id'],
                        'debit'         => ($invoice['invoice_type'] == 'invoice')?$debit:$credit,
                        'credit'        => ($invoice['invoice_type'] == 'invoice')?$credit:$debit
                    ];
                }

                // create a debit line on account "trade debtors"
                $debit = round($prices_sum - $downpayments_sum, 2);
                $credit = 0.0;
                // assign with handling of reversing entries
                $accounting_entries[] = [
                    'name'          => 'commercial debts',
                    'has_invoice'   => true,
                    'invoice_id'    => $id,
                    'account_id'    => $account_trade_debtors['id'],
                    'debit'         => ($invoice['invoice_type'] == 'invoice')?$debit:$credit,
                    'credit'        => ($invoice['invoice_type'] == 'invoice')?$credit:$debit
                ];

                // append generated entries to result
                $result[$id] = $accounting_entries;
            }
        }

        return $result;
    }
}
