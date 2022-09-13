<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;

class BankStatementLine extends \sale\booking\BankStatementLine {

    public static function getColumns() {

        return [

            'bank_statement_id' => [
                'type'              => 'many2one',
                'foreign_object'    => BankStatement::getType(),
                'description'       => 'The bank statement the line relates to.'
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\CenterOffice',
                'description'       => 'Center office related to the satement (based on account number).',
                'onupdate'          => 'onupdateCenterOfficeId'
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The customer the line relates to, if known (set at status change).',
                'readonly'          => true
            ],

            'payments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => Payment::getType(),
                'foreign_field'     => 'statement_line_id',
                'description'       => 'The list of payments this line relates to .',
                'onupdate'          => 'sale\pay\BankStatementLine::onupdatePaymentsIds',
                'ondetach'          => 'delete'
            ]

        ];
    }


    /**
     * Handler for center_office_id updates.
     *
     */
    public static function onupdateCenterOfficeId($om, $oids, $values, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BankStatementLine::onupdateCenterOfficeId", QN_REPORT_DEBUG);

        $om->call(self::getType(), 'reconcile', $oids, $values, $lang);
    }


    /**
     * Try to automatically reconcile a newly created statement line with a funding.
     * This method is called by current class (onupdateCenterOfficeId) and controller `lodging_sale_pay_bankstatementline_do-reconcile`
     */
    public static function reconcile($om, $oids, $values, $lang) {
        $lines = $om->read(get_called_class(), $oids, ['amount', 'center_office_id', 'structured_message']);

        if($lines > 0) {
            foreach($lines as $lid => $line) {

                $found_funding_id = null;

                $candidates_fundings_ids = $om->search(Funding::getType(), [ ['payment_reference', '=', $line['structured_message']] ]);

                // if there's no match, fall back to using message as reference
                if($candidates_fundings_ids <= 0 && !count($candidates_fundings_ids)) {
                    $candidates_fundings_ids = $om->search(Funding::getType(), [ ['payment_reference', '=', preg_replace('/[^0-9.]+/', '', $line['message'])] ]);
                }

                if($candidates_fundings_ids > 0 && count($candidates_fundings_ids)) {
                    // there should be at max 1 funding (since payment_reference should be unique, based on funding order and booking number)
                    $fundings = $om->read(Funding::getType(), $candidates_fundings_ids, ['id', 'is_paid', 'booking_id.fundings_ids']);
                    if($fundings > 0 && count($fundings)) {

                        $funding = reset($fundings);

                        // candidate 1: exact match with payment_reference AND still waiting for payment (whatever the due_amount)
                        if($funding['is_paid'] == false) {
                            $found_funding_id = $funding['id'];
                        }
                        // candidate 2: payment_reference matches another funding AND amount matches due_amount of a left over funding from a same booking
                        // #memo - this supports secondary payments for a booking, made with the reference of a previous funding
                        else {
                            $sibling_fundings = $om->read(Funding::getType(), $funding['booking_id.fundings_ids'], ['is_paid', 'due_amount']);
                            if($sibling_fundings > 0) {
                                foreach($sibling_fundings as $fid => $sibling_funding) {
                                    // ignore funding itself
                                    if($fid == $funding['id']) {
                                        continue;
                                    }
                                    if($sibling_funding['is_paid'] == false && $sibling_funding['due_amount'] == $line['amount']) {
                                        $found_funding_id = $fid;
                                    }
                                }
                            }
                            if(!$found_funding_id) {
                                // error: the amount has already been paid
                                // #todo - notify accountant that a reimbursment is due
                                $om->update(get_called_class(), $lid, ['status' => 'to_refund']);
                                continue;
                            }
                        }

                    }
                }

                if($found_funding_id) {
                    // create a new payment with received amount
                    $om->create(Payment::getType(), [
                        'funding_id'        => $found_funding_id,
                        'statement_line_id' => $lid,
                        'amount'            => $line['amount'],
                        'payment_method'    => 'bank'
                    ], $lang);
                    // mark the line as successfully reconciled
                    $om->update(get_called_class(), $lid, ['status' => 'reconciled']);
                }

            } /* end foreach */
        }
    }

}