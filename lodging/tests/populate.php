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

$providers = eQual::inject(['context', 'orm', 'auth', 'access']);


$tests = [
    '0101' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-01'),
                'date_to'     => strtotime('2022-04-04'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_id' => 164,
                'customer_nature_id' => 5,
                // 'status' => 'option'
              ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'has_pack'      => true,
                'pack_id'       => 1756,
            ]);

            

            $groups->update([
                'date_from'     => strtotime('2022-04-01'),
                'date_to'       => strtotime('2022-04-04'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0102' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-01'),
                'date_to'     => strtotime('2022-04-04'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_id' => 165,
                'customer_nature_id' => 5,
                // 'status' => 'option'
           ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'has_pack'      => true,
                'pack_id'       => 1756,
            ]);

            

            $groups->update([
                'date_from'     => strtotime('2022-04-01'),
                'date_to'       => strtotime('2022-04-05'),
            ]);

            $groups->update([
                'nb_pers'       => 2
            ]);


            $booking = Booking::id($booking['id'])->read(['price'])->first();
               run('do', 'lodging_booking_option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0103' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-01'),
                'date_to'     => strtotime('2022-04-04'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_id' => 162,
                'customer_nature_id' => 5,
                // 'status' => 'option'
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'has_pack'      => true,
                'pack_id'       => 1756,
            ]);

            

            $groups->update([
                'date_from'     => strtotime('2022-04-01'),
                'date_to'       => strtotime('2022-04-06'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);


            $booking = Booking::id($booking['id'])->read(['price'])->first();
               run('do', 'lodging_booking_option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0104' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-06'),
                'date_to'     => strtotime('2022-04-15'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_id' => 169,
                'customer_nature_id' => 5,
                // 'status' => 'option'
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'has_pack'      => true,
                'pack_id'       => 1756,
            ]);

            

            $groups->update([
                'date_from'     => strtotime('2022-04-06'),
                'date_to'       => strtotime('2022-04-15'),
            ]);

            $groups->update([
                'nb_pers'       => 15
            ]);


            $booking = Booking::id($booking['id'])->read(['price'])->first();
           run('do', 'lodging_booking_option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0105' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-01'),
                'date_to'     => strtotime('2022-04-04'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_id' => 170,
                'customer_nature_id' => 5,
                // 'status' => 'option'
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'has_pack'      => true,
                'pack_id'       => 1756,
            ]);

            
            $groups->update([
                'date_from'     => strtotime('2022-04-08'),
                'date_to'       => strtotime('2022-04-15'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
               run('do', 'lodging_booking_option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0106' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-15'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_id' => 160,
                'customer_nature_id' => 5,
                // 'status' => 'option'
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'has_pack'      => true,
                'pack_id'       => 1756,
            ]);

            
            $groups->update([
                'date_from'     => strtotime('2022-04-15'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
               run('do', 'lodging_booking_option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0106b' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-15'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 1,
                'center_id'   => 25,
                'customer_id' => 160,
                'customer_nature_id' => 5,
                // 'status' => 'option'
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour Villers-Sainte-Gertrude',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'has_pack'      => true,
                'pack_id'       => 1756,
            ]);

            
            $groups->update([
                'date_from'     => strtotime('2022-04-15'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
               run('do', 'lodging_booking_option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0107' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-15'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 1,
                'center_id'   => 26,
                'customer_id' => 160,
                'customer_nature_id' => 5,
                // 'status' => 'option'
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'has_pack'      => true,
                'pack_id'       => 412,
            ]);

            
            $groups->update([
                'date_from'     => strtotime('2022-04-15'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0108' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-15'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_id' => 176,
                'customer_nature_id' => 5,
                // 'status' => 'option'
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'has_pack'      => true,
                'pack_id'       => 413,
            ]);

            
            $groups->update([
                'date_from'     => strtotime('2022-04-15'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0109' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-15'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_id' => 175,
                'customer_nature_id' => 5,
                // 'status' => 'option'
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'has_pack'      => true,
                'pack_id'       => 413,
            ]);

            
            $groups->update([
                'date_from'     => strtotime('2022-04-15'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0110' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-15'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_id' => 174,
                'customer_nature_id' => 5,
                // 'status' => 'option'
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'has_pack'      => true,
                'pack_id'       => 413,
            ]);

            
            $groups->update([
                'date_from'     => strtotime('2022-04-15'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0111' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-15'),
                'date_to'     => strtotime('2022-04-17'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_id' => 173,
                'customer_nature_id' => 5,
                // 'status' => 'option'
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'has_pack'      => true,
                'pack_id'       => 413,
            ]);

            
            $groups->update([
                'date_from'     => strtotime('2022-04-15'),
                'date_to'       => strtotime('2022-04-17'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    ),
    '0112' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2022-04-17'),
                'date_to'     => strtotime('2022-04-22'),
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_id' => 172,
                'customer_nature_id' => 5,
                // 'status' => 'option'
            ])->first();

            $groups = BookingLineGroup::create([
                'booking_id'    => $booking['id'],
                'name'          => 'Séjour somewhere',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'has_pack'      => true,
                'pack_id'       => 413,
            ]);

            
            $groups->update([
                'date_from'     => strtotime('2022-04-17'),
                'date_to'       => strtotime('2022-04-22'),
            ]);

            $groups->update([
                'nb_pers'       => 3
            ]);

            $booking = Booking::id($booking['id'])->read(['price'])->first();
            run('do', 'lodging_booking_option', ['id' => $booking['id']]);
            return ($booking['price']);
        },
        'assert'            =>  function ($price) {
            return $price;
        }
    )    
];


// <?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/

// use equal\orm\ObjectManager;
// use equal\http\HttpRequest;
// use core\User;
// use core\Group;
// use lodging\sale\booking\Booking;
// use lodging\sale\booking\BookingLine;
// use lodging\sale\booking\BookingLineGroup;

// $providers = eQual::inject(['context', 'orm', 'auth', 'access']);



   
    // '0101' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {

    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-08'),
    //             'type_id'     => 1,
    //             'center_id'   => 29,
    //             'customer_id' => 124,
    //             'customer_nature_id' => 5,
    //         ])->first();

    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Rochefort',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 378
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-08')
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 3
    //         ]);

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();

    //         return ($booking['price']);
    //     },
    //     'assert'            =>  function ($price) {
    //         return ($price == 146);
    //     }
    // ),

    // '0102' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {

    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-08'),
    //             'type_id'     => 1,
    //             'center_id'   => 28,
    //             'customer_id' => 112
    //         ])->first();


    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Louvain-la-neuve',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 378
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-08')
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 3
    //         ]);

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();

    //         return ($booking['price']);
    //     },
    //     'assert'            =>  function ($price) {
          
    //         return $price == 162.95;
    //     }
    // ),

    // '0103' => [
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {
    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-10'),
    //             'type_id'     => 1,
    //             'center_id'   => 29,
    //             'customer_id' => 127
    //         ])->first();

    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Rochefort',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 379
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-10')
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 4
    //         ]);

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();

    //         return ($booking['price']);
    //     },
    //     'assert'            =>  function ($price) {
    //         return $price == 518;
    //     }
    // ],

    // '0104' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-10'),
    //             'type_id'     => 1,
    //             'center_id'   => 28,
    //             'customer_id' => 133,
    //         ])->first();

    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 379,
                
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-10'),
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 4
    //         ]);

    //     $booking = Booking::id($booking['id'])->read(['price'])->first();
    //        return ($booking['price']);


    //     },
    //     'assert'            =>  function ($price) {

    //         return $price == 600.80;
    //     }
    // ),

    // '0105' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-08'),
    //             'type_id'     => 1,
    //             'center_id'   => 29,
    //             'customer_id' => 137,
    //         ])->first();

    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Rochefort',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 365,
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-08'),
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 2
    //         ]);

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();
    //         return ($booking['price']);


    //     },
    //     'assert'            =>  function ($price) {

    //         return $price == 55;
    //     }
    // ),

    // '0106' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {

    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-08'),
    //             'type_id'     => 1,
    //             'center_id'   => 28,
    //             'customer_id' => 162,
    //         ])->first();



    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Louvain-la-Neuve',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 365,
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-08'),
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 2
    //         ]);

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();
    //         return ($booking['price']);
    //     },
    //     'assert'            =>  function ($price) {

    //         return $price == 72.30;
    //     }
    // ),

    // '0107' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-08'),
    //             'type_id'     => 1,
    //             'center_id'   => 30,
    //             'customer_id' => 155,
    //         ])->first();



    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Wanne',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 365,
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-08'),
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 2
    //         ]);
            
    //         $booking = Booking::id($booking['id'])->read(['price'])->first();
    //         return ($booking['price']);
    //     },
    //     'assert'            =>  function ($price) {
    //         return $price == 60;
    //     }
    // ),

    // '0108' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-04-08'),
    //             'date_to'     => strtotime('2021-04-11'),
    //             'type_id'     => 1,
    //             'center_id'   => 25,
    //             'customer_id' => 164,
    //         ])->first();

    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour villers-Sainte-Gertrude',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 1756,
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2021-04-08'),
    //             'date_to'       => strtotime('2021-04-11'),
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 3
    //         ]);

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();
    //         return ($booking['price']);
    //     },
    //     'assert'            =>  function ($price) {
    //         return $price == 432.90999999999997;
    //     }
    // ),
    // '0109' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'  => strtotime('2021-09-06'),
    //             'date_to'    => strtotime('2021-09-08'),
    //             'state'        => 'instance',
    //             'type_id'   => 1,
    //             'center_id' => 25,
    //             'customer_id' => 186,
    //         ])->first();


    //         $groups1 = BookingLineGroup::create([
    //             'booking_id' => $booking['id'],
    //             'name' => 'Séjour Louvain-la-Neuve',
    //             'order' => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type' => 'GA',
    //             'has_pack' => true,
    //             'pack_id' => 365,
    //         ]);


    //         $groups1->update([
    //             'date_from' => strtotime('2021-09-06'),
    //             'date_to' => strtotime('2021-09-08'),
    //         ]);

    //         $groups1->update([
    //             'nb_pers' => 2
    //         ]);

    //         $groups2  =  BookingLineGroup::create([
    //             'name'          => 'Séjour Louvain-la-Neuve',
    //             'order'         => 2,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-09-07'),
    //             'date_to'       => strtotime('2021-09-08'),
    //             'has_pack'      => true,
    //             'pack_id'       => 1756,
    //             'booking_id'    => $booking['id'],
    //             'nb_pers'       => 3
    //         ]);

    //         $groups2->update([
    //             'date_from'     => strtotime('2021-09-07'),
    //             'date_to'       => strtotime('2021-09-08'),
    //         ]);

    //         $groups2->update([
    //             'nb_pers'       => 3
    //         ]);


    //         $groups3  =  BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Louvain-la-Neuve',
    //             'order'         => 2,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 1745,
    //         ]);


    //         $groups3->update([
    //             'date_from'     => strtotime('2021-09-06'),
    //             'date_to'       => strtotime('2021-09-07'),
    //         ]);

    //         $groups3->update([
    //             'nb_pers'       => 1
    //         ]);

    //          $booking = Booking::id($booking['id'])->read(['price'])->first();
    //          return (double)(number_format($booking['price'],2));
    //     },
    //     'assert'                => function ($price) {
    //         return $price == 292.80;
    //     }
    // ),

    // '0110' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-04-10'),
    //             'date_to'     => strtotime('2021-04-11'),
    //             'type_id'     => 1,
    //             'center_id'   => 25,
    //             'customer_id' => 168,
    //         ])->first();


    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 364,
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2021-04-10'),
    //             'date_to'       => strtotime('2021-04-11'),
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 1
    //         ]);

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();
    //         return ($booking['price']);
    //     },
    //     'assert'            =>  function ($price) {
           
    //         return $price == 39.6;
    //     }
    // ),

    // '0111' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {

    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-06-15'),
    //             'date_to'     => strtotime('2021-06-20'),
    //             'type_id'     => 1,
    //             'center_id'   => 25,
    //             'customer_id' => 169,
    //         ])->first();

    //         $groups1 = BookingLineGroup::create([
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'booking_id'    => $booking['id'], 
    //             'pack_id' => 1764
    //         ]);

    //         $groups1->update([
    //             'date_from'     => strtotime('2021-06-15'),
    //             'date_to'       => strtotime('2021-06-20'),
    //         ]);

    //         $groups1->update([
    //             'nb_pers' => 45,
    //         ]);

    //         $groups2 = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 2,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 1762,    
    //         ]);

    //         $groups2->update([
    //             'date_from'     => strtotime('2021-06-15'),
    //             'date_to'       => strtotime('2021-06-20'),
    //         ]);

    //         $groups2->update([
    //             'nb_pers'       => 11
    //         ]);

    //         $groups3 = BookingLineGroup::create([
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 3,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 1758,
    //             'booking_id'    => $booking['id'],
    //         ]);

    //         $groups3->update([
    //             'date_from'     => strtotime('2021-06-15'),
    //             'date_to'       => strtotime('2021-06-20'),
    //         ]);

    //         $groups3->update([
    //             'nb_pers'       => 1
    //         ]);

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();
    //         return ($booking['price']);
    //     },
    //     'assert'            =>  function ($price) {
    //         return $price == 2446.00;
    //     }
    // ),


    // '0112' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-08'),
    //             'type_id'     => 1,
    //             'center_id'   => 25,
    //             'customer_id' => 122,
    //         ])->first();

    //         $groups1 = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Louvain-la-Neuve',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA', 
    //             'has_pack'      => true,
    //             'pack_id'       => 1367, 
    //         ]);

    //         $groups1->update([
    //             'date_from'     => strtotime('2021-06-15'),
    //             'date_to'       => strtotime('2021-06-18'),
    //         ]);

    //         $groups1->update([
    //             'nb_pers'       => 4
    //         ]);


    //         $groups2 = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Louvain-la-Neuve',
    //             'order'         => 2,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 1762, 
    //         ]);

    //         $groups2->update([
    //             'date_from'     => strtotime('2021-06-15'),
    //             'date_to'       => strtotime('2021-06-18'),
    //         ]);

    //         $groups2->update([
    //             'nb_pers'       => 12
    //         ]);

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();
    //         return ($booking['price']) ;
    //     },
    //     'assert'            =>  function ($price) {
    //         return $price == 609.78;
    //     }
    // ),


    // '0113' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2022-11-15'),
    //             'date_to'     => strtotime('2022-11-19'),
    //             'type_id'     => 4,
    //             'center_id'   => 25,
    //             'customer_id' => 99,
    //         ])->first();

        


    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 1,
    //             'rate_class_id' => 5,
    //             'sojourn_type'  => 'GA',   
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2022-11-15'),
    //             'date_to'       => strtotime('2022-11-19'),
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 434
    //         ]);

    //         $groups = $groups->first();

    //         $bookingLine1 = BookingLine::create([
    //             'booking_id'            => $booking['id'],
    //             'booking_line_group_id' => $groups['id'],
    //             'product_id'            => 341,
    //             'qty'                   => 433,
    //             'order'                 => 1
    //         ])->first();

    //         $bookingLine2 = BookingLine::create([
    //             'booking_id'            => $booking['id'],
    //             'booking_line_group_id' => $groups['id'],
    //             'product_id'            => 347,
    //             'qty'                   => 432,
    //             'order'                 => 2
    //         ])->first();

    //         $bookingLine3 = BookingLine::create([
    //             'booking_id'            => $booking['id'],
    //             'booking_line_group_id' => $groups['id'],
    //             'product_id'            => 335,
    //             'qty'                   => 434,
    //             'order'                 => 3
    //         ])->first();

    //         $bookingLine4 = BookingLine::create([
    //             'booking_id'            => $booking['id'],
    //             'booking_line_group_id' => $groups['id'],
    //             'product_id'            => 351,
    //             'qty'                   => 432,
    //             'order'                 => 4
    //         ])->first();

    //         $bookingLine5 = BookingLine::create([
    //             'booking_id'            => $booking['id'],
    //             'booking_line_group_id' => $groups['id'],
    //             'product_id'            => 850,
    //             'qty'                   => 432,
    //             'order'                 => 5
    //         ])->first();

    //         $bookingLine6 = BookingLine::create([
    //             'booking_id'            => $booking['id'],
    //             'booking_line_group_id' => $groups['id'],
    //             'product_id'            => 353,
    //             'qty'                   => 1,
    //             'order'                 => 6
    //         ])->first();

    //         $bookingLine7 = BookingLine::create([
    //             'booking_id'            => $booking['id'],
    //             'booking_line_group_id' => $groups['id'],
    //             'product_id'            => 1153,
    //             'qty'                   => 46,
    //             'order'                 => 7
    //         ])->first();

           
    //         $om = &ObjectManager::getInstance();
    //         $om->write('lodging\sale\booking\BookingLineGroup', $groups['id'], ['nb_pers' => 108]);
    //         $om->write('lodging\sale\booking\BookingLine', $bookingLine1['id'], ['qty' => 432]);
    //         $om->write('lodging\sale\booking\BookingLine', $bookingLine2['id'], ['qty' => 433]);
    //         $om->write('lodging\sale\booking\BookingLine', $bookingLine3['id'], ['qty' => 434]);
    //         $om->write('lodging\sale\booking\BookingLine', $bookingLine4['id'], ['qty' => 432]);
    //         $om->write('lodging\sale\booking\BookingLine', $bookingLine5['id'], ['qty' => 432]);
    //         $om->write('lodging\sale\booking\BookingLine', $bookingLine7['id'], ['qty' => 46]);


    //         $groups2 = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 2,
    //             'rate_class_id' => 5,
    //             'sojourn_type'  => 'GA',
    //         ]);

    //         $groups2->update([
    //             'date_from'     => strtotime('2022-11-16'),
    //             'date_to'       => strtotime('2022-11-17'),
    //         ]);

    //         $groups2->update([
    //             'nb_pers'       => 2
    //         ]);

    //         $groups2 = $groups2->first();

    //         $groups3 = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 3,
    //             'rate_class_id' => 5,
    //             'sojourn_type'  => 'GA',
    //         ]);

    //         $groups3->update([
    //             'date_from'     => strtotime('2022-11-15'),
    //             'date_to'       => strtotime('2022-11-16'),
    //         ]);

    //         $groups3->update([
    //             'nb_pers'       => 46
    //         ]);

    //         $groups3 = $groups3->first();
    //         $bookingLine8 = BookingLine::create([
    //             'booking_id'            => $booking['id'],
    //             'booking_line_group_id' => $groups2['id'],
    //             'product_id'            => 353,
    //             'qty'                   => 2,
    //             'order'                 => 1
    //         ])->first();

    //         $bookingLine9 = BookingLine::create([
    //             'booking_id'            => $booking['id'],
    //             'booking_line_group_id' => $groups3['id'],
    //             'product_id'            => 1153,
    //             'qty'                   => 46,
    //             'order'                 => 1
    //         ])->first();

    //         $om->write('lodging\sale\booking\BookingLineGroup', $groups2['id'], ['nb_pers' => 2]);
    //         $om->write('lodging\sale\booking\BookingLineGroup', $groups3['id'], ['nb_pers' => 46]);
    //         $booking = Booking::id($booking['id'])->read(['price'])->first();
    //         return ($booking['price']);
    //     },
    //     'assert'            =>  function ($price) {
    //         return ($price == 14973.539999999999);
    //     }
    // ),

    // '0114' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {

    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2022-07-28'),
    //             'date_to'     => strtotime('2021-07-31'),
    //             'type_id'     => 1,
    //             'center_id'   => 25,
    //             'customer_id' => 102,
    //         ])->first();

    //         $groups1 = BookingLineGroup::create([
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'booking_id'    => $booking['id'], 
    //         ]);

    //         $groups1->update([
    //             'date_from'     => strtotime('2022-07-28'),
    //             'date_to'       => strtotime('2022-07-31'),
    //         ]);

    //         $groups1->update([
    //             'nb_pers'       => 3
    //         ]);

    //         $groups1 = $groups1->first();

    //         $bookingLine1 = BookingLine::create([
    //             'booking_id'            => $booking['id'],
    //             'booking_line_group_id' => $groups1['id'],
    //             'product_id'            => 352,
    //             'qty'                   => 11,
    //             'order'                 => 1
    //         ])->first();

    //         $bookingLine2 = BookingLine::create([
    //             'booking_id'            => $booking['id'],
    //             'booking_line_group_id' => $groups1['id'],
    //             'product_id'            => 353,
    //             'qty'                   => 22,
    //             'order'                 => 2
    //         ])->first();

    //         $bookingLine3 = BookingLine::create([
    //             'booking_id'            => $booking['id'],
    //             'booking_line_group_id' => $groups1['id'],
    //             'product_id'            => 354,
    //             'qty'                   => 12,
    //             'order'                 => 3
    //         ])->first();

    //         $bookingLine4 = BookingLine::create([
    //             'booking_id'            => $booking['id'],
    //             'booking_line_group_id' => $groups1['id'],
    //             'product_id'            => 2102,
    //             'qty'                   => 1,
    //             'order'                 => 4
    //         ])->first();

    //         $om = &ObjectManager::getInstance();
    //         $om->write('lodging\sale\booking\BookingLineGroup', $groups1['id'], ['nb_pers' => 44]);

    //         $groups2 = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 2,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 1762,   
    //         ]);

    //         $groups2->update([
    //             'date_from'     => strtotime('2022-07-28'),
    //             'date_to'       => strtotime('2022-07-31'),
    //         ]);

    //         $groups2->update([
    //             'nb_pers'       => 12
    //         ]);

    //         $groups2 = $groups2->first();

    //         $groups3 = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 2,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 378, 
    //         ]);

    //         $groups3->update([
    //             'date_from'     => strtotime('2022-07-28'),
    //             'date_to'       => strtotime('2022-07-31'),
    //         ]);

    //         $groups3->update([
    //             'nb_pers'       => 18
    //         ]);

    //         $groups3 = $groups3->first();

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();
    //         return ($booking['price']);
    //     },
    //     'assert'            =>  function ($price) {
    //         return ($price == 2612.87);
    //     }
    // ),

    // '0115' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-10'),
    //             'type_id'     => 1,
    //             'center_id'   => 29,
    //             'customer_id' => 90,
    //         ])->first();

    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'], 
    //             'name'          => 'Séjour Rochefort',
    //             'order'         => 1,
    //             'rate_class_id' => 7,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 412,
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-10')
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 10
    //         ]);

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();
    //         return $booking['price'];
    //     },
    //     'assert'            =>  function ($price) {

    //         return ($price == 996.80);
    //     }
    // ),

    // '0116' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-10'),
    //             'type_id'     => 4,
    //             'center_id'   => 29,
    //             'customer_id' => 106,
    //         ])->first();

    //         $groups   =  BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Rochefort',
    //             'order'         => 1,
    //             'rate_class_id' => 5,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 413,
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-10')
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 11
    //         ]);

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();
    //         return $booking['price'];


    //     },
    //     'assert'            =>  function ($price) {

    //         return ($price == 1237.80);
    //     }
    // ),


    // '0117' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-09'),
    //             'type_id'     => 1,
    //             'center_id'   => 29,
    //             'customer_id' => 92,
    //         ])->first();

    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Rochefort',
    //             'order'         => 1,
    //             'rate_class_id' => 5,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 413,  
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-09')
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 10
    //         ]);

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();
    //         return $booking['price'];


    //     },
    //     'assert'            =>  function ($price) {

    //         return ($price == 790.16);
    //     }
    // ),


    // '0118' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-04-08'),
    //             'date_to'     => strtotime('2021-04-11'),
    //             'type_id'     => 4,
    //             'center_id'   => 25,
    //             'customer_id' => 10852,
    //         ])->first();

    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 1,
    //             'rate_class_id' => 7,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 1304,
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2021-04-08'),
    //             'date_to'       => strtotime('2021-04-11')
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 12
    //         ]);

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();
    //         return $booking['price'];


    //     },
    //     'assert'            =>  function ($price) {

    //         return ($price == 1013.53);
    //     }
    // ),


    // '0119' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-04-09'),
    //             'date_to'     => strtotime('2021-04-12'),
    //             'type_id'     => 4,
    //             'center_id'   => 25,
    //             'customer_id' => 120,
    //         ])->first();

    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 1,
    //             'rate_class_id' => 5,
    //             'sojourn_type'  => 'GA',
    //             'has_pack'      => true,
    //             'pack_id'       => 1484,  
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2021-04-09'),
    //             'date_to'       => strtotime('2021-04-12'),
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 12
    //         ]);

    //         $booking = Booking::id($booking['id'])->read(['price'])->first();
    //         return $booking['price'];


    //     },
    //     'assert'            =>  function ($price) {

    //         return ($price == 1192.8899999999999);
    //     }
    // ),

    // '0120' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {

            

    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-11'),
    //             'type_id'     => 4,
    //             'center_id'   => 3,
    //             'customer_id' => 11172,
    //         ])->first();

    //         $groups = BookingLineGroup::create([
    //             'booking_id'        => $booking['id'],
    //             'name'              => 'Séjour Arbrefontaine - École',
    //             'order'             => 1,
    //             'rate_class_id'     => 7,
    //             'sojourn_type'      => 'GG',
    //             'has_pack'          => true,
    //             'pack_id'           => 851,
    //         ]);

    //         $groups->update([
    //             'date_from'         => strtotime('2021-11-07'),
    //             'date_to'           => strtotime('2021-11-11'),
    //         ]);

    //         $groups->update([
    //             'nb_pers'           => 10
    //         ]);

    //          $booking = Booking::id($booking['id'])->read(['price'])->first();
    //          return $booking['price'];
    //     },
    //     'assert'            =>  function ($price) {
    //         return ($price == 1425.8);
    //     }
    // ),

    // '0121' => array(
    //     'description'  =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'       =>  array('double'),
    //     'test'         =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-10'),
    //             'type_id'     => 4,
    //             'center_id'   => 3,
    //             'customer_id' => 204,
    //         ])->first();

    
    //         $groups = BookingLineGroup::create([
    //             'booking_id'    => $booking['id'],
    //             'name'          => 'Séjour Arbrefontaine - École',
    //             'order'         => 1,
    //             'rate_class_id' => 5,
    //             'sojourn_type'  => 'GG',
    //             'has_pack'      => true,
    //             'pack_id'       => 852,
    //         ]);

    //         $groups->update([
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-10'),
    //         ]);

    //         $groups->update([
    //             'nb_pers'       => 11
    //         ]);

    //          $booking = Booking::id($booking['id'])->read(['price'])->first();
    //          return $booking['price'];
    //     },
    //     'assert'                 =>  function ($price) {
    //         return ($price == 1218.5);
    //     }
    // )


