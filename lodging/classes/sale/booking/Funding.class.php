<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

use lodging\sale\booking\Invoice;
use lodging\sale\booking\InvoiceLine;
use lodging\sale\catalog\Product;
use sale\price\Price;
use core\setting\Setting;


class Funding extends \lodging\sale\pay\Funding {

    public static function getColumns() {

        return [
            // override to use local calcName with booking_id
            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Booking::getType(),
                'description'       => 'Booking the contract relates to.',
                'ondelete'          => 'cascade',        // delete funding when parent booking is deleted
                'required'          => true
            ],

            // override to use custom onupdateDueAmount
            'due_amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Amount expected for the funding (computed based on VAT incl. price).',
                'required'          => true,
                'onupdate'          => 'onupdateDueAmount',
                'dependencies'      => ['name', 'amount_share']
            ],

            // override to reference booking.paid_amount
            'paid_amount' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => "Total amount that has been received (can be greater than due_amount).",
                'function'          => 'calcPaidAmount',
                'store'             => true,
                'dependencies'      => ['booking_id.paid_amount']
            ],

            'amount_share' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/percent',
                'function'          => 'calcAmountShare',
                'store'             => true,
                'description'       => "Share of the payment over the total due amount (booking)."
            ],

            // override to use local calcPaymentReference with booking_id
            'payment_reference' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcPaymentReference',
                'description'       => 'Message for identifying the purpose of the transaction.',
                'store'             => true
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Invoice::getType(),
                'description'       => 'The invoice targeted by the funding, if any.',
                'visible'           => [ ['type', '=', 'invoice'] ]
            ]

        ];
    }

    public static function calcName($om, $oids, $lang) {
        $result = [];
        $fundings = $om->read(get_called_class(), $oids, ['booking_id.name', 'due_amount'], $lang);

        if($fundings > 0) {
            foreach($fundings as $oid => $funding) {
                $result[$oid] = $funding['booking_id.name'].'    '.Setting::format_number_currency($funding['due_amount']);
            }
        }
        return $result;
    }

    public static function calcAmountShare($om, $oids, $lang) {
        $result = [];
        $fundings = $om->read(self::getType(), $oids, ['booking_id.price', 'due_amount'], $lang);

        if($fundings > 0) {
            foreach($fundings as $oid => $funding) {
                $result[$oid] = round($funding['due_amount'] / $funding['booking_id.price'], 2);
            }
        }

        return $result;
    }

    public static function calcPaymentReference($om, $oids, $lang) {
        $result = [];
        $fundings = $om->read(get_called_class(), $oids, ['booking_id.name', 'type', 'order', 'payment_deadline_id.code'], $lang);
        foreach($fundings as $oid => $funding) {
            $booking_code = intval($funding['booking_id.name']);
            if($funding['payment_deadline_id.code']) {
                $code_ref = intval($funding['payment_deadline_id.code']);
            }
            else {
                // arbitrary value : 151 for first funding, 152 for second funding, ...
                $code_ref = 150;
                if($funding['order']) {
                    $code_ref += $funding['order'];
                }
            }
            $result[$oid] = self::_get_payment_reference($code_ref, $booking_code);
        }
        return $result;
    }

    public static function onupdateDueAmount($orm, $oids, $values, $lang) {
        $orm->update(self::getType(), $oids, ['name' => null, 'amount_share' => null], $lang);
    }

    /**
     * Check wether an object can be created.
     * These tests come in addition to the unique constraints returned by method `getUnique()`.
     * Checks wheter the sum of the fundings of a booking remains lower than the price of the booking itself.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $values     Associative array holding the values to be assigned to the new instance (not all fields might be set).
     * @param  string                       $lang       Language in which multilang fields are being updated.
     * @return array            Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be created.
     */
    public static function cancreate($om, $values, $lang) {
        if(isset($values['booking_id']) && isset($values['due_amount'])) {
            $bookings = $om->read(Booking::getType(), $values['booking_id'], ['price', 'fundings_ids.due_amount'], $lang);
            if($bookings > 0 && count($bookings)) {
                $booking = reset($bookings);
                $fundings_price = (float) $values['due_amount'];
                foreach($booking['fundings_ids.due_amount'] as $fid => $funding) {
                    $fundings_price += (float) $funding['due_amount'];
                }
                if(($booking['price'] - $fundings_price) <= 0.0001) {
                    return ['status' => ['exceded_price' => "Sum of the fundings cannot be higher than the booking total ({$fundings_price}, {$booking['price']})."]];
                }
            }
        }
        return parent::cancreate($om, $values, $lang);
    }


    /**
     * Check wether an object can be updated.
     * These tests come in addition to the unique constraints returned by method `getUnique()`.
     * Checks wheter the sum of the fundings of each booking remains lower than the price of the booking itself.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $oids       List of objects identifiers.
     * @param  array                        $values     Associative array holding the new values to be assigned.
     * @param  string                       $lang       Language in which multilang fields are being updated.
     * @return array            Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang) {
        $fundings = $om->read(self::getType(), $oids, ['booking_id'], $lang);

        if($fundings > 0) {
            foreach($fundings as $fid => $funding) {
                $bookings = $om->read(Booking::getType(), $funding['booking_id'], ['price', 'fundings_ids.due_amount'], $lang);
                if($bookings > 0 && count($bookings)) {
                    $booking = reset($bookings);
                    $fundings_price = 0.0;
                    if(isset($values['due_amount'])) {
                        $fundings_price = (float) $values['due_amount'];
                    }
                    foreach($booking['fundings_ids.due_amount'] as $oid => $odata) {
                        if($oid != $fid) {
                            $fundings_price += (float) $odata['due_amount'];
                        }
                    }
                    if(($booking['price'] - $fundings_price) <= 0.0001) {
                        return ['status' => ['exceded_price' => "Sum of the fundings cannot be higher than the booking total ({$fundings_price}, {$booking['price']})."]];
                    }
                }
            }
        }
        return parent::canupdate($om, $oids, $values, $lang);
    }

    /**
     * Convert an installment to an invoice.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     */
    public static function _convertToInvoice($om, $oids, $values, $lang) {

        $fundings = $om->read(self::getType(), $oids, [
            'due_amount',
            'booking_id',
            'booking_id.customer_id',
            'booking_id.date_from',
            'booking_id.center_id.organisation_id',
            'booking_id.center_id.center_office_id',
            'booking_id.center_id.price_list_category_id'
            ], $lang);

        if($fundings > 0) {

            foreach($fundings as $fid => $funding) {

                // retrieve downpayment product
                $downpayment_product_id = 0;

                $downpayment_sku = Setting::get_value('sale', 'invoice', 'downpayment.sku.'.$funding['booking_id.center_id.organisation_id']);
                if($downpayment_sku) {
                    $products_ids = $om->search(Product::getType(), ['sku', '=', $downpayment_sku]);
                    if($products_ids > 0 && count($products_ids)) {
                        $downpayment_product_id = reset($products_ids);
                    }
                }

                // create a new invoice
                $invoice_id = $om->create(Invoice::getType(), [
                    'organisation_id'   => $funding['booking_id.center_id.organisation_id'],
                    'center_office_id'  => $funding['booking_id.center_id.center_office_id'],
                    'booking_id'        => $funding['booking_id'],
                    'partner_id'        => $funding['booking_id.customer_id'],
                    'funding_id'        => $fid
                ], $lang);

                /*
                    Find vat rule, based on Price for product for current year
                */
                $vat_rate = 0.0;

                // find suitable price list
                $price_lists_ids = $om->search('sale\price\PriceList', [
                        ['price_list_category_id', '=', $funding['booking_id.center_id.price_list_category_id']],
                        ['date_from', '<=', $funding['booking_id.date_from']],
                        ['date_to', '>=', $funding['booking_id.date_from']],
                        ['status', 'in', ['published']]
                    ],
                    ['is_active' => 'desc']
                );

                // search for a matching Price within the found Price List
                foreach($price_lists_ids as $price_list_id) {
                    // there should be one or zero matching pricelist with status 'published', if none of the found pricelist
                    $prices_ids = $om->search('sale\price\Price', [ ['price_list_id', '=', $price_list_id], ['product_id', '=', $downpayment_product_id]]);
                    if($prices_ids > 0 && count($prices_ids)) {
                        $prices = $om->read(Price::getType(), $prices_ids, ['vat_rate'], $lang);
                        $price = reset($prices);
                        $vat_rate = $price['vat_rate'];
                    }
                }

                // #memo - funding already includes the VAT, if any (funding due_amount cannot be changed)
                $unit_price = $funding['due_amount'];

                if($vat_rate > 0) {
                    // deduct VAT from due amount
                    $unit_price = round($unit_price / (1+$vat_rate), 4);
                }

                // create invoice line related to the downpayment
                $om->create(InvoiceLine::getType(), [
                    'invoice_id' => $invoice_id,
                    'product_id' => $downpayment_product_id,
                    'unit_price' => $unit_price,
                    'qty'        => 1,
                    'vat_rate'   => $vat_rate
                ]);

                // convert funding to 'invoice' type
                $om->update(Funding::getType(), $fid, ['type' => 'invoice', 'invoice_id' => $invoice_id]);
            }
        }
    }

}