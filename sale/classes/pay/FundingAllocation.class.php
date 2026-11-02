<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace sale\pay;

use equal\orm\Model;
use finance\accounting\AccountingJournal;
use finance\bank\BankStatementLine;

/**
 * Allocation of an accounting movement to a funding.
 *
 * Funding allocations and payments share the same storage while remaining
 * distinct persistent model types through their model scope.
 */
class FundingAllocation extends Model {


    public static function getDescription() {
        return 'An allocation links an accounting movement to a funding and records the amount assigned to it.';
    }

    public static function getColumns() {
        return [
            'organization_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'identity\Organization',
                'description'    => 'Organization the allocation belongs to.',
                'required'       => true,
                'default'        => 1,
                'dependents'     => ['journal_id']
            ],

            'journal_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'finance\accounting\AccountingJournal',
                'function'       => 'calcJournalId',
                'description'    => 'Bank and cash accounting journal of the organization.',
                'readonly'       => true,
                'store'          => true
            ],

            'payment_origin' => [
                'type'        => 'string',
                'selection'   => ['funding_allocation'],
                'description' => 'Origin of the allocated amount.',
                'default'     => 'funding_allocation',
                'readonly'    => true
            ],

            'amount' => [
                'type'        => 'float',
                'usage'       => 'amount/money:2',
                'description' => 'Amount assigned to the funding.',
                'dependents'  => ['funding_id' => ['is_paid', 'paid_amount']]
            ],

            'description' => [
                'type'  => 'alias',
                'alias' => 'communication'
            ],

            'communication' => [
                'type'        => 'string',
                'description' => 'Description of the allocation.',
                'help'        => 'This can be a message from the counterpart or a description of the accounting movement.'
            ],

            'receipt_date' => [
                'type'        => 'datetime',
                'description' => 'Date and time at which the allocated amount was received or paid.',
                'default'     => function() {
                    return time();
                }
            ],

            'origin_object_class' => [
                'type'        => 'string',
                'description' => 'Entity class from which the allocation originates.',
                'help'        => 'Stored with its full namespace and used together with origin_object_id.'
            ],

            'origin_object_id' => [
                'type'        => 'integer',
                'description' => 'Identifier of the object from which the allocation originates.'
            ],

            'linked_payment_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'sale\pay\FundingAllocation',
                'description'    => 'Funding allocation linked to the current one, if any.',
                'help'           => 'Used for symmetrical allocations between two fundings.',
                'domain'         => ['id', '<>', 'object.id'],
                'readonly'       => true
            ],

            'accounting_operation_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'finance\accounting\operation\AccountingOperation',
                'description'    => 'Accounting operation targeted by the allocation, if any.',
                'domain'         => ['organization_id', '=', 'object.organization_id']
            ],

            'purchase_invoice_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'purchase\accounting\invoice\PurchaseInvoice',
                'description'    => 'Purchase invoice targeted by the allocation, if any.',
                'ondelete'       => 'null',
                'domain'         => ['organization_id', '=', 'object.organization_id'],
                'visible'        => ['origin_object_class', '=', 'purchase\accounting\invoice\PurchaseInvoice']
            ],

            'invoice_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'sale\accounting\invoice\SaleInvoice',
                'description'    => 'Sale invoice targeted by the allocation, if any.',
                'ondelete'       => 'null',
                'domain'         => ['organization_id', '=', 'object.organization_id'],
                'visible'        => ['origin_object_class', '=', 'sale\accounting\invoice\SaleInvoice']
            ],

            'sale_invoice_id' => [
                'type'  => 'alias',
                'alias' => 'invoice_id'
            ],

            'statement_line_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'finance\bank\BankStatementLine',
                'description'    => 'Bank statement line from which the allocation originates.',
                'visible'        => ['origin_object_class', '=', 'finance\bank\BankStatementLine']
            ],

            'bank_statement_line_id' => [
                'type'  => 'alias',
                'alias' => 'statement_line_id'
            ],

            'accounting_entry_line_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'finance\accounting\AccountingEntryLine',
                'description'    => 'Accounting entry line targeted by the allocation.'
            ],

            'funding_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'sale\pay\Funding',
                'description'    => 'Funding to which the amount is allocated.',
                'ondelete'       => 'cascade'
            ],

            'status' => [
                'type'        => 'string',
                'selection'   => ['posted'],
                'default'     => 'posted',
                'description' => 'Status of the allocation.',
                'readonly'    => true
            ]
        ];
    }

    public static function getActions() {
        return [
            'refresh_related_objects' => [
                'description' => 'Refresh amounts computed on the related funding and bank statement line.',
                'policies'    => [],
                'function'    => 'doRefreshRelatedObjects'
            ]
        ];
    }

    protected static function oncreate($self) {
        $self->do('refresh_related_objects');
    }

    protected static function onafterupdate($self) {
        $self->do('refresh_related_objects');
    }

    protected static function doRefreshRelatedObjects($self) {
        $self->read(['funding_id', 'bank_statement_line_id']);
        foreach($self as $allocation) {
            if($allocation['funding_id']) {
                Funding::id($allocation['funding_id'])->update([
                    'paid_amount' => null,
                    'is_paid'     => null
                ]);
            }
            if($allocation['bank_statement_line_id']) {
                BankStatementLine::id($allocation['bank_statement_line_id'])->update([
                    'remaining_amount' => null,
                    'is_reconciled'    => null
                ]);
            }
        }
    }

    protected static function calcJournalId($self) {
        $result = [];
        $self->read(['organization_id']);
        foreach($self as $id => $allocation) {
            if(!$allocation['organization_id']) {
                continue;
            }
            $journal = AccountingJournal::search([
                    ['organization_id', '=', $allocation['organization_id']],
                    ['journal_type', '=', 'CASH']
                ])
                ->read(['id'])
                ->first();
            if($journal) {
                $result[$id] = $journal['id'];
            }
        }
        return $result;
    }
}
