<?php

/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

// use lodging\sale\booking\Booking;
// use lodging\sale\booking\BookingLine;
// use lodging\sale\booking\BookingLineGroup;
// use lodging\sale\booking\BookingLineRentalUnitAssignement;

// $providers = eQual::inject(['context', 'orm', 'auth', 'access']);


// $tests = [
    
//     '0101' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-01'),
//                 'date_to'     => strtotime('2022-04-04'),
//                 'type_id'     => 1,
//                 'center_id'   => 25,
//                 'customer_id' => 164,
//                 'customer_nature_id' => 5,
                
//               ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour Villers-Sainte-Gertrude',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 1756,
//             ]);

            

//             $groups->update([
//                 'date_from'     => strtotime('2022-04-01'),
//                 'date_to'       => strtotime('2022-04-04'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 3
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0102' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-01'),
//                 'date_to'     => strtotime('2022-04-04'),
//                 'type_id'     => 1,
//                 'center_id'   => 25,
//                 'customer_id' => 165,
//                 'customer_nature_id' => 5,
                
//            ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour Villers-Sainte-Gertrude',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 1756,
//             ]);

            

//             $groups->update([
//                 'date_from'     => strtotime('2022-04-01'),
//                 'date_to'       => strtotime('2022-04-05'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 2
//             ]);


//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//                run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0103' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-01'),
//                 'date_to'     => strtotime('2022-04-04'),
//                 'type_id'     => 1,
//                 'center_id'   => 25,
//                 'customer_id' => 162,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour Villers-Sainte-Gertrude',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 1756,
//             ]);

            

//             $groups->update([
//                 'date_from'     => strtotime('2022-04-01'),
//                 'date_to'       => strtotime('2022-04-06'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 3
//             ]);


//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//                run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0104' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-06'),
//                 'date_to'     => strtotime('2022-04-15'),
//                 'type_id'     => 1,
//                 'center_id'   => 25,
//                 'customer_id' => 169,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour Villers-Sainte-Gertrude',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 1756,
//             ]);

            

//             $groups->update([
//                 'date_from'     => strtotime('2022-04-06'),
//                 'date_to'       => strtotime('2022-04-15'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 15
//             ]);


//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//            run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0105' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-01'),
//                 'date_to'     => strtotime('2022-04-04'),
//                 'type_id'     => 1,
//                 'center_id'   => 25,
//                 'customer_id' => 170,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour Villers-Sainte-Gertrude',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 1756,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-08'),
//                 'date_to'       => strtotime('2022-04-15'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 3
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//                run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0106' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-15'),
//                 'date_to'     => strtotime('2022-04-17'),
//                 'type_id'     => 1,
//                 'center_id'   => 25,
//                 'customer_id' => 160,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour Villers-Sainte-Gertrude',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 1756,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-15'),
//                 'date_to'       => strtotime('2022-04-17'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 3
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//                run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0106' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-15'),
//                 'date_to'     => strtotime('2022-04-17'),
//                 'type_id'     => 1,
//                 'center_id'   => 25,
//                 'customer_id' => 160,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour Villers-Sainte-Gertrude',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 1756,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-15'),
//                 'date_to'       => strtotime('2022-04-17'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 3
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//                run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0107' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-15'),
//                 'date_to'     => strtotime('2022-04-17'),
//                 'type_id'     => 1,
//                 'center_id'   => 26,
//                 'customer_id' => 160,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 412,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-15'),
//                 'date_to'       => strtotime('2022-04-17'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 3
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),




//     // Rochefort
//     '0108' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-15'),
//                 'date_to'     => strtotime('2022-04-17'),
//                 'type_id'     => 1,
//                 'center_id'   => 29,
//                 'customer_id' => 176,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 413,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-15'),
//                 'date_to'       => strtotime('2022-04-17'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 3
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0109' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-15'),
//                 'date_to'     => strtotime('2022-04-17'),
//                 'type_id'     => 1,
//                 'center_id'   => 29,
//                 'customer_id' => 175,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 413,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-15'),
//                 'date_to'       => strtotime('2022-04-17'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 3
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0110' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-15'),
//                 'date_to'     => strtotime('2022-04-17'),
//                 'type_id'     => 1,
//                 'center_id'   => 29,
//                 'customer_id' => 174,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 413,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-15'),
//                 'date_to'       => strtotime('2022-04-17'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 3
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0111' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-15'),
//                 'date_to'     => strtotime('2022-04-17'),
//                 'type_id'     => 1,
//                 'center_id'   => 29,
//                 'customer_id' => 173,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 413,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-15'),
//                 'date_to'       => strtotime('2022-04-17'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 3
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0112' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-17'),
//                 'date_to'     => strtotime('2022-04-22'),
//                 'type_id'     => 1,
//                 'center_id'   => 29,
//                 'customer_id' => 172,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 413,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-17'),
//                 'date_to'       => strtotime('2022-04-22'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 10
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0113' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-15'),
//                 'date_to'     => strtotime('2022-04-30'),
//                 'type_id'     => 1,
//                 'center_id'   => 29,
//                 'customer_id' => 175,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 413,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-15'),
//                 'date_to'       => strtotime('2022-04-30'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 3
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0114' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-23'),
//                 'date_to'     => strtotime('2022-04-30'),
//                 'type_id'     => 1,
//                 'center_id'   => 29,
//                 'customer_id' => 177,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 413,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-23'),
//                 'date_to'       => strtotime('2022-04-30'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 3
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0115' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-12'),
//                 'date_to'     => strtotime('2022-04-18'),
//                 'type_id'     => 1,
//                 'center_id'   => 29,
//                 'customer_id' => 179,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 413,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-12'),
//                 'date_to'       => strtotime('2022-04-18'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 5
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),

//     '0116' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-12'),
//                 'date_to'     => strtotime('2022-04-20'),
//                 'type_id'     => 1,
//                 'center_id'   => 29,
//                 'customer_id' => 160,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 412,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-12'),
//                 'date_to'       => strtotime('2022-04-20'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 7
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),

//     '0117' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-20'),
//                 'date_to'     => strtotime('2022-04-25'),
//                 'type_id'     => 1,
//                 'center_id'   => 29,
//                 'customer_id' => 150,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 412,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-20'),
//                 'date_to'       => strtotime('2022-04-25'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 6
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),

//     '0118' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-27'),
//                 'date_to'     => strtotime('2022-04-30'),
//                 'type_id'     => 1,
//                 'center_id'   => 29,
//                 'customer_id' => 151,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 412,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-27'),
//                 'date_to'       => strtotime('2022-04-30'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 2
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),

//     '0119' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-17'),
//                 'date_to'     => strtotime('2022-04-23'),
//                 'type_id'     => 1,
//                 'center_id'   => 29,
//                 'customer_id' => 152,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 412,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-17'),
//                 'date_to'       => strtotime('2022-04-23'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 4
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0120' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-17'),
//                 'date_to'     => strtotime('2022-04-23'),
//                 'type_id'     => 1,
//                 'center_id'   => 29,
//                 'customer_id' => 138,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 412,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-17'),
//                 'date_to'       => strtotime('2022-04-23'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 4
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
//     '0121' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-17'),
//                 'date_to'     => strtotime('2022-04-23'),
//                 'type_id'     => 1,
//                 'center_id'   => 25,
//                 'customer_id' => 140,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 412,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-17'),
//                 'date_to'       => strtotime('2022-04-23'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 4
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),

//     '0122' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-12'),
//                 'date_to'     => strtotime('2022-04-25'),
//                 'type_id'     => 1,
//                 'center_id'   => 26,
//                 'customer_id' => 141,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 412,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-12'),
//                 'date_to'       => strtotime('2022-04-25'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 2
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),

//     '0123' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-20'),
//                 'date_to'     => strtotime('2022-04-28'),
//                 'type_id'     => 1,
//                 'center_id'   => 26,
//                 'customer_id' => 143,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 412,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-20'),
//                 'date_to'       => strtotime('2022-04-28'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 12
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),

//     '0124' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-10'),
//                 'date_to'     => strtotime('2022-04-17'),
//                 'type_id'     => 1,
//                 'center_id'   => 26,
//                 'customer_id' => 144,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 412,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-10'),
//                 'date_to'       => strtotime('2022-04-17'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 12
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),

//     '0125' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-12'),
//                 'date_to'     => strtotime('2022-04-18'),
//                 'type_id'     => 1,
//                 'center_id'   => 29,
//                 'customer_id' => 138,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 412,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-12'),
//                 'date_to'       => strtotime('2022-04-18'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 12
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),

//     '0126' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-16'),
//                 'date_to'     => strtotime('2022-04-22'),
//                 'type_id'     => 1,
//                 'center_id'   => 29,
//                 'customer_id' => 137,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 412,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-16'),
//                 'date_to'       => strtotime('2022-04-22'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 12
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),

//     '0127' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-25'),
//                 'date_to'     => strtotime('2022-04-30'),
//                 'type_id'     => 1,
//                 'center_id'   => 25,
//                 'customer_id' => 125,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 370,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-25'),
//                 'date_to'       => strtotime('2022-04-30'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 3
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),

//     '0128' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-25'),
//                 'date_to'     => strtotime('2022-04-30'),
//                 'type_id'     => 1,
//                 'center_id'   => 25,
//                 'customer_id' => 126,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 370,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-25'),
//                 'date_to'       => strtotime('2022-04-30'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 2
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),

//     '0129' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-25'),
//                 'date_to'     => strtotime('2022-04-30'),
//                 'type_id'     => 1,
//                 'center_id'   => 25,
//                 'customer_id' => 127,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 370,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-25'),
//                 'date_to'       => strtotime('2022-04-30'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 2
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),

//     '0130' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-25'),
//                 'date_to'     => strtotime('2022-04-30'),
//                 'type_id'     => 1,
//                 'center_id'   => 25,
//                 'customer_id' => 128,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 370,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-25'),
//                 'date_to'       => strtotime('2022-04-30'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 2
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),

//     '0131' => array(
//         'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
//         'return'            =>  array('double'),
//         'test'              =>  function () {


//             $booking = Booking::create([
//                 'date_from'   => strtotime('2022-04-25'),
//                 'date_to'     => strtotime('2022-04-30'),
//                 'type_id'     => 1,
//                 'center_id'   => 25,
//                 'customer_id' => 129,
//                 'customer_nature_id' => 5,
                
//             ])->first();

//             $groups = BookingLineGroup::create([
//                 'booking_id'    => $booking['id'],
//                 'name'          => 'Séjour somewhere',
//                 'order'         => 1,
//                 'rate_class_id' => 4,
//                 'sojourn_type'  => 'GA',
//                 'has_pack'      => true,
//                 'pack_id'       => 370,
//             ]);

            
//             $groups->update([
//                 'date_from'     => strtotime('2022-04-25'),
//                 'date_to'       => strtotime('2022-04-30'),
//             ]);

//             $groups->update([
//                 'nb_pers'       => 3
//             ]);

//             $booking = Booking::id($booking['id'])->read(['price'])->first();
//             run('do', 'lodging_booking_option', ['id' => $booking['id']]);
//             return ($booking['price']);
//         },
//         'assert'            =>  function ($price) {
//             return $price;
//         }
//     ),
// ];
