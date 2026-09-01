<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace finance\accounting\operation;

use equal\orm\Model;

/**
 * Input line of a generic accounting operation.
 *
 * Operation lines are translated into accounting entry lines when their
 * parent operation is posted.
 */
class AccountingOperationLine extends Model {

    public static function getName() {
        return 'Accounting operation line';
    }

    public static function getDescription() {
        return 'Line used to generate an accounting entry line for an accounting operation.';
    }

    public static function getColumns() {
        return [
            'description' => [
                'type'        => 'string',
                'description' => 'Short description identifying the operation line.',
                'dependents'  => ['name']
            ],

            'debit' => [
                'type'        => 'float',
                'usage'       => 'amount/money:4',
                'description' => 'Amount debited from the account.',
                'default'     => 0.0,
                'dependents'  => ['operation_id' => 'is_balanced']
            ],

            'credit' => [
                'type'        => 'float',
                'usage'       => 'amount/money:4',
                'description' => 'Amount credited to the account.',
                'default'     => 0.0,
                'dependents'  => ['operation_id' => 'is_balanced']
            ],

            'vat_rate' => [
                'type'        => 'float',
                'usage'       => 'amount/rate',
                'description' => 'VAT rate associated with the operation line.',
                'default'     => 0.0
            ],

            'operation_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'finance\accounting\operation\AccountingOperation',
                'description'    => 'Accounting operation the line belongs to.',
                'required'       => true,
                'ondelete'       => 'cascade',
                'dependents'     => ['organisation_id', 'journal_id']
            ],

            'account_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'finance\accounting\Account',
                'description'    => 'Accounting account affected by the line.',
                'required'       => true,
                'ondelete'       => 'null',
                'domain'         => ['is_group_account', '=', false],
                'dependents'     => ['account_code', 'is_expense', 'is_income']
            ],

            'name' => [
                'type'        => 'computed',
                'result_type' => 'string',
                'description' => 'Display name of the accounting operation line.',
                'relation'    => ['description'],
                'store'       => true,
                'readonly'    => true
            ],

            'organisation_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'identity\Organisation',
                'description'    => 'Organisation inherited from the accounting operation.',
                'relation'       => ['operation_id' => 'org_id'],
                'store'          => true,
                'readonly'       => true
            ],

            'journal_id' => [
                'type'           => 'computed',
                'result_type'    => 'many2one',
                'foreign_object' => 'finance\accounting\AccountingJournal',
                'description'    => 'Accounting journal inherited from the operation.',
                'relation'       => ['operation_id' => 'journal_id'],
                'store'          => true,
                'instant'        => true,
                'readonly'       => true
            ],

            'account_code' => [
                'type'        => 'computed',
                'result_type' => 'string',
                'description' => 'Code of the related accounting account.',
                'relation'    => ['account_id' => 'code'],
                'store'       => true,
                'readonly'    => true
            ],

            'is_expense' => [
                'type'        => 'computed',
                'result_type' => 'boolean',
                'description' => 'Whether the selected account is an expense account.',
                'function'    => 'calcIsExpense',
                'store'       => true,
                'instant'     => true,
                'readonly'    => true
            ],

            'is_income' => [
                'type'        => 'computed',
                'result_type' => 'boolean',
                'description' => 'Whether the selected account is an income account.',
                'function'    => 'calcIsIncome',
                'store'       => true,
                'instant'     => true,
                'readonly'    => true
            ]
        ];
    }

    public function getIndexes(): array {
        return [
            ['operation_id'],
            ['account_id']
        ];
    }

    public static function getActions() {
        return [
            'set_default_description' => [
                'description' => 'Copy the operation description when the line has none.',
                'policies'    => [],
                'function'    => 'doSetDefaultDescription'
            ]
        ];
    }

    protected static function calcIsExpense($self): array {
        $result = [];

        $self->read(['account_id' => ['account_class']]);
        foreach($self as $id => $line) {
            $result[$id] = isset($line['account_id']['account_class'])
                && $line['account_id']['account_class'] === '06';
        }

        return $result;
    }

    protected static function calcIsIncome($self): array {
        $result = [];

        $self->read(['account_id' => ['account_class']]);
        foreach($self as $id => $line) {
            $result[$id] = isset($line['account_id']['account_class'])
                && $line['account_id']['account_class'] === '07';
        }

        return $result;
    }

    protected static function doSetDefaultDescription($self) {
        $self->read(['description', 'operation_id' => ['description']]);
        foreach($self as $id => $line) {
            if(!$line['description'] && $line['operation_id']['description']) {
                self::id($id)->update([
                    'description' => $line['operation_id']['description']
                ]);
            }
        }
    }

    protected static function oncreate($self, $values, $lang) {
        $self->do('set_default_description');
    }

    public static function onchange($event, $values, $view) {
        $result = [];

        if(isset($event['vat_rate']) && $event['vat_rate'] >= 1) {
            $result['vat_rate'] = round($event['vat_rate'] / 100, 4);
        }
        if(isset($event['debit']) && (float) $event['debit'] > 0.0) {
            $result['credit'] = 0.0;
        }
        elseif(isset($event['credit']) && (float) $event['credit'] > 0.0) {
            $result['debit'] = 0.0;
        }

        return $result;
    }

    public static function cancreate($self, $values) {
        if(empty($values['operation_id'])) {
            return ['operation_id' => ['missing' => 'Accounting operation is required.']];
        }

        $operation = AccountingOperation::id($values['operation_id'])
            ->read(['status'])
            ->first();
        if(!$operation || $operation['status'] !== 'pending') {
            return ['operation_id' => ['non_editable' => 'Lines can only be added to a pending operation.']];
        }

        $error = self::validateAmounts(
            (float) ($values['debit'] ?? 0.0),
            (float) ($values['credit'] ?? 0.0)
        );
        if($error) {
            return ['debit' => [$error => 'Exactly one positive debit or credit amount is required.']];
        }

        return parent::cancreate($self, $values);
    }

    public static function canupdate($self, $values) {
        $self->read(['operation_id' => ['status'], 'debit', 'credit']);

        foreach($self as $line) {
            if($line['operation_id']['status'] !== 'pending') {
                return ['operation_id' => ['non_editable' => 'Lines can only be changed while their operation is pending.']];
            }

            $debit = (float) ($values['debit'] ?? $line['debit']);
            $credit = (float) ($values['credit'] ?? $line['credit']);
            $error = self::validateAmounts($debit, $credit);
            if($error) {
                return ['debit' => [$error => 'Exactly one positive debit or credit amount is required.']];
            }
        }

        return parent::canupdate($self, $values);
    }

    public static function candelete($self) {
        $self->read(['operation_id' => ['status']]);

        foreach($self as $line) {
            if($line['operation_id']['status'] !== 'pending') {
                return ['operation_id' => ['non_removable' => 'Lines can only be deleted while their operation is pending.']];
            }
        }

        return parent::candelete($self);
    }

    private static function validateAmounts(float $debit, float $credit): ?string {
        if($debit < 0.0 || $credit < 0.0) {
            return 'negative_amount';
        }
        if(($debit > 0.0 && $credit > 0.0) || ($debit === 0.0 && $credit === 0.0)) {
            return 'invalid_amount';
        }

        return null;
    }
}
