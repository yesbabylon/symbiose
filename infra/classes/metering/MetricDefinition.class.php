<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\metering;

use equal\orm\Model;

class MetricDefinition extends Model {

    public static function getColumns() {
        return [

            'code' => [
                'type'              => 'string',
                'usage'             => 'text/plain:128',
                'description'       => 'Unique technical code of the metric.',
                'required'          => true
            ],

            'name' => [
                'type'              => 'string',
                'description'       => 'Human-readable label of the metric.',
                'required'          => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Functional description of what is measured.'
            ],

            'category' => [
                'type'              => 'string',
                'usage'             => 'text/plain:64',
                'selection'         => [
                    'fmt',
                    'edms',
                    'google_doc_ai',
                    'auth',
                    'mail',
                    'database'
                ],
                'description'       => 'Functional category of the metric.',
                'required'          => true
            ],

            'unit' => [
                'type'              => 'string',
                'usage'             => 'text/plain:32',
                'selection'         => [
                    'count',
                    'bytes',
                    'calls',
                    'users',
                    'emails'
                ],
                'description'       => 'Unit used by the metric.',
                'required'          => true
            ],

            'value_type' => [
                'type'              => 'string',
                'usage'             => 'text/plain:32',
                'selection'         => [
                    'integer',
                    'decimal',
                    'string'
                ],
                'description'       => 'Expected type of value returned by the collector.',
                'default'           => 'integer',
                'required'          => true
            ],

            'collector' => [
                'type'              => 'string',
                'usage'             => 'text/plain:128',
                'description'       => 'Controller used to collect the metric value.',
                'required'          => true
            ],

            'is_active' => [
                'type'              => 'boolean',
                'description'       => 'Indicates whether this metric should be collected.',
                'default'           => true
            ]

        ];
    }

    public function getUnique() {
        return [
            ['code']
        ];
    }
}
