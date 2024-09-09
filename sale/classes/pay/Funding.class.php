<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\pay;

use equal\orm\Model;
use core\setting\Setting;

class Funding extends Model {

    public static function getDescription() {
        return 'A funding is an amount of money that a customer ows to your organisation. It can be an installment or an invoice.';
    }

    public static function getColumns() {

        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Display name of funding.',
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
                'foreign_field'     => 'funding_id',
                'description'       => 'Customer payments of the funding.',
                'dependents'        => ['paid_amount', 'is_paid']
            ],

            'funding_type' => [
                'type'              => 'string',
                'selection'         => [
                    'installment',
                    'invoice'
                ],
                'default'           => 'invoice',
                'description'       => "Type of funding, is it a simple installment or is it linked to an specific invoice."
            ],

            'due_amount' => [
                'type'              => 'float',
                'usage'             => 'amount/money:2',
                'description'       => 'Amount expected for the funding.',
                'required'          => true,
                'dependents'        => ['name']
            ],

            'due_date' => [
                'type'              => 'date',
                'description'       => "Deadline before which the funding is expected."
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
                'store'             => true,
                'instant'           => true
            ],

            'is_paid' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => "Has the full payment been received?",
                'function'          => 'calcIsPaid',
                'store'             => true,
                'instant'           => true
            ],

            'amount_share' => [
                'type'              => 'float',
                'usage'             => 'amount/percent',
                'description'       => "Share of the payment over the total due amount."
            ],

            'invoice_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\accounting\invoice\Invoice',
                'description'       => 'The invoice targeted by the funding, if any.',
                'help'              => 'As a convention, this field is set when a funding relates to an invoice: either because the funding has been invoiced (downpayment or balance invoice), or because it is an installment (deduced from the due amount).'
            ],

            'payment_reference' => [
                'type'              => 'string',
                'description'       => 'Message for identifying the purpose of the transaction.',
                'default'           => ''
            ]

        ];
    }

    public static function calcName($self) {
        $result = [];
        $self->read(['due_amount', 'invoice_id' => ['name']]);
        foreach($self as $id => $funding) {
            $result[$id] = Setting::format_number_currency($funding['due_amount']);

            if($funding['invoice_id']['name']) {
                $result[$id] .= '    '.$funding['invoice_id']['name'];
            }
        }

        return $result;
    }

    public static function calcPaidAmount($self) {
        $result = [];
        $self->read(['payments_ids' => ['amount']]);
        foreach($self as $id => $funding) {
            $result[$id] = array_reduce($funding['payments_ids']->get(true), function ($c, $a) {
                return $c + $a['amount'];
            }, 0);
        }

        return $result;
    }

    public static function calcIsPaid($self) {
        $result = [];
        $self->read(['due_amount', 'paid_amount']);
        foreach($self as $id => $funding) {
            $result[$id] = $funding['paid_amount'] >= $funding['due_amount'] && $funding['due_amount'] > 0;
        }

        return $result;
    }

    public static function canupdate($self, $values) {
        $allowed = ['is_paid', 'invoice_id','funding_type'];
        $count_non_allowed = 0;

        foreach($values as $field => $value) {
            if(!in_array($field, $allowed)) {
                ++$count_non_allowed;
            }
        }

        if($count_non_allowed > 0) {
            $self->read(['is_paid', 'due_amount', 'paid_amount', 'payments_ids']);
            foreach($self as $funding) {
                if($funding['is_paid'] && $funding['due_amount'] == $funding['paid_amount'] && count($funding['payments_ids'])) {
                    return ['is_paid' => ['non_editable' => 'No change is allowed once the funding has been fully paid.']];
                }
            }
        }

        return parent::canupdate($self, $values);
    }

    public static function candelete($self) {
        $self->read(['is_paid', 'paid_amount', 'invoice_id' => ['status', 'invoice_type'], 'payments_ids']);
        foreach($self as $funding) {
            if($funding['is_paid'] || $funding['paid_amount'] != 0 || count($funding['payments_ids']) > 0) {
                return ['payments_ids' => ['non_removable_funding' => 'Funding paid or partially paid cannot be deleted.']];
            }
            if(isset($funding['invoice_id']['status']) && $funding['invoice_id']['status'] == 'invoice' && $funding['invoice_id']['invoice_type'] == 'invoice') {
                return ['invoice_id' => ['non_removable_funding' => 'Funding relating to an invoice cannot be deleted.']];
            }
        }

        return parent::candelete($self);
    }

    public static function ondelete($om, $oids) {
        $cron = $om->getContainer()->get('cron');

        foreach($oids as $fid) {
            // remove any previously scheduled task
            $cron->cancel("booking.funding.overdue.{$fid}");
        }
        parent::ondelete($om, $oids);
    }

    public static function onchange($event, $values) {
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
    protected static function _get_payment_reference($prefix, $suffix) {
        $a = intval($prefix);
        $b = intval($suffix);
        $control = ((76*$a) + $b ) % 97;
        $control = ($control == 0)?97:$control;
        return sprintf("%3d%04d%03d%02d", $a, $b / 1000, $b % 1000, $control);
    }
}
