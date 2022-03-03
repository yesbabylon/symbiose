<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\sale\booking\Booking;
use sale\pay\PaymentPlan;
use core\Task;
use lodging\sale\booking\BookingLine;
use lodging\sale\booking\Contract;
use lodging\sale\booking\ContractLine;
use lodging\sale\booking\ContractLineGroup;
use lodging\sale\booking\Funding;


list($params, $providers) = announce([
    'description'   => "Sets booking as confirmed.",
    'params'        => [
        'id' =>  [
            'description'   => 'Identifier of the booking for which the composition has to be generated.',
            'type'          => 'integer',
            'min'           => 1,
            'required'      => true
        ],
    ],
    'access' => [
        'visibility'        => 'public',		// 'public' (default) or 'private' (can be invoked by CLI only)	// list of users ids granted 
        'groups'            => ['booking.default.user'],// list of groups ids or names granted 
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'cron']
]);


list($context, $orm, $cron) = [$providers['context'], $providers['orm'], $providers['cron']];

// read booking object
$booking = Booking::id($params['id'])
                  ->read([
                        'status',
                        'date_from',
                        'date_to',
                        'price',                                  // total price VAT incl.
                        'center_id',
                        'booking_lines_groups_ids' => [
                            'name',
                            'date_from',
                            'date_to',
                            'has_pack',
                            'is_locked',
                            'pack_id' => ['id', 'display_name'],
                            'vat_rate',
                            'unit_price',
                            'qty',
                            'nb_nights',
                            'nb_pers',
                            'booking_lines_ids' => [
                                'product_id',
                                'unit_price',
                                'vat_rate',
                                'qty',
                                'price_adapters_ids' => ['type', 'value', 'is_manual_discount']
                            ]
                        ],
                        'customer_id' => ['id', 'rate_class_id']
                  ])
                  ->first();

if(!$booking) {
    throw new Exception("unknown_booking", QN_ERROR_UNKNOWN_OBJECT);
}

if($booking['status'] != 'option') {
    throw new Exception("incompatible_status", QN_ERROR_INVALID_PARAM);
}

// remove existing CRON tasks for reverting the booking to quote
$cron->cancel("booking.option.deprecation.{$params['id']}");


/*
    Generate the contract
*/

// remember all booking lines involved
$booking_lines_ids = [];

$contract = Contract::create([
        'date'          => time(),
        'booking_id'    => $params['id'],
        'status'        => 'pending',
        'valid_until'   => time() + (30 * 86400),
        'customer_id'   => $booking['customer_id']['id']
    ])
    ->first();

foreach($booking['booking_lines_groups_ids'] as $group_id => $group) {
    $group_label = $group['name'].' : ';

    if($group['date_from'] == $group['date_to']) {
        $group_label .= date('d/m/y', $group['date_from']);
    }
    else {
        $group_label .= date('d/m/y', $group['date_from']).' - '.date('d/m/y', $group['date_to']);
    }

    $group_label .= ' - '.$group['nb_pers'].' p.';

    if($group['has_pack'] && $group['is_locked'] ) {
        // create a contract group based on the booking group

        $contract_line_group = ContractLineGroup::create([
            'name'              => $group_label,
            'is_pack'           => true,
            'contract_id'       => $contract['id']
        ])->first();

        // create a line based on the group
        $c_line = [
            'contract_id'               => $contract['id'],
            'contract_line_group_id'    => $contract_line_group['id'],
            'product_id'                => $group['pack_id']['id'],
            'vat_rate'                  => $group['vat_rate'],
            'unit_price'                => $group['unit_price'],
            'qty'                       => $group['qty']
        ];

        $contract_line = ContractLine::create($c_line)->first();
        ContractLineGroup::ids($contract_line_group['id'])->update([ 'contract_line_id' => $contract_line['id'] ]);
    }
    else {
        $contract_line_group = ContractLineGroup::create([
            'name'              => $group_label,
            'is_pack'           => false,
            'contract_id'       => $contract['id']
        ])->first();
    }

    // create as many lines as the group booking_lines
    foreach($group['booking_lines_ids'] as $lid => $line) {
        $booking_lines_ids[] = $lid;

        $c_line = [
            'contract_id'               => $contract['id'],
            'contract_line_group_id'    => $contract_line_group['id'],
            'product_id'                => $line['product_id'],
            'vat_rate'                  => $line['vat_rate'],
            'unit_price'                => $line['unit_price'],
            'qty'                       => $line['qty']
        ];

        $disc_value = 0;
        $disc_percent = 0;
        $free_qty = 0;
        foreach($line['price_adapters_ids'] as $aid => $adata) {
            if($adata['is_manual_discount']) {
                if($adata['type'] == 'amount') {
                    $disc_value += $adata['value'];
                }
                else if($adata['type'] == 'percent') {
                    $disc_percent += $adata['value'];
                }
                else if($adata['type'] == 'freebie') {
                    $free_qty += $adata['value'];
                }
            }
            // auto granted freebies are displayed as manual discounts
            else {
                if($adata['type'] == 'freebie') {
                    $free_qty += $adata['value'];
                }
            }
        }
        // convert discount value to a percentage
        $disc_value = $disc_value / (1 + $line['vat_rate']);
        $price = $line['unit_price'] * $line['qty'];
        $disc_value_perc = ($price) ? ($price - $disc_value) / $price : 0;
        $disc_percent += (1-$disc_value_perc);

        $c_line['free_qty'] = $free_qty;
        $c_line['discount'] = $disc_percent;
        ContractLine::create($c_line);
    }

}

// mark all booking lines as 'is_invoiced'
BookingLine::ids($booking_lines_ids)->update(['is_invoiced' => true]);


/*
    Genarate the payment plan (expected fundings of the booking)
*/

// default rate class to 'general public'
$rate_class_id = 4;

if($booking['customer_id']['rate_class_id']) {
    $rate_class_id = $booking['customer_id']['rate_class_id'];
}

// look for a payment plan matching the rate_class applied on the booking
$plans_ids = PaymentPlan::search(['rate_class_id', '=', $rate_class_id])->ids();
if($plans_ids < 0 || !count($plans_ids)) {
    // if no payment plan was found, use the default plan
    $plans_ids = PaymentPlan::search(['rate_class_id', 'is', NULL])->ids();
}

if($plans_ids < 0 || !count($plans_ids)) {
    throw new Exception("missing_payment_plan", QN_ERROR_INVALID_CONFIG);
}

$payment_plan = PaymentPlan::ids($plans_ids)
                            ->read([
                                'payment_deadlines_ids' => ['delay_from_event','delay_from_event_offset','delay_count','type','amount_share']
                            ])
                            ->first();

if($payment_plan < 0 || !count($plans_ids)) {
    throw new Exception("cannot_read_object", QN_ERROR_UNKNOWN_OBJECT);
}

$funding_order = 0;
foreach($payment_plan['payment_deadlines_ids'] as $deadline_id => $deadline) {
    $funding = [
        'payment_deadline_id'   => $deadline_id,
        'booking_id'            => $params['id'],
        'center_id'             => $booking['center_id'],
        'due_amount'            => round($booking['price'] * $deadline['amount_share'], 2),
        'is_paid'               => false,
        'type'                  => $deadline['type'],
        'order'                 => $funding_order
    ];

    $date = time();         // default delay is starting today (at confirmation time / equivalent to 'booking')
    switch($deadline['delay_from_event']) {
        case 'booking':
            $date = time();
            break;
        case 'checkin':
            $date = $booking['date_from'];
            break;
        case 'checkout':
            $date = $booking['date_to'];
            break;
    }
    $funding['issue_date'] = $date + $deadline['delay_from_event_offset'];
    $funding['due_date'] = $date + $deadline['delay_from_event_offset'] + ($deadline['delay_count'] * 86400);

    // request funding creation
    try {
        Funding::create($funding);
    }
    catch(Exception $e) {
        // ignore duplicates (not created)
    }

    ++$funding_order;
}

// Update booking status
Booking::id($params['id'])->update(['status' => 'confirmed', 'has_contract' => true]);


$context->httpResponse()
        // ->status(204)
        ->status(200)
        ->body([])
        ->send();