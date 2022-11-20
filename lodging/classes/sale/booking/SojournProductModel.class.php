<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;
use equal\orm\Model;

class SojournProductModel extends Model {

    public static function getName() {
        return "Product Model Group";
    }

    public static function getDescription() {
        return "Product model groups are created while selecting the services for a booking.\n
        Each object groups one or more assignement to a matching and available rental unit.\n";
    }

    public static function getColumns() {
        return [
            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'description'       => 'The booking the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'ondelete'          => 'cascade'         // delete assignment when parent booking is deleted
            ],

            'booking_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\BookingLineGroup',
                'description'       => 'Booking lines Group the assignment relates to.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'product_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\catalog\ProductModel',
                'description'       => "Product Model of this variant.",
                'required'          => true
            ],

            'rental_unit_assignments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\SojournProductModelRentalUnitAssignement',
                'foreign_field'     => 'sojourn_product_model_id',
                'description'       => 'Rental unit assigned to booking line.',
                'ondetach'          => 'delete',
                'onupdate'          => 'onupdateRentalUnitAssignmentsIds'
            ],

            'qty' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => 'Total persons assigned to this model.',
                'function'          => 'calcQty',
                'store'             => true
            ],

            'is_accomodation' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Flag marking the SPM as an accomodation (from targeted product).',
                'function'          => 'calcIsAccomodation',
                'store'             => true
            ]
        ];
    }

    public static function calcQty($om, $ids, $lang) {
        $result = [];
        $models = $om->read(self::getType(), $ids, ['rental_unit_assignments_ids.qty'], $lang);
        foreach($models as $oid => $model) {
            $result[$oid] = array_reduce($model['rental_unit_assignments_ids.qty'], function($c, $a) { return $a['qty'] + $c; }, 0);
        }
        return $result;
    }

    public static function calcIsAccomodation($om, $ids, $lang) {
        $result = [];
        $models = $om->read(self::getType(), $ids, ['product_model_id.is_accomodation'], $lang);
        foreach($models as $oid => $model) {
            $result[$oid] = $model['product_model_id.is_accomodation'];
        }
        return $result;
    }

    public static function onupdateRentalUnitAssignmentsIds($om, $ids, $values, $lang) {
        $om->update(self::getType(), $ids, ['qty' => null]);
    }

    public function getUnique() {
        return [
            ['booking_line_group_id', 'product_model_id']
        ];
    }

}