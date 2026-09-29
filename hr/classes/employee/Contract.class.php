<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace hr\employee;

use equal\orm\Model;

class Contract extends Model {

    public static function getColumns(): array {
        return [

            'employee_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'hr\employee\Employee',
                'description'       => 'The employee the contract relates to.',
                'required'          => true
            ],

            'date_start' => [
                'type'              => 'date',
                'description'       => 'Date of the first day of work.',
                'required'          => true,
                'dependents'        => ['employee_id' => ['date_start']]
            ],

            'date_end' => [
                'type'              => 'date',
                'description'       => 'Date of the last day of work.',
                'help'              => 'Date at which the contract ends (known in advance for fixed-term or unknown for permanent).',
                'dependents'        => ['employee_id' => ['date_end']]
            ]

        ];
    }

    public static function canupdate($self, $values): array {
        $self->read(['employee_id', 'date_start', 'date_end']);

        foreach($self as $id => $contract) {
            $employee_id = array_key_exists('employee_id', $values) ? $values['employee_id'] : $contract['employee_id'];
            $date_start = array_key_exists('date_start', $values) ? $values['date_start'] : $contract['date_start'];
            $date_end = array_key_exists('date_end', $values) ? $values['date_end'] : $contract['date_end'];

            $other_contracts = self::search([
                ['employee_id', '=', $employee_id],
                ['id', '<>', $id]
            ])
                ->read(['date_start', 'date_end']);

            foreach($other_contracts as $other_contract) {
                $starts_before_end = is_null($date_end) || $other_contract['date_start'] <= $date_end;
                $ends_after_start = is_null($other_contract['date_end']) || $other_contract['date_end'] >= $date_start;

                if($starts_before_end && $ends_after_start) {
                    return [
                        'date_start' => [
                            'overlapping_contract' => 'The contract period overlaps another contract for this employee.'
                        ]
                    ];
                }
            }
        }

        return parent::canupdate($self, $values);
    }
}
