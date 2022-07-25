<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

use core\setting\Setting;

class Invoice extends \sale\booking\Invoice {

    public static function getColumns() {

        return [

            'invoice_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => InvoiceLine::getType(),
                'foreign_field'     => 'invoice_id',
                'description'       => 'Detailed lines of the invoice.',
                'ondetach'          => 'delete',
                'onupdate'          => 'onupdateInvoiceLinesIds'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'description'       => 'Booking the invoice relates to.',
                'required'          => true
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Funding',
                'description'       => 'The funding the invoice originates from, if any.'
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\CenterOffice',
                'description'       => 'Office the invoice relates to (for center management).',
                'required'          => true
            ],

            'number' => [
                'type'              => 'string',
                'description'       => "Number of the invoice, according to organisation logic (@see config/invoicing).",
                'default'           => '[proforma]'
            ],

            'payment_reference' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcPaymentReference',
                'description'       => 'Message for identifying payments related to the invoice.',
                'store'             => true
            ],
            
            'reversed_invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => self::getType(),
                'description'       => "Credit note that was created for cancelling the invoice.",
                'visible'           => ['status', '=', 'cancelled']
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'proforma',             // draft invoice (no number yet)
                    'invoice',              // final invoice (with unique number and accounting entries)
                    'cancelled'             // the invoice has been cancelled (through reversing entries)
                ],
                'default'           => 'proforma'
            ]

        ];
    }


    /**
     * Assign a number and a date to the invoices.
     */
    public static function _setNumber($om, $oids, $values, $lang) {
        $result = [];

        $invoices = $om->read(get_called_class(), $oids, ['status', 'organisation_id', 'center_office_id.code'], $lang);

        foreach($invoices as $oid => $invoice) {

            $organisation_id = $invoice['organisation_id'];
            $format = Setting::get_value('finance', 'invoice', 'invoice.sequence_format', '%05d{sequence}');
            $year = Setting::get_value('finance', 'invoice', 'invoice.fiscal_year');
            $sequence = Setting::get_value('sale', 'invoice', 'invoice.sequence.'.$organisation_id);

            if($sequence) {
                Setting::set_value('sale', 'invoice', 'invoice.sequence.'.$organisation_id, $sequence + 1);
                $number = Setting::parse_format($format, [
                    'year'      => $year,
                    'office'    => $invoice['center_office_id.code'],
                    'org'       => $organisation_id,
                    'sequence'  => $sequence
                ]);
                $om->write(__CLASS__, $oid, ['number' => $number, 'date' => time()], $lang);
            }

        }
        return $result;
    }


    public static function calcPaymentReference($om, $oids, $lang) {
        $result = [];
        $invoices = $om->read(get_called_class(), $oids, ['booking_id.name']);
        foreach($invoices as $oid => $invoice) {
            $booking_code = intval($invoice['booking_id.name']);
            // arbitrary value : 155 for final invoice
            $code_ref = 155;
            $result[$oid] = self::_get_payment_reference($code_ref, $booking_code);
        }
        return $result;
    }

    /**
     * Handler for invoice change (checks if status has changed from 'proforma' to 'invoice').
     * This is performed before the actual update, so changes are not prevented by canupdate (since status will be updated at the end of the cycle).
     *
     * @param \equal\orm\ObjectManager  $om Instance of the objects manager.
     */
    public static function onupdate($om, $ids, $values, $lang) {
        // only upon request for final invoice creation
        if(isset($values['status']) && $values['status'] == 'invoice') {
            /*
                Generate an invoice number
            */
            $om->callonce(__CLASS__, '_setNumber', $ids, [], $lang);

            /*
                Generate the accounting entries
            */
            $invoices = $om->read(self::getType(), $ids, ['type', 'organisation_id', 'invoice_lines_ids'], $lang);
            if($invoices > 0) {
                foreach($invoices as $oid => $invoice) {
                    // retrieve downpayment product
                    $downpayment_product_id = 0;

                    $downpayment_sku = Setting::get_value('sale', 'invoice', 'downpayment.sku.'.$invoice['organisation_id']);
                    if($downpayment_sku) {
                        $products_ids = $om->search(\lodging\sale\catalog\Product::getType(), ['sku', '=', $downpayment_sku]);
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
                                // if some VTA is due, deduct the sum accordingly
                                $debit_vat_sum += $vat_amount;
                                // create a debit line with the product, on sale account 70xxxxx (id=895) (VAT excl.)
                                $debit = abs($line['total']);
                                $credit = 0.0;
                                $accounting_entries[] = [
                                    'name'          => $line['name'],
                                    'invoice_id'    => $oid,
                                    'account_id'    => 895,
                                    'debit'         => ($invoice['type'] == 'invoice')?$debit:$credit,
                                    'credit'        => ($invoice['type'] == 'invoice')?$credit:$debit
                                ];
                            }
                            // line is a regular product line
                            else {
                                // sum up VAT amounts
                                $credit_vat_sum += $vat_amount;
                                // sum up sale prices vente (VAT incl. price)
                                $prices_sum += $line['price'];
                                $rule_lines = [];
                                // handle installment invoice
                                if($line['product_id'] == $downpayment_product_id) {
                                    // generate virtual rule for downpayment
                                    $rule_lines = [
                                        ['account_id' => 895, 'share' => 1.0]
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
                                            'name'          => $line['name'],
                                            'invoice_id'    => $oid,
                                            'account_id'    => $rline['account_id'],
                                            'debit'         => ($invoice['type'] == 'invoice')?$debit:$credit,
                                            'credit'        => ($invoice['type'] == 'invoice')?$credit:$debit
                                        ];
                                    }
                                }
                            }
                        }

                        // create a credit line on account 451 : taxes TVA à payer (somme des TVA) (id=517)
                        if($credit_vat_sum > 0) {
                            $debit = 0.0;
                            $credit = round($credit_vat_sum, 2);
                            // assign with handling of reversing entries
                            $accounting_entries[] = [
                                'name'          => 'taxes TVA à payer',
                                'invoice_id'    => $oid,
                                'account_id'    => 517,
                                'debit'         => ($invoice['type'] == 'invoice')?$debit:$credit,
                                'credit'        => ($invoice['type'] == 'invoice')?$credit:$debit
                            ];
                        }

                        // create a debit line on account 451 : taxes TVA à payer (somme des TVA) (id=517)
                        if($debit_vat_sum > 0) {
                            $debit = round($debit_vat_sum, 2);
                            $credit = 0.0;
                            // assign with handling of reversing entries
                            $accounting_entries[] = [
                                'name'          => 'taxes TVA à payer',
                                'invoice_id'    => $oid,
                                'account_id'    => 517,
                                'debit'         => ($invoice['type'] == 'invoice')?$debit:$credit,
                                'credit'        => ($invoice['type'] == 'invoice')?$credit:$debit
                            ];
                        }

                        // create a debit line on account 40000 (id=421): créances commerciales (sommes des prix de vente TVAC - somme des acomptes)
                        $debit = round($prices_sum-$downpayments_sum, 2);
                        $credit = 0.0;
                        // assign with handling of reversing entries
                        $accounting_entries[] = [
                            'name'          => 'créances commerciales',
                            'invoice_id'    => $oid,
                            'account_id'    => 421,
                            'debit'         => ($invoice['type'] == 'invoice')?$debit:$credit,
                            'credit'        => ($invoice['type'] == 'invoice')?$credit:$debit
                        ];

                        // generate all required entries
                        foreach($accounting_entries as $eid => $entry) {
                            $om->create(\finance\accounting\AccountingEntry::getType(), $entry);
                        }

                    }
                }
            }
        }
    }
}