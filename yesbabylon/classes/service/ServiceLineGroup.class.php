<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace yesbabylon\service;
use equal\orm\Model;

class ServiceLineGroup extends \sale\contract\ContractLineGroup {

    public static function getColumns() {

        return [

            'service_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'yesbabylon\service\Service',
                'description'       => 'The service the line relates to.',
            ],

            'service_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'yesbabylon\service\ServiceLine',
                'foreign_field'     => 'service_line_group_id',
                'description'       => 'Service lines that belong to the service.',
                'ondetach'          => 'delete'
            ]

        ];
    }

}