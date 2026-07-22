<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\server;

use equal\data\DataGenerator;
use equal\orm\Model;

class Instance extends Model {

    public static function getDescription() {
        return 'Instance manages service or product instances, detailing type, version, URL, access information, and running software.';
    }

    public static function getColumns() {

        return [

            'name'    => [
                'type'              => 'string',
                'unique'            => true,
                'required'          => true,
                'description'       => 'Unique identifier of the instance.',
                'help'              => 'Fully qualified domain name (FQDN). Example: test.be.'
            ],

            'uuid' => [
                'type'              => 'string',
                'usage'             => 'text/plain:36',
                'unique'            => true,
                'description'       => 'Unique identifier from the Master instance.'
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => 'Short description of the instance.'
            ],

            'instance_type' => [
                'type'              => 'string',
                'selection'         => ['dev', 'staging', 'prod', 'replica'],
                'description'       => 'Type of instance.',
                'default'           => 'dev'
            ],

            'has_dns_record' => [
                'type'              => 'boolean',
                'description'       => 'Marks the instance as having or not DNS record.',
                'default'           => false
            ],

            'version' => [
                'type'              => 'string',
                'selection'         => ['development', 'staging', 'preview', 'production'],
                'description'       => 'Branch version.',
                'default'           => 'development'
            ],

            'branch' => [
                'type'              => 'string',
                'description'       => 'Branch used by the instance.'
            ],

            'url' => [
                'type'              => 'string',
                'usage'             => 'uri/url',
                'description'       => 'Front-end home URL.'
            ],

            'last_synced' => [
                'type'              => 'datetime',
                'description'       => 'Date of last automatic status update.',
                'help'              => 'The "up" field can be auto updated by the action "infra_server_fetch-status".'
            ],

            'up' => [
                'type'              => 'boolean',
                'description'       => 'Is the instance currently up, is set according to the last infra\server\Status retrieval.',
                'default'           => false
            ],

            'send_alerts' => [
                'type'              => 'boolean',
                'description'       => "Are monitoring alerts sent for that instance.",
                'default'           => true
            ],

            'product_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'inventory\Product',
                'description'       => 'Product the instance belongs to.',
                'required'          => true
            ],

            'server_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\server\Server',
                'description'       => 'Server on which the instance runs.',
                'ondelete'          => 'cascade',
                'required'          => true
            ],

            'accesses_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'infra\Access',
                'foreign_field'     => 'instance_id',
                'description'       => 'Information about how to access the instance.'
            ],

            'softwares_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\Software',
                'foreign_field'     => 'instance_id',
                'description'       => 'Information about the list of  software running on the instance.'
            ],

            'statuses_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'infra\server\Status',
                'foreign_field'     => 'instance_id',
                'description'       => 'Statuses of the instance.'
            ]

        ];
    }

    protected static function oncreate($self, $orm) {
        $self->read(['instance_type']);
        foreach($self as $id => $instance) {
            // generate a new UUID
            do {
                $uuid = DataGenerator::uuid();
                $existing = $orm->search(static::class, ['uuid', '=', $uuid]);
            } while( $existing > 0 && count($existing) > 0 );

            $orm->update(static::class, $id, ['uuid' => $uuid]);
        }
    }
}
