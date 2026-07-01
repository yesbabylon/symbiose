<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\metering;

use equal\orm\Model;

class MeteringReadingLine extends Model {

    public static function getColumns() {
        return [

            'metering_reading_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\metering\MeteringReading',
                'description'       => 'Parent metering reading.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'metric_definition_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\metering\MetricDefinition',
                'description'       => 'Metric definition measured by this line.',
                'required'          => true,
                'dependents'        => ['name', 'metric_code', 'metric_name', 'unit']
            ],

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'relation'          => ['metric_definition_id' => 'name'],
                'description'       => 'Display name of the metering reading line.',
                'store'             => true
            ],

            'metric_code' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'relation'          => ['metric_definition_id' => 'code'],
                'description'       => 'Metric code copied for easier export.',
                'store'             => true,
                'instant'           => true
            ],

            'metric_name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'relation'          => ['metric_definition_id' => 'name'],
                'description'       => 'Metric label copied for easier export.',
                'store'             => true,
                'instant'           => true
            ],

            'value' => [
                'type'              => 'string',
                'usage'             => 'text/plain:128',
                'description'       => 'Measured value stored as text.'
            ],

            'unit' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'relation'          => ['metric_definition_id' => 'unit'],
                'description'       => 'Unit copied from the metric definition.',
                'store'             => true,
                'instant'           => true
            ],

            'details' => [
                'type'              => 'string',
                'usage'             => 'text/json',
                'description'       => 'Optional JSON encoded raw details returned by the collector.'
            ],

            'status' => [
                'type'              => 'string',
                'usage'             => 'text/plain:32',
                'selection'         => [
                    'done',
                    'failed',
                    'skipped'
                ],
                'description'       => 'Status of this specific metric collection.',
                'default'           => 'done',
                'required'          => true
            ],

            'error' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Optional error message if the collection failed.',
                'visible'           => ['status', '=', 'failed']
            ]

        ];
    }

    public function getUnique() {
        return [
            ['metering_reading_id', 'metric_definition_id']
        ];
    }
}
