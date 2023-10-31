<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace finance\accounting;
use equal\orm\Model;
use core\setting\Setting;

class Invoice extends Model {

    public static function getName() {
        return "Invoice";
    }

    public static function getDescription() {
        return "An invoice is a legal document issued by a seller to a buyer that relates to a sale, and is part of the accounting system.";
    }

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'alias',
                'alias'             => "number"
            ],

            'customer_ref' => [
                'type'              => 'string',
                'description'       => 'Reference that must appear on invoice (requested by customer).'
            ],

            'is_deposit' => [
                'type'              => 'boolean',
                'description'       => 'Marks the invoice as a deposit one, relating to a downpayment.',
                'default'           => false
            ],

            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => "The organization that emitted the invoice.",
                'default'           => 1
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'proforma',             // draft invoice (no number yet)
                    'invoice',              // final invoice (with unique number and accounting entries)
                    'cancelled'             // the invoice has been cancelled (through reversing entries)
                ],
                'default'           => 'proforma',
                'onupdate'          => 'onupdateStatus'
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'invoice',
                    'credit_note'
                ],
                'default'           => 'invoice'
            ],

            'number' => [
                'type'              => 'computed',
                'result_type'        => 'string',
                'description'       => "Number of the invoice, according to organization logic (@see config/invoicing).",
                'function'          => 'calcNumber',
                'store'             => true
            ],

            'is_paid' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => "Indicator of the invoice payment status.",
                'visible'           => ['status', '=', 'invoice'],
                'function'          => 'calcIsPaid',
                'store'             => true
            ],

            'reversed_invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => self::getType(),
                'description'       => "Credit note that was created for cancelling the invoice, if any.",
                'visible'           => ['status', '=', 'cancelled']
            ],

            'payment_status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',          // non-paid, payment terms delay running
                    'overdue',          // non-paid, and payment terms delay is over
                    'debit_balance',    // partially paid: customer still has to pay something
                    'credit_balance',   // fully paid and a reimbursement to customer is required
                    'balanced'          // fully paid and balanced
                ],
                'visible'           => ['status', '=', 'invoice'],
                'default'           => 'pending'
            ],

            'payment_reference' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcPaymentReference',
                'description'       => 'Message for identifying payments related to the invoice.',
                'store'             => true
            ],

            'date' => [
                'type'              => 'datetime',
                'description'       => 'Emission date of the invoice.',
                'default'           => time()
            ],

            'partner_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \identity\Partner::getType(),
                'description'       => "The counter party organization the invoice relates to.",
                'required'          => true
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'function'          => 'calcPrice',
                'usage'             => 'amount/money:2',
                'store'             => true,
                'description'       => "Final tax-included invoiced amount (computed)."
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'function'          => 'calcTotal',
                'usage'             => 'amount/money:4',
                'description'       => 'Total tax-excluded price of the invoice (computed).',
                'store'             => true
            ],

            'invoice_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\InvoiceLine',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Detailed lines of the invoice.',
                'ondetach'          => 'delete',
                'onupdate'          => 'onupdateInvoiceLinesIds'
            ],

            'invoice_line_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\InvoiceLineGroup',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Groups of lines of the invoice.',
                'ondetach'          => 'delete',
                'onupdate'          => 'onupdateInvoiceLineGroupsIds'
            ],

            'accounting_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => AccountingEntry::getType(),
                'foreign_field'     => 'invoice_id',
                'description'       => 'Accounting entries relating to the lines of the invoice.',
                'ondetach'          => 'delete'
            ],

            'fundings_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pay\Funding',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Fundings related to the invoice (should be max. 1).'
            ],

            'payment_terms_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\PaymentTerms',
                'description'       => "The payment terms to apply to the invoice.",
                'default'           => 1
            ],

            'due_date' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'description'       => "Deadline for the payment is expected, from payment terms.",
                'function'          => 'calcDueDate',
                'store'             => true
            ],

            'is_exported' => [
                'type'              => 'boolean',
                'description'       => 'Mark the invoice as exported (part of an export to elsewhere).',
                'default'           => false
            ]

        ];
    }

    public static function calcIsPaid($om, $oids, $lang) {
        $result = [];
        // #memo - fundings_ids targets all fundings relating to invoice: this includes the installments
        // we need to limit the check to the direct funding, if any
        $invoices = $om->read(get_called_class(), $oids, ['status', 'price', 'funding_id.paid_amount'], $lang);
        if($invoices > 0) {
            foreach($invoices as $oid => $invoice) {
                $result[$oid] = false;
                if($invoice['status'] != 'invoice') {
                    // proforma invoices cannot be marked as paid
                    continue;
                }
                if($invoice['price'] == 0) {
                    // mark the invoice as paid, whatever its funding
                    $result[$oid] = true;
                    continue;
                }
                if($invoice['funding_id.paid_amount'] && $invoice['funding_id.paid_amount'] == $invoice['price']) {
                    $result[$oid] = true;
                }
            }
        }
        return $result;
    }

    public static function calcPaymentReference($om, $oids, $lang) {
        $result = [];
        $invoices = $om->read(get_called_class(), $oids, ['number']);
        foreach($invoices as $oid => $invoice) {
            $number = intval($invoice['number']);
            // arbitrary value : 155 for balance (final) invoice
            $code_ref = 155;
            $result[$oid] = self::_get_payment_reference($code_ref, $number);
        }
        return $result;
    }

    public static function calcNumber($om, $oids, $lang) {
        $result = [];

        $invoices = $om->read(get_called_class(), $oids, ['status', 'organisation_id'], $lang);

        foreach($invoices as $oid => $invoice) {

            // no code is generated for proforma
            if($invoice['status'] == 'proforma') {
                $result[$oid] = '[proforma]';
                continue;
            }

            $result[$oid] = '';

            $organisation_id = $invoice['organisation_id'];

            $format = Setting::get_value('finance', 'invoice', 'invoice.sequence_format', '%05d{sequence}');
            $year = Setting::get_value('finance', 'invoice', 'invoice.fiscal_year');
            $sequence = Setting::get_value('sale', 'invoice', 'invoice.sequence.'.$organisation_id);

            if($sequence) {
                Setting::set_value('sale', 'invoice', 'invoice.sequence.'.$organisation_id, $sequence + 1);

                $result[$oid] = Setting::parse_format($format, [
                    'year'      => $year,
                    'org'       => $organisation_id,
                    'sequence'  => $sequence
                ]);
            }
        }
        return $result;
    }

    public static function calcPrice($om, $oids, $lang) {
        $result = [];

        $invoices = $om->read(get_called_class(), $oids, ['invoice_lines_ids.price'], $lang);

        foreach($invoices as $oid => $invoice) {
            $price = array_reduce($invoice['invoice_lines_ids.price'], function ($c, $a) {
                return $c + $a['price'];
            }, 0.0);
            $result[$oid] = round($price, 2);
        }
        return $result;
    }

    public static function calcTotal($om, $oids, $lang) {
        $result = [];

        $invoices = $om->read(get_called_class(), $oids, ['invoice_lines_ids.total'], $lang);

        foreach($invoices as $oid => $invoice) {
            $total = array_reduce($invoice['invoice_lines_ids.total'], function ($c, $a) {
                return $c + round($a['total'], 2);
            }, 0.0);
            $result[$oid] = round($total, 2);
        }
        return $result;
    }

    public static function calcDueDate($om, $oids, $lang) {
        $result = [];

        $invoices = $om->read(get_called_class(), $oids, ['created', 'payment_terms_id.delay_from', 'payment_terms_id.delay_count'], $lang);
        if($invoices > 0) {
            foreach($invoices as $oid => $invoice) {
                $from = $invoice['payment_terms_id.delay_from'];
                $delay = $invoice['payment_terms_id.delay_count'];
                $origin = $invoice['created'];
                switch($from) {
                    case 'created':
                        $due_date = $origin + ($delay*24*3600);
                        break;
                    case 'next_month':
                    default:
                        $due_date = strtotime(date("Y-m-t", $origin)) + ($delay*24*3600);
                        break;
                }
                $result[$oid] = $due_date;
            }
        }
        return $result;
    }

    public static function onupdateInvoiceLinesIds($om, $oids, $values, $lang) {
        $om->update(__CLASS__, $oids, ['price' => null, 'total' => null]);
    }

    public static function onupdateInvoiceLineGroupsIds($om, $oids, $values, $lang) {
        $om->update(__CLASS__, $oids, ['price' => null, 'total' => null]);
    }

    public static function onupdateStatus($om, $oids, $values, $lang) {
        if(isset($values['status']) && $values['status'] == 'invoice') {
            // reset invoice number and set emission date
            $om->update(__CLASS__, $oids, ['number' => null, 'date' => time()], $lang);
            // generate an invoice number (force immediate recomuting)
            $om->read(__CLASS__, $oids, ['number'], $lang);
            // generate accounting entries
            $invoices_accounting_entries = self::_generateAccountingEntries($om, $oids, [], $lang);
            // create new entries objects
            foreach($invoices_accounting_entries as $oid => $accounting_entries) {
                foreach($accounting_entries as $entry) {
                    $om->create(AccountingEntry::getType(), $entry);
                }
            }
        }
    }

    /**
     * Check wether an object can be updated, and perform some additional operations if necessary.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  \equal\orm\ObjectManager   $om         ObjectManager instance.
     * @param  array                      $oids       List of objects identifiers.
     * @param  array                      $values     Associative array holding the new values to be assigned.
     * @param  string                     $lang       Language in which multilang fields are being updated.
     * @return array                      Returns an associative array mapping fields with their error messages. En empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang='en') {
        $res = $om->read(self::getType(), $oids, ['status']);

        if($res > 0) {
            foreach($res as $oids => $odata) {
                // status can only be changed from 'proforma' to 'invoice' and from 'invoice' to 'cancelled'
                if($odata['status'] == 'proforma') {
                    if(isset($values['status']) && !in_array($values['status'], ['proforma', 'invoice'])) {
                        return ['status' => ['non_editable' => 'Invoice status can only be updated from proforma to invoice.']];
                    }
                }
                if($odata['status'] == 'invoice') {
                    if(!isset($values['status']) || !in_array($values['status'], ['invoice', 'cancelled'])) {
                        // only allow modifiable fields
                        if( count(array_diff(array_keys($values), ['customer_ref','payment_status','is_exported'])) ) {
                            return ['status' => ['non_editable' => 'Invoice can only be updated while its status is proforma.']];
                        }
                    }
                }
            }
        }
        return parent::canupdate($om, $oids, $values, $lang);
    }

    /**
     * Check wether the invoice can be deleted.
     *
     * @param  \equal\orm\ObjectManager    $om         ObjectManager instance.
     * @param  array                       $oids       List of objects identifiers.
     * @return array                       Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be deleted.
     */
    public static function candelete($om, $oids) {
        $res = $om->read(get_called_class(), $oids, [ 'status' ]);

        if($res > 0) {
            foreach($res as $oids => $odata) {
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
     * @param  \equal\orm\ObjectManager    $om         ObjectManager instance.
     * @param  array                       $oids       List of objects identifiers.
     * @param  array                       $values     (unused)
     * @param  string                      $lang       Language code in which to process the request.
     * @return array                       Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be deleted.
     */
    public static function _generateAccountingEntries($om, $oids, $values, $lang) {
        $result = [];
        // generate the accounting entries
        $invoices = $om->read(self::getType(), $oids, ['status', 'type', 'organisation_id', 'invoice_lines_ids'], $lang);
        if($invoices > 0) {
            // retrieve specific accounts numbers
            $account_sales = Setting::get_value('finance', 'invoice', 'account.sales', 'not_found');
            $account_sales_taxes = Setting::get_value('finance', 'invoice', 'account.sales_taxes', 'not_found');
            $account_trade_debtors = Setting::get_value('finance', 'invoice', 'account.trade_debtors', 'not_found');

            $res = $om->search(AccountChartLine::getType(), ['code', '=', $account_sales]);
            $account_sales_id = reset($res);

            $res = $om->search(AccountChartLine::getType(), ['code', '=', $account_sales_taxes]);
            $account_sales_taxes_id = reset($res);

            $res = $om->search(AccountChartLine::getType(), ['code', '=', $account_trade_debtors]);
            $account_trade_debtors_id = reset($res);

            if(!$account_sales_id || !$account_sales_taxes_id || !$account_trade_debtors_id) {
                // a mandatory value could not be retrieved
                trigger_error("ORM::missing mandatory account", QN_REPORT_ERROR);
                return [];
            }

            foreach($invoices as $oid => $invoice) {
                if($invoice['status'] != 'invoice') {
                    continue;
                }
                // default downpayment product to null
                $downpayment_product_id = 0;
                // retrieve downpayment product
                $downpayment_sku = Setting::get_value('sale', 'invoice', 'downpayment.sku.'.$invoice['organisation_id']);
                if($downpayment_sku) {
                    $products_ids = $om->search(\sale\catalog\Product::getType(), ['sku', '=', $downpayment_sku]);
                    if($products_ids) {
                        $downpayment_product_id = reset($products_ids);
                    }
                }

                $accounting_entries = [];
                // fetch invoice lines
                $lines = $om->read(InvoiceLine::getType(), $invoice['invoice_lines_ids'], [
                    'name', 'description', 'product_id', 'qty', 'total', 'price',
                    'price_id.accounting_rule_id.accounting_rule_line_ids'
                ], $lang);

                if($lines > 0) {
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
                                'invoice_id'        => $oid,
                                'invoice_line_id'   => $lid,
                                'account_id'        => $account_sales_id,
                                'debit'             => ($invoice['type'] == 'invoice')?$debit:$credit,
                                'credit'            => ($invoice['type'] == 'invoice')?$credit:$debit
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
                                    ['account_id' => $account_sales_id, 'share' => 1.0]
                                ];
                            }
                            else if (isset($line['price_id.accounting_rule_id.accounting_rule_line_ids'])) {
                                // for products, retrieve all lines of accounting rule
                                $rule_lines = $om->read(\finance\accounting\AccountingRuleLine::getType(), $line['price_id.accounting_rule_id.accounting_rule_line_ids'], ['account_id', 'share']);
                            }
                            foreach($rule_lines as $rid => $rline) {
                                if(isset($rline['account_id']) && isset($rline['share'])) {
                                    // create a credit line with product name, on the account related by the product (VAT excl. price)
                                    $debit = 0.0;
                                    $credit = round($line['total'] * $rline['share'], 2);
                                    $accounting_entries[] = [
                                        'name'              => $line['name'],
                                        'has_invoice'       => true,
                                        'invoice_id'        => $oid,
                                        'invoice_line_id'   => $lid,
                                        'account_id'        => $rline['account_id'],
                                        'debit'             => ($invoice['type'] == 'invoice')?$debit:$credit,
                                        'credit'            => ($invoice['type'] == 'invoice')?$credit:$debit
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
                            'invoice_id'    => $oid,
                            'account_id'    => $account_sales_taxes_id,
                            'debit'         => ($invoice['type'] == 'invoice')?$debit:$credit,
                            'credit'        => ($invoice['type'] == 'invoice')?$credit:$debit
                        ];
                    }

                    // create a debit line on account "taxes to pay"
                    if($debit_vat_sum > 0) {
                        $debit = round($debit_vat_sum, 2);
                        $credit = 0.0;
                        // assign with handling of reversing entries
                        $accounting_entries[] = [
                            'name'          => 'taxes TVA à payer',
                            'has_invoice'   => true,
                            'invoice_id'    => $oid,
                            'account_id'    => $account_sales_taxes_id,
                            'debit'         => ($invoice['type'] == 'invoice')?$debit:$credit,
                            'credit'        => ($invoice['type'] == 'invoice')?$credit:$debit
                        ];
                    }

                    // create a debit line on account "trade debtors"
                    $debit = round($prices_sum-$downpayments_sum, 2);
                    $credit = 0.0;
                    // assign with handling of reversing entries
                    $accounting_entries[] = [
                        'name'          => 'créances commerciales',
                        'has_invoice'   => true,
                        'invoice_id'    => $oid,
                        'account_id'    => $account_trade_debtors_id,
                        'debit'         => ($invoice['type'] == 'invoice')?$debit:$credit,
                        'credit'        => ($invoice['type'] == 'invoice')?$credit:$debit
                    ];

                    // append generated entries to result
                    $result[$oid] = $accounting_entries;
                }
            }
        }
        return $result;
    }

    /**
     * Compute a Structured Reference using belgian SCOR (StructuredCommunicationReference) reference format.
     *
     * Note:
     *  format is aaa-bbbbbbb-XX
     *  where Xaaa is the prefix, bbbbbbb is the suffix, and XX is the control number, that must verify (aaa * 10000000 + bbbbbbb) % 97
     *  as 10000000 % 97 = 76
     *  we do (aaa * 76 + bbbbbbb) % 97
     */
    protected static function _get_payment_reference($prefix, $suffix) {
        $a = intval($prefix);
        $b = intval($suffix);
        $control = ((76*$a) + $b ) % 97;
        $control = ($control == 0)?97:$control;
        return sprintf("%3d%04d%03d%02d", $a, $b / 1000, $b % 1000, $control);
    }
}