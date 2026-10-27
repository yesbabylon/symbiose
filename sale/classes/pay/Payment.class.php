<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\pay;

use finance\bank\BankStatementLine;

class Payment extends FundingAllocation {

    public static function getDescription() {
        return 'A payment is an amount of money paid by a customer for a product or service.'
            .' It can originate from the cashdesk or a bank transfer.';
    }

    public static function getColumns() {
        return [
            'customer_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'sale\customer\Customer',
                'description'    => 'Customer to whom the payment relates.'
            ],

            'receipt_bank_account_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'finance\bank\BankAccount',
                'description'    => 'Bank account on which the payment was received or sent.',
                'help'           => 'This account can differ from the bank account expected by the funding.',
                'readonly'       => true,
                'domain'         => ['organization_id', '=', 'object.organization_id']
            ],

            'payment_origin' => [
                'type'        => 'string',
                'selection'   => [
                    'cashdesk',
                    'bank'
                ],
                'description' => 'Origin of the received money.',
                'default'     => 'bank'
            ],

            'payment_method' => [
                'type'        => 'string',
                'selection'   => [
                    'cash',
                    'bank_card',
                    'voucher',
                    'wire_transfer'
                ],
                'description' => 'Method used for the payment.',
                'default'     => 'wire_transfer'
            ],

            'operation_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'sale\pos\Operation',
                'description'    => 'Cashdesk operation to which the payment relates.',
                'visible'        => ['payment_origin', '=', 'cashdesk']
            ],

            'statement_line_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'finance\bank\BankStatementLine',
                'description'    => 'Bank statement line from which the payment originates.',
                'visible'        => ['payment_origin', '=', 'bank']
            ],

            'voucher_ref' => [
                'type'        => 'string',
                'description' => 'Reference of the voucher used for the payment.',
                'visible'     => [
                    ['payment_origin', '=', 'cashdesk'],
                    ['payment_method', '=', 'voucher']
                ]
            ],

            'accounting_entry_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'finance\accounting\AccountingEntry',
                'description'    => 'Accounting entry generated for the payment.'
            ],

            'is_exported' => [
                'type'        => 'boolean',
                'description' => 'Whether the payment was exported to another system.',
                'help'        => 'An exported payment can no longer be modified.',
                'default'     => false
            ],

            'status' => [
                'type'        => 'string',
                'selection'   => [
                    'proforma',
                    'posted'
                ],
                'default'     => 'proforma',
                'description' => 'Current posting status of the payment.'
            ]
        ];
    }

    public static function getWorkflow() {
        return [
            'proforma' => [
                'description' => 'Payment being prepared.',
                'icon'        => 'draw',
                'transitions' => [
                    'post' => [
                        'description' => 'Post the payment.',
                        'onafter'     => 'onafterPost',
                        'status'      => 'posted'
                    ]
                ]
            ],
            'posted' => [
                'description' => 'Posted payment.',
                'icon'        => 'check',
                'transitions' => [
                    'revert' => [
                        'description' => 'Revert the payment to proforma.',
                        'onafter'     => 'onafterRevert',
                        'status'      => 'proforma'
                    ]
                ]
            ]
        ];
    }

    protected static function onafterPost($self) {
        $self->do('refresh_related_objects');
    }

    protected static function onafterRevert($self) {
        $self->do('refresh_related_objects');
    }

    protected static function onbeforedelete($self) {
        $self->read(['linked_payment_id' => ['status']]);
        foreach($self as $payment) {
            if(!$payment['linked_payment_id']) {
                continue;
            }
            FundingAllocation::id($payment['linked_payment_id']['id'])->delete(true);
        }
    }

    public static function onchange($event, $values) {
        $result = [];

        if(isset($event['payment_origin'])) {
            switch($event['payment_origin']) {
                case 'cashdesk':
                    $result['bank_statement_line_id'] = null;
                    break;
                case 'bank':
                    $result['payment_method'] = 'wire_transfer';
                    $result['operation_id'] = null;
                    $result['voucher_ref'] = null;
                    break;
            }
        }

        if(array_key_exists('funding_id', $event) && $event['funding_id']) {
            $funding = Funding::id($event['funding_id'])
                ->read(['funding_type', 'due_amount', 'invoice_id' => ['customer_id' => ['name']]])
                ->first();

            if($funding) {
                if($funding['funding_type'] === 'invoice' && $funding['invoice_id'] && $funding['invoice_id']['customer_id']) {
                    $result['customer_id'] = [
                        'id'   => $funding['invoice_id']['customer_id']['id'],
                        'name' => $funding['invoice_id']['customer_id']['name']
                    ];
                    $result['invoice_id'] = $funding['invoice_id']['id'];
                }

                if(isset($values['amount']) && abs($values['amount']) > abs($funding['due_amount'])) {
                    $result['amount'] = $funding['due_amount'];
                }
            }
        }

        return $result;
    }

    public static function candelete($self) {
        $self->read(['status']);
        foreach($self as $payment) {
            if($payment['status'] !== 'proforma') {
                return ['status' => ['non_removable' => 'Posted payments cannot be deleted manually.']];
            }
        }
        return parent::candelete($self);
    }

    public static function canupdate($self, $values) {
        $self->read([
            'status',
            'is_exported',
            'payment_origin',
            'amount',
            'bank_statement_line_id' => ['amount', 'remaining_amount']
        ]);

        foreach($self as $payment) {
            if($payment['is_exported']) {
                return ['is_exported' => ['non_editable' => 'Once exported, a payment can no longer be updated.']];
            }

            $payment_origin = $values['payment_origin'] ?? $payment['payment_origin'];
            if($payment_origin !== 'bank' || !isset($values['amount'])) {
                continue;
            }

            $statement_line = $payment['bank_statement_line_id'];
            if(isset($values['bank_statement_line_id'])) {
                $statement_line = BankStatementLine::id($values['bank_statement_line_id'])
                    ->read(['amount', 'remaining_amount'])
                    ->first();
            }
            if(!$statement_line) {
                continue;
            }

            $sign_line = intval($statement_line['amount'] > 0) - intval($statement_line['amount'] < 0);
            $sign_payment = intval($values['amount'] > 0) - intval($values['amount'] < 0);
            if($sign_line !== $sign_payment) {
                return ['amount' => ['incompatible_sign' => 'Payment and bank statement line amounts must have the same sign.']];
            }

            $remaining = round($statement_line['remaining_amount'] + $payment['amount'] - $values['amount'], 2);
            if(($statement_line['amount'] < 0 && $remaining > 0) || ($statement_line['amount'] >= 0 && $remaining < 0)) {
                return ['amount' => ['excessive_amount' => 'Payment amount cannot exceed the bank statement line remaining amount.']];
            }
        }

        return parent::canupdate($self, $values);
    }
}
