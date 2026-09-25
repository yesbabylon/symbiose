<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\accounting\invoice;

use symbiose\setting\Setting;
use finance\accounting\Account;
use finance\accounting\AccountingEntry;
use finance\accounting\AccountingEntryLine;
use finance\accounting\AccountingJournal;
use sale\customer\Customer;
use sale\pay\Funding;
use sale\receivable\Receivable;

class SaleInvoice extends \finance\accounting\operation\AccountingOperation {

    public static function getModelTable(): string {
        return 'sale_accounting_invoice_invoice';
    }

    public static function getName() {
        return 'Sale invoice';
    }

    public static function getDescription() {
        return 'A sale invoice is a legal document issued after some goods have been sold to a customer.';
    }

    public static function getColumns() {

        return [
            'description' => [
                'type'        => 'computed',
                'result_type' => 'string',
                'relation'    => ['name'],
                'store'       => true,
                'readonly'    => true,
                'description' => 'Accounting description of the invoice.'
            ],

            'operation_type' => [
                'type'        => 'computed',
                'result_type' => 'string',
                'function'    => 'calcOperationType',
                'store'       => true,
                'instant'     => true,
                'readonly'    => true,
                'description' => 'Accounting operation type derived from the invoice type.'
            ],

            'posting_date' => [
                'type'        => 'computed',
                'result_type' => 'date',
                'function'    => 'calcPostingDate',
                'store'       => true,
                'instant'     => true,
                'readonly'    => true,
                'description' => 'Accounting date derived from the invoice emission date.'
            ],

            'operation_number' => [
                'type'        => 'computed',
                'result_type' => 'string',
                'relation'    => ['invoice_number'],
                'store'       => true,
                'readonly'    => true,
                'description' => 'Accounting operation number matching the invoice number.'
            ],

            'journal_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'finance\accounting\AccountingJournal',
                'description'    => 'Accounting journal used to post the invoice.',
                'domain'         => [
                    ['organization_id', '=', 'object.organization_id']
                ],
                'readonly'       => true
            ],

            'reversal_of_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'sale\accounting\invoice\SaleInvoice',
                'description'    => 'Posted invoice reversed by this credit note.',
                'readonly'       => true
            ],

            'reversal_operations_ids' => [
                'type'           => 'one2many',
                'foreign_object' => 'sale\accounting\invoice\SaleInvoice',
                'foreign_field'  => 'reversal_of_id',
                'description'    => 'Credit notes created to reverse this invoice.',
                'readonly'       => true,
                'order'          => 'emission_date',
                'sort'           => 'desc'
            ],

            'operation_lines_ids' => [
                'type'           => 'computed',
                'result_type'    => 'one2many',
                'foreign_object' => 'finance\accounting\operation\AccountingOperationLine',
                'description'    => 'Generic operation lines are unused by sale invoices.',
                'function'       => 'calcOperationLines',
                'readonly'       => true
            ],

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'store'             => true,
                'function'          => 'calcName',
                'description'       => 'Label of the invoice, depending on its status'
            ],

            'organization_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Organization',
                'description'       => 'The organization that emitted the invoice.',
                'default'           => 1,
                'required'          => true
            ],

            'status' => [
                'type'              => 'string',
                'description'       => 'Current status of the invoice.',
                'selection'         => [
                    'proforma',             // draft invoice (no number yet)
                    'posted',               // final invoice (with unique number and accounting entries)
                    'cancelled'             // the invoice has been cancelled (through reversing entries)
                ],
                'default'           => 'proforma'
            ],

            'reversed_invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\accounting\invoice\SaleInvoice',
                'description'       => 'Credit note that was created for cancelling the invoice, if any.',
                'visible'           => ['status', '=', 'cancelled']
            ],

            'reference' => [
                'type'              => 'string',
                'description'       => 'Note or comments to be addressed to the customer.',
                'help'              => 'Arbitrary text displayed at the top of the invoice.'
            ],

            'invoice_type' => [
                'type'              => 'string',
                'description'       => 'Whether the document is an invoice or a credit note.',
                'selection'         => [
                    'invoice',
                    'credit_note'
                ],
                'default'           => 'invoice',
                'dependents'        => ['operation_type', 'price_billed']
            ],

            'is_downpayment' => [
                'type'              => 'boolean',
                'description'       => 'Marks the invoice as a deposit invoice relating to a downpayment (funding).',
                'default'           => false
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\Funding',
                'description'       => 'The funding related to the invoice.'
            ],

            'invoice_purpose' => [
                'type'              => 'string',
                'description'       => 'Is the invoice concerning a sale to a customer or a buy from a supplier.',
                'default'           => 'sell',
                'visible'           => false
            ],

            'invoice_number' => [
                'type'              => 'string',
                'description'       => 'Number of the invoice, according to organization logic.',
                'default'           => '[proforma]',
                'dependents'        => ['name', 'operation_number', 'payment_reference']
            ],

            'payment_status' => [
                'type'              => 'string',
                'selection'         => [
                    'pending',
                    'overdue',
                    'debit_balance',
                    'credit_balance',
                    'balanced'
                ],
                'visible'           => ['status', '=', 'posted'],
                'default'           => 'pending',
                'description'       => 'Payment state of the invoice.'
            ],

            'payment_reference' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcPaymentReference',
                'description'       => 'Message for identifying payments related to the invoice.',
                'store'             => true,
                'instant'           => true
            ],

            'emission_date' => [
                'type'              => 'datetime',
                'description'       => 'Reference date for computing the due date.',
                'help'              => 'This value can be changed while the invoice is `proforma`, but cannot be changed afterward (once emitted).',
                'default'           => function() { return time(); },
                'dependents'        => ['due_date', 'posting_date']
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
                'foreign_object'    => 'sale\accounting\invoice\SaleInvoiceLine',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Detailed lines of the invoice.',
                'ondetach'          => 'delete',
                'dependents'        => ['total', 'price']
            ],

            'invoice_line_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\accounting\invoice\SaleInvoiceLineGroup',
                'foreign_field'     => 'invoice_id',
                'description'       => 'Groups of lines of the invoice.',
                'ondetach'          => 'delete',
                'dependents'        => ['total', 'price']
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

            /**
             * Specific Sale Invoice columns
             */

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The counter party organization the invoice relates to.',
                'required'          => true,
                'dependents'        => ['name']
            ],

            'customer_ref' => [
                'type'              => 'string',
                'description'       => 'Reference that must appear on invoice (requested by customer).'
            ],

            'payment_terms_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\PaymentTerms',
                'description'       => 'The payment terms to apply to the invoice.',
                'default'           => 1
            ],

            'price_billed' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'function'          => 'calcPriceBilled',
                'usage'             => 'amount/money:2',
                'store'             => true,
                'description'       => "Final tax-included amount used for display (inverted for credit notes)."
            ],

            'accounting_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'finance\accounting\AccountingEntry',
                'foreign_field'     => 'origin_object_id',
                'domain'            => ['origin_object_class', '=', 'sale\accounting\invoice\SaleInvoice'],
                'description'       => 'Accounting entries relating to the lines of the invoice.',
                'ondetach'          => 'delete',
                'dependents'        => ['is_balanced']
            ],

            'is_balanced' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Whether all accounting entries of the invoice are balanced.',
                'function'          => 'calcIsBalanced',
                'readonly'          => true
            ]

        ];
    }

    protected static function calcOperationType($self): array {
        $result = [];

        $self->read(['invoice_type']);
        foreach($self as $id => $invoice) {
            $result[$id] = $invoice['invoice_type'] === 'credit_note'
                ? 'sale_credit_note'
                : 'sale_invoice';
        }

        return $result;
    }

    protected static function calcOperationLines($self): array {
        $result = [];

        $self->read(['id']);
        foreach($self as $id => $invoice) {
            $result[$id] = [];
        }

        return $result;
    }

    protected static function calcPostingDate($self): array {
        $result = [];

        $self->read(['emission_date']);
        foreach($self as $id => $invoice) {
            $result[$id] = $invoice['emission_date']
                ? strtotime(date('Y-m-d', $invoice['emission_date']))
                : null;
        }

        return $result;
    }

    protected static function calcIsBalanced($self): array {
        $result = [];

        $self->read(['accounting_entries_ids' => ['is_balanced']]);
        foreach($self as $id => $invoice) {
            $is_balanced = count($invoice['accounting_entries_ids']) > 0;
            foreach($invoice['accounting_entries_ids'] as $entry) {
                if(!$entry['is_balanced']) {
                    $is_balanced = false;
                    break;
                }
            }
            $result[$id] = $is_balanced;
        }

        return $result;
    }

    public static function calcTotal($self): array {
        $result = [];
        $self->read(['invoice_lines_ids' => ['total']]);
        $currency_decimal_precision = Setting::get_value('core', 'locale', 'currency.decimal_precision', 2);
        foreach($self as $id => $invoice) {
            $total = array_reduce($invoice['invoice_lines_ids']->get(true), function($carry, $line) use($currency_decimal_precision) {
                return $carry + round($line['total'], $currency_decimal_precision);
            }, 0.0);
            $result[$id] = round($total, $currency_decimal_precision);
        }

        return $result;
    }

    /**
     * #todo - this is incorrect, workaround based on 21% tax
     *
     * #memo - VAT is computed by rate, on sums of lines grouped by rate
     */
    public static function calcPrice($self): array {
        $result = [];
        $self->read(['total']);
        $currency_decimal_precision = Setting::get_value('core', 'locale', 'currency.decimal_precision', 2);
        foreach($self as $id => $invoice) {
            $price = $invoice['total'] * 1.21;
            $result[$id] = round($price, $currency_decimal_precision);
        }

        return $result;
    }

    public static function calcPriceBilled($self) {
        $result = [];
        $self->read(['invoice_type', 'price']);
        foreach($self as $id => $invoice) {
            $result[$id] = $invoice['invoice_type'] == 'invoice' ? $invoice['price'] : -$invoice['price'];
        }

        return $result;
    }


    public static function getPolicies(): array {
        return [
            'can-be-invoiced' => [
                'description' => 'Verifies that the proforma can be invoiced.',
                'function'    => 'policyCanBeInvoiced'
            ]
        ];
    }

    public static function policyCanBeInvoiced($self): array {
        $result = [];
        $self->read(['invoice_lines_ids']);
        foreach($self as $id => $invoice) {
            if(count($invoice['invoice_lines_ids']) === 0) {
                $result[$id] = false;
            }
        }

        return $result;
    }

    public static function getWorkflow() {
        return [
            'proforma' => [
                'description' => 'Draft invoice, still waiting to be completed and for customer approval.',
                'icon' => 'edit',
                'transitions' => [
                    'post' => [
                        'description' => 'Post the invoice and generate its definitive accounting data.',
                        'help'        => 'Posting assigns the definitive number and generates the accounting entries.',
                        'policies'    => [
                            'can-be-invoiced',
                        ],
                        'onbefore'  => 'onbeforePost',
                        'onafter'   => 'onafterPost',
                        'status'    => 'posted',
                    ],
                    'cancel-proforma' => [
                        'description' => 'Delete the proforma and set receivables statuses back to open.',
                        'onafter' => 'onafterCancelProforma',
                        'status'  => 'proforma',
                    ]
                ],
            ],
            'posted' => [
                'description' => 'Invoice has been posted, can no longer be modified and can be sent to the customer.',
                'icon' => 'receipt_long',
                'transitions' => [
                    'cancel' => [
                        'description' => 'Set the invoice and receivables statuses as cancelled.',
                        'onafter' => 'onafterCancel',
                        'status' => 'cancelled',
                    ],
                    'cancel-keep-receivables' => [
                        'description' => 'Set the invoice status as cancelled and set receivables statuses back to open.',
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

    public static function onchange($event, $values): array {
        $result = [];

        return $result;
    }

    public static function calcName($self): array {
        $result = [];
        $self->read(['invoice_number',  'customer_id' => ['name']]);
        foreach($self as $id => $invoice) {
            $result[$id] = $invoice['invoice_number'].' - '.$invoice['customer_id']['name'];
        }
        return $result;
    }

    public static function calcPaymentReference($self): array {
        $result = [];
        $self->read(['status', 'invoice_number']);
        foreach($self as $id => $invoice) {
            // #memo - prevent generating a payment reference for a proforma
            if($invoice['status'] == 'posted') {
                // arbitrary value for balance (final) invoice
                $code_ref = 500;

                $result[$id] = self::computePaymentReference($code_ref, preg_replace('/\D/', '', $invoice['invoice_number']));
            }
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
    protected static function computePaymentReference($prefix, $suffix) {
        $a = intval($prefix);
        $b = intval($suffix);
        $control = ((76*$a) + $b ) % 97;
        $control = ($control == 0) ? 97 : $control;
        return sprintf("%3d%04d%03d%02d", $a, $b / 1000, $b % 1000, $control);
    }

    public static function calcDueDate($self): array {
        $result = [];
        $self->read(['emission_date', 'payment_terms_id' => ['delay_from', 'delay_count']]);
        foreach($self as $id => $invoice) {
            $result[$id] = strtotime('+1 month');

            if(!isset($invoice['emission_date'], $invoice['payment_terms_id']['delay_from'], $invoice['payment_terms_id']['delay_count'])) {
                continue;
            }

            $from = $invoice['payment_terms_id']['delay_from'];
            $delay = $invoice['payment_terms_id']['delay_count'];
            $emission_date = $invoice['emission_date'];

            switch($from) {
                case 'created':
                    $due_date = $emission_date + ($delay * 86400);
                    break;
                case 'next_month':
                default:
                    $due_date = strtotime(date('Y-m-t', $emission_date)) + ($delay * 86400);
                    break;
            }

            $result[$id] = $due_date;
        }

        return $result;
    }

    public static function onbeforePost($self) {
        $self->read(['organization_id']);
        // Try to generate the accounting entries according to the invoices lines.
        $self->do('generate_accounting_entries');
        foreach($self as $id => $invoice) {
            $format = Setting::get_value('sale', 'accounting', 'invoice.sequence_format', '%2d{year}-%05d{sequence}', ['organization_id' => $invoice['organization_id']]);
            $year = Setting::get_value('finance', 'accounting', 'fiscal_year', date('Y'), ['organization_id' => $invoice['organization_id']]);
            $sequence = Setting::fetch_and_add('sale', 'accounting', 'invoice.sequence', 1, ['organization_id' => $invoice['organization_id']]);
            if(!$sequence) {
                throw new \Exception('APP::unable to retrieve sequence for invoice', EQ_ERROR_INVALID_CONFIG);
            }
            $invoice_number = Setting::parse_format($format, [
                    'year'      => $year,
                    'org'       => $invoice['organization_id'],
                    'sequence'  => $sequence
                ]);
            self::id($id)->update([
                    'invoice_number' => $invoice_number,
                    'posted_at'      => time(),
                    'due_date'       => null,
                    'price'          => null,
                    'total'          => null
                ]);
        }
    }


    /**
     * Generate the fundings for a collection of invoices that just transitioned to "posted".
     * Fundings must be created here because due_date is set at invoice emission
    */
    public static function onafterPost($self) {
        try {
            // #memo - failing in emitting the fundings cannot interrupt the transition
            $self->do('create_funding');
        }
        catch(\Exception $e) {
            trigger_error("APP::error while creating invoices funding: {$e->getMessage()}", EQ_REPORT_ERROR);
        }
    }

    public static function onafterCancelProforma($self) {
        foreach($self as $id => $invoice) {
            $receivables_ids = Receivable::search([
                    ['status', '=', 'settled'],
                    ['invoice_id', '=', $id],
                ])
                ->ids();

            Receivable::ids($receivables_ids)
                ->update([
                    'status'          => 'open',
                    'invoice_id'      => null,
                    'invoice_line_id' => null
                ]);
        }
        $self->delete(true);
    }

    public static function onafterCancel($self) {
        $self->read(['id']);
        foreach($self as $invoice) {
                $receivables_ids = Receivable::search([
                    ['status', '=', 'settled'],
                    ['invoice_id', '=', $invoice['id']],
                ])
                ->ids();

            Receivable::ids($receivables_ids)
                ->update(['status' => 'cancelled']);
        }

        $self->do('reverse');
        $self->update(['cancelled_at' => time()]);
    }

    public static function onafterCancelKeepReceivables($self) {
        $self->read(['id']);
        foreach($self as $invoice) {
            $receivables_ids = Receivable::search([
                    ['status', '=', 'settled'],
                    ['invoice_id', '=', $invoice['id']],
                ])
                ->ids();

            Receivable::ids($receivables_ids)
                ->update([
                    'status'          => 'open',
                    'invoice_id'      => null,
                    'invoice_line_id' => null
                ]);
        }

        $self->do('reverse');
        $self->update(['cancelled_at' => time()]);
    }

    public static function getActions() {
        return [
            'reverse' => [
                'description'   => 'Creates a new invoice of type credit note to reverse invoice.',
                'help'          => 'Reversing an invoice can only be done when status is "posted".',
                'policies'      => [],
                'function'      => 'doReverseInvoice'
            ],
            'create_funding' => [
                'description'   => 'Create the funding according to the invoice.',
                'policies'      => [],
                'function'      => 'doCreateFunding'
            ],
            'generate_accounting_entries' => [
                'description'   => 'Creates accounting entries according to  invoice lines.',
                'policies'      => [],
                'function'      => 'doGenerateAccountingEntries'
            ]
        ];
    }

    /**
     * Create new credit notes to reverse the invoices.
     */
    public static function doReverseInvoice($self) {
        $self->read([
                'status',
                'invoice_type',
                'payment_status',
                'reversed_invoice_id',
                'organization_id',
                'customer_id',
                'is_downpayment',
                'invoice_line_groups_ids' => [
                    'name',
                    'invoice_lines_ids' => [
                        'description',
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
                || isset($invoice['reversed_invoice_id']) ) {
                continue;
            }

            $reversed_invoice = SaleInvoice::create([
                    'invoice_type'        => 'credit_note',
                    'status'              => 'proforma',
                    'emission_date'       => time(),
                    'organization_id'     => $invoice['organization_id'],
                    'customer_id'         => $invoice['customer_id'],
                    'is_downpayment'      => $invoice['is_downpayment'],
                    'reversed_invoice_id' => $invoice['id'],
                    'reversal_of_id'      => $invoice['id']
                ])
                ->read(['id'])
                ->first(true);
            foreach($invoice['invoice_line_groups_ids'] as $invoice_line_group) {
                $reversed_group = SaleInvoiceLineGroup::create([
                        'name'       => $invoice_line_group['name'],
                        'invoice_id' => $reversed_invoice['id']
                    ])
                    ->first(true);

                foreach($invoice_line_group['invoice_lines_ids'] as $line) {
                    SaleInvoiceLine::create([
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
                // no payment was received yet : mark both invoices as balanced (no transaction required)
                SaleInvoice::id($reversed_invoice['id'])->update(['payment_status' => 'balanced']);
                SaleInvoice::id($invoice['id'])->update(['payment_status' => 'balanced']);
            }
            else {
                // #todo: Alert finance_accounting - reimbursement needed
            }

            SaleInvoice::id($invoice['id'])
                ->update(['reversed_invoice_id' => $reversed_invoice['id']]);
        }
    }

    /**
     * Create the fundings according to the invoices.
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

            SaleInvoice::id($invoice['id'])
                ->update(['funding_id' => $funding['id']]);
        }
    }

    /**
     * Create the accounting entries according tp invoices lines.
     */
    public static function doGenerateAccountingEntries($self) {
        $self->read(['id', 'organization_id', 'accounting_entries_ids' => ['id', 'entry_lines_ids']]);

        foreach($self as $id => $invoice) {
            $journal = AccountingJournal::search([['organization_id', '=', $invoice['organization_id']], ['journal_type', '=', 'SALE']])->read(['id'])->first();

            if(!$journal) {
                throw new \Exception('missing_mandatory_journal', EQ_ERROR_INVALID_CONFIG);
            }

            SaleInvoice::id($id)->update(['journal_id' => $journal['id']]);

            // remove previously created entries, if any (there should be none)
            $accounting_entries_ids = array_map(function ($a) { return $a['id']; }, $invoice['accounting_entries_ids']->get(true));
            AccountingEntry::ids($accounting_entries_ids)->delete(true);

            // generate accounting entries
            $accounting_entry_lines = self::computeAccountingEntryLines($id);

            if(empty($accounting_entry_lines)) {
                throw new \Exception('invalid_invoice', EQ_ERROR_UNKNOWN);
            }

            $entry = AccountingEntry::create([
                    'journal_id'            => $journal['id'],
                    'origin_object_class'   => self::getType(),
                    'origin_object_id'      => $id,
                    'status'                => 'posted'
                ])
                ->first();

            // create new entries objects and assign to the sale journal
            foreach($accounting_entry_lines as $line) {
                $line['accounting_entry_id'] = $entry['id'];
                AccountingEntryLine::create($line);
            }

        }
    }

    private static function computeAccountingEntryLines($invoice_id) {
        $result = [];

        // retrieve specific accounts numbers
        $account_sales = Setting::get_value('sale', 'accounting', 'account.sales', 'not_found');
        $account_sales_taxes = Setting::get_value('sale', 'accounting', 'account.sales_taxes', 'not_found');
        $account_trade_debtors = Setting::get_value('sale', 'accounting', 'account.trade_debtors', 'not_found');
        // $account_downpayments = Setting::get_value('sale', 'accounting', 'account.downpayment', 'not_found');

        $accountSales = Account::search(['code', '=', $account_sales])->read(['id', 'description'])->first();
        $accountSalesTaxes = Account::search(['code', '=', $account_sales_taxes])->read(['id', 'description'])->first();
        $accountTradeDebtors = Account::search(['code', '=', $account_trade_debtors])->read(['id', 'description'])->first();
        // $accountDownpayments = Account::search(['code', '=', $account_downpayments])->first();


        if(!$accountSales) {
            throw new \Exception('APP::missing mandatory account sales', EQ_ERROR_INVALID_CONFIG);
        }

        if(!$accountSalesTaxes) {
            throw new \Exception('APP::missing mandatory account sales taxes', EQ_ERROR_INVALID_CONFIG);
        }

        if(!$accountTradeDebtors) {
            throw new \Exception('APP::missing mandatory account trade debtors', EQ_ERROR_INVALID_CONFIG);
        }

        $invoice = self::id($invoice_id)->read(['id', 'price', 'invoice_type', 'invoice_lines_ids'])->first();

        if(!$invoice) {
            throw new \Exception('ORM::unknown invoice ['.$invoice_id.']', EQ_ERROR_INVALID_PARAM);
        }

        $map_accounting_entries = [];

        // fetch invoice lines
        $lines = SaleInvoiceLine::ids($invoice['invoice_lines_ids'])
            ->read([
                'total', 'price',
                'price_id' => [
                    'accounting_rule_id' => [
                        'vat_rule_id' => ['account_id'],
                        'accounting_rule_line_ids' => ['share', 'account_id']
                    ]
                ]
            ]);

        foreach($lines as $lid => $line) {

            if(!isset($line['price_id'])) {
                throw new \Exception("APP::invoice line [{$lid}] without price for invoice [{$invoice_id}]", EQ_ERROR_UNKNOWN);
            }

            if(!isset($line['price_id']['accounting_rule_id'])) {
                throw new \Exception("APP::invoice line [{$lid}] without accounting rule for invoice [{$invoice_id}]", EQ_ERROR_UNKNOWN);
            }

            if(!isset($line['price_id']['accounting_rule_id']['accounting_rule_line_ids'])
                || !count($line['price_id']['accounting_rule_id']['accounting_rule_line_ids'])) {
                throw new \Exception("APP::invoice line [{$lid}] without accounting rule lines for invoice [{$invoice_id}]", EQ_ERROR_UNKNOWN);
            }

            if(!isset($line['price_id']['accounting_rule_id']['vat_rule_id'])) {
                throw new \Exception("APP::invoice line [{$lid}] without VAT rule for invoice [{$invoice_id}]", EQ_ERROR_UNKNOWN);
            }

            // #memo - Only one VAT rate can be applied per line: we should only retrieve the associated account.
            $vat_account_id = $line['price_id']['accounting_rule_id']['vat_rule_id']['account_id'];

            if(!isset($map_accounting_entries[$vat_account_id])) {
                $map_accounting_entries[$vat_account_id] = 0.0;
            }

            $vat_amount = ($line['price'] < 0 ? -1.0 : 1.0) * (abs($line['price']) - abs($line['total']));
            $map_accounting_entries[$vat_account_id] += $vat_amount;

            $remaining_amount = $line['total'];

            $count_rules = count($line['price_id']['accounting_rule_id']['accounting_rule_line_ids']);
            $i = 1;

            foreach($line['price_id']['accounting_rule_id']['accounting_rule_line_ids'] as $rule_line_id => $ruleLine) {
                if(!isset($ruleLine['account_id'], $ruleLine['share']) || $ruleLine['account_id'] <= 0 || $ruleLine['share'] <= 0) {
                    throw new \Exception("APP::invalid accounting rule line [{$rule_line_id}] (missing account_id or share) for invoice line [{$lid}] of invoice [{$invoice_id}]", EQ_ERROR_UNKNOWN);
                }

                // last line
                if($i == $count_rules) {
                    $amount = $remaining_amount;
                }
                else {
                    $amount = round($line['total'] * $ruleLine['share'], 2);
                    $remaining_amount -= $amount;
                }

                if(!isset($map_accounting_entries[$ruleLine['account_id']])) {
                    $map_accounting_entries[$ruleLine['account_id']] = 0.0;
                }

                $map_accounting_entries[$ruleLine['account_id']] += $amount;

                ++$i;
            }
        }

        // create credit lines on sales & taxes accounts
        foreach($map_accounting_entries as $account_id => $amount) {
            $account = Account::id($account_id)->read(['description'])->first();
            $result[] = [
                    'name'          => $account['description'],
                    'account_id'    => $account_id,
                    'debit'         => ($invoice['invoice_type'] == 'credit_note')?$amount:0.0,
                    'credit'        => ($invoice['invoice_type'] == 'invoice')?$amount:0.0
                ];
        }

        // create a debit line on account "trade debtors"
        $result[] = [
                'name'          => $accountTradeDebtors['description'],
                'account_id'    => $accountTradeDebtors['id'],
                'debit'         => ($invoice['invoice_type'] == 'invoice')?$invoice['price']:0.0,
                'credit'        => ($invoice['invoice_type'] == 'credit_note')?$invoice['price']:0.0
            ];

        return $result;
    }

    /**
     * Allow business and technical synchronization fields after posting.
     */
    public static function canupdate($self, $values) {
        $self->read(['status']);

        $editable_fields = [
            'status',
            'payment_status',
            'customer_ref',
            'funding_id',
            'reversed_invoice_id',
            'reversal_of_id',
            'posted_at',
            'cancelled_at',
            'journal_id',
            'name',
            'description',
            'operation_type',
            'posting_date',
            'operation_number',
            'is_balanced',
            'payment_reference',
            'due_date',
            'total',
            'price',
            'price_billed'
        ];

        foreach($self as $id => $invoice) {
            if(
                $invoice['status'] !== 'proforma'
                && count(array_diff(array_keys($values), $editable_fields)) > 0
            ) {
                return [
                    'status' => [
                        'non_editable' => "Invoice {$id} can only be edited while proforma."
                    ]
                ];
            }
        }

        return [];
    }

    /**
     * Only proforma invoices can be deleted.
     */
    public static function candelete($self) {
        $self->read(['status']);

        foreach($self as $invoice) {
            if($invoice['status'] !== 'proforma') {
                return ['status' => ['non_removable' => 'Invoice can only be deleted while its status is proforma.']];
            }
        }

        return [];
    }
}
