<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;
use equal\orm\Model;

class BookingLineGroupAgeRangeAssignment extends Model {

    public static function getName() {
        return "Age Range Assignment";
    }

    /*
        Assignments are created while selecting the hosts details/composition for a booking group.
        Each group is assigned to one or more age ranges.
    */

    public static function getColumns() {
        return [

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\Booking',
                'description'       => 'The booking the line relates to (for consistency, lines should be accessed using the group they belong to).',
                'ondelete'          => 'cascade'
            ],

            'booking_line_group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\BookingLineGroup',
                'description'       => 'Booking lines Group the assignment relates to.',
                'required'          => true,
                'ondelete'          => 'cascade'
            ],

            'qty' => [
                'type'              => 'integer',
                'description'       => 'Number of persons assigned to the age range for related booking group.',
                'default'           => 1,
                'onupdate'          => 'onupdateQty'
            ],

            'age_range_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\AgeRange',
                'description'       => 'Age range assigned to booking group.',
                'ondelete'          => 'null',
                'onupdate'          => 'onupdateAgeRangeId'
            ]

        ];
    }

    /**
     * Handler for qty updates.
     * Update parent sojourn nb_pers according to currently set age range assignements.
     */
    public static function onupdateQty($om, $oids, $values, $lang) {
        $assignments = $om->read(self::getType(), $oids, ['booking_line_group_id', 'booking_line_group_id.nb_pers', 'booking_line_group_id.age_range_assignments_ids'], $lang);
        if($assignments > 0 && count($assignments)) {
            foreach($assignments as $id => $assignment) {
                $siblings = $om->read(self::getType(), $assignment['booking_line_group_id.age_range_assignments_ids'], ['qty'], $lang);
                $qty = array_reduce($siblings, function($c, $a) { return $c+$a['qty']; }, 0);
                if($qty != $assignment['booking_line_group_id.nb_pers']) {
                    // will trigger onupdateNbPers()
                    $om->update(BookingLineGroup::getType(), $assignment['booking_line_group_id'], ['nb_pers' => $qty]);
                }
            }
        }
    }

    public static function onupdateAgeRangeId($om, $oids, $values, $lang) {
        $assignments = $om->read(self::getType(), $oids, ['booking_line_group_id'], $lang);
        $booking_line_groups_ids = array_map(function ($a) {return $a['booking_line_group_id'];}, $assignments);
        $om->callonce(BookingLineGroup::getType(), '_updatePack', $booking_line_groups_ids, [], $lang);
    }

    /**
     * Hook invoked before object deletion for performing object-specific additional operations.
     * Update nb_pers of parent sojourn.
     *
     * @param  \equal\orm\ObjectManager     $om         ObjectManager instance.
     * @param  array                        $oids       List of objects identifiers.
     * @return void
     */
    public static function ondelete($om, $oids) {
        $assignments = $om->read(self::getType(), $oids, ['qty', 'booking_line_group_id', 'booking_line_group_id.nb_pers']);
        if($assignments > 0 && count($assignments)) {
            foreach($assignments as $id => $assignment) {
                $om->update(BookingLineGroup::getType(), $assignment['booking_line_group_id'], ['nb_pers' => $assignment['booking_line_group_id.nb_pers'] - $assignment['qty']]);
            }
        }
        parent::ondelete($om, $oids);
    }

    /**
     * Check wether an object can be deleted, and perform some additional operations if necessary.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  object   $om         ObjectManager instance.
     * @param  array    $oids       List of objects identifiers.
     * @return boolean  Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be deleted.
     */
    public static function candelete($om, $oids) {
        $assignments = $om->read(self::getType(), $oids, ['booking_id.status', 'booking_line_group_id.is_extra']);

        if($assignments > 0) {
            foreach($assignments as $assignment) {
                if($assignment['booking_line_group_id.is_extra']) {
                    if(!in_array($assignment['booking_id.status'], ['confirmed', 'validated', 'checkedin', 'checkedout'])) {
                        return ['booking_id' => ['non_editable' => 'Assignments can only be updated after confirmation and before invoicing.']];
                    }
                }
                else {
                    if($assignment['booking_id.status'] != 'quote') {
                        return ['booking_id' => ['non_editable' => 'Assignments cannot be updated for non-quote bookings.']];
                    }
                }
            }
        }

        return parent::candelete($om, $oids);
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
        $assignments = $om->read(self::getType(), $oids, ['booking_id.status', 'booking_line_group_id.is_extra'], $lang);
        if($assignments > 0) {
            foreach($assignments as $assignment) {
                if($assignment['booking_line_group_id.is_extra']) {
                    if(!in_array($assignment['booking_id.status'], ['confirmed', 'validated', 'checkedin', 'checkedout'])) {
                        return ['booking_id' => ['non_editable' => 'Rental units assignments cannot be updated for non-quote bookings.']];
                    }
                }
                else {
                    if($assignment['booking_id.status'] != 'quote') {
                        return ['booking_id' => ['non_editable' => 'Rental units assignments cannot be updated for non-quote bookings.']];
                    }
                }
            }
        }
        return parent::canupdate($om, $oids, $values, $lang);
    }

    /**
     * Check wether an object can be created.
     * These tests come in addition to the unique constraints return by method `getUnique()`.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  ObjectManager    $om         ObjectManager instance.
     * @param  array            $values     Associative array holding the values to be assigned to the new instance (not all fields might be set).
     * @param  string           $lang       Language in which multilang fields are being updated.
     * @return array            Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be created.
     */
    public static function cancreate($om, $values, $lang) {
        if(isset($values['booking_line_group_id'])) {
            $groups = $om->read(BookingLineGroup::getType(), $values['booking_line_group_id'], ['booking_id.status', 'is_extra'], $lang);
            $group = reset($groups);
            if($group['is_extra']) {
                if(!in_array($group['booking_id.status'], ['confirmed', 'validated', 'checkedin', 'checkedout'])) {
                    return ['booking_id' => ['non_editable' => 'Rental units assignments cannot be updated for non-quote bookings.']];
                }
            }
            else {
                if($group['booking_id.status'] != 'quote') {
                    return ['booking_id' => ['non_editable' => 'Rental units assignments cannot be updated for non-quote bookings.']];
                }
            }
        }
        return parent::cancreate($om, $values, $lang);
    }

    public function getUnique() {
        return [
            ['booking_line_group_id', 'age_range_id']
        ];
    }

}