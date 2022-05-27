<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;


class Repairing extends \sale\booking\Repairing {


    public static function getColumns() {
        return [

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\Center',
                'description'       => 'The center the repairing relates to.',
                'required'          => true,
                'ondelete'          => 'cascade'         // delete repairing when parent center is deleted
            ],

            'repairs_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\Repair',
                'foreign_field'     => 'repairing_id',
                'description'       => 'Consumptions related to the booking.',
                'ondetach'          => 'delete'
            ],

            'rental_units_ids' => [
                'type'              => 'many2many',
                'foreign_object'    => 'lodging\realestate\RentalUnit',
                'foreign_field'     => 'repairings_ids',
                'rel_table'         => 'sale_rel_repairing_rentalunit',
                'rel_foreign_key'   => 'rental_unit_id',
                'rel_local_key'     => 'repairing_id',
                'description'       => 'List of rental units assigned to the scheduled repairing.',
                'onupdate'          => 'onupdateRentalUnitsIds'
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

    public static function onupdateRentalUnitsIds($om, $oids, $values, $lang) {
        $om->call(__CLASS__, '_updateRepairs', $oids, [], $lang);
    }

    public static function onupdateDateFrom($om, $oids, $values, $lang) {
        $om->call(__CLASS__, '_updateRepairs', $oids, [], $lang);
    }

    public static function onupdateDateTo($om, $oids, $values, $lang) {
        $om->call(__CLASS__, '_updateRepairs', $oids, [], $lang);
    }

    public static function _updateRepairs($om, $oids, $values, $lang) {
        // generate consumptions
        $repairings = $om->read(__CLASS__, $oids, ['repairs_ids', 'center_id', 'date_from', 'date_to', 'rental_units_ids'], $lang);
        if($repairings > 0) {

            foreach($repairings as $oid => $odata) {

                // remove existing repairs
                $repairs_ids = array_map(function($a) { return "-$a";}, $odata['repairs_ids']);
                $om->write(__CLASS__, $oid, ['repairs_ids' => $repairs_ids]);

                $nb_days = floor( ($odata['date_to'] - $odata['date_from']) / (60*60*24) ) + 1;
                list($day, $month, $year) = [ date('j', $odata['date_from']), date('n', $odata['date_from']), date('Y', $odata['date_from']) ];
                for($i = 0; $i < $nb_days; ++$i) {
                    $c_date = mktime(0, 0, 0, $month, $day+$i, $year);
                    foreach($odata['rental_units_ids'] as $rental_unit_id) {
                        $fields = [
                            'repairing_id'          => $oid,
                            'center_id'             => $odata['center_id'],
                            'date'                  => $c_date,
                            'rental_unit_id'        => $rental_unit_id
                        ];
                        $om->create('lodging\sale\booking\Repair', $fields, $lang);
                    }
                }
            }
        }
    }


}