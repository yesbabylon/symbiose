<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\booking\Consumption;
use lodging\realestate\RentalUnit;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Update the status of accomodation rental-units based on their current occupation. This script is meant to be run on a daily basis.",
    'params' 		=>	[
    ],
    'access' => [
        'visibility'        => 'private'
    ],
    'response' => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers' => ['context', 'orm']
]);

/**
 * @var \equal\php\Context $context
 * @var \equal\orm\ObjectManager $orm
 */
list($context, $orm) = [ $providers['context'], $providers['orm'] ];


/*
    silently reset all rental_units to ready
*/
// #memo - no log, no permission check
$rental_units_ids = $orm->search('lodging\realestate\RentalUnit', ['is_accomodation', '=', true]);
$orm->write('lodging\realestate\RentalUnit', $rental_units_ids, ['status' => 'ready', 'action_required' => 'none']);

/*
    adapt status for rental_units targeted by consumptions
*/
// get current day
$today = time();

// fetch all consumptions & repairs for current day
$consumptions = Consumption::search([['date', '=', $today], ['is_rental_unit', '=', true]])->read(['type', 'schedule_to', 'rental_unit_id'])->get();

foreach($consumptions as $cid => $consumption) {
    if($consumption['type'] == 'ooo') {
        $orm->write('lodging\realestate\RentalUnit', $consumption['rental_unit_id'], ['status' => 'ooo', 'action_required' => 'repair']);
    }
    else {
        $rental_unit = RentalUnit::id($consumption['rental_unit_id'])->read(['is_accomodation'])->first();
        
        if($rental_unit['is_accomodation']) {
            $action_required = 'cleanup_daily';
            $status = 'busy_full';
            if($consumption['schedule_to'] < 24*3600) {
                $action_required = 'cleanup_full';
            }
            if($consumption['type'] == 'part') {
                $status = 'busy_part';
            }
            $orm->write('lodging\realestate\RentalUnit', $consumption['rental_unit_id'], ['status' => $status, 'action_required' => $action_required]);
        }
    }
}

$context->httpResponse()
        ->status(204)
        ->send();