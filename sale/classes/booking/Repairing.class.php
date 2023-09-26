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

    public static function getDescription() {
        return "Repairings are episodes of repairs and maintenance impacting one or more rental units of a given Center.";
    }

    public static function getLink() {
        return "/booking/#/repairings/repairing/object.id";
    }

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
            ],

            // time fields are based on dates from repairs (consumptions)
            'time_from' => [
                'type'              => 'computed',
                'result_type'       => 'time',
                'function'          => 'calcTimeFrom',
                'store'             => true
            ],

            'time_to' => [
                'type'              => 'computed',
                'result_type'       => 'time',
                'function'          => 'calcTimeTo',
                'store'             => true
            ]

        ];
    }

    public static function calcName($om, $oids, $lang) {
        $result = [];
        $repairings = $om->read(self::getType(), $oids, ['description'], $lang);
        if($repairings > 0) {
            foreach($repairings as $oid => $odata) {
                $result[$oid] = mb_substr(strip_tags($odata['description']), 0, 25);
            }
        }
        return $result;
    }

    public static function calcTimeFrom($om, $oids, $lang) {
        $result = [];
        $repairings = $om->read(self::getType(), $oids, ['repairs_ids']);
        if($repairings > 0) {
            foreach($repairings as $oid => $repairing) {
                $min_date = PHP_INT_MAX;
                $time_from = 0;
                $repairs = $om->read(Repair::getType(), $repairing['repairs_ids'], ['date', 'schedule_from']);
                if($repairs > 0 && count($repairs)) {
                    foreach($repairs as $rid => $repair) {
                        if($repair['date'] < $min_date) {
                            $min_date = $repair['date'];
                            $time_from = $repair['schedule_from'];
                        }
                    }
                    $result[$oid] = $time_from;
                }
            }
        }
        return $result;
    }

    public static function calcTimeTo($om, $oids, $lang) {
        $result = [];
        $repairings = $om->read(self::getType(), $oids, ['repairs_ids']);
        if($repairings > 0) {
            foreach($repairings as $oid => $repairing) {
                $max_date = 0;
                $time_to = 0;
                $repairs = $om->read(Repair::getType(), $repairing['repairs_ids'], ['date', 'schedule_to']);
                if($repairs > 0 && count($repairs)) {
                    foreach($repairs as $rid => $repair) {
                        if($repair['date'] > $max_date) {
                            $max_date = $repair['date'];
                            $time_to = $repair['schedule_to'];
                        }
                    }
                    $result[$oid] = $time_to;
                }
            }
        }
        return $result;
    }

    public static function onupdateDescription($om, $oids, $values, $lang) {
        $om->update(self::getType(), $oids, ['name' => null]);
        $om->read(self::getType(), $oids, ['name']);
    }

    public static function onupdateDateFrom($om, $oids, $values, $lang) {

    }

    public static function onupdateDateTo($om, $oids, $values, $lang) {

    }

}