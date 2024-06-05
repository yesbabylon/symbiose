<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\accounting\invoice;

use symbiose\setting\Setting;
use finance\accounting\AccountChartLine;
use finance\accounting\AccountingEntry;
use finance\accounting\AccountingRuleLine;
use sale\catalog\Product;
use sale\customer\Customer;
use sale\pay\Funding;
use sale\receivable\Receivable;

class Invoice extends \finance\accounting\Invoice {

    public static function getName() {
        return 'Sale invoice';
    }

    public static function getDescription() {
        return 'A sale invoice is a legal document issued after some goods have been sold to a customer.';
    }

    public static function getColumns() {

        return [

            'organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organisation',
                'description'       => 'The organization that emitted the invoice.',
                'default'           => 1
            ],

            'invoice_purpose' => [
                'type'              => 'string',
                'description'       => 'Is the invoice concerning a sale to a customer or a buy from a supplier.',
                'default'           => 'sell',
                'visible'           => false
            ],

            'invoice_number' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Number of the invoice, according to organization logic.',
                'function'          => 'calcInvoiceNumber',
                'store'             => true
            ],

            'payment_reference' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcPaymentReference',
                'description'       => 'Message for identifying payments related to the invoice.',
                'store'             => true,
                'instant'           => true
            ],

            'due_date' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'description'       => 'Deadline for the payment is expected, from payment terms.',
                'function'          => 'calcDueDate',
                'store'             => true,
                'instant'           => true
            ],

            'invoice_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\accounting\invoice\InvoiceLine',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Detailed lines of the invoice.',
                'ondetach'          => 'delete',
                'dependencies'      => ['total', 'price']
            ],

            'invoice_line_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\accounting\invoice\InvoiceLineGroup',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Groups of lines of the invoice.',
                'ondetach'          => 'delete',
                'dependencies'      => ['total', 'price']
            ],

            /**
             * Specific Sale Invoice columns
             */

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The counter party organization the invoice relates to.',
                'required'          => true,
                'dependencies'      => ['invoice_number']
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

            'payment_terms_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\PaymentTerms',
                'description'       => 'The payment terms to apply to the invoice.',
                'default'           => 1
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\Funding',
                'description'       => 'The funding related to the invoice.'
            ]
        ];
    }

    public static function onchange($event, $values): array {
        $result = [];
        if(isset($event['customer_id'], $values['status']) && $values['status'] == 'proforma'){
            $customer = Customer::search(['id', '=', $event['customer_id']])
                ->read(['name'])
                ->first();

            $result['invoice_number'] = '[proforma]['.$customer['name'].']['.date('Y-m-d').']';
        }

        return $result;
    }

    public static function calcInvoiceNumber($self): array {
        $result = [];
        $self->read(['status', 'organisation_id', 'customer_id' => ['name']]);
        foreach($self as $id => $invoice) {
            // no code is generated for proforma
            if($invoice['status'] == 'proforma') {
                $result[$id] = '[proforma]['.date('Y-m-d').']';
                continue;
            }

            $result[$id] = '';

            $organisation_id = $invoice['organisation_id'];

            $format = Setting::get_value('sale', 'invoice', 'sequence_format', '%2d{year}-%05d{sequence}');
            $year = Setting::get_value('sale', 'invoice', 'fiscal_year', date('Y'));
            $sequence = Setting::fetch_and_add('sale', 'invoice', 'sequence', 1, compact('organisation_id'));

            if($sequence) {
                $result[$id] = Setting::parse_format($format, [
                    'year'      => $year,
                    'org'       => $organisation_id,
                    'sequence'  => $sequence
                ]);
            }
        }

        return $result;
    }

    public static function calcPaymentReference($self): array {
        $result = [];
        $self->read(['invoice_number']);
        foreach($self as $id => $invoice) {
            $invoice_number = intval($invoice['invoice_number']);

            // arbitrary value for balance (final) invoice
            $code_ref = 200;

            $result[$id] = self::_get_payment_reference($code_ref, $invoice_number);
        }

        return $result;
    }

    /**
     * Compute a Structured Reference using belgian SCOR (Structured COmmunication Reference) reference format.
     *
     * Note:
     *  format is aaa-bbbbbbb-XX
     *  where aaa is the prefix, bbbbbbb is the suffix, and XX is the control number, that must verify (aaa * 10000000 + bbbbbbb) % 97
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

    public static function calcDueDate($self): array {
        $result = [];
        $self->read(['emission_date', 'payment_terms_id' => ['delay_from', 'delay_count']]);
        foreach($self as $id => $invoice) {
            if(!isset($invoice['emission_date'], $invoice['payment_terms_id']['delay_from'], $invoice['payment_terms_id']['delay_count'])) {
                continue;
            }

            $from = $invoice['payment_terms_id']['delay_from'];
            $delay = $invoice['payment_terms_id']['delay_count'];
            $emission_date = $invoice['emission_date'];

            switch($from) {
                case 'created':
                    $due_date = $emission_date + ($delay * 24 * 3600);
                    break;
                case 'next_month':
                default:
                    $due_date = strtotime(date('Y-m-t', $emission_date)) + ($delay * 24 * 3600);
                    break;
            }

            $result[$id] = $due_date;
        }

        return $result;
    }

    public static function getWorkflow() {
        return [
            'proforma' => [
                'description' => 'Draft invoice, still waiting to be completed and for customer approval.',
                'icon' => 'edit',
                'transitions' => [
                    'invoice' => [
                        'description' => 'Update the invoice status based on the `invoice` field.',
                        'help' => 'The `invoice` field is set by a dedicated controller that manages invoice approval requests.',
                        'policies' => [
                            'can-be-invoiced',
                        ],
                        'onbefore' => 'onbeforeInvoice',
                        'onafter' => 'onafterInvoice',
                        'status' => 'invoice',
                    ],
                    'cancel-proforma' => [
                        'description' => 'Delete the proforma and set receivables statuses back to pending.',
                        'onafter' => 'onafterCancelProforma',
                        'status' => 'proforma',
                    ]
                ],
            ],
            'invoice' => [
                'description' => 'Invoice can no longer be modified and can be sent to the customer.',
                'icon' => 'receipt_long',
                'transitions' => [
                    'cancel' => [
                        'description' => 'Set the invoice and receivables statuses as cancelled.',
                        'onafter' => 'onafterCancel',
                        'status' => 'cancelled',
                    ],
                    'cancel-keep-receivables' => [
                        'description' => 'Set the invoice status as cancelled and set receivables statuses back to pending.',
                        'onafter' => 'onafterCancelKeepReceivables',
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

    public static function onbeforeInvoice($self) {
        $self->read(['id']);
        foreach($self as $id => $invoice) {
            // Data modified before status changed to "invoice" because fields can only be updated while invoice has status "proforma"
            self::id($id)
                ->update(['emission_date' => time()]);
        }

        try {
            $self->do('create_accounting_entries');
        } catch(\Exception $e) {
            trigger_error("PHP::unable to create invoice accounting entries: {$e->getMessage()}", QN_REPORT_ERROR);
        }
    }

    public static function onafterInvoice($self) {
        // Force computing the invoice number that was set to null in onbeforeInvoice
        $self->read(['invoice_number', 'due_date']);

        // Funding must be created here because it needs the due_date force computed above
        try {
            $self->do('create_funding');
        } catch(\Exception $e) {
            trigger_error("PHP::unable to create funding: {$e->getMessage()}", QN_REPORT_ERROR);
        }
    }

    public static function onafterCancelProforma($self) {
        $self->read(['id']);
        foreach($self as $invoice) {
            $receivables_ids = Receivable::search([
                ['status', '=', 'invoiced'],
                ['invoice_id', '=', $invoice['id']],
            ])
                ->ids();

            Receivable::ids($receivables_ids)
                ->update([
                    'status'          => 'pending',
                    'invoice_id'      => null,
                    'invoice_line_id' => null
                ]);

            Invoice::id($invoice['id'])
                ->delete();
        }
    }

    public static function onafterCancel($self) {
        $self->read(['id']);
        foreach($self as $invoice) {
            $receivables_ids = Receivable::search([
                ['status', '=', 'invoiced'],
                ['invoice_id', '=', $invoice['id']],
            ])
                ->ids();

            Receivable::ids($receivables_ids)
                ->update(['status' => 'cancelled']);
        }

        $self->do('reverse');
    }

    public static function onafterCancelKeepReceivables($self) {
        $self->read(['id']);
        foreach($self as $invoice) {
            $receivables_ids = Receivable::search([
                ['status', '=', 'invoiced'],
                ['invoice_id', '=', $invoice['id']],
            ])
                ->ids();

            Receivable::ids($receivables_ids)
                ->update([
                    'status'          => 'pending',
                    'invoice_id'      => null,
                    'invoice_line_id' => null
                ]);
        }

        $self->do('reverse');
    }

    public static function getActions() {
        return [
            'create_accounting_entries' => [
                'description'   => 'Generates and creates the accounting entries according to the invoice lines.',
                'policies'      => [],
                'function'      => 'doCreateAccountingEntries'
            ],
            'generate_accounting_entries' => [
                'description'   => 'Generates the accounting entries according to the invoice lines.',
                'help'          => 'Returns generated accounting entries mapped by invoice lines.',
                'policies'      => [],
                'function'      => 'doGenerateAccountingEntries'
            ],
            'reverse' => [
                'description'   => 'Creates a new invoice of type credit note to reverse invoice.',
                'help'          => 'Reversing an invoice can only be done when status is "invoice".',
                'policies'      => [],
                'function'      => 'doReverseInvoice'
            ],
            'create_funding' => [
                'description'   => 'Create the funding according to the invoice.',
                'policies'      => [],
                'function'      => 'doCreateFunding'
            ],
        ];
    }

    /**
     * Generates the accounting entries according to the invoice lines.
     */
    public static function doCreateAccountingEntries($self) {
        $invoice_lines_accounting_entries = $self->do('generate_accounting_entries');

        // create new entries objects
        foreach($invoice_lines_accounting_entries as $invoice_line_accounting_entries) {
            foreach($invoice_line_accounting_entries as $accounting_entry) {
                AccountingEntry::create($accounting_entry);
            }
        }
    }

    /**
     * Returns generated accounting entries mapped by invoice lines.
     */
    public static function doGenerateAccountingEntries($self): array {
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
            if($invoice['status'] != 'invoice') {
                continue;
            }

            // default downpayment product to null
            $downpayment_product_id = 0;

            // retrieve downpayment product
            $downpayment_sku = Setting::get_value('sale', 'invoice', 'downpayment.sku.'.$invoice['organisation_id']);
            if($downpayment_sku) {
                $downpayment_product = Product::search(['sku', '=', $downpayment_sku])
                    ->read(['id'])
                    ->first();

                if(isset($downpayment_product['id'])) {
                    $downpayment_product_id = $downpayment_product['id'];
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
                            'debit'             => ($invoice['invoice_type'] == 'invoice') ? $debit : $credit,
                            'credit'            => ($invoice['invoice_type'] == 'invoice') ? $credit : $debit
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
                                    'debit'             => ($invoice['invoice_type'] == 'invoice') ? $debit : $credit,
                                    'credit'            => ($invoice['invoice_type'] == 'invoice') ? $credit : $debit
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
                        'debit'         => ($invoice['invoice_type'] == 'invoice') ? $debit : $credit,
                        'credit'        => ($invoice['invoice_type'] == 'invoice') ? $credit : $debit
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
                        'debit'         => ($invoice['invoice_type'] == 'invoice') ? $debit : $credit,
                        'credit'        => ($invoice['invoice_type'] == 'invoice') ? $credit : $debit
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
                    'debit'         => ($invoice['invoice_type'] == 'invoice') ? $debit : $credit,
                    'credit'        => ($invoice['invoice_type'] == 'invoice') ? $credit : $debit
                ];

                // append generated entries to result
                $result[$id] = $accounting_entries;
            }
        }

        return $result;
    }

    /**
     * Creates a new invoice of type credit note to reverse invoice.
     */
    public static function doReverseInvoice($self) {
        $self->read([
            'status',
            'invoice_type',
            'reversed_invoice_id',
            'organisation_id',
            'customer_id',
            'is_deposit',
            'invoice_line_groups_ids' => [
                'name',
                'invoice_lines_ids' => [
                    'product_id',
                    'price_id',
                    'qty',
                    'free_qty',
                    'discount',
                    'downpayment_invoice_id',
                    'vat_rate',
                    'unit_price',
                    'total',
                    'price'
                ]
            ]
        ]);

        foreach($self as $invoice) {
            if( $invoice['status'] !== 'cancelled'
                || $invoice['invoice_type'] !== 'invoice'
                || isset($invoice['reversed_invoice_id'])
            ) {
                continue;
            }

            $reversed_invoice = Invoice::create([
                'invoice_type'        => 'credit_note',
                'status'              => 'proforma',
                'emission_date'       => time(),
                'organisation_id'     => $invoice['organisation_id'],
                'customer_id'         => $invoice['customer_id'],
                'is_deposit'          => $invoice['is_deposit'],
                'reversed_invoice_id' => $invoice['id']
            ])
                ->first();

            foreach($invoice['invoice_line_groups_ids'] as $invoice_line_group) {
                $reversed_group = InvoiceLineGroup::create([
                    'name'       => $invoice_line_group['name'],
                    'invoice_id' => $reversed_invoice['id']
                ])
                    ->first(true);

                foreach($invoice_line_group['invoice_lines_ids'] as $line) {
                    InvoiceLine::create([
                        'description'            => $line['description'],
                        'invoice_id'             => $reversed_invoice['id'],
                        'invoice_line_group_id'  => $reversed_group['id'],
                        'product_id'             => $line['product_id'],
                        'price_id'               => $line['price_id'],
                        'qty'                    => $line['qty'],
                        'free_qty'               => $line['free_qty'],
                        'discount'               => $line['discount'],
                        'downpayment_invoice_id' => $line['downpayment_invoice_id']
                    ])
                        ->update([
                            'vat_rate'   => $line['vat_rate'],
                            'unit_price' => $line['unit_price'],
                            'total'      => $line['total'],
                            'price'      => $line['price']
                        ]);
                }
            }

            if(in_array($invoice['payment_status'], ['pending', 'overdue'])) {
                Invoice::id($reversed_invoice['id'])->update(['payment_status' => 'balanced']);
                Invoice::id($invoice['id'])->update(['payment_status' => 'balanced']);
            }
            else {
                // TODO: Alert finance_accounting reimbursement needed
            }

            Invoice::id($invoice['id'])
                ->update(['reversed_invoice_id' => $reversed_invoice['id']]);
        }
    }

    /**
     * Create the funding according to the invoice.
     */
    public static function doCreateFunding($self) {
        $self->read(['id', 'price', 'payment_reference', 'due_date', 'funding_id']);

        foreach($self as $invoice) {
            $funding = Funding::create([
                'description'         => 'Sold Invoice',
                'invoice_id'          => $invoice['id'],
                'due_amount'          => round($invoice['price'], 2),
                'is_paid'             => false,
                'funding_type'        => 'invoice',
                'payment_reference'   => $invoice['payment_reference'],
                'due_date'            => $invoice['due_date']
            ])
                ->first();

            Invoice::id($invoice['id'])
                ->update(['funding_id' => $funding['id']]);
        }
    }
}
