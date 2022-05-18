<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;
use equal\orm\Model;
use equal\html\HTMLToText;

class Repairing extends Model {


    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "Excerpt of the description to serve as reference.",
                'function'          => 'calcName',
                'store'             => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => "Reason of the repairing, for internal use.",
                'default'           => '',
                'onupdate'          => 'onupdateDescription'
            ],

            'repairs_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Repair',
                'foreign_field'     => 'repairing_id',
                'description'       => 'Consumptions related to the booking.'
            ],

            'rental_units_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'realestate\RentalUnit',
                'foreign_field'     => 'repairings_ids',
                'rel_table'         => 'sale_rel_repairing_rentalunit',
                'rel_foreign_key'   => 'rental_unit_id',
                'rel_local_key'     => 'repairing_id',
                'description'       => 'List of rental units assigned to the scheduled repairing.'
            ],

            'date_from' => [
                'type'              => 'date',
                'description'       => "",
                'default'           => time(),
                'onupdate'          => 'onupdateDateFrom'
            ],

            'date_to' => [
                'type'              => 'date',
                'description'       => "",
                'default'           => time(),
                'onupdate'          => 'onupdateDateTo'                
            ]

        ];
    }

    public static function calcName($om, $oids, $lang) {
        $result = [];
        $repairings = $om->read(__CLASS__, $oids, ['description'], $lang);
        if($repairings > 0) {
            foreach($repairings as $oid => $odata) {
                $result[$oid] = substr(HTMLToText::convert($odata['description']), 0, 25);
            }
        }
        return $result;
    }

    public static function onupdateDescription($om, $oids, $lang) {
        $om->write(get_called_class(), $oids, ['name' => null]);
        $om->read(get_called_class(), $oids, ['name']);
    }

    public static function onupdateDateFrom($om, $oids, $lang) {

    }

    public static function onupdateDateTo($om, $oids, $lang) {

    }

}