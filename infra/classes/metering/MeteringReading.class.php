<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\metering;

use equal\orm\Model;

class MeteringReading extends Model {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => 'Display name of the metering reading.',
                'function'          => 'calcName',
                'store'             => true
            ],

            'instance_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\server\Instance',
                'description'       => 'Customer instance for which the reading is generated.',
                'dependents'        => ['instance_name', 'name'],
                'required'          => true
            ],

            'instance_name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'relation'          => ['instance_id' => 'name'],
                'description'       => 'Human-readable name of the measured instance.',
                'store'             => true,
                'instant'           => true,
                'dependents'        => ['name']
            ],

            'measured_at' => [
                'type'              => 'datetime',
                'description'       => 'Date and time at which the reading was generated.',
                'default'           => fn() => time(),
                'required'          => true,
                'dependents'        => ['name']
            ],

            'period_start' => [
                'type'              => 'datetime',
                'description'       => 'Optional start of the measured period.'
            ],

            'period_end' => [
                'type'              => 'datetime',
                'description'       => 'Optional end of the measured period.'
            ],

            'status' => [
                'type'              => 'string',
                'usage'             => 'text/plain:32',
                'selection'         => [
                    'pending',
                    'running',
                    'done',
                    'failed'
                ],
                'description'       => 'Current status of the reading generation.',
                'default'           => 'pending',
                'required'          => true
            ],

            'metering_reading_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'infra\metering\MeteringReadingLine',
                'foreign_field'     => 'metering_reading_id',
                'description'       => 'Lines generated for this reading.'
            ],

            'logs' => [
                'type'              => 'string',
                'usage'             => 'text/json',
                'description'       => 'Optional JSON encoded technical details or generation errors.'
            ]

        ];
    }

    protected static function calcName($self) {
        $result = [];

        $self->read(['instance_name', 'measured_at']);
        foreach($self as $id => $reading) {
            $instance_name = $reading['instance_name'] ?: 'Instance';
            $measured_at = $reading['measured_at'] ? date('Y-m-d H:i', $reading['measured_at']) : '';

            $result[$id] = trim($instance_name . ' - ' . $measured_at, ' -');
        }

        return $result;
    }
}
