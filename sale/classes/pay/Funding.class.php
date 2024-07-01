<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pay;
use equal\orm\Model;
use core\setting\Setting;

class Funding extends Model {

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcName',
                'store'             => true
            ],

            'description' => [
                'type'              => 'string',
                'description'       => "Optional description to identify the funding."
            ],

            'payments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => Payment::getType(),
                'foreign_field'     => 'funding_id'
            ],

            'type' => [
                'type'              => 'string',
                'selection'         => [
                    'installment',
                    'invoice'
                ],
                'default'           => 'installment',
                'description'       => "Deadlines are installment except for last one: final invoice."
            ],

            'order' => [
                'type'              => 'integer',
                'description'       => 'Order by which the funding have to be sorted when presented.',
                'default'           => 0
            ],

            'due_amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Amount expected for the funding (computed based on VAT incl. price).',
                'required'          => true,
                'onupdate'          => 'onupdateDueAmount'
            ],

            'due_date' => [
                'type'              => 'date',
                'description'       => "Deadline before which the funding is expected.",
                'default'           => time()
            ],

            'issue_date' => [
                'type'              => 'date',
                'description'       => "Date at which the request for payment has to be issued.",
                'default'           => time()
            ],

            'paid_amount' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'description'       => "Total amount that has been received (can be greater than due_amount).",
                'function'          => 'calcPaidAmount',
                'store'             => true
            ],

            'is_paid' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => "Has the full payment been received?",
                'function'          => 'calcIsPaid',
                'store'             => true,
            ],

            'amount_share' => [
                'type'              => 'float',
                'usage'             => 'amount/percent',
                'description'       => "Share of the payment over the total due amount."
            ],

            'payment_deadline_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\pay\PaymentDeadline',
                'description'       => "The deadline model used for creating the funding, if any.",
                'onupdate'          => 'onupdatePaymentDeadlineId'
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'finance\accounting\Invoice',
                'ondelete'          => 'null',
                'description'       => 'The invoice targeted by the funding, if any.',
                'help'              => 'As a convention, this field is set when a funding relates to an invoice: either because the funding has been invoiced (downpayment or balance invoice), or because it is an installment (deduced from the due amount)'
            ],

            'payment_reference' => [
                'type'              => 'string',
                'description'       => 'Message for identifying the purpose of the transaction.',
                'default'           => ''
            ]
        ];
    }


    public static function calcName($om, $oids, $lang) {
        $result = [];
        $fundings = $om->read(get_called_class(), $oids, ['payment_deadline_id.name', 'due_amount'], $lang);

        if($fundings > 0) {
            foreach($fundings as $oid => $funding) {
                $result[$oid] = Setting::format_number_currency($funding['due_amount']).'    '.$funding['payment_deadline_id.name'];
            }
        }
        return $result;
    }

    public static function calcPaidAmount($om, $oids, $lang) {
        $result = [];
        $fundings = $om->read(self::getType(), $oids, ['payments_ids.amount'], $lang);
        if($fundings > 0) {
            foreach($fundings as $fid => $funding) {
                $result[$fid] = array_reduce($funding['payments_ids.amount'], function ($c, $funding) {
                    return $c + $funding['amount'];
                }, 0);
            }
        }
        return $result;
    }

    public static function calcIsPaid($om, $oids, $lang) {
        $result = [];
        $fundings = $om->read(self::getType(), $oids, ['due_amount', 'paid_amount'], $lang);
        if($fundings > 0) {
            foreach($fundings as $fid => $funding) {
                $result[$fid] = false;
                if($funding['paid_amount'] >= $funding['due_amount'] && $funding['due_amount'] > 0) {
                    $result[$fid] = true;
                }
            }
        }
        return $result;
    }

    public static function onupdateDueAmount($om, $oids, $values, $lang) {
        // reset the name
        $om->update(self::getType(), $oids, ['name' => null], $lang);
    }

    /**
     * Update the description according to the deadline, when set.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $oids       List of objects identifiers.
     * @param  array                        $values     Associative array holding the new values to be assigned.
     * @param  string                       $lang       Language in which multilang fields are being updated.
     */
    public static function onupdatePaymentDeadlineId($om, $oids, $values, $lang) {
        $fundings = $om->read(self::getType(), $oids, ['payment_deadline_id.name'], $lang);
        if($fundings > 0) {
            foreach($fundings as $oid => $funding) {
                if($funding['payment_deadline_id.name'] && strlen($funding['payment_deadline_id.name']) > 0) {
                    $om->update(self::getType(), $oid, ['description' => $funding['payment_deadline_id.name']], $lang);
                }
            }
        }
    }

    /**
     * Check wether an object can be updated, and perform some additional operations if necessary.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $oids       List of objects identifiers.
     * @param  array                        $values     Associative array holding the new values to be assigned.
     * @param  string                       $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang) {
        // handle exceptions for fields that can always be updated
        $allowed = ['is_paid', 'invoice_id'];
        $count_non_allowed = 0;

        foreach($values as $field => $value) {
            if(!in_array($field, $allowed)) {
                ++$count_non_allowed;
            }
        }

        if($count_non_allowed > 0) {
            $fundings = $om->read(self::getType(), $oids, ['is_paid', 'due_amount', 'paid_amount', 'payments_ids'], $lang);
            if($fundings > 0) {
                foreach($fundings as $funding) {
                    if($funding['is_paid'] && $funding['due_amount'] == $funding['paid_amount'] && count($funding['payments_ids'])) {
                        return ['is_paid' => ['non_editable' => 'No change is allowed once the funding has been fully paid.']];
                    }
                }
            }
        }
        return parent::canupdate($om, $oids, $values, $lang);
    }

    /**
     * Check wether the identity can be deleted.
     *
     * @param  \equal\orm\ObjectManager    $om        ObjectManager instance.
     * @param  array                       $ids       List of objects identifiers.
     * @return array                       Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be deleted.
     */
    public static function candelete($om, $ids) {
        $fundings = $om->read(self::getType(), $ids, [ 'is_paid', 'paid_amount', 'invoice_id', 'invoice_id.status', 'payments_ids' ]);

        if($fundings > 0) {
            foreach($fundings as $id => $funding) {
                if( $funding['is_paid'] || $funding['paid_amount'] != 0 || ($funding['payments_ids'] && count($funding['payments_ids']) > 0) ) {
                    return ['payments_ids' => ['non_removable_funding' => 'Funding paid or partially paid cannot be deleted.']];
                }
                if( !is_null($funding['invoice_id']) && $funding['invoice_id.status'] == 'invoice' ) {
                    return ['invoice_id' => ['non_removable_funding' => 'Funding relating to an invoice cannot be deleted.']];
                }
            }
        }
        return parent::candelete($om, $ids);
    }


    /**
     * Hook invoked before object deletion for performing object-specific additional operations.
     * Remove the scheduled tasks related to the deleted fundings.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $oids       List of objects identifiers.
     * @return void
     */
    public static function ondelete($om, $oids) {
        $cron = $om->getContainer()->get('cron');

        foreach($oids as $fid) {
            // remove any previously scheduled task
            $cron->cancel("booking.funding.overdue.{$fid}");
        }
        parent::ondelete($om, $oids);
    }

    /**
     * Signature for single object change from views.
     *
     * @param  \equal\orm\ObjectManager     $om        Object Manager instance.
     * @param  Array                        $event     Associative array holding changed fields as keys, and their related new values.
     * @param  Array                        $values    Copy of the current (partial) state of the object (fields depend on the view).
     * @param  String                       $lang      Language (char 2) in which multilang field are to be processed.
     * @return Array    Associative array mapping fields with their resulting values.
     */
    public static function onchange($om, $event, $values, $lang='en') {
        $result = [];

        // if 'is_paid' is set manually, adapt 'paid_mount' consequently
        if(isset($event['is_paid'])) {
            $result['paid_amount'] = $values['due_amount'];
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
    public static function _get_payment_reference($prefix, $suffix) {
        $a = intval($prefix);
        $b = intval($suffix);
        $control = ((76*$a) + $b ) % 97;
        $control = ($control == 0)?97:$control;
        return sprintf("%3d%04d%03d%02d", $a, $b / 1000, $b % 1000, $control);
    }
}