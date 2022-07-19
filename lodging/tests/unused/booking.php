<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\orm\ObjectManager;
use equal\http\HttpRequest;
use core\User;
use core\Group;
use lodging\sale\booking\Booking;
use lodging\sale\booking\BookingLine;
use lodging\sale\booking\BookingLineGroup;
use lodging\sale\booking\BookingLineGroupAgeRangeAssignment;

$providers = eQual::inject(['context', 'orm', 'auth', 'access']);

$tests = [
    //0xxx : calls related to QN methods
    '0101 Lewyllie' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-08'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_id' => 124
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Rochefort',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 378,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-08')
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();

            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return ($price == 146);
        }
    ),

    '0102 Familie Veltjen' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-08'),
                'type_id'     => 1,
                'center_id'   => 28,
                'customer_id' => 112
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Louvain-la-neuve',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 378,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-08')
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();

            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            // faudrait juste comparer avec le booking line group price qui n'existe pas pour le moment.
            return $price == 162.95;
        }
    ),

    '0103 Jackie Buysse' => [
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-10'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_id' => 127
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Rochefort',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 379,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-10')
            ]);

            $groups->update([
                'nb_pers'       => 4
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();

            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price == 518;
        }
    ],

    '0104 Michele Malbrecq' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-10'),
                'type_id'     => 1,
                'center_id'   => 28,
                'customer_id' => 133,
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 379,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-10'),
            ]);

            $groups->update([
                'nb_pers'       => 4
            ]);

        $booking = Booking::id($booking['id'])->read(['price'])->first();
           return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price == 600.80;
        }
    ),

    '0105 Mireille Wauters' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-08'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_id' => 137,
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Rochefort',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 365,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-08'),
            ]);

            $groups->update([
                'nb_pers'       => 2
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price == 55;
        }
    ),

    '0106 Mathieu Braekeveld' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-08'),
                'type_id'     => 1,
                'center_id'   => 28,
                'customer_id' => 162,
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Louvain-la-Neuve',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 365,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-08'),
            ]);

            $groups->update([
                'nb_pers'       => 2
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price == 72.30;
        }
    ),

    '0107 Verena Müllender' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-08'),
                'type_id'     => 1,
                'center_id'   => 30,
                'customer_id' => 155,
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Wanne',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 365,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-08'),
            ]);

            $groups->update([
                'nb_pers'       => 2
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price == 60;
        }
    ),

    '0108 Olivier Signet' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-04-08'),
                'date_to'     => strtotime('2021-04-11'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_id' => 164,
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 1756,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-04-08'),
                'date_to'       => strtotime('2021-04-11'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price == 432.93;
        }
    ),
    '0109 Michèle Rochus' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'  => strtotime('2021-09-06'),
                'date_to'    => strtotime('2021-09-08'),
                'state'        => 'instance',
                'type_id'   => 1,
                'center_id' => 25,
                'customer_id' => 186,
            ])->first();

            $groups1 = BookingLineGroup::create([
                'booking_id' => $booking['id'],
                'name' => 'Séjour Louvain-la-Neuve',
                'order' => 1,
                'rate_class_id' => 4,
                'sojourn_type_id' => 1,
                'has_pack' => true,
                'pack_id' => 365,
                'is_sojourn'    => true
            ]);

            $group1 = $groups1->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group1['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups1->update([
                'date_from' => strtotime('2021-09-06'),
                'date_to' => strtotime('2021-09-08'),
            ]);

            $groups1->update([
                'nb_pers' => 2
            ]);

            $groups2  =  BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Louvain-la-Neuve',
                'order'         => 2,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 1756,
                'is_sojourn'    => true
            ]);

            $group2 = $groups2->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group2['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups2->update([
                'date_from'     => strtotime('2021-09-07'),
                'date_to'       => strtotime('2021-09-08'),
            ]);

            $groups2->update([
                'nb_pers'       => 3
            ]);

            $groups3  =  BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Louvain-la-Neuve',
                'order'         => 2,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 1745,
                'is_sojourn'    => true
            ]);

            $group3 = $groups3->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group3['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups3->update([
                'date_from'     => strtotime('2021-09-06'),
                'date_to'       => strtotime('2021-09-07'),
            ]);

            $groups3->update([
                'nb_pers'       => 1
            ]);

             $booking = Booking::id($booking['id'])->read(['price'])->first();
             return (double)(number_format($booking['price'],2));
        },
        'assert'                => function ($price) {
            return $price == 292.82;
        }
    ),

    '0110 Nina Jantke' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-04-10'),
                'date_to'     => strtotime('2021-04-11'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_id' => 168,
            ])->first();


            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 364,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-04-10'),
                'date_to'       => strtotime('2021-04-11'),
            ]);

            $groups->update([
                'nb_pers'       => 1
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            // faudrait juste comparer avec le booking line group price qui n'existe pas pour le moment
            return $price == 39.6;
        }
    ),

    '0111 Marina Niessen' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-06-15'),
                'date_to'     => strtotime('2021-06-20'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_id' => 169,
            ])->first();

            $groups1 = BookingLineGroup::create([
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'booking_id'    => $booking['id'],
                'pack_id' => 1764,
                'is_sojourn'    => true
            ]);

            $group1 = $groups1->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group1['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups1->update([
                'date_from'     => strtotime('2021-06-15'),
                'date_to'       => strtotime('2021-06-20'),
            ]);

            $groups1->update([
                'nb_pers' => 45,
            ]);

            $groups2 = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 2,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 1762,
                'is_sojourn'    => true
            ]);

            $group2 = $groups2->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group2['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups2->update([
                'date_from'     => strtotime('2021-06-15'),
                'date_to'       => strtotime('2021-06-20'),
            ]);

            $groups2->update([
                'nb_pers'       => 11
            ]);

            $groups3 = BookingLineGroup::create([
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 3,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 1758,
                'booking_id'    => $booking['id'],
                'is_sojourn'    => true
            ]);

            $group3 = $groups3->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group3['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups3->update([
                'date_from'     => strtotime('2021-06-15'),
                'date_to'       => strtotime('2021-06-20'),
            ]);

            $groups3->update([
                'nb_pers'       => 1
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price == 2446.68;
        }
    ),


    '0112 Sara Huylebroeck' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-08'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_id' => 122,
            ])->first();

            $groups1 = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Louvain-la-Neuve',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 1367,
                'is_sojourn'    => true
            ]);

            $group1 = $groups1->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group1['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups1->update([
                'date_from'     => strtotime('2021-06-15'),
                'date_to'       => strtotime('2021-06-18'),
            ]);

            $groups1->update([
                'nb_pers'       => 4
            ]);

            $groups2 = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Louvain-la-Neuve',
                'order'         => 2,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 1762,
                'is_sojourn'    => true
            ]);

            $group2 = $groups2->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group2['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups2->update([
                'date_from'     => strtotime('2021-06-15'),
                'date_to'       => strtotime('2021-06-18'),
            ]);

            $groups2->update([
                'nb_pers'       => 12
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            return ($booking['price']) ;
        },
        'assert'            =>  function ($price) {
            return $price == 609.90;
        }
    ),


    '0113 Cindy Moreels' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2022-11-15'),
                'date_to'     => strtotime('2022-11-19'),
                'type_id'     => 4,
                'center_id'   => 25,
                'customer_id' => 99,
            ])->first();


            // nb_pers devient null après créatoin du pack

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 5,
                'sojourn_type_id'=> 1,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2022-11-15'),
                'date_to'       => strtotime('2022-11-19'),
            ]);


            $group = $groups->first();

            $bookingLine1 = BookingLine::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'product_id'            => 341,
                'qty'                   => 433,
                'order'                 => 1
            ]);

            $bookingLine2 = BookingLine::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'product_id'            => 347,
                'qty'                   => 432,
                'order'                 => 2
            ]);

            $bookingLine3 = BookingLine::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'product_id'            => 335,
                'qty'                   => 434,
                'order'                 => 3
            ]);

            $bookingLine4 = BookingLine::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'product_id'            => 351,
                'qty'                   => 432,
                'order'                 => 4
            ]);

            $bookingLine5 = BookingLine::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'product_id'            => 850,
                'qty'                   => 432,
                'order'                 => 5
            ]);

            BookingLine::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'product_id'            => 353,
                'qty'                   => 1,
                'order'                 => 6
            ]);

            $bookingLine7 = BookingLine::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'product_id'            => 1153,
                'qty'                   => 46,
                'order'                 => 7
            ]);

            $groups->update([
                'nb_pers'       => 108
            ]);

            $bookingLine1->update([
                'nb_pers'       => 432
            ]);
            $bookingLine2->update([
                'nb_pers'       => 433
            ]);
            $bookingLine3->update([
                'nb_pers'       => 434
            ]);
            $bookingLine4->update([
                'nb_pers'       => 432
            ]);
            $bookingLine5->update([
                'nb_pers'       => 432
            ]);
            $bookingLine7->update([
                'nb_pers'       => 46
            ]);

            $groups2 = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 2,
                'rate_class_id' => 5,
                'sojourn_type_id'=> 1,
                'is_sojourn'    => true
            ]);

            $group2 = $groups2->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group2['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups2->update([
                'date_from'     => strtotime('2022-11-16'),
                'date_to'       => strtotime('2022-11-17'),
            ]);

            $groups2->update([
                'nb_pers'       => 2
            ]);

            $groups2 = $groups2->first();

            $groups3 = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 3,
                'rate_class_id' => 5,
                'sojourn_type_id'=> 1,
                'is_sojourn'    => true
            ]);

            $group3 = $groups3->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group3['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups3->update([
                'date_from'     => strtotime('2022-11-15'),
                'date_to'       => strtotime('2022-11-16'),
            ]);

            $groups3->update([
                'nb_pers'       => 46
            ]);

            $groups3 = $groups3->first();
            BookingLine::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $groups2['id'],
                'product_id'            => 353,
                'qty'                   => 2,
                'order'                 => 1
            ])->first();

            BookingLine::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $groups3['id'],
                'product_id'            => 1153,
                'qty'                   => 46,
                'order'                 => 1
            ])->first();

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return ($price == 13317.84);
        }
    ),

    '0114 Pélerins de St-Gregor Von Aachen' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2022-07-28'),
                'date_to'     => strtotime('2021-07-31'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_id' => 102,
            ])->first();

            $groups1 = BookingLineGroup::create([
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'booking_id'    => $booking['id'],
                'is_sojourn'    => true
            ]);

            $groups1->update([
                'date_from'     => strtotime('2022-07-28'),
                'date_to'       => strtotime('2022-07-31'),
            ]);

            $groups1->update([
                'nb_pers'       => 3
            ]);

            $group1 = $groups1->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group1['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            BookingLine::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group1['id'],
                'product_id'            => 352,
                'qty'                   => 11,
                'order'                 => 1
            ])->first();

            BookingLine::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group1['id'],
                'product_id'            => 353,
                'qty'                   => 22,
                'order'                 => 2
            ])->first();

            BookingLine::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group1['id'],
                'product_id'            => 354,
                'qty'                   => 12,
                'order'                 => 3
            ])->first();

            BookingLine::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group1['id'],
                'product_id'            => 2102,
                'qty'                   => 1,
                'order'                 => 4
            ])->first();

            $groups1->update([
                'nb_pers'       => 41
            ]);

            $groups2 = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 2,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 1762,
                'is_sojourn'    => true
            ]);

            $groups2->update([
                'date_from'     => strtotime('2022-07-28'),
                'date_to'       => strtotime('2022-07-31'),
            ]);

            $groups2->update([
                'nb_pers'       => 12
            ]);

            $group2 = $groups2->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group2['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups3 = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 2,
                'rate_class_id' => 4,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 378,
                'is_sojourn'    => true
            ]);

            $groups3->update([
                'date_from'     => strtotime('2022-07-28'),
                'date_to'       => strtotime('2022-07-31'),
            ]);

            $groups3->update([
                'nb_pers'       => 18
            ]);

            $group3 = $groups3->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group3['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return ($price == 2861.23);
        }
    ),

    '0115 Ecole de Pietrebais' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-10'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_id' => 90,
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Rochefort',
                'order'         => 1,
                'rate_class_id' => 7,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 412,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-10')
            ]);

            $groups->update([
                'nb_pers'       => 10
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            return $booking['price'];
        },
        'assert'            =>  function ($price) {
            return ($price == 801.32);
        }
    ),

    '0116 Ecole le Bon Départ' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-10'),
                'type_id'     => 4,
                'center_id'   => 29,
                'customer_id' => 106,
            ])->first();

            $groups   =  BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Rochefort',
                'order'         => 1,
                'rate_class_id' => 5,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 413,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-10')
            ]);

            $groups->update([
                'nb_pers'       => 11
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            return $booking['price'];
        },
        'assert'            =>  function ($price) {
            return ($price == 1092.80);
        }
    ),


    '0117 Collège Notre Dame de Bonsecours' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-09'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_id' => 92,
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Rochefort',
                'order'         => 1,
                'rate_class_id' => 5,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 413,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-09')
            ]);

            $groups->update([
                'nb_pers'       => 10
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            return $booking['price'];
        },
        'assert'            =>  function ($price) {
            return ($price == 701.96);
        }
    ),


    '0118 Ecole Saint Jean' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-04-08'),
                'date_to'     => strtotime('2021-04-11'),
                'type_id'     => 4,
                'center_id'   => 25,
                'customer_id' => 10852,
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 7,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 1304,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-04-08'),
                'date_to'       => strtotime('2021-04-11')
            ]);

            $groups->update([
                'nb_pers'       => 12
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            return $booking['price'];
        },
        'assert'            =>  function ($price) {
            return ($price == 962.19);
        }
    ),


    '0119 Ecole Communale de Chôdes' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-04-09'),
                'date_to'     => strtotime('2021-04-12'),
                'type_id'     => 4,
                'center_id'   => 25,
                'customer_id' => 120,
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 5,
                'sojourn_type_id'=> 1,
                'has_pack'      => true,
                'pack_id'       => 1484,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-04-09'),
                'date_to'       => strtotime('2021-04-12'),
            ]);

            $groups->update([
                'nb_pers'       => 12
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            return $booking['price'];
        },
        'assert'            =>  function ($price) {
            return ($price == 1141.56);
        }
    ),

    '0120 Ecole Communale de Muno' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            //seules les nuitées gratuites quand on est en GG ?
            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-11'),
                'type_id'     => 4,
                'center_id'   => 3,
                'customer_id' => 11172,
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'        => $booking['id'],
                'name'              => 'Séjour Arbrefontaine - École',
                'order'             => 1,
                'rate_class_id'     => 7,
                'sojourn_type_id'   => 2,
                'has_pack'          => true,
                'pack_id'           => 851,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'         => strtotime('2021-11-07'),
                'date_to'           => strtotime('2021-11-11'),
            ]);

            $groups->update([
                'nb_pers'           => 10
            ]);

             $booking = Booking::id($booking['id'])->read(['price'])->first();
             return $booking['price'];
        },
        'assert'            =>  function ($price) {
            return ($price == 1379.00);
        }
    ),

    '0121 Heilig Graf Instituut' => array(
        'description'  =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'       =>  array('double'),
        'test'         =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-10'),
                'type_id'     => 4,
                'center_id'   => 3,
                'customer_id' => 204,
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Arbrefontaine - École',
                'order'         => 1,
                'rate_class_id' => 5,
                'sojourn_type_id'=> 2,
                'has_pack'      => true,
                'pack_id'       => 852,
                'is_sojourn'    => true
            ]);

            $group = $groups->first();

            BookingLineGroupAgeRangeAssignment::create([
                'booking_id'            => $booking['id'],
                'booking_line_group_id' => $group['id'],
                'age_range_id'          => 1,
                'qty'                   => 3,
                'is_active'             => true
            ]);

            $groups->update([
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-10'),
            ]);

            $groups->update([
                'nb_pers'       => 11
            ]);

             $booking = Booking::id($booking['id'])->read(['price'])->first();
             return $booking['price'];
        },
        'assert'                 =>  function ($price) {
            return ($price == 1179.50);
        }
    )
];
