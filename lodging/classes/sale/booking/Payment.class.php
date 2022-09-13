<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

class Payment extends \lodging\sale\pay\Payment {

    public static function getColumns() {

        return [
            'booking_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'function'          => 'calcBookingId',
                'foreign_object'    => Booking::getType(),
                'description'       => 'The booking the payement relates to, if any (computed).',
                'store'             => true
            ],

            'funding_id' => [
                'type'              => 'many2one',
                'foreign_object'    => Funding::getType(),
                'description'       => 'The funding the payement relates to, if any.',
                'onupdate'          => 'onupdateFundingId'
            ],

            'center_office_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'lodging\identity\CenterOffice',
                'function'          => 'calcCenterOfficeId',
                'description'       => 'Center office related to the satement (from statement_line_id).',
                'store'             => true
            ],

            'statement_line_id' => [
                'type'              => 'many2one',
                'foreign_object'    => BankStatementLine::getType(),
                'description'       => 'The bank statement line the payment relates to.',
                'visible'           => [ ['payment_origin', '=', 'bank'] ]
            ]
        ];
    }


    public static function calcBookingId($om, $oids, $lang) {
        $result = [];
        $items = $om->read(self::getType(), $oids, ['funding_id.booking_id']);

        foreach($items as $oid => $odata) {
            if(isset($odata['funding_id.booking_id'])) {
                $result[$oid] = $odata['funding_id.booking_id'];
            }
        }
        return $result;
    }

    public static function calcCenterOfficeId($om, $oids, $lang) {
        $result = [];
        $items = $om->read(self::getType(), $oids, ['statement_line_id.center_office_id']);

        foreach($items as $oid => $odata) {
            if(isset($odata['statement_line_id.center_office_id'])) {
                $result[$oid] = $odata['statement_line_id.center_office_id'];
            }
        }
        return $result;
    }

    /**
     * Check newly assigned funding and create an invoice for long term downpayments.
     * From an accounting perspective, if a downpayment has been received and is not related to an invoice yet,
     * it must relate to a service that will be delivered within the current year.
     * If the service will be delivered the downpayment is converted into an invoice.
     *
     * #memo - This cannot be undone.
     */
    public static function onupdateFundingId($om, $ids, $values, $lang) {
        // call parent onupdate
        parent::onupdateFundingId($om, $ids, $values, $lang);

        $payments = $om->read(self::getType(), $ids, ['funding_id', 'funding_id.booking_id', 'funding_id.booking_id.date_from', 'funding_id.type']);

        if($payments > 0) {
            foreach($payments as $pid => $payment) {
                // if payment relates to a funding attached to a booking that will occur after the 31th of december of current year, convert the funding to an invoice
                if($payment['funding_id'] && $payment['funding_id.booking_id']) {
                    $current_year_last_day = mktime(0, 0, 0, 12, 31, date('Y'));
                    if($payment['funding_id.type'] != 'invoice' && $payment['funding_id.booking_id.date_from'] > $current_year_last_day) {
                        // convert the funding to an invoice
                        $om->callonce(Funding::getType(), '_convertToInvoice', $payment['funding_id']);
                    }
                }
            }
        }
        // reset booking_id
        $om->update(self::getType(), $ids, ['booking_id' => null]);
    }

    /**
     * Signature for single object change from views.
     *
     * @param  Object   $om        Object Manager instance.
     * @param  Array    $event     Associative array holding changed fields as keys, and their related new values.
     * @param  Array    $values    Copy of the current (partial) state of the object.
     * @param  String   $lang      Language (char 2) in which multilang field are to be processed.
     * @return Array    Associative array mapping fields with their resulting values.
     */
    public static function onchange($om, $event, $values, $lang=DEFAULT_LANG) {
        $result = [];

        if(isset($event['funding_id'])) {
            $fundings = $om->read(Funding::getType(), $event['funding_id'], [
                    'type',
                    'due_amount',
                    'booking_id',
                    'booking_id.name',
                    'booking_id.customer_id.id',
                    'booking_id.customer_id.name',
                    'invoice_id.partner_id.id',
                    'invoice_id.partner_id.name'
                ],
                $lang
            );

            if($fundings > 0) {
                $funding = reset($fundings);
                $result['booking_id'] = [ 'id' => $funding['booking_id'], 'name' => $funding['booking_id.name'] ];
                if($funding['type'] == 'invoice')  {
                    $result['partner_id'] = [ 'id' => $funding['invoice_id.partner_id.id'], 'name' => $funding['invoice_id.partner_id.name'] ];
                }
                else {
                    $result['partner_id'] = [ 'id' => $funding['booking_id.customer_id.id'], 'name' => $funding['booking_id.customer_id.name'] ];
                }
                if(isset($values['amount']) && $values['amount'] > $funding['due_amount']) {
                    $result['amount'] = $funding['due_amount'];
                }
            }
        }

        return $result;
    }
}