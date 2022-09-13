<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;
use equal\orm\Model;

class SojournProductModelRentalUnitAssignement extends Model {

    public static function getName() {
        return "Rental Unit Assignement";
    }

    public static function getDescription() {
        return "Assignements are created while selecting the services for a booking.\n
        Each product line that targets a product model that is used to assign one or morea rental unit, based on capacity and capacity.\n";
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

            'sojourn_product_model_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\SojournProductModel',
                'description'       => "Product Model group of the assignment.",
                'ondelete'          => 'cascade',
                'required'          => true
            ],

            'qty' => [
                'type'              => 'integer',
                'description'       => 'Number of persons assigned to the rental unit for related booking line.',
                'default'           => 1
            ],

            'rental_unit_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\realestate\RentalUnit',
                'description'       => 'Rental unit assigned to booking line.',
                'ondelete'          => 'null',
                'onupdate'          => 'onupdateRentalUnitId'
            ],

            'is_accomodation' => [
                'type'              => 'computed',
                'result_type'       => 'boolean',
                'description'       => 'Total persons assigned to this model.',
                'function'          => 'calcIsAccomodation',
                'store'             => true
            ]

        ];
    }

    public static function calcIsAccomodation($om, $ids, $lang) {
        $result = [];
        $assignments = $om->read(self::getType(), $ids, ['rental_unit_id.is_accomodation'], $lang);
        foreach($assignments as $oid => $assignment) {
            $result[$oid] = $assignment['rental_unit_id.is_accomodation'];
        }
        return $result;
    }

    public static function onupdateRentalUnitId($om, $oids, $values, $lang) {
        $om->update(self::getType(), $oids, ['is_accomodation' => null], $lang);
    }

    public function getUnique() {
        return [
            ['sojourn_product_model_id', 'rental_unit_id']
        ];
    }

    /**
     * Check wether an object can be updated, and perform some additional operations if necessary.
     * This method can be overriden to define a more precise set of tests.
     * It prevents updating if the parent booking is not in quote.
     *
     * @param  object   $om         ObjectManager instance.
     * @param  array    $oids       List of objects identifiers.
     * @param  array    $values     Associative array holding the new values to be assigned.
     * @param  string   $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang=DEFAULT_LANG) {
        $lines = $om->read(get_called_class(), $oids, ['booking_id.status'], $lang);
        if($lines > 0) {
            foreach($lines as $line) {
                if(!in_array($line['booking_id.status'], ['quote', 'checkedout'])) {
                    return ['booking_id' => ['non_editable' => 'Rental units assignments cannot be updated for non-quote bookings.']];
                }
            }
        }

        return parent::canupdate($om, $oids, $values, $lang);
    }

}