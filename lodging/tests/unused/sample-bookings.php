<?php

/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\sale\booking\Booking;
use lodging\sale\booking\BookingLineGroup;
use lodging\sale\booking\BookingLineGroupAgeRangeAssignment;

$providers = eQual::inject(['context', 'orm', 'auth', 'access']);

/*
    This is a file for filling in the DB with sample bookings.
    No tests are actually performed.
*/

$tests = [

    '0101' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-01'),
                'date_to'     => strtotime('2022-04-04'),
                'type_id'     => 4,
                'center_id'   => 25,
                'customer_identity_id' => 15002557,
                'customer_nature_id' => 5
              ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 1756,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-01'),
                'date_to'       => strtotime('2022-04-04'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0102' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-01'),
                'date_to'     => strtotime('2022-04-04'),
                'type_id'     => 4,
                'center_id'   => 25,
                'customer_identity_id' => 12118,
                'customer_nature_id' => 5
           ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 1756,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-01'),
                'date_to'       => strtotime('2022-04-05'),
            ]);

            $groups->update([
                'nb_pers'       => 2
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
               run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0103' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-01'),
                'date_to'     => strtotime('2022-04-04'),
                'type_id'     => 4,
                'center_id'   => 25,
                'customer_identity_id' => 12118,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 1756,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-01'),
                'date_to'       => strtotime('2022-04-06'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
               run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0104' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-06'),
                'date_to'     => strtotime('2022-04-15'),
                'type_id'     => 4,
                'center_id'   => 25,
                'customer_identity_id' => 15002566,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 1756,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-06'),
                'date_to'       => strtotime('2022-04-15'),
            ]);

            $groups->update([
                'nb_pers'       => 15
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
           run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0105' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-01'),
                'date_to'     => strtotime('2022-04-04'),
                'type_id'     => 4,
                'center_id'   => 25,
                'customer_identity_id' => 15002569,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 1756,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-08'),
                'date_to'       => strtotime('2022-04-15'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
               run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0106' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-15'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 4,
                'center_id'   => 25,
                'customer__identity_id' => 15002549,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 1756,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-15'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
               run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0106' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-15'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 4,
                'center_id'   => 25,
                'customer_identity_id' => 15002549,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 1756,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-15'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
               run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0107' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-15'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 4,
                'center_id'   => 26,
                'customer_identity_id' => 15002549,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 412,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-15'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),


    // Rochefort
    '0108' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-15'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 4,
                'center_id'   => 29,
                'customer_identity_id' => 15030033,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 413,
                'is_sojourn'    => true
            ]);


            $groups->update([
                'date_from'     => strtotime('2022-04-15'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0109' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-15'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15001667,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 413,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-15'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0110' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-15'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15002586,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 413,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-15'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0111' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-15'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15002574,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 413,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-15'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0112' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-17'),
                'date_to'     => strtotime('2022-04-22'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15002573,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 413,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-17'),
                'date_to'       => strtotime('2022-04-22'),
            ]);

            $groups->update([
                'nb_pers'       => 10
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0113' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-15'),
                'date_to'     => strtotime('2022-04-30'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15001667,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 413,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-15'),
                'date_to'       => strtotime('2022-04-30'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0114' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-23'),
                'date_to'     => strtotime('2022-04-30'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15002597,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 413,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-23'),
                'date_to'       => strtotime('2022-04-30'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0115' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-12'),
                'date_to'     => strtotime('2022-04-18'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 8234,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 413,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-12'),
                'date_to'       => strtotime('2022-04-18'),
            ]);

            $groups->update([
                'nb_pers'       => 5
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),

    '0116' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-12'),
                'date_to'     => strtotime('2022-04-20'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15002549,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 412,
                'is_sojourn'    => true
            ]);


            $groups->update([
                'date_from'     => strtotime('2022-04-12'),
                'date_to'       => strtotime('2022-04-20'),
            ]);

            $groups->update([
                'nb_pers'       => 7
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),

    '0117' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-20'),
                'date_to'     => strtotime('2022-04-25'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15002511,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 412,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-20'),
                'date_to'       => strtotime('2022-04-25'),
            ]);

            $groups->update([
                'nb_pers'       => 6
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),

    '0118' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-27'),
                'date_to'     => strtotime('2022-04-30'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15002517,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 412,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-27'),
                'date_to'       => strtotime('2022-04-30'),
            ]);

            $groups->update([
                'nb_pers'       => 2
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),

    '0119' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-17'),
                'date_to'     => strtotime('2022-04-23'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 12505,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 412,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-17'),
                'date_to'       => strtotime('2022-04-23'),
            ]);

            $groups->update([
                'nb_pers'       => 4
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0120' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-17'),
                'date_to'     => strtotime('2022-04-23'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15002468,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 412,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-17'),
                'date_to'       => strtotime('2022-04-23'),
            ]);

            $groups->update([
                'nb_pers'       => 4
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0121' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-17'),
                'date_to'     => strtotime('2022-04-23'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_identity_id' => 4190,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 412,
                'is_sojourn'    => true
            ]);


            $groups->update([
                'date_from'     => strtotime('2022-04-17'),
                'date_to'       => strtotime('2022-04-23'),
            ]);

            $groups->update([
                'nb_pers'       => 4
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),

    '0122' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-12'),
                'date_to'     => strtotime('2022-04-25'),
                'type_id'     => 1,
                'center_id'   => 26,
                'customer_identity_id' => 15030026,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 412,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-12'),
                'date_to'       => strtotime('2022-04-25'),
            ]);

            $groups->update([
                'nb_pers'       => 2
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),

    '0123' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-20'),
                'date_to'     => strtotime('2022-04-28'),
                'type_id'     => 1,
                'center_id'   => 26,
                'customer_identity_id' => 15030027,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 412,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-20'),
                'date_to'       => strtotime('2022-04-28'),
            ]);

            $groups->update([
                'nb_pers'       => 12
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),

    '0124' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-10'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 1,
                'center_id'   => 26,
                'customer_identity_id' => 251,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 412,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-10'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 12
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),

    '0125' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-12'),
                'date_to'     => strtotime('2022-04-18'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15002468,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 412,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-12'),
                'date_to'       => strtotime('2022-04-18'),
            ]);

            $groups->update([
                'nb_pers'       => 12
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            // run('do', 'lodging_booking_do-checkin', ['id' => $booking['id']]);
            // run('do', 'lodging_booking_do-checkout', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),

    '0126' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-03-28'),
                'date_to'     => strtotime('2022-03-30'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15001911,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 412,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-03-28'),
                'date_to'       => strtotime('2022-03-30'),
            ]);

            $groups->update([
                'nb_pers'       => 4
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            run('do', 'lodging_composition_import', ['data'=>'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bmtXWwWutq5dzUqOrrqatRxddTWMxXTV1dDiI4OI2UlbSaOMKKDb2tVWgauta1ezkqOrrmYtR1ddDWMxXXU1tJiu3lfTVtIoIwrotna1VeBq69rVrOToqqtZy9FVV8NYTFddDS2mq/fVtJU0yogCuq1dbRW42qZ2NSs5uupq1nJ01dUwFtNVV0OL6aqraStplBEFdFu72ipwtU3talZydNXVrOXoqqthLKarroYW09X7atpKGmVEAd3Wrlb+slGu4mxqV7OSo6uuZi1HV10NYzFddTW0mK7eV9NW0igjCui2drXhr87qK5D1h60PVnJ01dWs5eiqq2EspquuhhbT1ftq2koaZURKtzzUtvfVzjuoXG1Tu5qVmC4icjWLmC5a5Gq64RERrXcREV2NTholjTKigG5rV1sHrrapXc1Kjq66mrUcXXU1jEVzFxHTVVdDi2xCo6RRRhTQbe1q5c859chQu5qVHF11NWs5uupqGIvpqquhxXNXxOykraRRRhTQbe1qw1+yynG3djUrObrqatZydNXVMBbTVVdDi+mqq2kraZQRBXRbu9rwuXihW7ualRxddTVrObrqahiL6aqrocV01dW0lTTKiAK6rV1t+ONeoVu7mpUcXXU1azm66moYi+mqq6HFdNXVtJU0yogCuq1dbR242rZ2NSs5uupq1nJ01dUwFtNVV0OL6aqraStplBEFdFu72jpwtW3talZydNXVrOXoqqthLKarroYW01VX01bSKCMK6LZ2tXXgatva1azk6KqrWcvRVVfDWExXXQ0tpquupq2kUUYU0G3tasOXv9TH3W19X81Kjq66mrUcXXU1jMV01dXQYrrqatpKGmVESrd8tUJbVzvvoHK1be1qVmK6iMjVLGK6aJGr6YZHRGQTiIiuRieNkkYZUUC3tasN3yclc7d2NSs5uupq1nJ01dUwFs1dRExXXQ0tcjWNkkYZUUC3tasN3wMndGtXs5Kjq65mLUdXXQ1jMV11NbR47qqraStplBEFdFu72iZwtW3talZydNXVrOXoqqthLKarroYW01VX01bSKCMK6LZ2tU3gatva1azk6KqrWcvRVVfDWExXXQ0tpquupq2kUUYU0G3tapvA1ba1q1nJ0VVXs5ajq66GsZiuuhpaTFddTVtJo4wooNva1TaBq+1qV7OSo6uuZi1HV10NYzFddTW0mK66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','booking_id' => $booking['id']]);
            run('do', 'lodging_payments_import' , ['data'=>'MDAwMDAyNzAzMjIxOTAwNSAgICAgICAgMDAwMjg5NjQgIFBST0dOTyBBTEVYQU5EUkUgICAgICAgICAgQ1JFR0JFQkIgICAwMDQwMTIxNDQ2NyAwMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDINCjEwMDM5MTkxMTU4Mjc5MjA5IEVVUjBCRSAgICAgICAgICAgICAgICAgIDAwMDAwMDAwMTE1ODMyNDAxOTAyMjBLQUxFTyAtIENFTlRSRSBCRUxHRSBUT1VSSUNvbXB0ZSBkJ2VudHJlcHJpc2UgQ0JDICAgICAgICAgICAgMDM5DQoyMTAwMDEwMDAwWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAwMDAwMDAwMDE1ODgwMDIwMDIyMDAwMTUwMDAwMTEwMTE1MTAyMjA1MTE2MCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMjAwMTIwMDM5MDEgMA0KMjIwMDAxMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBHS0NDQkVCQiAgICAgICAgICAgICAgICAgICAxIDANCjIzMDAwMTAwMDBCRTUwMDAxNTg4MDY2NDE4ICAgICAgICAgICAgICAgICAgICAgREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMCAxDQozMTAwMDEwMDAxWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAxNTAwMDAxMDAxREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDEgMA0KMzIwMDAxMDAwMUpFQU4gREVCT1JTVSAxNy8yMDEgICAgICAgICA1MDAwICBOYW11ciAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwIDANCjgwMzkxOTExNTgyNzkyMDkgRVVSMEJFICAgICAgICAgICAgICAgICAgMDAwMDAwMDAxMTc1NDI0MDIwMDIyMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwDQo5ICAgICAgICAgICAgICAgMDAwMDA3MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMTU4ODAwICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMg==']);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),

    '0127' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-05-20'),
                'date_to'     => strtotime('2022-05-25'),
                'type_id'     => 1,
                'center_id'   => 30,
                'customer_identity_id' => 15030021,
                'customer_nature_id' => 5,
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 395,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-05-20'),
                'date_to'       => strtotime('2022-05-25'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();

            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            run('do', 'lodging_composition_import', ['data'=>'UEsDBBQABgAIAAAAIQBBN4LPbgEAAAQFAAATAAgCW0NvbnRlbnRfVHlwZXNdLnhtbCCiBAIooAACAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACsVMluwjAQvVfqP0S+Vomhh6qqCBy6HFsk6AeYeJJYJLblGSj8fSdmUVWxCMElUWzPWybzPBit2iZZQkDjbC76WU8kYAunja1y8T39SJ9FgqSsVo2zkIs1oBgN7+8G07UHTLjaYi5qIv8iJRY1tAoz58HyTulCq4g/QyW9KuaqAvnY6z3JwlkCSyl1GGI4eINSLRpK3le8vFEyM1Ykr5tzHVUulPeNKRSxULm0+h9J6srSFKBdsWgZOkMfQGmsAahtMh8MM4YJELExFPIgZ4AGLyPdusq4MgrD2nh8YOtHGLqd4662dV/8O4LRkIxVoE/Vsne5auSPC/OZc/PsNMilrYktylpl7E73Cf54GGV89W8spPMXgc/oIJ4xkPF5vYQIc4YQad0A3rrtEfQcc60C6Anx9FY3F/AX+5QOjtQ4OI+c2gCXd2EXka469QwEgQzsQ3Jo2PaMHPmr2w7dnaJBH+CW8Q4b/gIAAP//AwBQSwMEFAAGAAgAAAAhALVVMCP0AAAATAIAAAsACAJfcmVscy8ucmVscyCiBAIooAACAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACskk1PwzAMhu9I/IfI99XdkBBCS3dBSLshVH6ASdwPtY2jJBvdvyccEFQagwNHf71+/Mrb3TyN6sgh9uI0rIsSFDsjtnethpf6cXUHKiZylkZxrOHEEXbV9dX2mUdKeSh2vY8qq7iooUvJ3yNG0/FEsRDPLlcaCROlHIYWPZmBWsZNWd5i+K4B1UJT7a2GsLc3oOqTz5t/15am6Q0/iDlM7NKZFchzYmfZrnzIbCH1+RpVU2g5abBinnI6InlfZGzA80SbvxP9fC1OnMhSIjQS+DLPR8cloPV/WrQ08cudecQ3CcOryPDJgosfqN4BAAD//wMAUEsDBBQABgAIAAAAIQCXNjM+UgQAALgKAAAPAAAAeGwvd29ya2Jvb2sueG1stFZrb5tIFP2+0v4HykbKh4rAYPADxamwAcWVk7p24iqrSNEYBjM1rwyDH6363/cONomd7K7cdBfZA/PgzLn3nnuH8w/rJJaWhBU0S7syOtNkiaR+FtB03pVvbzylLUsFx2mA4ywlXXlDCvnDxe+/na8ytphl2UICgLToyhHnuaWqhR+RBBdnWU5SmAkzlmAOXTZXi5wRHBQRITyJVV3TmmqCaSpvESx2DEYWhtQnTuaXCUn5FoSRGHOgX0Q0L2q0xD8GLsFsUeaKnyU5QMxoTPmmApWlxLcG8zRjeBaD2WtkSmsGvyb8kQaNXu8EU6+2SqjPsiIL+RlAq1vSr+xHmorQgQvWr31wHJKhMrKkIoZPrFjzjayaT1jNZzCk/TIaAmlVWrHAeW9EM5+46fLFeUhjMt1KV8J5fo0TEalYlmJccDegnARduQXdbEWeB8AqVua9ksYwq5ua3pbViyc5jxh0IPZ2zAlLMSf9LOUgtR31X5VVhd2PMhCxNCaPJWUEcgckBOZAi30Lz4oR5pFUsrgr96372wIsvP9IcHpfy76439Mefi30n1Af9oXxKhi8JbV9fmk8cGNWrbARZxI8D5wheHmCl+BziGywS8kBOBU1HlKfWejhu+dqCFzcUhqGYypGo99R7J7ZVlCzZTY7juHYqP0DjGFNy89wyaNdOAV0VzYgdq+mrvC6nkGaVdLgmcZ3bXcp4v6iqed+HEY748QXxUOq65kdzzNGeZRs5TS5tBUTQbLX85e4iKY4LsHs+d3t+P1X31l2vF6fj6Z577P9tRNM/bty7d81XXvgLVoZW63U+C7BN7Tot4j7aTPsZbOPn+L5YzJy3dai9+fmW77Z6I/fBsuxqdnd7vNmExzz3WYpHdKlffU+GnOfmcb1eqE+Lj4fLM5p2s/KFDyHKmuF8v3FhLPS5yUDwkjYLor2lJJV8Sx60ZXWX2gaZKuurCANiv7msLuqJr/QgEeQNQ3dhDTajl0SOo/EnrrZEhWD6SIqXfkgGs42Gh5cimgOoqHuUaqOB6BW3aW0Smko+RGRgtOIzAibE1H5H0IG55I4SoTgDEhpS+zJBkFlo1rDBCSkKQlELAF0r7eDfgh5iio/4XhSw2nyxWm954tNT9/9MXa9d+fqHtQ/4TIS/i/Q6zhNzkaMghdsOFF/iv2JfYKsk+EJamgvbNi3CFzl49gfMUncqpTuIE3vCPmQNR8WvLpDjaIQZ2RodkvrGIrmNiDJ2x1daRsNXekbju6aLddxe6ZIcvGdYP0Xp2VVKq06IQXLCDN+w7C/gM+WMQl7uKjVrgLffbJQfXpaAygaHvIUA3U0pddrGorpeA2zhZy+a3rPZIX54RvPqrZavU2wyL1C1Peqb4nW240+DYbbgZ0qDwq4NXaE33dv/9vCCVgfkyMXe9MjF/avr26ujlw7dG8evnjHLraveo69W6/+rXe20RNtpTm1jvnFXwAAAP//AwBQSwMEFAAGAAgAAAAhAIE+lJfzAAAAugIAABoACAF4bC9fcmVscy93b3JrYm9vay54bWwucmVscyCiBAEooAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAKxSTUvEMBC9C/6HMHebdhUR2XQvIuxV6w8IybQp2yYhM3703xsqul1Y1ksvA2+Gee/Nx3b3NQ7iAxP1wSuoihIEehNs7zsFb83zzQMIYu2tHoJHBRMS7Orrq+0LDppzE7k+ksgsnhQ45vgoJRmHo6YiRPS50oY0as4wdTJqc9Adyk1Z3su05ID6hFPsrYK0t7cgmilm5f+5Q9v2Bp+CeR/R8xkJSTwNeQDR6NQhK/jBRfYI8rz8Zk15zmvBo/oM5RyrSx6qNT18hnQgh8hHH38pknPlopm7Ve/hdEL7yim/2/Isy/TvZuTJx9XfAAAA//8DAFBLAwQUAAYACAAAACEAWJprDI8dAACIvgAAGAAAAHhsL3dvcmtzaGVldHMvc2hlZXQxLnhtbJyU227bMAyG7wfsHQzd17ZsxzkgTrG1K1agGIKl3a4VmU6E2JYnKacOe/dRPiVAb9wGiaVI4sefFOn57anInQMoLWSZEOr6xIGSy1SUm4S8PD/cTIijDStTlssSEnIGTW4Xnz/Nj1Lt9BbAOEgodUK2xlQzz9N8CwXTrqygxJ1MqoIZ/Ks2nq4UsLQ2KnIv8P3YK5goSUOYqSEMmWWCw73k+wJK00AU5Mygfr0Vle5oBR+CK5ja7asbLosKEWuRC3OuocQp+OxxU0rF1jnGfaIR485J4TfAX9i5qdffeCoEV1LLzLhI9hrNb8OfelOP8Z70Nv5BGBp5Cg7CXuAFFXxMEh31rOACCz8Ii3uYTZea7UWakL9++7nBkdqHf3l0e//IYl7XyVIt5hXbwArMS7VUTibMs1ziAtYq8RZzrz+VCiwImwRHQZaQL8HsiYZje6Y+8kvAUV/NHcPWK8iBG0BRlDgHPJAQ6+srVuluaXMKR+IYWT1BZu4gz5FKsT9epSxWnOXww9Y2rlL/enVlm+KJneXeWKfttm2XtZQ7u/SIHn0bYe3fSmbciAM0Pr5R23J/6ijsvI/SmnYRX4fzULcYJmfNNNzJ/LdIzda6JU4KGdvn5mqRulFEIz8ORv3uT3n8DmKzNWgTuRFela31WXq+B82xyVCsG1oZXOaYQnw6hbAvC+wRdqrHY+Mytu+Ks20X3ON7bWTRiWntG0usrdpyTJzWMghc6k/DMaoaRMAU1QQcW0L4TsK0JeDYEqjvxnGbmkEibIabDOCkgwTuZBKP6WRwKLTPI046SuyGYeCH1F7TMC1dTilOLikZjaL4HVKwT5uA4nCChdCpuS6aXo2ty7oi/gMAAP//AAAA//+0nY1u28gVhV8l8AM4IvW/cAKsYlu2Zl4icI1mUeymiNPd7dt3KN5DnjvnxnYKT4F20bOHQ+nTiJyPlKWrpy+Pj9+vP3///PHq29e/3n37cNFfvHv69+c/nj5cdL905f98+V6y7uLdw3+evn/9/e7xt38OSQn+7lafH375x3+vH58eHv8o2eJyub74ePUwjHIYhim1zcW78m+eSvznx65fX73/8+PV+4fy37K3aZfLYJfd9rJfv3Kv006HgT5cbMq+aaebaafnR/ZpKr23x3otyY0kt5IcOXFPZ/VWT2cYqMDf0dPZzwjHZzN1pmcjyY0kt5IcOXHPprwIMh9+5sWZp8Qw0vnp4KF+kuRakhtJbiU5SnI3JqvdRdmXezplQurT2V8WxtF8nmbWsFmZWWXazzNrsaxm1lSaXgtJbiS5leQoyZ0k95KcJEmSZE4clm2AZXv502/AYZgPF0uhXvD+/wcVvAi/DqOUCdQPw49HmTFZ793L0lcvy1SaXhZJbiS5leQoyZ0k95KcJEmS5DHZLIRbeWI6W/vLbfTCfP/y28O/Dl+fOzgXAHaU7xbRK9JflkOnHOhfMTBej/Ow5X3CR6zqXXKDzmZ6GW8RbafozqKVe2mX1Vj32PA8485T4oRoNY2VEK2Fb1fe0Ap4dVmOhq86303Hh/NAwxPHHLuzZH2ere69NpxV9d0Qs3/2oHQe6MPFupyJ5qPScltN/7k1zX+NbjS61eio0Z1G9xqdNEoWldMbHle26PzSeWThEuGnD0+dnbN1HtTn7OE4My16br9++/3z+L4a1j7rn50dv3bj2XVdHjCdPjr/Qh3QmifRJ42uNbrR6FajIyJ+k9nj4hVG16/847rHhvvphTpplDTKFgXHta5eVRTe5UGM77kKd7/+6fPQr+fxy/JznlsHi7Z8bNrt6/fKuG5wpf3Cl66jkfbVi3kTlbpl1boNh6rOX0eU+FXa7/yDurPSxpWqpe89RioH/2ke7quX+4SSX0VXpFI4VFXKKJVpNu1vOT89/xavV2bPTYnd/zElxrVPN8/iw2AnZS/b+R3xSaNrjW40utXoiGje451Fmzm6R6ujd5c9rvmslrSVEQVntXo199Yox2VeX/5B551qHh26sTX8g1q1jVlryVOyW1aWc43W6iySy75byarClp481Tqaa+fFwW24t746Yx6t1fvHJO84e35uj33VugeF8ZGvl111NDmh4M4Ny6qV/DDLfrXtNv2mgpDx9JymLOd3pX/DDatONv3hrfCjY/A2XnM+tzz5tRx9z+t1ngD7ivXBSsM5dZ4lq+rpf7KWnyWr6mh6jdYzs2R8SMsXZom13Ouvs8Se3guzZGwNp0K6JiGzZGoNF0qiWWKFF2aJG+aHs8Se3utmSa0gbz5Lhh2U1RYvYut1/qEctYeSnyVLOZaMrZeOJdZ6ZpZMDT5yVaflW3tMfm86S+zpvTBLxtZLs2Rq/WiWWOGFWeKG+eEsMQivmiXDobLpseS8g3qW1GccK70wS6z1wixB68ezZG48N0vCvckswdN7fpZY64VZMrd+MEtQeH6W+GF+NEvw9F43S2rbfutjyaBsciyp1hIHKy3ny0ifEM0LrmuL1udrMufFww1a5wXXuJ7QDY+I6FIAonmdea/RSaOkUUakVzbLdbHG78Hxmr4/UlfH4MP5UZRLgEx33G7JdMfI0bUW05UNjxie6VqL6Up0woZzK2mUEQV06ysQbz53x2sTnm69Whr+7XCBlelaxHTHyNG1FtOVDY8Ynulai+lKdMKGTFdaGa2AbnD95U3Xor3d33CrjGoVdrCSoztu5+auXTThI4O1mK5seMTwTNdaTFeiEzZkutLKaAV0g6stb0vXbsA4utXFgMNwO6+euxbx3B0jN3etxXRlwyOGZ7rWYroSnbAh05VWRiugG1y4eFu64+UAd2SoDenQjyU3dy1iumPk6FqL6cqGRwzPdK1F1zLQmlGeNEoaZUQB3eBaxtvStYsZPHdrszwMlzrquWsR0x0jR9daTFc2PGJ4pmstnrsSnbAhz11pZbQCuq2vAQw3r+sV2aoyqYOV3Nw1R2W6Y+ToWovpyoZHDM90rcV0JTphQ6YrrYxWQLe1O/eBO9dXxg5WcnTN7ZiuGTaf1azFdGXDI4ZnutZiuhKdsCHTlVZGS+kOAtfUOc87qJxzVTunlZguIqJrEc9dtIiubnjU6A4R0dXopFHSKCMK6LZ2tWXgaqva1azk6I7b8YrMWo6utZiubHjE8DR3ETFd25DOatpKGmVEAd3WrlaI6XG3djUrObrqatZydNXVMNY86Y+ImK66GlpMV1pJWxlRQLe1q5XJp3RrV7OSo6uuZi1HV10NYzFdazFddTVsyHSllbSVEQV0W7vacHVe1gy1q1nJ0VVXs5ajq66GsZiutZiuuho2ZLrSStrKiAK6rV2tfEZT6dauZiVHV13NWo6uuhrGYrrWYrrqatiQ6UoraSsjCui2drVl4Grr6m7WwUqOrrqatRxddTWMxXStxXQt4rOaRCeMRSsyjTKigG5rV1sGrrauP0ZjJUdXXc1ajq66GsZiutZiuupq2JDnrrSStjKigG5rV1sGrrauXc1Kjq66mrUcXXU1jMV0rcV01dWwIdOVVtJWRhTQbe1qy8DV1tUN/IOVHF11NWs5uupqGIvpWovpqqthQ6YrraStjEjprlq72nkHlauta1ezEtNFRK5mEdNFi2xCNzwiIrqI6Lir0UmjpFFGFNBt7WqrwNXWtatZydFVV7OWo6uuhrFo7iJiuiJm92jR3NUoaZQRBXRbu9oqcLV17WpWcnTV1azl6KqrYSymay2mq66GDZmuupq2MqKAbmtXWwWutq5dzUqOrrqatRxddTWMxXTV1dDiI4OI2UlbSaOMKKDb2tVWgauta1ezkqOrrmYtR1ddDWMxXXU1tJiu3lfTVtIoIwrotna1VeBq69rVrOToqqtZy9FVV8NYTFddDS2mq/fVtJU0yogCuq1dbRW42qZ2NSs5uupq1nJ01dUwFtNVV0OL6aqraStplBEFdFu72ipwtU3talZydNXVrOXoqqthLKarroYW09X7atpKGmVEAd3Wrlb+slGu4mxqV7OSo6uuZi1HV10NYzFddTW0mK7eV9NW0igjCui2drXhr87qK5D1h60PVnJ01dWs5eiqq2EspquuhhbT1ftq2koaZURKtzzUtvfVzjuoXG1Tu5qVmC4icjWLmC5a5Gq64RERrXcREV2NTholjTKigG5rV1sHrrapXc1Kjq66mrUcXXU1jEVzFxHTVVdDi2xCo6RRRhTQbe1q5c859chQu5qVHF11NWs5uupqGIvpqquhxXNXxOykraRRRhTQbe1qw1+yynG3djUrObrqatZydNXVMBbTVVdDi+mqq2kraZQRBXRbu9rwuXihW7ualRxddTVrObrqahiL6aqrocV01dW0lTTKiAK6rV1t+ONeoVu7mpUcXXU1azm66moYi+mqq6HFdNXVtJU0yogCuq1dbR242rZ2NSs5uupq1nJ01dUwFtNVV0OL6aqraStplBEFdFu72jpwtW3talZydNXVrOXoqqthLKarroYW01VX01bSKCMK6LZ2tXXgatva1azk6KqrWcvRVVfDWExXXQ0tpquupq2kUUYU0G3tasOXv9TH3W19X81Kjq66mrUcXXU1jMV01dXQYrrqatpKGmVESrd8tUJbVzvvoHK1be1qVmK6iMjVLGK6aJGr6YZHRGQTiIiuRieNkkYZUUC3tasN3yclc7d2NSs5uupq1nJ01dUwFs1dRExXXQ0tcjWNkkYZUUC3tasN3wMndGtXs5Kjq65mLUdXXQ1jMV11NbR47qqraStplBEFdFu72iZwtW3talZydNXVrOXoqqthLKarroYW01VX01bSKCMK6LZ2tU3gatva1azk6KqrWcvRVVfDWExXXQ0tpquupq2kUUYU0G3tapvA1ba1q1nJ0VVXs5ajq66GsZiuuhpaTFddTVtJo4wooNva1TaBq+1qV7OSo6uuZi1HV10NYzFddTW0mK66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','booking_id' => $booking['id']]);
            run('do', 'lodging_payments_import' , ['data'=>'MDAwMDAxOTA1MjIxOTAwNSAgICAgICAgMDAwMjg5NjQgIFBST0dOTyBBTEVYQU5EUkUgICAgICAgICAgQ1JFR0JFQkIgICAwMDQwMTIxNDQ2NyAwMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDINCjEwMDM5MTkxMTU4Mjc3Mjg2IEVVUjBCRSAgICAgICAgICAgICAgICAgIDAwMDAwMDAwMTE1ODEyNDAxOTAyMjBLQUxFTyAtIENFTlRSRSBCRUxHRSBUT1VSSUNvbXB0ZSBkJ2VudHJlcHJpc2UgQ0JDICAgICAgICAgICAgMDM5DQoyMTAwMDEwMDAwWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAwMDAwMDAwMDEzNDAwMDIwMDIyMDAwMTUwMDAwMTEwMTE1MTA4MTExMTEyNyAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMjAwMTIwMDM5MDEgMA0KMjIwMDAxMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBHS0NDQkVCQiAgICAgICAgICAgICAgICAgICAxIDANCjIzMDAwMTAwMDBCRTUwMDAxNTg4MDY2NDE4ICAgICAgICAgICAgICAgICAgICAgREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMCAxDQozMTAwMDEwMDAxWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAxNTAwMDAxMDAxREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDEgMA0KMzIwMDAxMDAwMUpFQU4gREVCT1JTVSAxNy8yMDEgICAgICAgICA1MDAwICBOYW11ciAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwIDANCjgwMzkxOTExNTgyNzcyODYgRVVSMEJFICAgICAgICAgICAgICAgICAgMDAwMDAwMDAxMTc1MTI0MDIwMDIyMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwDQo5ICAgICAgICAgICAgICAgMDAwMDA3MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMTM0MDAwICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMg==']);
            run('do', 'lodging_payments_import' , ['data'=>'MDAwMDAxMjA1MjIxOTAwNSAgICAgICAgMDAwMjg5NjQgIFBST0dOTyBBTEVYQU5EUkUgICAgICAgICAgQ1JFR0JFQkIgICAwMDQwMTIxNDQ2NyAwMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDINCjEwMDM5MTkxMTU4Mjc3Mjg2IEVVUjBCRSAgICAgICAgICAgICAgICAgIDAwMDAwMDAwMTE1ODEyNDAxOTAyMjBLQUxFTyAtIENFTlRSRSBCRUxHRSBUT1VSSUNvbXB0ZSBkJ2VudHJlcHJpc2UgQ0JDICAgICAgICAgICAgMDM5DQoyMTAwMDEwMDAwWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAwMDAwMDAwMDA4Mzc1MDIwMDIyMDAwMTUwMDAwMTEwMTE1MTA4MTExMTEyNyAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMjAwMTIwMDM5MDEgMA0KMjIwMDAxMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBHS0NDQkVCQiAgICAgICAgICAgICAgICAgICAxIDANCjIzMDAwMTAwMDBCRTUwMDAxNTg4MDY2NDE4ICAgICAgICAgICAgICAgICAgICAgREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMCAxDQozMTAwMDEwMDAxWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAxNTAwMDAxMDAxREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDEgMA0KMzIwMDAxMDAwMUpFQU4gREVCT1JTVSAxNy8yMDEgICAgICAgICA1MDAwICBOYW11ciAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwIDANCjgwMzkxOTExNTgyNzcyODYgRVVSMEJFICAgICAgICAgICAgICAgICAgMDAwMDAwMDAxMTc1MTI0MDIwMDIyMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwDQo5ICAgICAgICAgICAgICAgMDAwMDA3MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDgzNzUwICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMg==']);
            run('do', 'lodging_booking_do-checkin', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),

    '0128' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-25'),
                'date_to'     => strtotime('2022-04-30'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_identity_id' => 15002427,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 370,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-25'),
                'date_to'       => strtotime('2022-04-30'),
            ]);

            $groups->update([
                'nb_pers'       => 2
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            // run('do', 'lodging_booking_do-checkin', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),

    '0129' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-25'),
                'date_to'     => strtotime('2022-04-30'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_identity_id' => 12615,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 370,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-25'),
                'date_to'       => strtotime('2022-04-30'),
            ]);

            $groups->update([
                'nb_pers'       => 2
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            run('do', 'lodging_composition_import', ['data'=>'UEsDBBQABgAIAAAAIQBBN4LPbgEAAAQFAAATAAgCW0NvbnRlbnRfVHlwZXNdLnhtbCCiBAIooAACAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACsVMluwjAQvVfqP0S+Vomhh6qqCBy6HFsk6AeYeJJYJLblGSj8fSdmUVWxCMElUWzPWybzPBit2iZZQkDjbC76WU8kYAunja1y8T39SJ9FgqSsVo2zkIs1oBgN7+8G07UHTLjaYi5qIv8iJRY1tAoz58HyTulCq4g/QyW9KuaqAvnY6z3JwlkCSyl1GGI4eINSLRpK3le8vFEyM1Ykr5tzHVUulPeNKRSxULm0+h9J6srSFKBdsWgZOkMfQGmsAahtMh8MM4YJELExFPIgZ4AGLyPdusq4MgrD2nh8YOtHGLqd4662dV/8O4LRkIxVoE/Vsne5auSPC/OZc/PsNMilrYktylpl7E73Cf54GGV89W8spPMXgc/oIJ4xkPF5vYQIc4YQad0A3rrtEfQcc60C6Anx9FY3F/AX+5QOjtQ4OI+c2gCXd2EXka469QwEgQzsQ3Jo2PaMHPmr2w7dnaJBH+CW8Q4b/gIAAP//AwBQSwMEFAAGAAgAAAAhALVVMCP0AAAATAIAAAsACAJfcmVscy8ucmVscyCiBAIooAACAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACskk1PwzAMhu9I/IfI99XdkBBCS3dBSLshVH6ASdwPtY2jJBvdvyccEFQagwNHf71+/Mrb3TyN6sgh9uI0rIsSFDsjtnethpf6cXUHKiZylkZxrOHEEXbV9dX2mUdKeSh2vY8qq7iooUvJ3yNG0/FEsRDPLlcaCROlHIYWPZmBWsZNWd5i+K4B1UJT7a2GsLc3oOqTz5t/15am6Q0/iDlM7NKZFchzYmfZrnzIbCH1+RpVU2g5abBinnI6InlfZGzA80SbvxP9fC1OnMhSIjQS+DLPR8cloPV/WrQ08cudecQ3CcOryPDJgosfqN4BAAD//wMAUEsDBBQABgAIAAAAIQCXNjM+UgQAALgKAAAPAAAAeGwvd29ya2Jvb2sueG1stFZrb5tIFP2+0v4HykbKh4rAYPADxamwAcWVk7p24iqrSNEYBjM1rwyDH6363/cONomd7K7cdBfZA/PgzLn3nnuH8w/rJJaWhBU0S7syOtNkiaR+FtB03pVvbzylLUsFx2mA4ywlXXlDCvnDxe+/na8ytphl2UICgLToyhHnuaWqhR+RBBdnWU5SmAkzlmAOXTZXi5wRHBQRITyJVV3TmmqCaSpvESx2DEYWhtQnTuaXCUn5FoSRGHOgX0Q0L2q0xD8GLsFsUeaKnyU5QMxoTPmmApWlxLcG8zRjeBaD2WtkSmsGvyb8kQaNXu8EU6+2SqjPsiIL+RlAq1vSr+xHmorQgQvWr31wHJKhMrKkIoZPrFjzjayaT1jNZzCk/TIaAmlVWrHAeW9EM5+46fLFeUhjMt1KV8J5fo0TEalYlmJccDegnARduQXdbEWeB8AqVua9ksYwq5ua3pbViyc5jxh0IPZ2zAlLMSf9LOUgtR31X5VVhd2PMhCxNCaPJWUEcgckBOZAi30Lz4oR5pFUsrgr96372wIsvP9IcHpfy76439Mefi30n1Af9oXxKhi8JbV9fmk8cGNWrbARZxI8D5wheHmCl+BziGywS8kBOBU1HlKfWejhu+dqCFzcUhqGYypGo99R7J7ZVlCzZTY7juHYqP0DjGFNy89wyaNdOAV0VzYgdq+mrvC6nkGaVdLgmcZ3bXcp4v6iqed+HEY748QXxUOq65kdzzNGeZRs5TS5tBUTQbLX85e4iKY4LsHs+d3t+P1X31l2vF6fj6Z577P9tRNM/bty7d81XXvgLVoZW63U+C7BN7Tot4j7aTPsZbOPn+L5YzJy3dai9+fmW77Z6I/fBsuxqdnd7vNmExzz3WYpHdKlffU+GnOfmcb1eqE+Lj4fLM5p2s/KFDyHKmuF8v3FhLPS5yUDwkjYLor2lJJV8Sx60ZXWX2gaZKuurCANiv7msLuqJr/QgEeQNQ3dhDTajl0SOo/EnrrZEhWD6SIqXfkgGs42Gh5cimgOoqHuUaqOB6BW3aW0Smko+RGRgtOIzAibE1H5H0IG55I4SoTgDEhpS+zJBkFlo1rDBCSkKQlELAF0r7eDfgh5iio/4XhSw2nyxWm954tNT9/9MXa9d+fqHtQ/4TIS/i/Q6zhNzkaMghdsOFF/iv2JfYKsk+EJamgvbNi3CFzl49gfMUncqpTuIE3vCPmQNR8WvLpDjaIQZ2RodkvrGIrmNiDJ2x1daRsNXekbju6aLddxe6ZIcvGdYP0Xp2VVKq06IQXLCDN+w7C/gM+WMQl7uKjVrgLffbJQfXpaAygaHvIUA3U0pddrGorpeA2zhZy+a3rPZIX54RvPqrZavU2wyL1C1Peqb4nW240+DYbbgZ0qDwq4NXaE33dv/9vCCVgfkyMXe9MjF/avr26ujlw7dG8evnjHLraveo69W6/+rXe20RNtpTm1jvnFXwAAAP//AwBQSwMEFAAGAAgAAAAhAIE+lJfzAAAAugIAABoACAF4bC9fcmVscy93b3JrYm9vay54bWwucmVscyCiBAEooAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAKxSTUvEMBC9C/6HMHebdhUR2XQvIuxV6w8IybQp2yYhM3703xsqul1Y1ksvA2+Gee/Nx3b3NQ7iAxP1wSuoihIEehNs7zsFb83zzQMIYu2tHoJHBRMS7Orrq+0LDppzE7k+ksgsnhQ45vgoJRmHo6YiRPS50oY0as4wdTJqc9Adyk1Z3su05ID6hFPsrYK0t7cgmilm5f+5Q9v2Bp+CeR/R8xkJSTwNeQDR6NQhK/jBRfYI8rz8Zk15zmvBo/oM5RyrSx6qNT18hnQgh8hHH38pknPlopm7Ve/hdEL7yim/2/Isy/TvZuTJx9XfAAAA//8DAFBLAwQUAAYACAAAACEAWJprDI8dAACIvgAAGAAAAHhsL3dvcmtzaGVldHMvc2hlZXQxLnhtbJyU227bMAyG7wfsHQzd17ZsxzkgTrG1K1agGIKl3a4VmU6E2JYnKacOe/dRPiVAb9wGiaVI4sefFOn57anInQMoLWSZEOr6xIGSy1SUm4S8PD/cTIijDStTlssSEnIGTW4Xnz/Nj1Lt9BbAOEgodUK2xlQzz9N8CwXTrqygxJ1MqoIZ/Ks2nq4UsLQ2KnIv8P3YK5goSUOYqSEMmWWCw73k+wJK00AU5Mygfr0Vle5oBR+CK5ja7asbLosKEWuRC3OuocQp+OxxU0rF1jnGfaIR485J4TfAX9i5qdffeCoEV1LLzLhI9hrNb8OfelOP8Z70Nv5BGBp5Cg7CXuAFFXxMEh31rOACCz8Ii3uYTZea7UWakL9++7nBkdqHf3l0e//IYl7XyVIt5hXbwArMS7VUTibMs1ziAtYq8RZzrz+VCiwImwRHQZaQL8HsiYZje6Y+8kvAUV/NHcPWK8iBG0BRlDgHPJAQ6+srVuluaXMKR+IYWT1BZu4gz5FKsT9epSxWnOXww9Y2rlL/enVlm+KJneXeWKfttm2XtZQ7u/SIHn0bYe3fSmbciAM0Pr5R23J/6ijsvI/SmnYRX4fzULcYJmfNNNzJ/LdIzda6JU4KGdvn5mqRulFEIz8ORv3uT3n8DmKzNWgTuRFela31WXq+B82xyVCsG1oZXOaYQnw6hbAvC+wRdqrHY+Mytu+Ks20X3ON7bWTRiWntG0usrdpyTJzWMghc6k/DMaoaRMAU1QQcW0L4TsK0JeDYEqjvxnGbmkEibIabDOCkgwTuZBKP6WRwKLTPI046SuyGYeCH1F7TMC1dTilOLikZjaL4HVKwT5uA4nCChdCpuS6aXo2ty7oi/gMAAP//AAAA//+0nY1u28gVhV8l8AM4IvW/cAKsYlu2Zl4icI1mUeymiNPd7dt3KN5DnjvnxnYKT4F20bOHQ+nTiJyPlKWrpy+Pj9+vP3///PHq29e/3n37cNFfvHv69+c/nj5cdL905f98+V6y7uLdw3+evn/9/e7xt38OSQn+7lafH375x3+vH58eHv8o2eJyub74ePUwjHIYhim1zcW78m+eSvznx65fX73/8+PV+4fy37K3aZfLYJfd9rJfv3Kv006HgT5cbMq+aaebaafnR/ZpKr23x3otyY0kt5IcOXFPZ/VWT2cYqMDf0dPZzwjHZzN1pmcjyY0kt5IcOXHPprwIMh9+5sWZp8Qw0vnp4KF+kuRakhtJbiU5SnI3JqvdRdmXezplQurT2V8WxtF8nmbWsFmZWWXazzNrsaxm1lSaXgtJbiS5leQoyZ0k95KcJEmSZE4clm2AZXv502/AYZgPF0uhXvD+/wcVvAi/DqOUCdQPw49HmTFZ793L0lcvy1SaXhZJbiS5leQoyZ0k95KcJEmS5DHZLIRbeWI6W/vLbfTCfP/y28O/Dl+fOzgXAHaU7xbRK9JflkOnHOhfMTBej/Ow5X3CR6zqXXKDzmZ6GW8RbafozqKVe2mX1Vj32PA8485T4oRoNY2VEK2Fb1fe0Ap4dVmOhq86303Hh/NAwxPHHLuzZH2ere69NpxV9d0Qs3/2oHQe6MPFupyJ5qPScltN/7k1zX+NbjS61eio0Z1G9xqdNEoWldMbHle26PzSeWThEuGnD0+dnbN1HtTn7OE4My16br9++/3z+L4a1j7rn50dv3bj2XVdHjCdPjr/Qh3QmifRJ42uNbrR6FajIyJ+k9nj4hVG16/847rHhvvphTpplDTKFgXHta5eVRTe5UGM77kKd7/+6fPQr+fxy/JznlsHi7Z8bNrt6/fKuG5wpf3Cl66jkfbVi3kTlbpl1boNh6rOX0eU+FXa7/yDurPSxpWqpe89RioH/2ke7quX+4SSX0VXpFI4VFXKKJVpNu1vOT89/xavV2bPTYnd/zElxrVPN8/iw2AnZS/b+R3xSaNrjW40utXoiGje451Fmzm6R6ujd5c9rvmslrSVEQVntXo199Yox2VeX/5B551qHh26sTX8g1q1jVlryVOyW1aWc43W6iySy75byarClp481Tqaa+fFwW24t746Yx6t1fvHJO84e35uj33VugeF8ZGvl111NDmh4M4Ny6qV/DDLfrXtNv2mgpDx9JymLOd3pX/DDatONv3hrfCjY/A2XnM+tzz5tRx9z+t1ngD7ivXBSsM5dZ4lq+rpf7KWnyWr6mh6jdYzs2R8SMsXZom13Ouvs8Se3guzZGwNp0K6JiGzZGoNF0qiWWKFF2aJG+aHs8Se3utmSa0gbz5Lhh2U1RYvYut1/qEctYeSnyVLOZaMrZeOJdZ6ZpZMDT5yVaflW3tMfm86S+zpvTBLxtZLs2Rq/WiWWOGFWeKG+eEsMQivmiXDobLpseS8g3qW1GccK70wS6z1wixB68ezZG48N0vCvckswdN7fpZY64VZMrd+MEtQeH6W+GF+NEvw9F43S2rbfutjyaBsciyp1hIHKy3ny0ifEM0LrmuL1udrMufFww1a5wXXuJ7QDY+I6FIAonmdea/RSaOkUUakVzbLdbHG78Hxmr4/UlfH4MP5UZRLgEx33G7JdMfI0bUW05UNjxie6VqL6Up0woZzK2mUEQV06ysQbz53x2sTnm69Whr+7XCBlelaxHTHyNG1FtOVDY8Ynulai+lKdMKGTFdaGa2AbnD95U3Xor3d33CrjGoVdrCSoztu5+auXTThI4O1mK5seMTwTNdaTFeiEzZkutLKaAV0g6stb0vXbsA4utXFgMNwO6+euxbx3B0jN3etxXRlwyOGZ7rWYroSnbAh05VWRiugG1y4eFu64+UAd2SoDenQjyU3dy1iumPk6FqL6cqGRwzPdK1F1zLQmlGeNEoaZUQB3eBaxtvStYsZPHdrszwMlzrquWsR0x0jR9daTFc2PGJ4pmstnrsSnbAhz11pZbQCuq2vAQw3r+sV2aoyqYOV3Nw1R2W6Y+ToWovpyoZHDM90rcV0JTphQ6YrrYxWQLe1O/eBO9dXxg5WcnTN7ZiuGTaf1azFdGXDI4ZnutZiuhKdsCHTlVZGS+kOAtfUOc87qJxzVTunlZguIqJrEc9dtIiubnjU6A4R0dXopFHSKCMK6LZ2tWXgaqva1azk6I7b8YrMWo6utZiubHjE8DR3ETFd25DOatpKGmVEAd3WrlaI6XG3djUrObrqatZydNXVMNY86Y+ImK66GlpMV1pJWxlRQLe1q5XJp3RrV7OSo6uuZi1HV10NYzFdazFddTVsyHSllbSVEQV0W7vacHVe1gy1q1nJ0VVXs5ajq66GsZiutZiuuho2ZLrSStrKiAK6rV2tfEZT6dauZiVHV13NWo6uuhrGYrrWYrrqatiQ6UoraSsjCui2drVl4Grr6m7WwUqOrrqatRxddTWMxXStxXQt4rOaRCeMRSsyjTKigG5rV1sGrrauP0ZjJUdXXc1ajq66GsZiutZiuupq2JDnrrSStjKigG5rV1sGrrauXc1Kjq66mrUcXXU1jMV0rcV01dWwIdOVVtJWRhTQbe1qy8DV1tUN/IOVHF11NWs5uupqGIvpWovpqqthQ6YrraStjEjprlq72nkHlauta1ezEtNFRK5mEdNFi2xCNzwiIrqI6Lir0UmjpFFGFNBt7WqrwNXWtatZydFVV7OWo6uuhrFo7iJiuiJm92jR3NUoaZQRBXRbu9oqcLV17WpWcnTV1azl6KqrYSymay2mq66GDZmuupq2MqKAbmtXWwWutq5dzUqOrrqatRxddTWMxXTV1dDiI4OI2UlbSaOMKKDb2tVWgauta1ezkqOrrmYtR1ddDWMxXXU1tJiu3lfTVtIoIwrotna1VeBq69rVrOToqqtZy9FVV8NYTFddDS2mq/fVtJU0yogCuq1dbRW42qZ2NSs5uupq1nJ01dUwFtNVV0OL6aqraStplBEFdFu72ipwtU3talZydNXVrOXoqqthLKarroYW09X7atpKGmVEAd3Wrlb+slGu4mxqV7OSo6uuZi1HV10NYzFddTW0mK7eV9NW0igjCui2drXhr87qK5D1h60PVnJ01dWs5eiqq2EspquuhhbT1ftq2koaZURKtzzUtvfVzjuoXG1Tu5qVmC4icjWLmC5a5Gq64RERrXcREV2NTholjTKigG5rV1sHrrapXc1Kjq66mrUcXXU1jEVzFxHTVVdDi2xCo6RRRhTQbe1q5c859chQu5qVHF11NWs5uupqGIvpqquhxXNXxOykraRRRhTQbe1qw1+yynG3djUrObrqatZydNXVMBbTVVdDi+mqq2kraZQRBXRbu9rwuXihW7ualRxddTVrObrqahiL6aqrocV01dW0lTTKiAK6rV1t+ONeoVu7mpUcXXU1azm66moYi+mqq6HFdNXVtJU0yogCuq1dbR242rZ2NSs5uupq1nJ01dUwFtNVV0OL6aqraStplBEFdFu72jpwtW3talZydNXVrOXoqqthLKarroYW01VX01bSKCMK6LZ2tXXgatva1azk6KqrWcvRVVfDWExXXQ0tpquupq2kUUYU0G3tasOXv9TH3W19X81Kjq66mrUcXXU1jMV01dXQYrrqatpKGmVESrd8tUJbVzvvoHK1be1qVmK6iMjVLGK6aJGr6YZHRGQTiIiuRieNkkYZUUC3tasN3yclc7d2NSs5uupq1nJ01dUwFs1dRExXXQ0tcjWNkkYZUUC3tasN3wMndGtXs5Kjq65mLUdXXQ1jMV11NbR47qqraStplBEFdFu72iZwtW3talZydNXVrOXoqqthLKarroYW01VX01bSKCMK6LZ2tU3gatva1azk6KqrWcvRVVfDWExXXQ0tpquupq2kUUYU0G3tapvA1ba1q1nJ0VVXs5ajq66GsZiuuhpaTFddTVtJo4wooNva1TaBq+1qV7OSo6uuZi1HV10NYzFddTW0mK66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','booking_id' => $booking['id']]);

            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),

    '0130' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-05-20'),
                'date_to'     => strtotime('2022-05-25'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_identity_id' => 15002448,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 370,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-05-20'),
                'date_to'       => strtotime('2022-05-25'),
            ]);

            $groups->update([
                'nb_pers'       => 2
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            run('do', 'lodging_composition_import', ['data'=>'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bmtXWwWutq5dzUqOrrqatRxddTWMxXTV1dDiI4OI2UlbSaOMKKDb2tVWgauta1ezkqOrrmYtR1ddDWMxXXU1tJiu3lfTVtIoIwrotna1VeBq69rVrOToqqtZy9FVV8NYTFddDS2mq/fVtJU0yogCuq1dbRW42qZ2NSs5uupq1nJ01dUwFtNVV0OL6aqraStplBEFdFu72ipwtU3talZydNXVrOXoqqthLKarroYW09X7atpKGmVEAd3Wrlb+slGu4mxqV7OSo6uuZi1HV10NYzFddTW0mK7eV9NW0igjCui2drXhr87qK5D1h60PVnJ01dWs5eiqq2EspquuhhbT1ftq2koaZURKtzzUtvfVzjuoXG1Tu5qVmC4icjWLmC5a5Gq64RERrXcREV2NTholjTKigG5rV1sHrrapXc1Kjq66mrUcXXU1jEVzFxHTVVdDi2xCo6RRRhTQbe1q5c859chQu5qVHF11NWs5uupqGIvpqquhxXNXxOykraRRRhTQbe1qw1+yynG3djUrObrqatZydNXVMBbTVVdDi+mqq2kraZQRBXRbu9rwuXihW7ualRxddTVrObrqahiL6aqrocV01dW0lTTKiAK6rV1t+ONeoVu7mpUcXXU1azm66moYi+mqq6HFdNXVtJU0yogCuq1dbR242rZ2NSs5uupq1nJ01dUwFtNVV0OL6aqraStplBEFdFu72jpwtW3talZydNXVrOXoqqthLKarroYW01VX01bSKCMK6LZ2tXXgatva1azk6KqrWcvRVVfDWExXXQ0tpquupq2kUUYU0G3tasOXv9TH3W19X81Kjq66mrUcXXU1jMV01dXQYrrqatpKGmVESrd8tUJbVzvvoHK1be1qVmK6iMjVLGK6aJGr6YZHRGQTiIiuRieNkkYZUUC3tasN3yclc7d2NSs5uupq1nJ01dUwFs1dRExXXQ0tcjWNkkYZUUC3tasN3wMndGtXs5Kjq65mLUdXXQ1jMV11NbR47qqraStplBEFdFu72iZwtW3talZydNXVrOXoqqthLKarroYW01VX01bSKCMK6LZ2tU3gatva1azk6KqrWcvRVVfDWExXXQ0tpquupq2kUUYU0G3tapvA1ba1q1nJ0VVXs5ajq66GsZiuuhpaTFddTVtJo4wooNva1TaBq+1qV7OSo6uuZi1HV10NYzFddTW0mK66mraSRhlRQLe1q20CV9vVrmYlR1ddzVqOrroaxmK66mpoMV11NW0ljTKigG5rVxu+P6teM+xqV7OSo6uuZi1HV10NYzFddTW0mK66mraSRhlRQLe1q5XvvVK6tatZydFVV7OWo6uuhrGYrroaWkxXXU1bSaOMSOluW7vaeQeVq+1qV7MS00VErmYR00WLXE03PCIim0BEdDU6aZQ0yogCuq1dbRu42q52NSs5uupq1nJ01dUwFs1dRExXXQ0tcjWNkkYZUUC3tattA1fb1a5mJUdXXc1ajq66GsZiuupqaPHcVVfTVtIoIwrotna1rblaWapPX0K2q13NSuVjxtP3c1vEf2tpkaOrrqYbHhHx3BUxu0eL5660krYyooBua1fbmquVpfpMt3Y1K5WPGc901dWs5eiqq1mLXpYjIqYrYnaPFtOVVtJWRhTQbe1qw3ddDyuyslSf6NYyYR13YFBVs5aDq6qGsfjAoKqGFh8YVNW0lTTKiAK4rVVt+KLbGm7tEtZxcNXUrOXgqqlhLIarpoYWw1VT01bSKCMK4LY2tfK9sgK3VgnrOLgqatZycFXUMBbDVVFDi+GqqGkraZQRBXBbi9rWRI0PC7VJWMfBVU+zloOrnoaxGK56GloMVz1NW0mjjCiA29rTtuZpDFdEwgyJvorMNnPLhbHl4Kqm6YZHRHxCEye7R4tPaNJK2sqIFO6utaadd1Cd0GqPsA7PXERkaRYxXLTI0nTDIyKCi4hmrkYnjZJGGVEAt7Wl7czSeObWGmEdB1clzVoOrkoaxqLDAiKGq5KGFs1cjZJGGVEAt7Wk7UzSGG5tEdZxcNXRrOXgqqNhLIarjoYWz1x1NG0ljTKiAG5rR9uZozHcWiKs4+Dq7TRrObiqaBiL4ertNLQYrt5O01bSKCMK4LZWtJ0pGsOt7/dYx8FVQ7OWg6uGhrEYrt5NQ4vh6t00bSWNMqIAbmtD2wWGVn/N/cFKjq4qmrUcXVU0jMV0VdHQYrqqaNpKGmVEAd3WiraLFK12NCs5uupo1nJ01dEwFtNVR0OL6aqjaStplBEFdFs72i5wtK6WNCs5uipp1nJ0VdIwFtNVSUOL6aqkaStplBEFdFtL2i6QtK62NCs5umpp1nJ01dIwFtNVS0OL6aqlaStplBEFdFtb2i6wtK7WNCs5uno3zVqOrmoaxmK6ejcNLaard9O0lTTKiJTuvrWmnXdQaVpXe5qVmC4i8jSLmC5a5Gm64RERqQQioqvRSaOkUUYU0G3tafvA07pa1Kzk6KqoWcvRVVHDWDR3ETFdFTW0SNQ0ShplRAHd1qK2D0Stq03NSo6umpq1HF01NYzFdNXU0OK5q6amraRRRhTQbW1q+8DUulrVrOToqqpZy9FVVcNYTFdVDS2mq6qmraRRRhTQba1q+0DVutrVrOToqqtZy9FVV8NYTFddDS2mq66mraRRRhTQbe1q+8DV+vp2mpUcXXU1azm66moYi+mqq6HFdNXVtJU0yogCuq1dbR+4Wl+7mpUcXXU1azm66moYi+mqq6HFdNXVtJU0yogCuq1dbR/dT6tdzUqOrrqatRxddTWMxXTV1dBiuupq2koaZUQB3dautg9cra9dzUqOrrqatRxddTWMxXTV1dBiuupq2koaZUQB3dautg9crf6h3YOVHF11NWs5uupqGIvpqquhxXTV1bSVNMqIlG63aC1r4x4qW+trW0OLAU8Z+RoyRjz1yNiCbY9TRlYxZYQ5yMpPlw+cys87zb3y2+WS5SmLULc2t24RqFtfqxtaHrXKG3oeterbNB7N5ylzqNXgph4pXJAV1LJtQW1ZhLq1xnWLwOPq3w48oOVRq8mh51Gry03jOdRqc1PPzWr1uaBXUEuvoLYsQt3a6bpFIHX1j/kW1CZU9KGHKXMHkLHnUavYBduWA4iq3ZQ51Cp3Qa+gll5BbVmEurXgdYvA8Pra8NDys1odDz2PWi1vGs/NavW8qedQq+kFvYJaegW1ZRHq1rbXLQLdq39busxqUy03q1X40POoVfmm8Rxqlb6p51Cr9gW9glp6BbVlEerW6tctAvdb1u6Hlp/Van/oedTqf9N4DrUa4NRzqNUBg15BLb2C2rIIdWsP7BbRTbuF/P681TxrdUEM51mrDaLHH7ueMrcEEfu7n3puCSK9wlqywtqyiHVrK+wW0S28ha6szcncIUTNEMN51uqG6HnWaodTz81r9cOgV1hLr7C2LGLd2hG7RXRDb6FLazM0x1o9EcN51mqK6HnW6opTz7FWWwx6hbX0CmvLAtZdc2E876G+vbeo75F0VnPHEGS84LPMsUaPjVG3PU774GMIesxas9O0LRuj9vLUi1g3N8Yuutm3qO+YdFbzrANltJ5nHSgjxuNzIzLHOlBG9Ph4rVmaHvPcK6x/rIxdc2U870HmtayureZZB85oPc86cEaM51gHzoiem9eBM2qvsA6cEVk0r5s7YxfdCJRPvnVW86z1XiB6nnUgjRjPsQ6kET3HOpBG7RXWgTQii1g3l8Yuui3Yyfraap51YI3W86wDa8R4jnVgjeg51oE1aq+wDqwRWcS6uTV2gTWWd9/V+z8/Xr1/+Hj18O7bh4tDZzXPOtBG63nWgTZiPMc60Eb0HOtAG7VXWAfaiCxi3Vwbu0Aby7tPWJtv8ZrPNuU/t+ks86wDb9Rtyzok8EZkjnXgjdorrANvRBaxbu6NXeSN8om5zmp+XgfeaD3POvBGjOfmtd5HnPbrWOudxKBXWAfeiCxi3dwbu8gb5fNzndU868AbredZB96I8RzrwBvRc6wDb9ReYR14I7KIdXNv7CJvlE/TdVbzrANvtJ5nHXgjxnOsA29Ez7EOvFF7hXXgjcgC1n1zbzzvoV5fy2frOqs51sjYGy1zrNFjb9Rtj9M+2GXQY9aanaZt2Ru1l6dexLq5N/aRN8on7TqredaBN1rPsw68EePxvEbmWAfeiB57o2ZpeszsjehFrJt7Yx/caizvvnodYjXPOvBG63nWgTdiPMc68Eb03LwOvFF7hXXgjcgi1s29sY+8UT6F11nNsw680XqedeCNGM+xDrwRPcc68EbtFdaBNyKLWDf3xj7yRvlMXmc1zzrwRut51oE3YjzHOvBG9BzrwBu1V1gH3ogsYt3cG/vIG3vxRqt51oE3Ws+zDrwR4znWgTei51gH3qi9wjrwRmQR6+beOHwfzvDNLWV9NH0tTnn3yfF6rJXf35y+dqizTcuvRiK7RlZ+WQfZzdRbTdltsG1Zh9g+5vXK3ZTN58v7ICvrENt27hXWkpV1iGXn1+79t69/fbwq/zNch+j6n/DG4XLh393q88Mv//jv9ePTw+MfBd7icnkxXdQ4j/bhYs+8Rrcql9WJl2SFl2SFl2SFl2SFl2SFl2SFl2SFl2SFF2cVr59wv1fwGmXI8Rojz0uywkuywkuywkuywkuywkuywkuywkuywouzitfsb/0vA5AvZc50u8vha5se/vP0/evvd4+//fMcxpNrTbPLTG2aSeVR11F5MGNU7iOUCecfzHIWnPHBlGPAcL9hfCC3X7/9/nl4dOOD7HeXr32MeIj33XkPwxCY7CeNkkZ5igqh+Yi0mP+2oHoisz1MT2R6sNXz6LaXy737z/Pv4vIkxqV5SHBeSr/djgsrHI7K5Tj8JEOb8ecl0zj+88ez5bhIKNfudS7NJ6lXjWSH4GCg+Qj8qoHGY9P5tDJOivdPXx4fv19//v754/8AAAD//wAAAP//3FbbbuM2EP0VQq+FapGSLFmwDehqO3ayqXPBbl8CJqItrXUzRdVOiv2l/kR/rCPJUZxECxToWw1YIuYMZ8jDmUONC54L9iRYuKbZlpXT8XsDosk257GI0iuasol0M7dlHRMJRbSM7mlSgc3b2/tsSX+JyNfd9z29unhxdmWU3qfzb3FytztWa7bOi0DsL8I/1uxKxY966g6/XNw/Fra+IN89ZbW6/np7q99gMxqW6THciOy3yURCJU3EKcns+fD74K66vjve3hVivTbLy2XeOhVx5uZVJiYSVuofzNtztplINsGWi4mBVvCw4DFCi3q0rG0+VqwAE7QAJ4CGyMHErCET3FWM5mCfEx3ehjWvJwRgmEGIQCXWrDGoCpqpijUD7wCrYFSRjU3LJrCErKHrOqFbhqXB/4rWBbEW+vsdPmj1HgefiillfMtcliQlejodkSlNx50ZNefkYN1y8LAO8QFxAXF7EQ8QrxcJAAl6kRkgs17EB8TvReaAzHuRBSCLXuQCkIteZAnIshdZAbLqRWyswaobgj+wMwdk1Ys4Q2vVx6djWiuzh2cHqlftsS+gSZZY6UHa9mmQwdspT8chFRRaNoZ3nGfdsRM49fcQEs8FiMchyhMmgcwk+cFJaLaDLoYGjvLDIisqccnKElqoM/qc5/zcCCWXFuI2Fgk4XeXpI2coZGj791+CVbyUUOswke5ZFScJe0FxFsb7inGUMJS1E0QuaHI+7eEIOkIfOiWpd+tjWNiRW1UcTqQ/G6WBhwx/Uj+aUft4xX7Apjc5T6uE4ikeD7rxq5VMQbE6O4EOes/RJ85Yvf0b8Vxv9kB5Fmfb/8BdE+1EXRgO0nTwDL9XynqAk656oIQ+KOC/paOVZeVHoxEfCmQ6jqAOeBJnO7h8unErDCsMObhVE84X4Tv6bW9k27YrO14QyBpWDdkxVVt2HM92CdEVRXOafJ8immcR4R7rDjQIfDLUNVfWA1+TNQNGI9dRZHWIjaEbGK7h4f6Io7OI6llE3R76qu+bskGIJ2sj4si2Z6uyZ2iaa5rayPPd3oj1/dHtWjuL6DijwNR9XTZMz5A1J1Bk2/FV2cVaoDvYs0e20bJ8TmrB40x8Kdp+jOA2f8kzqHeXZYJxBty2FxS02SXl2xiaNmEbuE2VX4k6JETRDKJpykjTRnACPN5GP8NEXsAsCT3mQuRpM4wYDRlvhpscvjSaYX0dQrYbJqoCFRSO/yZ+gYoGIssnWte2DsNNLG7zOTvlkxAsHFbcyMpEAqUIwbcA6eiY0uv64iAjnNHdmfKglGYVTRrz6UOhlqNHvkN1Zalwl6X0CDRAOYDriY9XGL4FwBw35tYP1vbmNugSTsdPeZvk7bJrPD/kxme525R1bkUzdaNZyesCBl040IVDzuGbijEx/QcAAP//AwBQSwMEFAAGAAgAAAAhACjYpOyeBgAAjxoAABMAAAB4bC90aGVtZS90aGVtZTEueG1s7Fldixs3FH0v9D8M8+74a2ZsL/EGe2xn2+wmIeuk5FFryx5lNSMzkndjQqAkj4VCaVr6UuhbC6VtIIG+pE/9KdumtCnkL/RKM/ZIa7lJ0w2kJWtYZjRHV0f3Xh19nb9wO6bOEU45YUnbrZ6ruA5ORmxMkmnbvT4clJquwwVKxoiyBLfdBebuhe133zmPtkSEY+xA/YRvobYbCTHbKpf5CIoRP8dmOIFvE5bGSMBrOi2PU3QMdmNarlUqQTlGJHGdBMVgdhj9/A0YuzKZkBF2t5fW+xSaSASXBSOa7kvbOK+iYceHVYngCx7S1DlCtO1CQ2N2PMS3hetQxAV8aLsV9eeWt8+X0VZeiYoNdbV6A/WX18srjA9rqs10erBq1PN8L+is7CsAFeu4fqMf9IOVPQVAoxH0NOOi2/S7rW7Pz7EaKHu02O41evWqgdfs19c4d3z5M/AKlNn31vCDQQheNPAKlOF9i08atdAz8AqU4YM1fKPS6XkNA69AESXJ4Rq64gf1cNnbFWTC6I4V3vK9QaOWGy9QkA2r7JJNTFgiNuVajG6xdAAACaRIkMQRixmeoBGkcYgoOUiJs0umESTeDCWMQ3GlVhlU6vBf/jz1pDyCtjDSaktewISvFUk+Dh+lZCba7vtg1dUgz5989/zJI+f5k4cn9x6f3Pvx5P79k3s/ZLaMijsomeoVn339yZ9ffuj88eirZw8+s+O5jv/1+49++elTOxA6W3jh6ecPf3v88OkXH//+7QMLvJOiAx0+JDHmzmV87FxjMfRNecFkjg/Sf1ZjGCFi1EAR2LaY7ovIAF5eIGrDdbHpvBspCIwNeHF+y+C6H6VzQSwtX4piA7jHGO2y1OqAS7ItzcPDeTK1N57Oddw1hI5sbYcoMULbn89AWYnNZBhhg+ZVihKBpjjBwpHf2CHGlt7dJMTw6x4ZpYyziXBuEqeLiNUlQ3JgJFJRaYfEEJeFjSCE2vDN3g2ny6it1z18ZCJhQCBqIT/E1HDjRTQXKLaZHKKY6g7fRSKykdxfpCMd1+cCIj3FlDn9MebcVudKCv3Vgn4JxMUe9j26iE1kKsihzeYuYkxH9thhGKF4ZuVMkkjHvscPIUWRc5UJG3yPmSNEvkMcULIx3DcINsL9YiG4DrqqUyoSRH6Zp5ZYXsTMHI8LOkFYqQzIvqHmMUleKO2nRN1/K+rZrHRa1DspsQ6tnVNSvgn3HxTwHponVzGMmfUJ7K1+v9Vv93+v35vG8tmrdiHUoOHFal2t3eONS/cJoXRfLCje5Wr1zmF6Gg+gUG0r1N5ytZWbRfCYbxQM3DRFqo6TMvEBEdF+hGawxK+qTeuU56an3JkxDit/Vaz2xPiUbbV/mMd7bJztWKtVuTvNxIMjUZRX/FU57DZEhg4axS5sZV7ta6dqt7wkIOv+ExJaYyaJuoVEY1kIUfg7EqpnZ8KiZWHRlOaXoVpGceUKoLaKCqyfHFh1tV3fy04CYFOFKB7LOGWHAsvoyuCcaaQ3OZPqGQCLiWUGFJFuSa4buyd7l6XaS0TaIKGlm0lCS8MIjXGenfrRyVnGulWE1KAnXbEcDQWNRvN1xFqKyCltoImuFDRxjttuUPfheGyEZm13Ajt/eIxnkDtcrnsRncL52Uik2YB/FWWZpVz0EI8yhyvRydQgJgKnDiVx25XdX2UDTZSGKG7VGgjCG0uuBbLyppGDoJtBxpMJHgk97FqJ9HT2CgqfaYX1q6r+6mBZk80h3PvR+Ng5oPP0GoIU8xtV6cAx4XAAVM28OSZworkSsiL/Tk1MuezqR4oqh7JyRGcRymcUXcwzuBLRFR31tvKB9pb3GRy67sKDqZxg//Ws++KpWnpOE81izjRURc6adjF9fZO8xqqYRA1WmXSrbQMvtK611DpIVOss8YJZ9yUmBI1a0ZhBTTJel2Gp2XmpSe0MFwSaJ4INflvNEVZPvOrMD/VOZ62cIJbrSpX46u5Dv51gB7dAPHpwDjyngqtQwt1DimDRl50kZ7IBQ+S2yNeI8OTMU9J271T8jhfW/LBUafr9klf3KqWm36mXOr5fr/b9aqXXrd2FiUVEcdXP7l0GcB5FF/ntiypfu4GJl0du50YsLjN1tVJWxNUNTLVm3MBk1ynOUN6wuA4B0bkT1AateqsblFr1zqDk9brNUisMuqVeEDZ6g17oN1uDu65zpMBepx56Qb9ZCqphWPKCiqTfbJUaXq3W8RqdZt/r3M2XMdDzTD5yX4B7Fa/tvwAAAP//AwBQSwMEFAAGAAgAAAAhAIyYeg3RBwAA4lkAAA0AAAB4bC9zdHlsZXMueG1s7BzZjuI48H2l/Yco73QOEhpawGj6QBppdjTa6ZX2NQQD1uRAiemhd7X/vmUn6TgNwSGQC/XMQycmtuuucpXt8aed60gvKAix701k7UaVJeTZ/gJ7q4n81/OsN5SlkFjewnJ8D03kVxTKn6a//zYOyauDfqwRIhIM4YUTeU3I5k5RQnuNXCu88TfIg1+WfuBaBF6DlRJuAmQtQtrJdRRdVQeKa2FPjka4c+0ig7hW8HO76dm+u7EInmMHk1c2liy59t2XlecH1twBUHeaYdnSThsEurQLkklY6948LrYDP/SX5AbGVfzlEttoH9yRMlIsOx0JRi43kmYqqp7BfReUHMlQAvSCKfvk6XjpeySUbH/rEWCmAZBSGtz99Pxf3oz+Bq3xZ9Nx+I/0YjnQosnKdGz7jh9IBHgHpGMtnuWi6IsHy8HzANPPlpaLndeoWacNjN3xdy4G4tNGhQISgZPOMxJN8znAlnNwkoPj3YrGe8YuCqVv6Jf0p+9a3vuRGZKZkecUn4Qqakuoch7xMygxhlXPaK1i0lGctjyrKsYrQ8N+PWKhVTxPBiezWpwyc1VsbDJycWAuJpqXsmwZxIyqiMhMVAjWFDvOm3E3qRmHhukYvCBBgTeDFyl+fn7dgBH3wGFHxph9J/h6FVivms4kQYk+FXQIfQcvKBSrB951QABBMHU/PeNmNBqO+trQ1LXBsG+o/ace09R53AN7C7RDi4k8YLRTOEyoCykCtRAI9UYzRgCHcWuot4apD3QmFBWCEKzmE3k2m+n0P6X/KXMxrIHXcz9YQFCWuHLdAEJHbdOxg5YEhg3wak3/En9DJ/EJgchlOl5ga+V7lkO9cNKD7wnRHARuE3kBHRDlX+QP3vOCThLPUbAHg4eBU7ADAJ7AXbBHhGQrcHwj+YVBTxh7MtGrAqgGeSkjvmQN64ZEeK0t8eOgVeEEN1KOBIHcHgfkMPfbohKY1c9GQRBOvq+4tZIqR8ByeFrg63L4nGA6i8hex+F87w5SmtZl+HKJXD0oQo05xzlWZey666XPUKd8MS0fg5xt+1sdYV3Ms9UobxeHuYBt6aT8nIBXERdf6WqiBfbdRQu8dd+iyHjh9sD+sYVzbiwp7HkgphT2OXV1827AIyu4bCAs6CcG/f1M5wXFjYOTqLoAkP2wslbI48U85AZs5Dg/6CL+72WaIAAp3i0lb+vOXPIFUiqQhqE5+OQRcinxY5QLiF5ALvI6aToMcLiXZG02zuu3rTtHwYxVd9h0rJVmodK3e5bFSN8/O3jluYhmhwA+1uF74BNkE1Z9Yrk5hUcvQpbDc6iWQlTaLYUYU4rlIJz0jkCmhRS62ozeOJySJh4nWm6B6kmEtrT2A/wPdKdlF2pcZFp6I9im76B3zOjslvlcgWJc/TDSTNIxoMwOEG7QARhHHwJIbcPZSgJlz9Yrcr9mGG0gLIIaeGpu4haRcpckZmL5K7GS0q/A2jyjXWSFaTRCDTLLtR81VK3HRcSMpi1EQZmp20uxyKyEbNethGU8fh6M0H4sKrmY/uUajowSigS3tViIAK9KlI9E3hyt4DEN1yEgP8bxKPJO7G17+Z/BCQza1eEEu1+uECkQrM5yqukFSkG3mbcABfBrMfVl3BMXUmUUuyElyIsETnJWuTg1Y4GrxUkQR1TkVS6CE5ikZJ13/bLXjFMpxCcpWYFFG2qPpYqaTsMUtMS5kkV3VMeZxEO5wDZrS15Y2YwBuMyyIjdJDVvaY0bVFxAfXEoYdMke5+E54T8e2GdT6wkG2bRz+8L9IqjuCVs3UaUnKxKuFtarD65m6iZ1C3Bp9UxtSVKzuipOAlJpjqGUfrYh15BjfDLIlWNkyzIpdUdQpxUOiviAvRjqeg3jXtLkilHdy1JcMa6ghNllwAeuzUan5Z377VWzMuv/SoltZ7x7KU62zLtfotyUt98oyuWICl5w7vTw7gldkIE6SkkepmJbEPglVt7CuROryYMEL4Kd1gkvU1qe6hUnbjskXQY0uj+oYBLyGuvAXCUukwITlLhaXdvOw2lPfVsYInFqkYfGnlttNxoA7kHlFnCjZXFAXsW6cSzK7gUWSFH15Bf6qbq3f+8nVopsAG9JAe2kejpHWcj9cBua9veUsNMDhUo2eVHuYbJeBt79+n+74d0v7bUNXqDoUXlopmJQRBE5oc4i0ZZ6ahEk8tZ6gm15HdlcKtqD1BE0ICdwBTt9BXunPniROfZS+rhGbvamaSEqu0Nca1z8S0PeuBU9AfLMalxA8k7uNL/G3fMi3egko7QunAlgh5ThWDJ3GDtzFPvtDLNErxqcyF8x8qQ13MoXEDi2hrjNafMtduC+PHo6ecguEEwOd8c9v9ED1g5nvbkO785LAzSLXXoknP1K6FW47LD4G3wQuS7Q0to65Pntx4mcPv/BbkUAfYm/+o5ffMKGmMjp81d6+xykqMHGAEZfQ7gsDv5K2wBP5H+f7m9Hj08zvTdU74c9o4/M3si8f+yZxsP94+NspOrqw3/pNbrGGdfxsvuD4cC0ZtyFDlzaG8TIxsD/SNsmMvcSgc/OWwPYPOwjfaB+NjW1N+urWs8YWMPecNA3ezNT0x8Hxv2TOTM52M2S1/aqiqZFFwBT4M07AnfVOthLeJVwiG8FJsHrESSUhBNKejnz9H8AAAD//wMAUEsDBBQABgAIAAAAIQDcDK3ZGgYAAGtAAAAUAAAAeGwvc2hhcmVkU3RyaW5ncy54bWzkXMtuGzcU3QfIP1zMJg5qa57SaAR5Esexs2jiBkYadEtJlMyWQyokR7Wzyj901Z2X1abIol9g/Um+pJcjO05mqAYo0G4I2IJ0SfHykNTo+NwzHj+5rDisqNJMisMg7kUBUDGVMyYWh8GPb04PhgFoQ8SMcCnoYXBFdfCkfPhgrLUBfK/Qh8GFMctRGOrpBa2I7sklFdgyl6oiBl+qRaiXipKZvqDUVDxMomgQVoSJAKayFgbzDrIAasHe1fT4NpIlQTnWrByrcmzK/rA3Dk05DvGV/XmND/o9rAg/DPIgLMdTyaUCgzPAScY2ok6lMNseb1hFNZzRX+FcVkTY1jmpGL/aNjfdw2bMBtNIL8kUh8FJa6pWNChhZ/Lim8mPFCO8nTKxgduU5c315+FDi/gedeEj6kHkJerYS9SJl6hTL1FnXqLue4l64CXq3EvUXnKzgZfcLPeSm+VecrPcS26We8nNci+5We4lN8u95Ga5l9ws95Kb5V5ys6GX3GzoJTcbesnNhl5ys6GX3GzoJTcbesnNhl5ys6GX3GzoGzcz5ckBFrv5tsZ7V981ZdK7ub65hrvi731Duqsh29XQ39Uw2NWQ72oY7moodjXEUc8BIo6d0cQZTZ3RzBntO6MDZzR3RofOaOGKJk5siRNb4sSWOLElTmyJE1vixJY4sSVObIkTW+rEljqxpU5sqRNb6sSWOrGlTmypE1vqxJY6sWVObJkTW+bEljmxZU5smRNb5sSWObFlTmyZE1vfia3vxNZ3Yus7sfWd2PpObH0ntr4LmzUJOQw1Z7JqXxBfq81adMPPiaEwoyAI05qIKW2/74wYNC8Rzsxm/XVbYx06ltVSamb7wKyGhZL18nYMh5koTr9p6DnGVBPFnJaexgNFt7aiigmpvvD57FiJEQAuxkRRMNIQDhbrYvOnobXSMLp3Hn22BpnyWGKf1xJ9We1vlV2rffMRZp8+/E6FUXSpmKY31yPIojjpxXimB3l7TY9m6H3SnaW++1v7/svqrK42ayVh9ojNcHQ2Z9NmO3C7trvSGQN3iW/Wywu0k7Wy7ph8Z08bp9YEDVQ1/t65wOLGUvWPNrB/u3HlcynEZo0WMjnhbEGMZLg6HUuYndH/MJsdq9S1qH01n8bj9t+szh68lMx+tKI4jNIQ0N6Xw6cPvwEc9c57SR5GmbX85fD44YPzzXrB0aMnzPaUKMrxyYrC5hp4c0AVXTCNp9T2AWqA1GgSxGO7+Yvby4CGlbwii+bDgdcLzWYE++GDRv8gWLsem2I/HOpis55QtdgOZGSNp95Yg2HXa2fKI1BU4MBUQRzBz9hZA1nZkTGp3qxtBPZA1naedm4GZ2intsDDb7vt2xdLXmswRM1sr5VsuijFVnh09kEzDGG7oHaAJceLGX0POB9MoWHmOmGP25/KU4YWyy44u9Yv7BVDw9F0WtM212wug4WX6mDhpTpYeKkOFl6qg4WX6mDhpTpYeKkOFl6qg4WX6mDhZeU2jrwkZ3HkJTuLIy/pWRx5yc/iyEuCFkdeMrQ48pKixZGXHC2OvCRpceRfDfezBDwn05pb9XKFchvqfFMsL6CebVA6XFqVkD8i8znjrNE32wLe94RTCaSne5Mev71X6F5LvxXdrZSq2eaPBR3BOeqDqEPCD4rTq33I4AAFyiiCZ6q+pNwqh29QTh/Bd2kCe9HjBMXXAqIUsAsAE3P59Beb8oDoCe9NOlr8s5P2DLP9JueJEhRv8D6lSop2l7cME7eDzyRnoqPoly+oUJ2+r8LT8Kf2AMdW5CUGzm4+jtpt9LKzVq+wmrOjYGSbHOWllwRQvEf5NaCXgV1jQyacomxrgeJGGmYF29kjemnlWdqDt7S2SN/f6rXKdloSZZiyJRpOAO8Pr3Cf8F3bgd/VDHTNDOyRFZ02uq7dAnsbPBafUOTF7Vrirfao9eKzOdazrHTcDNlUuHBIK2mjht284fG+K8lMYgKriFOBE7En4IscFhaWJpaYzYK7r3d11g8FFPyqirODDGu7SdFe8RpnIUhFn9JL0qwGHvPOjpFqwojWst1wog2dkC/OTYj/LKD8GwAA//8DAFBLAwQUAAYACAAAACEAN/VDPwQBAADmAwAAIwAAAHhsL3dvcmtzaGVldHMvX3JlbHMvc2hlZXQxLnhtbC5yZWxz1JO7TgMxEEV7JP7Bmh57NzyEoniTIiCloIHwAcY7u2vFHlu2gzZ/jykQJAqiSUHK0cycezWP2Xx0lr1jTMaThJpXwJC0bw31El7Xj1f3wFJW1CrrCSXsMMG8ubyYPaNVuTSlwYTECoWShCHnMBUi6QGdStwHpJLpfHQqlzD2Iii9UT2KSVXdifiTAc0ek61aCXHVXgNb70JR/pvtu85oXHq9dUj5iIQYCilaQ5sCVbHHLMEpY7OfbhNGUg4XOCoXLHLt3VfRk2+L/sOYP0ssiONGJ+ditD4Xo7enNBqiobLAF8y53Hb6PgDOxUHuMK75m6Hf1n7zH6cp9r6z+QAAAP//AwBQSwMEFAAGAAgAAAAhABQ2+CeXAQAAsA8AACcAAAB4bC9wcmludGVyU2V0dGluZ3MvcHJpbnRlclNldHRpbmdzMS5iaW7sVz1LA0EQfbeJkJwhWFqFdFpYWCS9GitBhHBFSoWIBPzCxFoEf4Bg5+9KIwg2gv6AtBLn5TJk3ezlQAw2O8fc7uzMvN19N8VcCye4xJVoHQnaaKCJbXnq6OMUN+jJuy/WJm5xgTOxzkV7GMibEhXjyiuG1cLbfcGgjOfVRqmLCLWoY4yMEiHPruD+vRDdTHbgLvMyrAJ7B/uHcSX1PcbAi0ypKswrtYHiANgSIxE9PgLer+dtN46xI0+8riueG9cxHbO+BD4CZMrA2kZgIjAQGAgMBAYCA79ngL1FYapESfsZH56ZxAWuAwMzBsrSDc/qh7XkSlEW2H+yxmoy4Ujx9bJcp1/7Xdo2Zlds7qc9Kv2urfFpV569z/QYeNKJjDvSO2te3VrXqfrG459OSZsT3/2y7sxk9bnYLqcal4Vlc8OYO0ftvTj/EMKd63huk8+jN8lZtM/s44xnyZMVK0C5sevFzf+cYvp4y+KWNaui33zRuRbVmu87EZ+1nIdJv30WjW8t7X/2a/zf/7MPzVHyDQAA//8DAFBLAwQUAAYACAAAACEAxGXCPGkBAACbAgAAEQAIAWRvY1Byb3BzL2NvcmUueG1sIKIEASigAAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAfJJfS8MwFMXfBb9DyXuXpNt0C10HOvYgCgMrim8huduCTRqS6LZvb9audf5ByEtyzv1xziX5fK+r5AOcV7WZITogKAEjaqnMZoaeymU6QYkP3Ehe1QZm6AAezYvLi1xYJmoHK1dbcEGBTyLJeCbsDG1DsAxjL7aguR9Eh4niunaah3h1G2y5eOMbwBkhV1hD4JIHjo/A1PZEdEJK0SPtu6sagBQYKtBggsd0QPGXN4DT/s+BRjlzahUONnY6xT1nS9GKvXvvVW/c7XaD3bCJEfNT/PJw/9hUTZU57koAKnIpmHDAQ+2KFQ9OCcWTBXjBtfU5PlOPm6y4Dw9x6WsF8uZQ3AE3Of793llXTpkAsshIlqVknJJpSSaMDhkZvvZznSkmaYq3cUAmsQpri3fK8/B2US5R5NFRSkma0ZJcszFldBR5P+aP1VqgPiX+l9gmpKQkU5bFk50RO0DRhP7+nYpPAAAA//8DAFBLAwQUAAYACAAAACEAWK46PtUBAADnAwAAEAAIAWRvY1Byb3BzL2FwcC54bWwgogQBKKAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACkU8tu2zAQvBfoP6i8+BRLToOgMGgGhdMghxY1YCWHXgyGXElEKZIgacHuH/U7+mNdSrCsJEUP7W0fo93Z0ZDeHFqddeCDsmZFFvOCZGCElcrUK/JQ3l18IFmI3EiurYEVOUIgN+ztG7rx1oGPCkKGI0xYkSZGt8zzIBpoeZhj22Cnsr7lEVNf57aqlIBbK/YtmJhfFsV1DocIRoK8cONAMkxcdvFfh0orEr/wWB4dEma0tJHrUrXACpqfE/rROa0Ej3g9+6KEt8FWMft0EKBpPm1SZL0FsfcqHtOMaUq3gmtY40JWcR2A5ucCvQeexNxw5QOjXVx2IKL1WVA/UM4rkj3xAInminTcK24i0k2wIelj7UL07A72SmvUW0KGC8UeKSJwaPbh9JtprK7Yogdg8FfgMGujeY1rjG3bXz8h/P+WRHM4G9c/F6RUEU/6Wm24j3/Q53KqT89uUGcgim5qIJOzBp7A15BMtav8lO+oz+yEfQGevfuGvt7JnWqdh5Cewat7+x+GzF9wXdvWcXPExhh9VuZ7eHClveURTmZ4XqTbhnuQ6J/RLGOB3qMPvE5D1g03NcgT5nUjWfdxeLdscT0v3hfoykmN5ucXyn4DAAD//wMAUEsBAi0AFAAGAAgAAAAhAEE3gs9uAQAABAUAABMAAAAAAAAAAAAAAAAAAAAAAFtDb250ZW50X1R5cGVzXS54bWxQSwECLQAUAAYACAAAACEAtVUwI/QAAABMAgAACwAAAAAAAAAAAAAAAACnAwAAX3JlbHMvLnJlbHNQSwECLQAUAAYACAAAACEAlzYzPlIEAAC4CgAADwAAAAAAAAAAAAAAAADMBgAAeGwvd29ya2Jvb2sueG1sUEsBAi0AFAAGAAgAAAAhAIE+lJfzAAAAugIAABoAAAAAAAAAAAAAAAAASwsAAHhsL19yZWxzL3dvcmtib29rLnhtbC5yZWxzUEsBAi0AFAAGAAgAAAAhAFiaawyPHQAAiL4AABgAAAAAAAAAAAAAAAAAfg0AAHhsL3dvcmtzaGVldHMvc2hlZXQxLnhtbFBLAQItABQABgAIAAAAIQAo2KTsngYAAI8aAAATAAAAAAAAAAAAAAAAAEMrAAB4bC90aGVtZS90aGVtZTEueG1sUEsBAi0AFAAGAAgAAAAhAIyYeg3RBwAA4lkAAA0AAAAAAAAAAAAAAAAAEjIAAHhsL3N0eWxlcy54bWxQSwECLQAUAAYACAAAACEA3Ayt2RoGAABrQAAAFAAAAAAAAAAAAAAAAAAOOgAAeGwvc2hhcmVkU3RyaW5ncy54bWxQSwECLQAUAAYACAAAACEAN/VDPwQBAADmAwAAIwAAAAAAAAAAAAAAAABaQAAAeGwvd29ya3NoZWV0cy9fcmVscy9zaGVldDEueG1sLnJlbHNQSwECLQAUAAYACAAAACEAFDb4J5cBAACwDwAAJwAAAAAAAAAAAAAAAACfQQAAeGwvcHJpbnRlclNldHRpbmdzL3ByaW50ZXJTZXR0aW5nczEuYmluUEsBAi0AFAAGAAgAAAAhAMRlwjxpAQAAmwIAABEAAAAAAAAAAAAAAAAAe0MAAGRvY1Byb3BzL2NvcmUueG1sUEsBAi0AFAAGAAgAAAAhAFiuOj7VAQAA5wMAABAAAAAAAAAAAAAAAAAAG0YAAGRvY1Byb3BzL2FwcC54bWxQSwUGAAAAAAwADAAmAwAAJkkAAAAA','booking_id' => $booking['id']]);
            run('do', 'lodging_payments_import' , ['data'=>'MDAwMDAyNTA1MjIxOTAwNSAgICAgICAgMDAwMjg5NjQgIFBST0dOTyBBTEVYQU5EUkUgICAgICAgICAgQ1JFR0JFQkIgICAwMDQwMTIxNDQ2NyAwMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDINCjEwMDM5MTkxMTU4Mjc5MjA5IEVVUjBCRSAgICAgICAgICAgICAgICAgIDAwMDAwMDAwMTE1ODMyNDAxOTAyMjBLQUxFTyAtIENFTlRSRSBCRUxHRSBUT1VSSUNvbXB0ZSBkJ2VudHJlcHJpc2UgQ0JDICAgICAgICAgICAgMDM5DQoyMTAwMDEwMDAwWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAwMDAwMDAwMDA5NTEzMDIwMDIyMDAwMTUwMDAwMTEwMTE1MTA4MTExMTEyNyAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMjAwMTIwMDM5MDEgMA0KMjIwMDAxMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBHS0NDQkVCQiAgICAgICAgICAgICAgICAgICAxIDANCjIzMDAwMTAwMDBCRTUwMDAxNTg4MDY2NDE4ICAgICAgICAgICAgICAgICAgICAgREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMCAxDQozMTAwMDEwMDAxWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAxNTAwMDAxMDAxREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDEgMA0KMzIwMDAxMDAwMUpFQU4gREVCT1JTVSAxNy8yMDEgICAgICAgICA1MDAwICBOYW11ciAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwIDANCjgwMzkxOTExNTgyNzkyMDkgRVVSMEJFICAgICAgICAgICAgICAgICAgMDAwMDAwMDAxMTc1MTI0MDIwMDIyMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwDQo5ICAgICAgICAgICAgICAgMDAwMDA3MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDk1MTMwICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMg0K']);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),

    '0132' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-05-20'),
                'date_to'     => strtotime('2022-05-30'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_identity_id' => 15030014,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 370,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-05-20'),
                'date_to'       => strtotime('2022-05-30'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            run('do', 'lodging_composition_import' , ['data'=>'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','booking_id' => $booking['id']]);
            run('do', 'lodging_payments_import' , ['data'=>'MDAwMDAyMjA1MjIxOTAwNSAgICAgICAgMDAwMjg5NjQgIFBST0dOTyBBTEVYQU5EUkUgICAgICAgICAgQ1JFR0JFQkIgICAwMDQwMTIxNDQ2NyAwMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDINCjEwMDM5MDY4OTMxMDExMDc3IEVVUjBCRSAgICAgICAgICAgICAgICAgIDAwMDAwMDAwMTE1ODMyNDAxOTAyMjBLQUxFTyAtIENFTlRSRSBCRUxHRSBUT1VSSUNvbXB0ZSBkJ2VudHJlcHJpc2UgQ0JDICAgICAgICAgICAgMDM5DQoyMTAwMDEwMDAwWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAwMDAwMDAwMDIxMTE0MDIwMDIyMDAwMTUwMDAwMTEwMSAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMjAwMTIwMDM5MDEgMA0KMjIwMDAxMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBHS0NDQkVCQiAgICAgICAgICAgICAgICAgICAxIDANCjIzMDAwMTAwMDBCRTUwMDAxNTg4MDY2NDE4ICAgICAgICAgICAgICAgICAgICAgREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMCAxDQozMTAwMDEwMDAxWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAxNTAwMDAxMDAxREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDEgMA0KMzIwMDAxMDAwMUpFQU4gREVCT1JTVSAxNy8yMDEgICAgICAgICA1MDAwICBOYW11ciAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwIDANCjgwMzkwNjg5MzEwMTEwNzcgRVVSMEJFICAgICAgICAgICAgICAgICAgMDAwMDAwMDAxMTc1NDI0MDIwMDIyMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwDQo5ICAgICAgICAgICAgICAgMDAwMDA3MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMjExMTQwICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMg==']);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0133' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2023-05-20'),
                'date_to'     => strtotime('2023-05-30'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_identity_id' => 15002118,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 370,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2023-05-20'),
                'date_to'       => strtotime('2023-05-30'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /* BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            run('do', 'lodging_composition_import' , ['data'=>'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','booking_id' => $booking['id']]);
            run('do', 'lodging_payments_import' , ['data'=>'MDAwMDAyMjA1MjIxOTAwNSAgICAgICAgMDAwMjg5NjQgIFBST0dOTyBBTEVYQU5EUkUgICAgICAgICAgQ1JFR0JFQkIgICAwMDQwMTIxNDQ2NyAwMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDINCjEwMDM5MDY4OTMxMDExMDc3IEVVUjBCRSAgICAgICAgICAgICAgICAgIDAwMDAwMDAwMTE1ODMyNDAxOTAyMjBLQUxFTyAtIENFTlRSRSBCRUxHRSBUT1VSSUNvbXB0ZSBkJ2VudHJlcHJpc2UgQ0JDICAgICAgICAgICAgMDM5DQoyMTAwMDEwMDAwWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAwMDAwMDAwMDIxMTE0MDIwMDIyMDAwMTUwMDAwMTEwMSAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMjAwMTIwMDM5MDEgMA0KMjIwMDAxMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBHS0NDQkVCQiAgICAgICAgICAgICAgICAgICAxIDANCjIzMDAwMTAwMDBCRTUwMDAxNTg4MDY2NDE4ICAgICAgICAgICAgICAgICAgICAgREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMCAxDQozMTAwMDEwMDAxWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAxNTAwMDAxMDAxREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDEgMA0KMzIwMDAxMDAwMUpFQU4gREVCT1JTVSAxNy8yMDEgICAgICAgICA1MDAwICBOYW11ciAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwIDANCjgwMzkwNjg5MzEwMTEwNzcgRVVSMEJFICAgICAgICAgICAgICAgICAgMDAwMDAwMDAxMTc1NDI0MDIwMDIyMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwDQo5ICAgICAgICAgICAgICAgMDAwMDA3MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMjExMTQwICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMg==']);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0134' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2021-05-20'),
                'date_to'     => strtotime('2021-05-30'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_identity_id' => 15000737,
                'customer_nature_id' => 5
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 370,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-05-20'),
                'date_to'       => strtotime('2021-05-30'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            run('do', 'lodging_composition_import' , ['data'=>'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','booking_id' => $booking['id']]);
            run('do', 'lodging_payments_import' , ['data'=>'MDAwMDAyMjA1MjIxOTAwNSAgICAgICAgMDAwMjg5NjQgIFBST0dOTyBBTEVYQU5EUkUgICAgICAgICAgQ1JFR0JFQkIgICAwMDQwMTIxNDQ2NyAwMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDINCjEwMDM5MDY4OTMxMDExMDc3IEVVUjBCRSAgICAgICAgICAgICAgICAgIDAwMDAwMDAwMTE1ODMyNDAxOTAyMjBLQUxFTyAtIENFTlRSRSBCRUxHRSBUT1VSSUNvbXB0ZSBkJ2VudHJlcHJpc2UgQ0JDICAgICAgICAgICAgMDM5DQoyMTAwMDEwMDAwWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAwMDAwMDAwMDIxMTE0MDIwMDIyMDAwMTUwMDAwMTEwMSAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMjAwMTIwMDM5MDEgMA0KMjIwMDAxMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBHS0NDQkVCQiAgICAgICAgICAgICAgICAgICAxIDANCjIzMDAwMTAwMDBCRTUwMDAxNTg4MDY2NDE4ICAgICAgICAgICAgICAgICAgICAgREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMCAxDQozMTAwMDEwMDAxWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAxNTAwMDAxMDAxREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDEgMA0KMzIwMDAxMDAwMUpFQU4gREVCT1JTVSAxNy8yMDEgICAgICAgICA1MDAwICBOYW11ciAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwIDANCjgwMzkwNjg5MzEwMTEwNzcgRVVSMEJFICAgICAgICAgICAgICAgICAgMDAwMDAwMDAxMTc1NDI0MDIwMDIyMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwDQo5ICAgICAgICAgICAgICAgMDAwMDA3MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMjExMTQwICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMg==']);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0135' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-27'),
                'date_to'     => strtotime('2022-04-30'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 12567,
                'customer_nature_id' => 4
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Rochefort',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 394,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-27'),
                'date_to'       => strtotime('2022-04-30'),
            ]);

            $groups->update([
                'nb_pers'       => 6
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0136' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-14'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15002468,
                'customer_nature_id' => 4
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Rochefort',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 395,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-14'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 4
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0137' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-08'),
                'date_to'     => strtotime('2022-04-13'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15002468,
                'customer_nature_id' => 4
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Rochefort',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 396,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-08'),
                'date_to'       => strtotime('2022-04-13'),
            ]);

            $groups->update([
                'nb_pers'       => 4
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0138' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-16'),
                'date_to'     => strtotime('2022-04-25'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15002645,
                'customer_nature_id' => 4
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Rochefort',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 394,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-16'),
                'date_to'       => strtotime('2022-04-25'),
            ]);

            $groups->update([
                'nb_pers'       => 2
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0139' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-02'),
                'date_to'     => strtotime('2022-04-07'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_identity_id' => 15002644,
                'customer_nature_id' => 4
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Rochefort',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 394,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-02'),
                'date_to'       => strtotime('2022-04-07'),
            ]);

            $groups->update([
                'nb_pers'       => 2
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0140' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-13'),
                'date_to'     => strtotime('2022-04-20'),
                'type_id'     => 1,
                'center_id'   => 26,
                'customer_identity_id' => 15002043,
                'customer_nature_id' => 4
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Han-sur-Lesse',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 395,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-13'),
                'date_to'       => strtotime('2022-04-20'),
            ]);

            $groups->update([
                'nb_pers'       => 22
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0141' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-13'),
                'date_to'     => strtotime('2022-04-20'),
                'type_id'     => 1,
                'center_id'   => 26,
                'customer_identity_id' => 15002043,
                'customer_nature_id' => 4
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Han-sur-Lesse',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 395,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-13'),
                'date_to'       => strtotime('2022-04-20'),
            ]);

            $groups->update([
                'nb_pers'       => 22
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0142' => array(
        'description'       =>  'Creating sample booking',
        'return'            =>  array('double'),
        'test'              =>  function () {
            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-23'),
                'date_to'     => strtotime('2022-04-29'),
                'type_id'     => 1,
                'center_id'   => 26,
                'customer_identity_id' => 11605,
                'customer_nature_id' => 4
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Han-sur-Lesse',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'  => 1,
                'has_pack'      => true,
                'pack_id'       => 371,
                'is_sojourn'    => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-04-23'),
                'date_to'       => strtotime('2022-04-29'),
            ]);

            $groups->update([
                'nb_pers'       => 4
            ]);

            $group = $groups->first();

            /*BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);*/

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
            run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),

    // '0135' => array(
    //     'description'       =>  'Creating sample booking',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-05-11'),
    //             'date_to'     => strtotime('2021-05-13'),
    //             'type_id'     => 1,
    //             'center_id'   => 25,
    //             'customer_identity_id' => 4670,
    //             'customer_nature_id' => 5
    //         ])->first();

    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour somewhere',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type_id'  => 1,
    //             'has_pack'      => true,
    //             'pack_id'       => 395,
    //             'is_sojourn'    => true
    //         ]);


    //         $groups->update([
    //             'date_from'     => strtotime('2021-05-11'),
    //             'date_to'       => strtotime('2021-05-13'),
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 3
    //         ]);

    //         $group = $groups->first();

    //         BookingLineGroupAgeRangeAssignment::create([
    //             'booking_id'            => $booking['id'],
    //             'booking_line_group_id' => $group['id'],
    //             'age_range_id'          => 1,
    //             'qty'                   => 3,
    //             'is_active'             => true
    //         ]);

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();
    //         run('do', 'lodging_booking_do-option', ['id' => $booking['id']]);
    //         run('do', 'lodging_booking_do-confirm', ['id' => $booking['id']]);
    //         run('do', 'lodging_composition_import' , ['data'=>'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','booking_id' => $booking['id']]);
    //         run('do', 'lodging_payments_import' , ['data'=>'MDAwMDAxMTA1MjIxOTAwNSAgICAgICAgMDAwMjg5NjQgIFBST0dOTyBBTEVYQU5EUkUgICAgICAgICAgQ1JFR0JFQkIgICAwMDQwMTIxNDQ2NyAwMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDINCjEwMDM5MTkxMTU4Mjc3Mjg2IEVVUjBCRSAgICAgICAgICAgICAgICAgIDAwMDAwMDAwMTE1ODEyNDAxOTAyMjBLQUxFTyAtIENFTlRSRSBCRUxHRSBUT1VSSUNvbXB0ZSBkJ2VudHJlcHJpc2UgQ0JDICAgICAgICAgICAgMDM5DQoyMTAwMDEwMDAwWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAwMDAwMDAwMDAxNzYwMDIwMDIyMDAwMTUwMDAwMTEwMSAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMjAwMTIwMDM5MDEgMA0KMjIwMDAxMDAwMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBHS0NDQkVCQiAgICAgICAgICAgICAgICAgICAxIDANCjIzMDAwMTAwMDBCRTUwMDAxNTg4MDY2NDE4ICAgICAgICAgICAgICAgICAgICAgREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMCAxDQozMTAwMDEwMDAxWkRaVTAxMTE1QVNDVEJCRU9OVFZBMDAxNTAwMDAxMDAxREVCT1JTVSBKRUFOICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDEgMA0KMzIwMDAxMDAwMUpFQU4gREVCT1JTVSAxNy8yMDEgICAgICAgICA1MDAwICBOYW11ciAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwIDANCjgwMzkxOTExNTgyNzcyODYgRVVSMEJFICAgICAgICAgICAgICAgICAgMDAwMDAwMDAxMTc1MTI0MDIwMDIyMCAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAwDQo5ICAgICAgICAgICAgICAgMDAwMDA3MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDE3NjAwICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgMg==']);
    //         return ($booking['price']);
    //     },
    //     'assert'            =>  function ($price) {
    //         return $price;
    //     }
    // ),
];
