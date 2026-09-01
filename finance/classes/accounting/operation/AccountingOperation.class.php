<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace finance\accounting\operation;

use equal\orm\Model;
use finance\accounting\AccountingEntry;
use finance\accounting\AccountingEntryLine;
use symbiose\setting\Setting;

/**
 * Generic logical accounting document.
 *
 * An accounting operation owns the common lifecycle of accounting documents.
 * Specialized business documents can extend this model and share its table.
 */
class AccountingOperation extends Model {

    public static function getName() {
        return 'Accounting operation';
    }

    public static function getDescription() {
        return 'Logical accounting document responsible for one or more accounting entries.';
    }

    public static function getColumns() {
        return [

            'org_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'identity\Organisation',
                'description'    => 'Organisation the accounting operation belongs to.',
                'required'       => true,
                'default'        => 1,
                'dependents'     => ['fiscal_year']
            ],

            'description' => [
                'type'        => 'string',
                'usage'       => 'text/plain',
                'description' => 'Description of the accounting operation.',
                'required'    => true,
                'dependents'  => ['name']
            ],

            'operation_type' => [
                'type'        => 'string',
                'description' => 'Semantic type of the accounting operation.',
                'selection'   => [
                    'misc',
                    'sale_invoice',
                    'purchase_invoice'
                ],
                'default'     => 'misc',
                'required'    => true,
                'readonly'    => true
            ],

            'posting_date' => [
                'type'        => 'date',
                'usage'       => 'date/plain',
                'description' => 'Date at which the operation is accounted.',
                'required'    => true,
                'default'     => function() {
                    return time();
                },
                'dependents'  => ['fiscal_year']
            ],

            'operation_number' => [
                'type'        => 'string',
                'usage'       => 'text/plain:64',
                'description' => 'Number assigned when the operation is posted.',
                'readonly'    => true,
                'dependents'  => ['name']
            ],

            'posted_at' => [
                'type'        => 'datetime',
                'description' => 'Date and time at which the operation was posted.',
                'readonly'    => true
            ],

            'cancelled_at' => [
                'type'        => 'datetime',
                'description' => 'Date and time at which the operation was cancelled.',
                'readonly'    => true
            ],

            'journal_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'finance\accounting\AccountingJournal',
                'description'    => 'Accounting journal used for the operation.',
                'required'       => true,
                'domain'         => [
                    ['organisation_id', '=', 'object.org_id']
                ]
            ],

            'reversal_of_id' => [
                'type'           => 'many2one',
                'foreign_object' => 'finance\accounting\operation\AccountingOperation',
                'description'    => 'Posted operation reversed by this operation.',
                'readonly'       => true
            ],

            'reversal_operations_ids' => [
                'type'           => 'one2many',
                'foreign_object' => 'finance\accounting\operation\AccountingOperation',
                'foreign_field'  => 'reversal_of_id',
                'description'    => 'Operations created to reverse this operation.',
                'readonly'       => true,
                'order'          => 'posting_date',
                'sort'           => 'desc'
            ],

            'operation_lines_ids' => [
                'type'           => 'one2many',
                'foreign_object' => 'finance\accounting\operation\AccountingOperationLine',
                'foreign_field'  => 'operation_id',
                'description'    => 'Lines used to generate the accounting entries.',
                'ondetach'       => 'delete',
                'dependents'     => ['is_balanced']
            ],

            'accounting_entries_ids' => [
                'type'           => 'one2many',
                'foreign_object' => 'finance\accounting\AccountingEntry',
                'foreign_field'  => 'origin_object_id',
                'domain'         => [
                    'origin_object_class',
                    '=',
                    'finance\accounting\operation\AccountingOperation'
                ],
                'description'    => 'Accounting entries generated from the operation.',
                'dependents'     => ['is_balanced']
            ],

            'fiscal_year' => [
                'type'        => 'computed',
                'result_type' => 'string',
                'usage'       => 'text/plain:16',
                'description' => 'Fiscal year in which the operation is posted.',
                'function'    => 'calcFiscalYear',
                'store'       => true,
                'instant'     => true,
                'readonly'    => true
            ],

            'name' => [
                'type'        => 'computed',
                'result_type' => 'string',
                'description' => 'Display name of the accounting operation.',
                'function'    => 'calcName',
                'store'       => true,
                'readonly'    => true
            ],

            'is_balanced' => [
                'type'        => 'computed',
                'result_type' => 'boolean',
                'description' => 'Whether all generated accounting entries are balanced.',
                'function'    => 'calcIsBalanced',
                'readonly'    => true
            ],

            'status' => [
                'type'        => 'string',
                'description' => 'Current lifecycle status of the accounting operation.',
                'selection'   => [
                    'pending',
                    'proforma',
                    'posted',
                    'cancelled'
                ],
                'default'     => 'pending',
                'required'    => true,
                'dependents'  => ['name']
            ]

        ];
    }

    public function getIndexes(): array {
        return [
            ['org_id', 'status'],
            ['journal_id', 'posting_date'],
            ['operation_type', 'status']
        ];
    }

    public function getUnique() {
        return [
            ['org_id', 'journal_id', 'fiscal_year', 'operation_number']
        ];
    }

    public static function getPolicies(): array {
        return [
            'is-valid' => [
                'description' => 'Check whether the accounting operation is complete.',
                'function'    => 'policyIsValid'
            ],
            'can-post' => [
                'description' => 'Check whether the accounting operation can be posted.',
                'function'    => 'policyCanPost'
            ],
            'can-cancel' => [
                'description' => 'Check whether the posted accounting operation can be cancelled.',
                'function'    => 'policyCanCancel'
            ]
        ];
    }

    public static function getActions() {
        return [
            'generate_accounting_entries' => [
                'description' => 'Generate or verify the accounting entries of the operation.',
                'policies'    => [],
                'function'    => 'doGenerateAccountingEntries'
            ],
            'validate_accounting_entries' => [
                'description' => 'Validate the accounting entries of the operation.',
                'policies'    => [],
                'function'    => 'doValidateAccountingEntries'
            ],
            'assign_operation_number' => [
                'description' => 'Assign the definitive accounting operation number.',
                'policies'    => [],
                'function'    => 'doAssignOperationNumber'
            ],
            'reverse' => [
                'description' => 'Create and post an operation reversing this operation.',
                'policies'    => ['can-cancel'],
                'function'    => 'doReverse'
            ]
        ];
    }

    public static function getWorkflow() {
        return [
            'pending' => [
                'description' => 'Operation waiting to be completed and reviewed.',
                'icon'        => 'pending',
                'transitions' => [
                    'submit' => [
                        'description' => 'Mark the operation as complete and ready for posting.',
                        'policies'    => ['is-valid'],
                        'status'      => 'proforma'
                    ],
                    'cancel' => [
                        'description' => 'Cancel the pending operation while preserving its history.',
                        'onbefore'    => 'onbeforeCancel',
                        'status'      => 'cancelled'
                    ]
                ]
            ],
            'proforma' => [
                'description' => 'Completed operation ready for accounting treatment.',
                'icon'        => 'hourglass_top',
                'transitions' => [
                    'post' => [
                        'description' => 'Post the operation and validate its accounting entries.',
                        'policies'    => [
                            'is-valid',
                            'can-post'
                        ],
                        'onbefore' => 'onbeforePost',
                        'status'   => 'posted'
                    ],
                    'revert' => [
                        'description' => 'Return the operation to the pending state.',
                        'status'      => 'pending'
                    ],
                    'cancel' => [
                        'description' => 'Cancel the unposted operation while preserving its history.',
                        'onbefore'    => 'onbeforeCancel',
                        'status'      => 'cancelled'
                    ]
                ]
            ],
            'posted' => [
                'description' => 'Operation posted to accounting.',
                'icon'        => 'receipt_long',
                'transitions' => [
                    'cancel' => [
                        'description' => 'Reverse and cancel the posted operation.',
                        'policies'    => ['can-cancel'],
                        'onbefore'    => 'onbeforeCancelPosted',
                        'status'      => 'cancelled'
                    ]
                ]
            ],
            'cancelled' => [
                'description' => 'Cancelled accounting operation kept for audit purposes.',
                'icon'        => 'cancel',
                'transitions' => []
            ]
        ];
    }

    protected static function calcFiscalYear($self): array {
        $result = [];

        $self->read(['org_id', 'posting_date']);
        foreach($self as $id => $operation) {
            $default_year = date('Y', $operation['posting_date'] ?? time());
            $result[$id] = (string) Setting::get_value(
                'finance',
                'accounting',
                'fiscal_year',
                $default_year,
                ['organisation_id' => $operation['org_id']]
            );
        }

        return $result;
    }

    protected static function calcName($self): array {
        $result = [];

        $self->read(['operation_number', 'description']);
        foreach($self as $id => $operation) {
            $result[$id] = $operation['operation_number'] ?: $operation['description'];
        }

        return $result;
    }

    protected static function calcIsBalanced($self): array {
        $result = [];

        $self->read(['operation_lines_ids' => ['debit', 'credit']]);
        foreach($self as $id => $operation) {
            $debit = 0.0;
            $credit = 0.0;

            foreach($operation['operation_lines_ids'] as $line) {
                $debit += (float) $line['debit'];
                $credit += (float) $line['credit'];
            }

            $result[$id] = count($operation['operation_lines_ids']) > 0
                && abs($debit - $credit) < 0.0001
                && round($debit, 4) !== 0.0;
        }

        return $result;
    }

    protected static function policyIsValid($self): array {
        $result = [];

        $self->read([
            'description',
            'org_id',
            'posting_date',
            'journal_id' => ['organisation_id'],
            'operation_lines_ids' => [
                'account_id',
                'debit',
                'credit'
            ]
        ]);

        foreach($self as $id => $operation) {
            $errors = [];

            if(!$operation['description']) {
                $errors['missing_description'] = 'Description is required.';
            }
            if(!$operation['org_id']) {
                $errors['missing_organisation'] = 'Organisation is required.';
            }
            if(!$operation['posting_date']) {
                $errors['missing_posting_date'] = 'Posting date is required.';
            }
            if(!$operation['journal_id']) {
                $errors['missing_journal'] = 'Accounting journal is required.';
            }
            elseif($operation['journal_id']['organisation_id'] !== $operation['org_id']) {
                $errors['invalid_journal'] = 'Accounting journal belongs to another organisation.';
            }

            if(count($operation['operation_lines_ids']) === 0) {
                $errors['missing_operation_lines'] = 'At least one accounting operation line is required.';
            }
            else {
                $debit = 0.0;
                $credit = 0.0;

                foreach($operation['operation_lines_ids'] as $line) {
                    if(!$line['account_id']) {
                        $errors['missing_account'] = 'Every accounting operation line requires an account.';
                    }
                    $debit += (float) $line['debit'];
                    $credit += (float) $line['credit'];
                }

                if(abs($debit - $credit) >= 0.0001 || round($debit, 4) === 0.0) {
                    $errors['unbalanced_operation'] = 'Accounting operation lines must be balanced.';
                }
            }

            if($errors) {
                $result[$id] = $errors;
            }
        }

        return $result;
    }

    protected static function policyCanPost($self): array {
        $result = [];

        $self->read(['status']);
        foreach($self as $id => $operation) {
            if($operation['status'] !== 'proforma') {
                $result[$id] = [
                    'invalid_status' => 'Only a proforma accounting operation can be posted.'
                ];
            }
        }

        return $result;
    }

    protected static function policyCanCancel($self): array {
        $result = [];

        $self->read(['status', 'reversal_operations_ids']);
        foreach($self as $id => $operation) {
            if($operation['status'] !== 'posted') {
                $result[$id] = [
                    'invalid_status' => 'Only a posted accounting operation can be reversed.'
                ];
            }
            elseif(count($operation['reversal_operations_ids']) > 0) {
                $result[$id] = [
                    'already_reversed' => 'Accounting operation has already been reversed.'
                ];
            }
        }

        return $result;
    }

    protected static function doGenerateAccountingEntries($self) {
        $self->read([
            'journal_id',
            'accounting_entries_ids',
            'operation_lines_ids' => [
                'description',
                'account_id',
                'debit',
                'credit'
            ]
        ]);

        foreach($self as $id => $operation) {
            if(count($operation['accounting_entries_ids']) > 0) {
                continue;
            }
            if(count($operation['operation_lines_ids']) === 0) {
                throw new \Exception('missing_operation_lines', EQ_ERROR_INVALID_PARAM);
            }

            $entry = AccountingEntry::create([
                    'journal_id'          => $operation['journal_id'],
                    'origin_object_class' => static::getType(),
                    'origin_object_id'    => $id,
                    'status'              => 'pending'
                ])
                ->read(['id'])
                ->first();

            foreach($operation['operation_lines_ids'] as $line) {
                AccountingEntryLine::create([
                    'name'                => $line['description'],
                    'accounting_entry_id' => $entry['id'],
                    'account_id'          => $line['account_id'],
                    'debit'               => $line['debit'],
                    'credit'              => $line['credit']
                ]);
            }
        }
    }

    protected static function doValidateAccountingEntries($self) {
        $self->read([
            'accounting_entries_ids' => [
                'id',
                'status',
                'is_balanced',
                'entry_lines_ids'
            ]
        ]);

        foreach($self as $operation) {
            foreach($operation['accounting_entries_ids'] as $entry) {
                if(count($entry['entry_lines_ids']) === 0 || !$entry['is_balanced']) {
                    throw new \Exception('unbalanced_accounting_entry', EQ_ERROR_INVALID_PARAM);
                }
                if($entry['status'] === 'cancelled') {
                    throw new \Exception('cancelled_accounting_entry', EQ_ERROR_INVALID_PARAM);
                }

                AccountingEntry::id($entry['id'])
                    ->update(['status' => 'validated']);
            }
        }
    }

    protected static function doAssignOperationNumber($self) {
        $self->read([
            'operation_number',
            'org_id',
            'fiscal_year',
            'journal_id' => ['code']
        ]);

        foreach($self as $id => $operation) {
            if($operation['operation_number']) {
                continue;
            }

            $format = Setting::get_value(
                'finance',
                'accounting',
                'accounting_operation.number_format',
                '%s{journal}/%02d{year}/%05d{sequence}',
                ['organisation_id' => $operation['org_id']]
            );
            $sequence = Setting::fetch_and_add(
                'finance',
                'accounting',
                'accounting_operation.sequence',
                1,
                ['organisation_id' => $operation['org_id']]
            );

            if(!$sequence) {
                throw new \Exception('unable_to_assign_operation_number', EQ_ERROR_INVALID_CONFIG);
            }

            $operation_number = Setting::parse_format($format, [
                'year'     => $operation['fiscal_year'],
                'journal'  => $operation['journal_id']['code'],
                'org'      => $operation['org_id'],
                'sequence' => $sequence
            ]);

            self::id($id)->update(['operation_number' => $operation_number]);
        }
    }

    protected static function doReverse($self) {
        $self->read([
            'id',
            'name',
            'operation_type',
            'org_id',
            'journal_id',
            'operation_lines_ids' => [
                'description',
                'account_id',
                'debit',
                'credit',
                'vat_rate'
            ]
        ]);

        foreach($self as $operation) {
            $reversal = self::create([
                    'description'     => 'Reversal of ' . $operation['name'],
                    'operation_type'  => $operation['operation_type'],
                    'org_id'           => $operation['org_id'],
                    'journal_id'      => $operation['journal_id'],
                    'posting_date'    => time(),
                    'status'          => 'pending',
                    'reversal_of_id'  => $operation['id']
                ])
                ->read(['id'])
                ->first();

            foreach($operation['operation_lines_ids'] as $line) {
                AccountingOperationLine::create([
                    'operation_id' => $reversal['id'],
                    'description'  => $line['description'],
                    'account_id'   => $line['account_id'],
                    'debit'        => $line['credit'],
                    'credit'       => $line['debit'],
                    'vat_rate'     => $line['vat_rate']
                ]);
            }

            self::id($reversal['id'])->transition('submit');
            self::id($reversal['id'])->transition('post');
        }
    }

    protected static function onbeforePost($self) {
        $self->do('generate_accounting_entries');
        $self->do('validate_accounting_entries');
        $self->do('assign_operation_number');
        $self->update(['posted_at' => time()]);
    }

    protected static function onbeforeCancel($self) {
        $self->update(['cancelled_at' => time()]);
    }

    protected static function onbeforeCancelPosted($self) {
        $self->do('reverse');
        $self->update(['cancelled_at' => time()]);
    }

    public static function canupdate($self, $values) {
        $self->read(['status']);

        $technical_fields = [
            'status',
            'name',
            'operation_number',
            'posted_at',
            'cancelled_at',
            'reversal_of_id'
        ];

        foreach($self as $id => $operation) {
            if(
                $operation['status'] !== 'pending'
                && count(array_diff(array_keys($values), $technical_fields)) > 0
            ) {
                return [
                    'status' => [
                        'non_editable' => "Accounting operation {$id} can only be edited while pending."
                    ]
                ];
            }
        }

        return parent::canupdate($self, $values);
    }

    public static function candelete($self) {
        $self->read(['status']);

        foreach($self as $operation) {
            if($operation['status'] !== 'pending') {
                return [
                    'status' => [
                        'non_removable' => 'Only pending accounting operations can be deleted.'
                    ]
                ];
            }
        }

        return parent::candelete($self);
    }
}
