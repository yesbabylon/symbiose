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
                'foreign_object'    => 'lodging\sale\booking\BankStatement',
                'description'       => 'The bank statement the line relates to.'
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\CenterOffice',
                'description'       => 'Center office related to the satement (bassed on account number).',
                'onchange'          => 'onchangeCenterOfficeId'
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => 'The customer the line relates to, if known (set at status change).',
                'readonly'          => true
            ],

        ];
    }


    /**
     * Try to automatically reconcile a newly created statement line with a funding.
     * 
     */
    public static function onchangeCenterOfficeId($om, $oids, $lang) {
        trigger_error("QN_DEBUG_ORM::calling lodging\sale\booking\BankStatementLine::onchangeCenterOfficeId", QN_REPORT_DEBUG);

        $lines = $om->read(get_called_class(), $oids, ['amount', 'center_office_id', 'structured_message']);

        if($lines > 0) {
            foreach($lines as $lid => $line) {

                $fundings_ids = $om->search('lodging\sale\booking\Funding', [ ['is_paid', '=', false], ['center_office_id', '=', $line['center_office_id']] ]);

                if($fundings_ids > 0) {
                    $found_funding_id = null;
                    // candidates 1
                    $candidates_fundings_ids = $om->search('lodging\sale\booking\Funding', ['payment_reference', '=', $line['structured_message'] ]);
                    if($candidates_fundings_ids > 0 && count($candidates_fundings_ids) == 1) {
                        $found_funding_id = reset($candidates_fundings_ids);
                    }
                    else {
                        // candidates 2
                        $candidates_fundings_ids = $om->search('lodging\sale\booking\Funding', [ ['due_amount', '=', $line['amount']], ['id', 'in', $fundings_ids] ]);
                        if($candidates_fundings_ids > 0 && count($candidates_fundings_ids) == 1) {
                            $found_funding_id = reset($candidates_fundings_ids);
                        }
                    }
                    if($found_funding_id) {
                        // create a new payment with negative amount
                        $om->create('sale\pay\Payment', [
                            'funding_id'        => $found_funding_id,
                            'statement_line_id' => $lid,
                            'amount'            => $line['amount'],
                            'payment_method'    => 'bank'
                        ], $lang);
                        $om->write(get_called_class(), $lid, ['status' => 'reconciled']);
                    }
                }
            }
        }
    }

}