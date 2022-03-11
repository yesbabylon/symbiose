<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\identity\Center;
use equal\data\DataFormatter;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description' =>  "Generate the HTML email signature for a Center, in a given language.",
    'params' =>  [
        'center_id' => [
            'description'   => 'Identifier of the center for which the signature is requested.',
            'type'          => 'integer',
            'required'      => true
        ],
        'lang' =>  [
            'description'   => 'Language to use for multilang contents.',
            'type'          => 'string',
            'usage'         => 'language/iso-639',
            'default'       => DEFAULT_LANG
        ]
    ],
    'access' => [
      'visibility'          => 'public',
      'groups'              => ['booking.default.user'],
    ],
    'response' => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context']
]);

list($context) = [ $providers['context'] ];

$signature = '';

$center = Center::id($params['center_id'])
                ->read([
                    'organisation_id' => ['website'],
                    'center_office_id' => ['signature', 'phone', 'fax', 'email', 'address_street', 'address_city', 'address_zip'],
                    'use_office_details',
                    'address_street',
                    'address_city',
                    'address_zip',
                    'phone',
                    'fax',
                    'email'
                ], $params['lang'])
                ->first();

if($center && count($center)) {

  if(isset($center['center_office_id']['signature'])) {
    $signature .= "{$center['center_office_id']['signature']}";
  }

  if($center['use_office_details']) {
    if(isset($center['center_office_id']['address_street'])) {
      $signature .= "{$center['center_office_id']['address_street']} <br />";
    }
    if(isset($center['center_office_id']['address_city']) && isset($center['center_office_id']['address_zip'])) {
      $signature .= "{$center['center_office_id']['address_zip']} {$center['center_office_id']['address_city']} <br />";
    }
    if(isset($center['center_office_id']['phone'])) {
      $center_phone = DataFormatter::format($center['center_office_id']['phone'], 'phone');
      $signature .= "☎ {$center_phone} <br />";
    }
    if(isset($center['center_office_id']['fax'])) {
      $center_fax = DataFormatter::format($center['center_office_id']['fax'], 'phone');
      $signature .= "🖷 {$center_fax} <br />";
    }
    if(isset($center['center_office_id']['email'])) {
      $signature .= "@ <a href=\"mailto:{$center['center_office_id']['email']}\">{$center['center_office_id']['email']}</a> <br />";
    }
  }
  else {
    if(isset($center['address_street'])) {
      $signature .= "{$center['address_street']} <br />";
    }
    if(isset($center['address_city']) && isset($center['address_zip'])) {
      $signature .= "{$center['address_zip']} {$center['address_city']} <br />";
    }
    if(isset($center['phone'])) {
      $center_phone = DataFormatter::format($center['phone'], 'phone');
      $signature .= "☎ {$center_phone} <br />";
    }
    if(isset($center['fax'])) {
      $center_fax = DataFormatter::format($center['fax'], 'phone');
      $signature .= "🖷 {$center_fax} <br />";
    }
    if(isset($center['email'])) {
      $signature .= "@ <a href=\"mailto:{$center['email']}\">{$center['email']}</a> <br />";
    }  
  }

  if(isset($center['organisation_id']['website'])) {
    $signature .= "<a href=\"{$center['organisation_id']['website']}\">{$center['organisation_id']['website']}</a>";
  }
}

$context->httpResponse()
        ->body(['signature' => $signature])
        ->send();