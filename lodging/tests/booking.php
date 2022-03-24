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
    //0xxx : calls related to QN methods
    '0101' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {

            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-08'),
                'state'       => 'instance',
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_id' => 124,
            ]);


            $booking_id = $booking->first();
           
            $bookingLineGroup = BookingLineGroup::create([
                'name'          => 'Séjour Rochefort',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-08'),
                'has_pack'      => true,
                'pack_id'       => 378,
                'booking_id'    => $booking_id['id'],
                'nb_pers'       => 3
            ])->read(['price'])->first();


            $booking_price = $booking->read('price')->first();
            return ($bookingLineGroup['price']);
        },
        'assert'            =>  function ($price) {
            return ($price == 146);
        }
    ),

    '0102' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-08'),
                'state'       => 'instance',
                'type_id'     => 1,
                'center_id'   => 28,
                'customer_id' => 112
            ]);

            $booking_id = $booking->first();

            
            $bookingLineGroup = BookingLineGroup::create([
                'name'          => 'Séjour Louvain-la-neuve',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-08'),
                'has_pack'      => true,
                'pack_id'       => 378,
                'booking_id'    => $booking_id['id'],
                'nb_pers'       => 3
            ])->read(['price'])->first();

            $booking_price = $booking->read(['price'])->first();
            return ($booking_price['price']);

        },
        'assert'            =>  function ($price) {
            // faudrait juste comparer avec le booking line group price qui n'existe pas pour le moment.
            return $price == 162.95;
        }
    ),
    '0103' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-10'),
                'state'       => 'instance',
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_id' => 127,
            ]);

            $booking_id = $booking->first();
          

            $bookingLineGroup = BookingLineGroup::create([
                'name'          => 'Séjour Rochefort',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-10'),
                'has_pack'      => true,
                'pack_id'       => 379,
                'booking_id'    => $booking_id['id'],
                'nb_pers'       => 4
            ])->read(['price'])->first();

            $booking_price = $booking->read(['price'])->first(); 
            return ($booking_price['price']);


        },
        'assert'            =>  function ($price) {
            return $price == 518;
        }
    ),

    '0104' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-10'),
                'state'       => 'instance',
                'type_id'     => 1,
                'center_id'   => 28,
                'customer_id' => 133,
            ]);

            $booking_id = $booking->first();
    

            $bookingLineGroup = BookingLineGroup::create([
                'name'          => 'Séjour Louvain-la-neuve',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-10'),
                'has_pack'      => true,
                'pack_id'       => 379,
                'booking_id'    => $booking_id['id'],
                'nb_pers'       => 4
            ])->read(['price'])->first();

           $booking_price = $booking->read(['price'])->first();  
           return ($booking_price['price']);


        },
        'assert'            =>  function ($price) {
            
            return $price == 600.80;
        }
    ),

    '0105' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-08'),
                'state'       => 'instance',
                'type_id'     => 1,
                'center_id'   => 29,
                'customer_id' => 137,
            ]);

            $booking_id = $booking->first();

            
            $bookingLineGroup = BookingLineGroup::create([
                'name'          => 'Séjour Rochefort',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-08'),
                'has_pack'      => true,
                'pack_id'       => 365,
                'booking_id'    => $booking_id['id'],
                'nb_pers'       => 2
            ])->read(['price'])->first();

            $booking_price = $booking->read(['price'])->first(); 
            return ($booking_price['price']);


        },
        'assert'            =>  function ($price) {
            
            return $price == 55;
        }
    ),

    '0106' => array(
        'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
        'return'            =>  array('double'),
        'test'              =>  function () {


            $booking = Booking::create([
                'date_from'   => strtotime('2021-11-07'),
                'date_to'     => strtotime('2021-11-08'),
                'state'       => 'instance',
                'type_id'     => 1,
                'center_id'   => 28,
                'customer_id' => 162,
            ]);

             $booking_id = $booking->first();
            
            $bookingLineGroup = BookingLineGroup::create([
                'name'          => 'Séjour Louvain-la-Neuve',
                'order'         => 1,
                'rate_class_id' => 4,
                'sojourn_type'  => 'GA',
                'date_from'     => strtotime('2021-11-07'),
                'date_to'       => strtotime('2021-11-08'),
                'has_pack'      => true,
                'pack_id'       => 365,
                'booking_id'    => $booking_id['id'],
                'nb_pers'       => 2
            ])->read(['price'])->first();

            $booking_price = $booking->read(['price'])->first(); 
            return ($booking_price['price']);


        },
        'assert'            =>  function ($price) {
            
            return $price == 72.30;
        }
    ),

    // '0107' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-08'),
    //             'state'       => 'instance',
    //             'type_id'     => 1,
    //             'center_id'   => 30,
    //             'customer_id' => 155,
    //         ]);

    //         $booking_id = $booking->first();

            
    //         $bookingLineGroup = BookingLineGroup::create([
    //             'name'          => 'Séjour Wanne',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-08'),
    //             'has_pack'      => true,
    //             'pack_id'       => 365,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 2
    //         ])->read(['price'])->first();

    //         $booking_price = $booking->read(['price'])->first(); 
    //         return ($booking_price['price']);


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
    //             'state'       => 'instance',
    //             'type_id'     => 1,
    //             'center_id'   => 25,
    //             'customer_id' => 164,
    //         ]);

    //          $booking_id = $booking->first();
            

    //         $bookingLineGroup = BookingLineGroup::create([
    //             'name'          => 'Séjour villers-Sainte-Gertrude',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-04-08'),
    //             'date_to'       => strtotime('2021-04-11'),
    //             'has_pack'      => true,
    //             'pack_id'       => 1756,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 3
    //         ])->read(['price'])->first();

    //         $booking_price = $booking->read(['price'])->first(); 
    //         return ($bookingLineGroup['price']);


    //     },
    //     'assert'            =>  function ($price) {
            
    //         return $price == 432.91;
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
    //         ]);

    //         $booking_id = $booking->first();
            
    //         $bookingLineGroup1 = BookingLineGroup::create([
    //             'name' => 'Séjour Louvain-la-Neuve',
    //             'order' => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type' => 'GA',
    //             'date_from' => strtotime('2021-09-06'),
    //             'date_to' => strtotime('2021-09-08'),
    //             'has_pack' => true,
    //             'pack_id' => 365,
    //             'booking_id' => $booking_id['id'],
    //             'nb_pers' => 2
    //         ])->read(['price'])->first();




    //         $bookingLineGroup2  =  BookingLineGroup::create([
    //             'name'          => 'Séjour Louvain-la-Neuve',
    //             'order'         => 2,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-09-07'),
    //             'date_to'       => strtotime('2021-09-08'),
    //             'has_pack'      => true,
    //             'pack_id'       => 1756,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 3
    //         ]);




    //         $bookingLineGroup3  =  BookingLineGroup::create([
    //             'name'          => 'Séjour Louvain-la-Neuve',
    //             'order'         => 2,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-09-06'),
    //             'date_to'       => strtotime('2021-09-07'),
    //             'has_pack'      => true,
    //             'pack_id'       => 1745,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 1
    //         ])->read(['price'])->first();
    //          $booking_price = $booking->read(['price'])->first();
    //          return (double)(number_format($booking_price['price'],2));
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
    //             'state'       => 'instance',
    //             'type_id'     => 1,
    //             'center_id'   => 25,
    //             'customer_id' => 168,
    //         ]);

    //         $booking_id = $booking->first();


    //         $bookingLineGroup = BookingLineGroup::create([
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-04-10'),
    //             'date_to'       => strtotime('2021-04-11'),
    //             'has_pack'      => true,
    //             'pack_id'       => 364,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 1
    //         ])->read(['price'])->first();
    // $booking_price = $booking->read(['price'])->first();             
    //         return ($booking_price['price']);
    //     },
    //     'assert'            =>  function ($price) {
    //         // faudrait juste comparer avec le booking line group price qui n'existe pas pour le moment 
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
    //             'state'       => 'instance',
    //             'type_id'     => 1,
    //             'center_id'   => 25,
    //             'customer_id' => 169,
    //         ]);

    //         $booking_id = $booking->first();

    //         $bookingLineGroup1 = BookingLineGroup::create([
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-06-15'),
    //             'date_to'       => strtotime('2021-06-20'),
    //             'has_pack'      => true,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers' => 45,
    //             'pack_id' => 1764
    //         ])->read(['price'])->first();

    //         $bookingLineGroup2 = BookingLineGroup::create([
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 2,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-06-15'),
    //             'date_to'       => strtotime('2021-06-20'),
    //             'has_pack'      => true,
    //             'pack_id'       => 1762,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 11
    //         ])->read(['price'])->first();


    //         $bookingLineGroup3 = BookingLineGroup::create([
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 3,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-06-15'),
    //             'date_to'       => strtotime('2021-06-20'),
    //             'has_pack'      => true,
    //             'pack_id'       => 1758,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 1
    //         ]);

    //         $booking_price = $booking->read(['price', 'total'])->first();
    //         return ($booking_price['price']);
    //     },
    //     'assert'            =>  function ($price) {
    //         return $price == 2782.09;
    //     }
    // ),
    // '0112' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-08'),
    //             'state'       => 'instance',
    //             'type_id'     => 1,
    //             'center_id'   => 25,
    //             'customer_id' => 122,
    //         ]);

    //         $booking_id = $booking->first();         

    //         $bookingLineGroup1 = BookingLineGroup::create([
    //             'name'          => 'Séjour Louvain-la-Neuve',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-06-15'),
    //             'date_to'       => strtotime('2021-06-18'),
    //             'has_pack'      => true,
    //             'pack_id'       => 1367,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 4
    //         ])->read(['price'])->first();

    //         // return ($bookingLineGroup1['price'] != 195) ;

    //         $bookingLineGroup2 = BookingLineGroup::create([
    //             'name'          => 'Séjour Louvain-la-Neuve',
    //             'order'         => 2,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-06-15'),
    //             'date_to'       => strtotime('2021-06-18'),
    //             'has_pack'      => true,
    //             'pack_id'       => 1762,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 12
    //         ])->read(['price'])->first();

    //         $booking_price = $booking->read(['price'])->first();
    //         return ($booking_price['price']) ;
    //         // TODO / Carte de Membres et frais de dossiers // carte petit gîte ?
    //     },
    //     'assert'            =>  function ($price) {
    //         return $price == 685.79;
    //     }
    // ),
    // '0113' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2022-11-15'),
    //             'date_to'     => strtotime('2022-11-19'),
    //             'state'       => 'instance',
    //             'type_id'     => 4,
    //             'center_id'   => 25,
    //             'customer_id' => 99,
    //         ]);

    //         $booking_id = $booking->first();

    //         // nb_pers devient null après créatoin du pack

    //         $bookingLineGroup = BookingLineGroup::create([
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 1,
    //             'rate_class_id' => 5,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2022-11-15'),
    //             'date_to'       => strtotime('2022-11-19'),
    //             'booking_id'    => $booking_id['id'],
    //         ])->read(['price'])->first();

            

    //         $bookingLine1 = BookingLine::create([
    //             'booking_id'            => $booking_id['id'],
    //             'booking_line_group_id' => $bookingLineGroup['id'],
    //             'product_id'            => 341,
    //             'qty'                   => 433,
    //             'order'                 => 1
    //         ])->first();

    //         $bookingLine2 = BookingLine::create([
    //             'booking_id'            => $booking_id['id'],
    //             'booking_line_group_id' => $bookingLineGroup['id'],
    //             'product_id'            => 347,
    //             'qty'                   => 432,
    //             'order'                 => 2
    //         ])->first();

    //         $bookingLine3 = BookingLine::create([
    //             'booking_id'            => $booking_id['id'],
    //             'booking_line_group_id' => $bookingLineGroup['id'],
    //             'product_id'            => 335,
    //             'qty'                   => 434,
    //             'order'                 => 3
    //         ])->first();

    //         $bookingLine4 = BookingLine::create([
    //             'booking_id'            => $booking_id['id'],
    //             'booking_line_group_id' => $bookingLineGroup['id'],
    //             'product_id'            => 351,
    //             'qty'                   => 432,
    //             'order'                 => 4
    //         ])->first();

    //         $bookingLine5 = BookingLine::create([
    //             'booking_id'            => $booking_id['id'],
    //             'booking_line_group_id' => $bookingLineGroup['id'],
    //             'product_id'            => 850,
    //             'qty'                   => 432,
    //             'order'                 => 5
    //         ])->first();

    //         $bookingLine6 = BookingLine::create([
    //             'booking_id'            => $booking_id['id'],
    //             'booking_line_group_id' => $bookingLineGroup['id'],
    //             'product_id'            => 353,
    //             'qty'                   => 1,
    //             'order'                 => 6
    //         ])->first();

    //         $bookingLine7 = BookingLine::create([
    //             'booking_id'            => $booking_id['id'],
    //             'booking_line_group_id' => $bookingLineGroup['id'],
    //             'product_id'            => 1153,
    //             'qty'                   => 46,
    //             'order'                 => 7
    //         ])->first();

    //         $om = &ObjectManager::getInstance();
    //         $om->write('lodging\sale\booking\BookingLineGroup', $bookingLineGroup['id'], ['nb_pers' => 108]);
    //         $om->write('lodging\sale\booking\BookingLine', $bookingLine1['id'], ['qty' => 432]);
    //         $om->write('lodging\sale\booking\BookingLine', $bookingLine2['id'], ['qty' => 433]);
    //         $om->write('lodging\sale\booking\BookingLine', $bookingLine3['id'], ['qty' => 434]);
    //         $om->write('lodging\sale\booking\BookingLine', $bookingLine4['id'], ['qty' => 432]);
    //         $om->write('lodging\sale\booking\BookingLine', $bookingLine5['id'], ['qty' => 432]);
    //         $om->write('lodging\sale\booking\BookingLine', $bookingLine7['id'], ['qty' => 46]);


    //         $bookingLineGroup2 = BookingLineGroup::create([
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 2,
    //             'rate_class_id' => 5,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2022-11-16'),
    //             'date_to'       => strtotime('2022-11-17'),
    //             'booking_id'    => $booking_id['id'],
    //         ])->read(['price'])->first();

    //         $bookingLineGroup3 = BookingLineGroup::create([
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 3,
    //             'rate_class_id' => 5,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2022-11-15'),
    //             'date_to'       => strtotime('2022-11-16'),
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 46
    //         ])->read(['price'])->first();


    //         $bookingLine8 = BookingLine::create([
    //             'booking_id'            => $booking_id['id'],
    //             'booking_line_group_id' => $bookingLineGroup2['id'],
    //             'product_id'            => 353,
    //             'qty'                   => 2,
    //             'order'                 => 1
    //         ])->first();

    //         $bookingLine9 = BookingLine::create([
    //             'booking_id'            => $booking_id['id'],
    //             'booking_line_group_id' => $bookingLineGroup3['id'],
    //             'product_id'            => 1153,
    //             'qty'                   => 46,
    //             'order'                 => 1
    //         ])->first();

    //         $om->write('lodging\sale\booking\BookingLineGroup', $bookingLineGroup2['id'], ['nb_pers' => 2]);
    //         $om->write('lodging\sale\booking\BookingLineGroup', $bookingLineGroup3['id'], ['nb_pers' => 46]);
    //         $booking_price = $booking->read(['price'])->first();
    //         return ($booking_price['price']);
    //     },
    //     'assert'            =>  function ($price) {
    //         return ($price == 19556.1);
    //     }
    // ),
    // '0114' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {

    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2022-07-28'),
    //             'date_to'     => strtotime('2021-07-31'),
    //             'state'       => 'instance',
    //             'type_id'     => 1,
    //             'center_id'   => 25,
    //             'customer_id' => 102,
    //         ]);

    //         $booking_id = $booking->first();
            

    //         $bookingLineGroup1 = BookingLineGroup::create([
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 1,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2022-07-28'),
    //             'date_to'       => strtotime('2022-07-31'),
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 3
    //         ])->read(['price'])->first();

    //         $bookingLine1 = BookingLine::create([
    //             'booking_id'            => $booking_id['id'],
    //             'booking_line_group_id' => $bookingLineGroup1['id'],
    //             'product_id'            => 352,
    //             'qty'                   => 11,
    //             'order'                 => 1
    //         ])->first();

    //         $bookingLine2 = BookingLine::create([
    //             'booking_id'            => $booking_id['id'],
    //             'booking_line_group_id' => $bookingLineGroup1['id'],
    //             'product_id'            => 353,
    //             'qty'                   => 22,
    //             'order'                 => 2
    //         ])->first();

    //         $bookingLine3 = BookingLine::create([
    //             'booking_id'            => $booking_id['id'],
    //             'booking_line_group_id' => $bookingLineGroup1['id'],
    //             'product_id'            => 354,
    //             'qty'                   => 12,
    //             'order'                 => 3
    //         ])->first();

    //         $bookingLine4 = BookingLine::create([
    //             'booking_id'            => $booking_id['id'],
    //             'booking_line_group_id' => $bookingLineGroup1['id'],
    //             'product_id'            => 2102,
    //             'qty'                   => 1,
    //             'order'                 => 4
    //         ])->first();

    //         $om = &ObjectManager::getInstance();
    //         $om->write('lodging\sale\booking\BookingLineGroup', $bookingLineGroup1['id'], ['nb_pers' => 44]);
    //         $om->write('lodging\sale\booking\BookingLine', $bookingLine1['id'], ['qty' => 11]);
    //         $om->write('lodging\sale\booking\BookingLine', $bookingLine2['id'], ['qty' => 22]);
    //         $om->write('lodging\sale\booking\BookingLine', $bookingLine3['id'], ['qty' => 12]);
           
    //         $bookingLineGroup2 = BookingLineGroup::create([
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 2,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2022-07-28'),
    //             'date_to'       => strtotime('2022-07-31'),
    //             'has_pack'      => true,
    //             'pack_id'       => 1762,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 12
    //         ])->read(['price'])->first();

    //         $bookingLineGroup3 = BookingLineGroup::create([
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 2,
    //             'rate_class_id' => 4,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2022-07-28'),
    //             'date_to'       => strtotime('2022-07-31'),
    //             'has_pack'      => true,
    //             'pack_id'       => 378,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 18
    //         ])->read(['price'])->first();

    //         $booking_price = $booking->read(['price'])->first();
    //         return ($booking_price['price']);
    //     },
    //     'assert'            =>  function ($price) {
    //         return ($price == 2845.99);
    //     }
    // ),

    // '0115' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-10'),
    //             'state'       => 'instance',
    //             'type_id'     => 1,
    //             'center_id'   => 29,
    //             'customer_id' => 90,
    //         ]);

    //         $booking_id = $booking->first();
            

    //         $bookingLineGroup = BookingLineGroup::create([
    //             'name'          => 'Séjour Rochefort',
    //             'order'         => 1,
    //             'rate_class_id' => 7,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-10'),
    //             'has_pack'      => true,
    //             'pack_id'       => 412,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 10
    //         ])->read(['price'])->first();
    //         $booking_price = $booking->read(['price'])->first();
    //         return $booking_price['price'];
    //     },
    //     'assert'            =>  function ($price) {

    //         return ($price == 1089.44);
    //     }
    // ),

    // '0116' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-10'),
    //             'state'       => 'instance',
    //             'type_id'     => 4,
    //             'center_id'   => 29,
    //             'customer_id' => 106,
    //         ]);

    //         $booking_id = $booking->first();
            

    //         $bookingLineGroup   =  BookingLineGroup::create([
    //             'name'          => 'Séjour Rochefort',
    //             'order'         => 1,
    //             'rate_class_id' => 5,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-10'),
    //             'has_pack'      => true,
    //             'pack_id'       => 413,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 11
    //         ])->read(['price'])->first();

    //         $booking_price = $booking->read(['price'])->first();
    //         return $booking_price['price'];


    //     },
    //     'assert'            =>  function ($price) {

    //         return ($price == 1335.8);
    //     }
    // ),


    // '0117' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-09'),
    //             'state'       => 'instance',
    //             'type_id'     => 1,
    //             'center_id'   => 29,
    //             'customer_id' => 92,
    //         ]);

    //         $booking_id = $booking->first();
    //         //         return $booking_price['total'];
            

    //         $bookingLineGroup = BookingLineGroup::create([
    //             'name'          => 'Séjour Rochefort',
    //             'order'         => 1,
    //             'rate_class_id' => 5,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-09'),
    //             'has_pack'      => true,
    //             'pack_id'       => 413,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 10
    //         ])->read(['price'])->first();

    //         $booking_price = $booking->read(['price'])->first();
    //         return $booking_price['price'];


    //     },
    //     'assert'            =>  function ($price) {

    //         return ($price == 838.12);
    //     }
    // ),


    // '0118' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-04-08'),
    //             'date_to'     => strtotime('2021-04-11'),
    //             'state'       => 'instance',
    //             'type_id'     => 4,
    //             'center_id'   => 25,
    //             'customer_id' => 10852,
    //         ]);

    //         $booking_id= $booking->first();

            
    //         $bookingLineGroup = BookingLineGroup::create([
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 1,
    //             'rate_class_id' => 7,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-04-08'),
    //             'date_to'       => strtotime('2021-04-11'),
    //             'has_pack'      => true,
    //             'pack_id'       => 1304,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 12
    //         ])->read(['price'])->first();

    //          $booking_price = $booking->read(['price'])->first();
    //         return $bookingLineGroup['price'];


    //     },
    //     'assert'            =>  function ($price) {

    //         return ($price == 1124.08);
    //     }
    // ),


    // '0119' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-04-09'),
    //             'date_to'     => strtotime('2021-04-12'),
    //             'state'       => 'instance',
    //             'type_id'     => 4,
    //             'center_id'   => 25,
    //             'customer_id' => 120,
    //         ]);

    //         $booking_id = $booking->first();


    //         $bookingLineGroup = BookingLineGroup::create([
    //             'name'          => 'Séjour Villers-Sainte-Gertrude',
    //             'order'         => 1,
    //             'rate_class_id' => 5,
    //             'sojourn_type'  => 'GA',
    //             'date_from'     => strtotime('2021-04-09'),
    //             'date_to'       => strtotime('2021-04-12'),
    //             'has_pack'      => true,
    //             'pack_id'       => 1484,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 12
    //         ])->read(['price'])->first();

    //         $booking_price = $booking->read(['price'])->first();
    //         return $booking_price['price'];

        
    //     },
    //     'assert'            =>  function ($price) {

    //         return ($price == 1297.28);
    //     }
    // ),

    // '0120' => array(
    //     'description'       =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'            =>  array('double'),
    //     'test'              =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-11'),
    //             'state'       => 'instance',
    //             'type_id'     => 4,
    //             'center_id'   => 3,
    //             'customer_id' => 11172,
    //         ]);

    //         $booking_id = $booking->first();


    //         $bookingLineGroup = BookingLineGroup::create([
    //             'name'              => 'Séjour Arbrefontaine - École',
    //             'order'             => 1,
    //             'rate_class_id'     => 7,
    //             'sojourn_type'      => 'GG',
    //             'date_from'         => strtotime('2021-11-07'),
    //             'date_to'           => strtotime('2021-11-11'),
    //             'has_pack'          => true,
    //             'pack_id'           => 851,
    //             'booking_id'        => $booking_id['id'],
    //             'nb_pers'           => 10
    //         ])                   
    //         ->read(['price'])
    //         ->first();

    //          $booking_price = $booking->read(['price'])->first();
    //          return $booking_price['price'];            
    //     },
    //     'assert'            =>  function ($price) {
    //         return ($price == 1457);
    //     }
    // ),

    // '0121' => array(
    //     'description'  =>  'Creating bookings and looking out for matching TOTAL PRICES',
    //     'return'       =>  array('double'),
    //     'test'         =>  function () {


    //         $booking = Booking::create([
    //             'date_from'   => strtotime('2021-11-07'),
    //             'date_to'     => strtotime('2021-11-10'),
    //             'state'       => 'instance',
    //             'type_id'     => 4,
    //             'center_id'   => 3,
    //             'customer_id' => 204,
    //         ]);

    //         $booking_id = $booking->first();

            
    //         $bookingLineGroup = BookingLineGroup::create([
    //             'name'          => 'Séjour Arbrefontaine - École',
    //             'order'         => 1,
    //             'rate_class_id' => 5,
    //             'sojourn_type'  => 'GG',
    //             'date_from'     => strtotime('2021-11-07'),
    //             'date_to'       => strtotime('2021-11-10'),
    //             'has_pack'      => true,
    //             'pack_id'       => 852,
    //             'booking_id'    => $booking_id['id'],
    //             'nb_pers'       => 11
    //         ])->read(['price'])->first();

    //          $booking_price = $booking->read(['total'])->first();
    //          return $booking_price['total'];
    //     },
    //     'assert'                 =>  function ($price) {
    //         return ($price == 1245.8);
    //     }
    // )

];
