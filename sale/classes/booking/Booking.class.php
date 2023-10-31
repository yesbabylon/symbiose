<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;
use equal\orm\Model;

class Booking extends Model {

    public static function getDescription() {
        return "Bookings group all the information that allow following up of the reservation process of rental units.";
    }

    public static function getLink() {
        return "/booking/#/booking/object.id";
    }

    public static function getColumns() {
        return [
            'creator' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\User',
                'description'       => 'User who created the entry.'
            ],

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "Code to serve as reference (might not be unique)",
                'function'          => 'calcName',
                'store'             => true
            ],

            'description' => [
                'type'              => 'string',
                'usage'             => 'text/plain',
                'description'       => "Reason or comments about the booking, if any (for internal use).",
                'default'           => ''
            ],

            'customer_reference' => [
                'type'              => 'string',
                'description'       => "Code or short string given by the customer as own reference, if any.",
                'default'           => ''
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\Customer',
                'description'       => "The customer whom the booking relates to (computed).",
                'onupdate'          => 'onupdateCustomerId'
            ],

            'customer_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Identity',
                'description'       => "The Customer identity.",
                'onupdate'          => 'onupdateCustomerIdentityId'
            ],

            'total' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:4',
                'function'          => 'calcTotal',
                'description'       => 'Total tax-excluded price of the booking.',
                'store'             => true
            ],

            'is_price_tbc' => [
                'type'              => 'boolean',
                'description'       => 'The booking contains products with prices to be confirmed.',
                'default'           => false
            ],

            'price' => [
                'type'              => 'computed',
                'result_type'       => 'float',
                'usage'             => 'amount/money:2',
                'function'          => 'calcPrice',
                'description'       => 'Final tax-included price of the booking.',
                'store'             => true
            ],

            // #todo
            // origin ID (internal, OTA, TO)

            // A booking can have several contacts (extending identity\Partner)
            'contacts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Contact',
                'foreign_field'     => 'booking_id',
                'description'       => 'List of contacts related to the booking, if any.',
                'ondetach'          => 'delete'
            ],

            'has_contract' => [
                'type'              => 'boolean',
                'description'       => "Has a contract been generated yet? Flag is reset in case of changes before the sojourn.",
                'default'           => false
            ],

            'contracts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Contract',
                'foreign_field'     => 'booking_id',
                'sort'              => 'desc',
                'description'       => 'List of contacts related to the booking, if any.'
            ],

            'booking_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingLine',
                'foreign_field'     => 'booking_id',
                'description'       => 'Detailed lines of the booking.'
            ],

            'booking_lines_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\BookingLineGroup',
                'foreign_field'     => 'booking_id',
                'description'       => 'Grouped lines of the booking.',
                'ondetach'          => 'delete',
                'onupdate'          => 'onupdateBookingLinesGroupsIds'
            ],

            'consumptions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Consumption',
                'foreign_field'     => 'booking_id',
                'description'       => 'Consumptions related to the booking.'
            ],

            'composition_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Composition',
                'description'       => 'The composition that relates to the booking.'
            ],

            'composition_items_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\CompositionItem',
                'foreign_field'     => 'booking_id',
                'description'       => "The items that refer to the composition.",
                'ondetach'          => 'delete'
            ],

            'type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\BookingType',
                'description'       => "The kind of booking it is about.",
                'default'           => 1                // default to 'general public'
            ],

            'status' => [
                'type'              => 'string',
                'selection'         => [
                    'quote',                    // booking is just informative: nothing has been booked in the planning
                    'option',                   // booking has been placed in the planning for 10 days
                    'confirmed',                // booking has been placed in the planning without time limit
                    'validated',                // signed contract and first installment have been received
                    'checkedin',                // host is currently occupying the booked rental unit
                    'checkedout',               // host has left the booked rental unit
                    'invoiced',
                    'debit_balance',            // customer still has to pay something
                    'credit_balance',           // a reimbusrsement to customer is required
                    'balanced'                  // booking is over and balance is cleared
                ],
                'description'       => 'Status of the booking.',
                'default'           => 'quote',
                'onupdate'          => 'onupdateStatus'
            ],

            'is_cancelled' => [
                'type'              => 'boolean',
                'description'       => "Flag marking the booking as cancelled (impacts status).",
                'default'           => false
            ],

            'is_noexpiry' => [
                'type'              => 'boolean',
                'description'       => "Flag marking an option as never expiring.",
                'default'           => false,
                'visible'           => ['status', '=', 'option']
            ],

            'cancellation_reason' => [
                'type'              => 'string',
                'selection'         => [
                    'other',                    // customer cancelled for a non-listed reason or without mentionning the reason (cancellation fees might apply)
                    'overbooking',              // the booking was cancelled due to failure in delivery of the service
                    'duplicate',                // several contacts of the same group made distinct bookings for the same sojourn
                    'internal_impediment',      // cancellation due to an incident impacting the rental units
                    'external_impediment',      // cancellation due to external delivery failure (organisation, means of transport, ...)
                    'health_impediment'         // cancellation for medical or mourning reason
                ],
                'description'       => "Reason for which the customer cancelled the booking.",
                'default'           => 'generic'
            ],

            'payment_status' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'selection'         => [
                    'due',                       // some due payment have not been received yet
                    'paid'                       // all expected payments have been received
                ],
                'function'          => 'calcPaymentStatus',
                'store'             => true
            ],

            // date fields are based on dates from booking line groups
            'date_from' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'function'          => 'calcDateFrom',
                'store'             => true,
                'default'           => time()
            ],

            'date_to' => [
                'type'              => 'computed',
                'result_type'       => 'date',
                'function'          => 'calcDateTo',
                'store'             => true,
                'default'           => time()
            ],

            // time fields are based on dates from booking line groups
            'time_from' => [
                'type'              => 'computed',
                'result_type'       => 'time',
                'function'          => 'calcTimeFrom',
                'store'             => true
            ],

            'time_to' => [
                'type'              => 'computed',
                'result_type'       => 'time',
                'function'          => 'calcTimeTo',
                'store'             => true
            ],

            'nb_pers' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => 'Approx. amount of persons involved in the booking.',
                'function'          => 'calcNbPers',
                'store'             => true
            ],

            'has_payer_organisation' => [
                'type'              => 'boolean',
                'description'       => "Flag to know if invoice must be sent to another Identity.",
                'default'           => false
            ],

            'payer_organisation_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'identity\Partner',
                'visible'           => [ 'has_payer_organisation', '=', true ],
                'domain'            => [ ['owner_identity_id', '=', 'object.customer_identity_id'], ['relationship', '=', 'payer'] ],
                'description'       => "The partner whom the invoices have to be sent to, if any."
            ],

            'fundings_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Funding',
                'foreign_field'     => 'booking_id',
                'description'       => 'Fundings that relate to the booking.',
                'ondetach'          => 'delete'
            ],

            'invoices_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Invoice',
                'foreign_field'     => 'booking_id',
                'description'       => 'Invoices that relate to the booking.'
            ]

        ];
    }


    public static function calcName($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(__CLASS__, $oids, ['created', 'customer_identity_id', 'customer_identity_id.name'], $lang);

        foreach($bookings as $oid => $odata) {
            $increment = 1;
            // search for bookings made the same day by same customer, if any
            if(!empty($odata['customer_id'])) {
                $bookings_ids = $om->search(__CLASS__, [ ['created', '=', $odata['created']], ['customer_identity_id','=', $odata['customer_identity_id']] ]);
                $increment = count($bookings_ids);
            }
            $result[$oid] = sprintf("%s-%08d-%02d", date("ymd", $odata['created']), $odata['customer_identity_id.name'], $increment);
        }
        return $result;
    }

    public static function calcDateFrom($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(__CLASS__, $oids, ['booking_lines_groups_ids']);

        foreach($bookings as $bid => $booking) {
            $min_date = PHP_INT_MAX;
            $booking_line_groups = $om->read('sale\booking\BookingLineGroup', $booking['booking_lines_groups_ids'], ['date_from', 'is_sojourn', 'is_event']);
            if($booking_line_groups > 0 && count($booking_line_groups)) {
                foreach($booking_line_groups as $gid => $group) {
                    if( ($group['is_sojourn']  || $group['is_event'] ) && $group['date_from'] < $min_date) {
                        $min_date = $group['date_from'];
                    }
                }
                $result[$bid] = $min_date;
            }
        }

        return $result;
    }

    public static function calcDateTo($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(__CLASS__, $oids, ['booking_lines_groups_ids']);

        if($bookings > 0) {
            foreach($bookings as $bid => $booking) {
                $max_date = 0;
                $booking_line_groups = $om->read('sale\booking\BookingLineGroup', $booking['booking_lines_groups_ids'], ['date_to', 'is_sojourn', 'is_event']);
                if($booking_line_groups > 0 && count($booking_line_groups)) {
                    foreach($booking_line_groups as $gid => $group) {
                        if( ($group['is_sojourn']  || $group['is_event'] ) && $group['date_to'] > $max_date) {
                            $max_date = $group['date_to'];
                        }
                    }
                    $result[$bid] = $max_date;
                }
            }
        }

        return $result;
    }

    public static function calcTimeFrom($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(__CLASS__, $oids, ['booking_lines_groups_ids']);

        foreach($bookings as $bid => $booking) {
            $min_date = PHP_INT_MAX;
            $time_from = 0;
            $booking_line_groups = $om->read('sale\booking\BookingLineGroup', $booking['booking_lines_groups_ids'], ['date_from', 'time_from', 'is_sojourn', 'is_event']);
            if($booking_line_groups > 0 && count($booking_line_groups)) {
                foreach($booking_line_groups as $gid => $group) {
                    if(($group['is_sojourn']  || $group['is_event'] ) && $group['date_from'] < $min_date) {
                        $min_date = $group['date_from'];
                        $time_from = $group['time_from'];
                    }
                }
                $result[$bid] = $time_from;
            }
        }

        return $result;
    }

    public static function calcTimeTo($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(__CLASS__, $oids, ['booking_lines_groups_ids']);

        if($bookings > 0) {
            foreach($bookings as $bid => $booking) {
                $max_date = 0;
                $time_to = 0;
                $booking_line_groups = $om->read('sale\booking\BookingLineGroup', $booking['booking_lines_groups_ids'], ['date_to', 'time_to', 'is_sojourn', 'is_event']);
                if($booking_line_groups > 0 && count($booking_line_groups)) {
                    foreach($booking_line_groups as $gid => $group) {
                        if(($group['is_sojourn']  || $group['is_event'] ) && $group['date_to'] > $max_date) {
                            $max_date = $group['date_to'];
                            $time_to = $group['time_to'];
                        }
                    }
                    $result[$bid] = $time_to;
                }
            }
        }

        return $result;
    }

    public static function calcNbPers($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(__CLASS__, $oids, ['booking_lines_groups_ids.nb_pers']);

        if($bookings > 0) {
            foreach($bookings as $bid => $booking) {
                $result[$bid] = array_reduce($booking['booking_lines_groups_ids.nb_pers'], function ($c, $group) {
                    return $c + $group['nb_pers'];
                }, 0);
            }
        }
        return $result;
    }

    /**
     * Payment status tells if a given booking is in order regarding the expected payment up to now.
     */
    public static function calcPaymentStatus($om, $oids, $lang) {
        // #todo
        $result = [];
        return $result;
    }

    public static function calcPrice($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(get_called_class(), $oids, ['booking_lines_groups_ids.price']);
        if($bookings > 0) {
            foreach($bookings as $bid => $booking) {
                $price = array_reduce($booking['booking_lines_groups_ids.price'], function ($c, $group) {
                    return $c + $group['price'];
                }, 0.0);
                $result[$bid] = round($price, 2);
            }
        }
        return $result;
    }

    public static function calcTotal($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(get_called_class(), $oids, ['booking_lines_groups_ids.total']);
        if($bookings > 0) {
            foreach($bookings as $bid => $booking) {
                $total = array_reduce($booking['booking_lines_groups_ids.total'], function ($c, $a) {
                    return $c + round($a['total'], 2);
                }, 0.0);
                $result[$bid] = round($total, 4);
            }
        }
        return $result;
    }

    /**
     * #memo - fundings can be partially paid.
     */
    public static function _updateStatusFromFundings($om, $oids, $values, $lang) {
        $bookings = $om->read(self::getType(), $oids, ['status', 'fundings_ids'], $lang);
        if($bookings > 0) {
            foreach($bookings as $bid => $booking) {
                $diff = 0.0;
                $fundings = $om->read(Funding::gettype(), $booking['fundings_ids'], ['due_amount', 'paid_amount'], $lang);
                foreach($fundings as $fid => $funding) {
                    $diff += $funding['due_amount'] - $funding['paid_amount'];
                }
                // discard bookings that are not yet closed (no checkout or remaining services to invoice)
                if(!in_array($booking['status'], ['invoiced', 'debit_balance', 'credit_balance'])) {
                    continue;
                }
                if($diff > 0.0001 ) {
                    // an unpaid amount remains
                    $om->update(self::getType(), $bid, ['status' => 'debit_balance']);
                }
                elseif($diff < 0) {
                    // a reimbursement is due
                    $om->update(self::getType(), $bid, ['status' => 'credit_balance']);
                }
                else {
                    // everything has been paid : booking can be archived
                    $om->update(self::getType(), $bid, ['status' => 'balanced']);
                }
            }
        }
    }

    // #todo - this should be part of the onupdate() hook
    public static function _resetPrices($om, $oids, $values, $lang) {
        $om->update(__CLASS__, $oids, ['total' => null, 'price' => null]);
    }

    public static function onupdateBookingLinesGroupsIds($om, $oids, $values, $lang) {
        $om->callonce(__CLASS__, '_resetPrices', $oids, [], $lang);
    }

    public static function onupdateStatus($om, $oids, $values, $lang) {
        $bookings = $om->read(get_called_class(), $oids, ['status'], $lang);
        if($bookings > 0) {
            foreach($bookings as $bid => $booking) {
                if($booking['status'] == 'confirmed') {
                    $om->update(get_called_class(), $bid, ['has_contract' => true], $lang);
                }
            }
        }
    }

    public static function onupdateCustomerId($om, $oids, $values, $lang) {
        trigger_error("ORM::calling sale\booking\Booking:onupdateCustomerId", QN_REPORT_DEBUG);
        $bookings = $om->read(__CLASS__, $oids, ['customer_identity_id', 'customer_id.partner_identity_id', 'contacts_ids.partner_identity_id'], $lang);

        if($bookings > 0) {
            foreach($bookings as $bid => $booking) {
                if(!$booking['customer_identity_id']) {
                    $om->update(__CLASS__, $bid, ['customer_identity_id' => $booking['customer_id.partner_identity_id']], $lang);
                }

                if(!in_array($booking['customer_id.partner_identity_id'], array_map( function($a) { return $a['partner_identity_id']; }, $booking['contacts_ids.partner_identity_id']))) {
                    // create a contact with the customer as 'booking' contact
                    $om->create('sale\booking\Contact', ['booking_id' => $bid, 'owner_identity_id' => $booking['customer_identity_id'], 'partner_identity_id' => $booking['customer_id.partner_identity_id']]);
                }
            }
        }
    }

    public static function onupdateCustomerIdentityId($om, $oids, $values, $lang) {
        trigger_error("ORM::calling sale\booking\Booking:onupdateCustomerIdentityId", QN_REPORT_DEBUG);
        // reset name
        $om->write(__CLASS__, $oids, ['name' => null]);
        $bookings = $om->read(__CLASS__, $oids, ['customer_identity_id', 'customer_id']);

        if($bookings > 0) {
            foreach($bookings as $oid => $booking) {
                if(!$booking['customer_id']) {
                    $partner_id = null;

                    // find the partner that related to this identity, if any
                    $partners_ids = $om->search('sale\customer\Customer', [
                        ['relationship', '=', 'customer'],
                        ['owner_identity_id', '=', 1],
                        ['partner_identity_id', '=', $booking['customer_identity_id']]
                    ]);
                    if(count($partners_ids)) {
                        $partner_id = reset($partners_ids);
                    }
                    else {
                        // read Identity [type_id]
                        $identities = $om->read('identity\Identity', $booking['customer_identity_id'], ['type_id']);
                        if($identities > 0) {
                            $identity = reset($identities);
                            $partner_id = $om->create('sale\customer\Customer', [
                                'partner_identity_id'   => $booking['customer_identity_id'],
                                'customer_type_id'      => $identity['type_id']
                            ]);
                        }
                    }
                    if($partner_id) {
                        $om->update(__CLASS__, $oid, ['customer_id' => $partner_id]);
                    }
                }
            }
        }
    }

    public static function candelete($om, $oids, $lang='en') {
        $res = $om->read(get_called_class(), $oids, [ 'status' ]);

        if($res > 0) {
            foreach($res as $oids => $odata) {
                if($odata['status'] != 'quote') {
                    return ['status' => ['non_editable' => 'Non-quote bookings cannot be deleted manually.']];
                }
            }
        }
        return parent::candelete($om, $oids, $lang);
    }

    /**
     * Check wether an object can be updated, and perform some additional operations if necessary.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  object   $om         ObjectManager instance.
     * @param  array    $oids       List of objects identifiers.
     * @param  array    $values     Associative array holding the new values to be assigned.
     * @param  string   $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang) {
        $res = $om->read(get_called_class(), $oids, [ 'status', 'customer_id', 'customer_identity_id' ]);

        // fields that can always be updated
        $authorized_fields = ['description'];

        if($res > 0) {
            $fields = array_keys($values);
            if(count($values) == 1 && in_array($fields[0], $authorized_fields))  {
                // allowed update
            }
            else {
                // check for accepted changes based on status
                foreach($res as $oids => $odata) {
                    if(in_array($odata['status'], ['invoiced','debit_balance','credit_balance','balanced'])) {
                        // fields that can be updated when the status has those values
                        $authorized_fields = ['status'];
                        foreach($values as $field => $value) {
                            if(!in_array($field, $authorized_fields)) {
                                return ['status' => ['non_editable' => 'Invoiced bookings edition is limited.']];
                            }
                        }
                    }
                    if( !$odata['customer_id'] && !$odata['customer_identity_id'] && !isset($values['customer_id']) && !isset($values['customer_identity_id']) ) {
                        return ['customer_id' => ['missing_mandatory' => 'Customer is mandatory.']];
                    }
                }

            }

        }

        return parent::canupdate($om, $oids, $values, $lang);
    }

}