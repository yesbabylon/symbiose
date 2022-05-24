<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/
use equal\orm\Domain;
use lodging\sale\booking\BankStatementLine;
use lodging\identity\Identity;
use lodging\sale\booking\BookingLineRentalUnitAssignement;
use lodging\sale\booking\Contact;
use sale\booking\Payment;

list($params, $providers) = announce([
    'description'   => 'Advanced search for Bookings: returns a collection of Booking according to extra paramaters.',
    'extends'       => 'core_model_collect',
    'params'        => [

        // inherited params
        'entity' =>  [
            'description'   => 'Full name (including namespace) of the class to look into (e.g. \'core\\User\').',
            'type'          => 'string',
            'required'      => true
        ],
        'fields' =>  [
            'description'   => 'Requested fields. If not specified, only \'id\' and \'name\' fields are returned.',
            'type'          => 'array',
            'default'       => ['id', 'name']
        ],
        'lang' =>  [
            'description'   => 'Language in which multilang field have to be returned (2 letters ISO 639-1).',
            'type'          => 'string',
            'default'       => DEFAULT_LANG
        ],
        'domain' => [
            'description'   => 'Criterias that results have to match (serie of conjunctions)',
            'type'          => 'array',
            'default'       => []
        ],
        'order' => [
            'description'   => 'Column to use for sorting results.',
            'type'          => 'string',
            'default'       => 'id'
        ],
        'sort' => [
            'description'   => 'The direction  (i.e. \'asc\' or \'desc\').',
            'type'          => 'string',
            'default'       => 'asc'
        ],
        'start' => [
            'description'   => 'The row from which results have to start.',
            'type'          => 'integer',
            'default'       => 0
        ],
        'limit' => [
            'description'   => 'The maximum number of results.',
            'type'          => 'integer',
            'min'           => 1,
            'max'           => 500,
            'default'       => 25
        ],



        'bank_account_iban' => [
            'type'          => 'string',
            'usage'         => 'uri/urn:iban',                
            'description'   => "Number of the bank account of the Identity, if any."
        ],

        'identity_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'identity\Identity',
            'description'       => 'Customer identity.'
        ],

        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'lodging\identity\Center',
            'description'       => "The center to which the booking relates to."
        ],

        'rental_unit_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'lodging\realestate\RentalUnit',
            'description'       => 'Rental unit on which to perform the search.'
        ]

    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => [ 'context', 'orm' ]
]);

/**
 * @var \equal\php\Context $context
 * @var \equal\orm\ObjectManager $orm
 */
list($context, $orm) = [ $providers['context'], $providers['orm'] ];



/*
    Add conditions to the domain to consider advanced parameters
*/
$domain = $params['domain'];
$bookings_ids = [];

/*
    center : trivial booking::center_id
*/
if(isset($params['center_id'])) {
    // add contraint on center_id
    $domain = Domain::conditionAdd($domain, ['center_id', '=', $params['center_id']]);
}

/* 
    bank_account_iban : search in statement lines and identities
*/
if(isset($params['bank_account_iban']) && strlen($params['bank_account_iban'])) {
    $found = false;
    // lookup in bank statement lines
    $lines_ids = BankStatementLine::search(['account_iban', '=', $params['bank_account_iban']])->ids();
    if(count($lines_ids)) {
        $payments = Payment::search(['statement_line_id', 'in', $lines_ids])->read(['id', 'booking_id'])->get();
        if(count($payments)) {
            $bookings_ids = array_map(function ($a) { return $a['booking_id']; }, $payments );
            $found = true;
        }
    }

    // lookup in identities    
    $identities_ids = Identity::search(['bank_account_iban', '=', $params['bank_account_iban']])->ids();
    if(count($identities_ids)) {        
        $domain = Domain::conditionAdd($domain, ['customer_identity_id', 'in', $identities_ids]);
        $found = true;
    }
    
    if(!$found) {
        // add a constraint to void the result set
        $bookings_ids = [0];
    }
}

/*
    identity_id : search in contacts (customer should be in it as well)
*/
if(isset($params['identity_id'])) {
    $contacts = Contact::search(['partner_identity_id', '=', $params['identity_id']])->read(['booking_id'])->get();
    if(count($contacts)) {
        if(count($bookings_ids)) {
            $bookings_ids = array_intersect(
                                $bookings_ids,
                                array_map(function ($a) { return $a['booking_id']; }, $contacts )
                            );
        }
        else {
            $bookings_ids = array_map(function ($a) { return $a['booking_id']; }, $contacts );
        }
        if(empty($bookings_ids)) {
            // add a constraint to void the result set
            $bookings_ids = [0];
        }                        
    }
    else {
        // add a constraint to void the result set
        $bookings_ids = [0];
    }
}

/*
    rental_unit : search amonst rental_unit_assignment
*/
if(isset($params['rental_unit_id'])) {
    $assignements = BookingLineRentalUnitAssignement::search(['rental_unit_id', '=', $params['rental_unit_id']])->read(['booking_id'])->get();
    if(count($assignements)) {
        if(count($bookings_ids)) {
            $bookings_ids = array_intersect( 
                                $bookings_ids,
                                array_map(function ($a) { return $a['booking_id']; }, $assignements )
                            );
        }
        else {
            $bookings_ids = array_map(function ($a) { return $a['booking_id']; }, $assignements );
        }
        if(empty($bookings_ids)) {
            // add a constraint to void the result set
            $bookings_ids = [0];
        }
    }
    else {
        // add a constraint to void the result set
        $bookings_ids = [0];
    }
}


if(count($bookings_ids)) {
    $domain = Domain::conditionAdd($domain, ['id', 'in', $bookings_ids]);
}

$params['domain'] = $domain;

$result = eQual::run('get', 'model_collect', $params, true);


$context->httpResponse()
        ->body($result)
        ->send();