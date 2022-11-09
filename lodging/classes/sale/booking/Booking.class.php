<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\sale\booking;
use core\setting\Setting;

/**
 * Virtual properties based on fields descriptors returned by getColumns()
 *
 * @property string                                    $name
 * @property \lodging\sale\customer\Customer           $customer_id
 * @property \lodging\identity\Identity                $customer_identity_id
 * @property \sale\customer\CustomerNature             $customer_nature_id
 * @property \lodging\identity\Center                  $center_id
 * @property \lodging\identity\CenterOffice            $center_office_id
 * @property \lodging\sale\booking\Contact             $contacts_ids
 * @property \lodging\sale\booking\Contract            $contracts_ids
 *
 */
class Booking extends \sale\booking\Booking {

    public static function getColumns() {
        return [

            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'description'       => "Code to serve as reference (should be unique).",
                'function'          => 'calcName',
                'store'             => true,
                'readonly'          => true
            ],

            'customer_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\customer\Customer',
                'description'       => "The customer whom the booking relates to (depends on selected identity).",
                'onupdate'          => 'onupdateCustomerId'
            ],

            'customer_identity_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\Identity',
                'description'       => "The customer whom the booking relates to.",
                'onupdate'          => 'onupdateCustomerIdentityId',
                'required'          => true
            ],

            'customer_nature_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\CustomerNature',
                'description'       => 'Nature of the customer (synched with customer) for views convenience.',
                'onupdate'          => 'onupdateCustomerNatureId',
                'required'          => true
            ],

            'center_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\Center',
                'description'       => "The center to which the booking relates to.",
                'required'          => true,
                'onupdate'          => 'lodging\sale\booking\Booking::onupdateCenterId'
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\identity\CenterOffice',
                'description'       => 'Office the invoice relates to (for center management).',
            ],

            'contacts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\Contact',
                'foreign_field'     => 'booking_id',
                'description'       => 'List of contacts related to the booking, if any.',
                'ondetach'          => 'delete'
            ],

            'contracts_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\Contract',
                'foreign_field'     => 'booking_id',
                'sort'              => 'desc',
                'description'       => 'List of contracts related to the booking, if any.'
            ],

            'consumptions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => Consumption::getType(),
                'foreign_field'     => 'booking_id',
                'description'       => 'Consumptions related to the booking.',
                'ondetach'          => 'delete'
            ],

            'booking_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLine',
                'foreign_field'     => 'booking_id',
                'description'       => 'Detailed consumptions of the booking.'
            ],

            'booking_lines_groups_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\BookingLineGroup',
                'foreign_field'     => 'booking_id',
                'description'       => 'Grouped consumptions of the booking.',
                'order'             => 'order',
                'ondetach'          => 'delete',
                'onupdate'          => 'onupdateBookingLinesGroupsIds'
            ],

            'sojourn_product_models_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\SojournProductModel',
                'foreign_field'     => 'booking_line_group_id',
                'description'       => "The product models groups assigned to the booking (from groups).",
                'ondetach'          => 'delete'
            ],

            'fundings_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => Funding::getType(),
                'foreign_field'     => 'booking_id',
                'description'       => 'Fundings that relate to the booking.',
                'ondetach'          => 'delete'
            ],

            'invoices_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\Invoice',
                'foreign_field'     => 'booking_id',
                'description'       => 'Invoices that relate to the booking.',
                'ondetach'          => 'delete'
            ],

            'nb_pers' => [
                'type'              => 'computed',
                'result_type'       => 'integer',
                'description'       => 'Approx. amount of persons involved in the booking.',
                'function'          => 'calcNbPers',
                'store'             => true
            ],

            'sojourn_type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'lodging\sale\booking\SojournType',
                'description'       => 'Default sojourn type of the booking (set according to booking center).'
            ],

            "is_invoiced" => [
                "type"              => "boolean",
                "description"       => "Flag for handling special case where invoice is emitted at confirmation.",
                "default"           => false
            ],

            'has_tour_operator' => [
                'type'              => 'boolean',
                'description'       => 'Mark the booking as completed by a Tour Operator.',
                'default'           => false
            ],

            'tour_operator_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \sale\customer\TourOperator::getType(),
                'domain'            => ['is_tour_operator', '=', true],
                'description'       => 'Tour Operator that completed the booking.',
                'visible'           => ['has_tour_operator', '=', true]
            ],

            'tour_operator_ref' => [
                'type'              => 'string',
                'description'       => 'Specific reference, voucher code, or booking ID for the TO.',
                'visible'           => ['has_tour_operator', '=', true]
            ],

            'mails_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'core\Mail',
                'foreign_field'     => 'object_id',
                'domain'            => ['object_class', '=', self::getType()]
            ],

            'rental_unit_assignments_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'lodging\sale\booking\SojournProductModelRentalUnitAssignement',
                'foreign_field'     => 'booking_id',
                'description'       => "The rental units assigned to the group (from lines)."
            ]

        ];
    }

    public static function onupdateBookingLinesGroupsIds($om, $oids, $values, $lang) {
        $om->callonce('sale\booking\Booking', '_resetPrices', $oids, [], $lang);
    }

    public static function calcName($om, $oids, $lang) {
        $result = [];

        $bookings = $om->read(self::getType(), $oids, ['center_id.center_office_id.code'], $lang);
        $format = Setting::get_value('sale', 'booking', 'booking.sequence_format', '%05d{sequence}');

        foreach($bookings as $oid => $booking) {

            $setting_name = 'booking.sequence.'.$booking['center_id.center_office_id.code'];
            $sequence = Setting::get_value('sale', 'booking', $setting_name);

            if($sequence) {
                Setting::set_value('sale', 'booking', $setting_name, $sequence + 1);

                $result[$oid] = Setting::parse_format($format, [
                    'center'    => $booking['center_id.center_office_id.code'],
                    'sequence'  => $sequence
                ]);
            }

        }
        return $result;
    }

    public static function calcNbPers($om, $oids, $lang) {
        $result = [];
        $bookings = $om->read(self::getType(), $oids, ['booking_lines_groups_ids']);

        if($bookings > 0) {
            foreach($bookings as $bid => $booking) {
                $result[$bid] = 0;
                $groups = $om->read(BookingLineGroup::getType(), $booking['booking_lines_groups_ids'], ['nb_pers', 'is_autosale', 'is_extra']);
                if($groups > 0) {
                    foreach($groups as $group_id => $group) {
                        if(!$group['is_autosale'] && !$group['is_extra']) {
                            $result[$bid] += $group['nb_pers'];
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Maintain sync with Customer
     */
    public static function onupdateCustomerNatureId($om, $oids, $values, $lang) {
        $bookings = $om->read(self::getType(), $oids, ['customer_id', 'customer_nature_id', 'customer_nature_id.rate_class_id'], $lang);

        if($bookings > 0) {
            foreach($bookings as $oid => $odata) {
                if($odata['customer_nature_id.rate_class_id']) {
                    $om->update('sale\customer\Customer', $odata['customer_id'], [
                            'customer_nature_id'    => $odata['customer_nature_id'],
                            'rate_class_id'         => $odata['customer_nature_id.rate_class_id']
                        ]);
                }
            }
        }
    }

    /**
     * Maintain sync with Customer when assigning a new customer by selecting a customer_identity_id
     * Customer is always selected by picking up an identity (there should always be only one 'customer' partner for a given identity for current organisation).
     * If the identity has a parent identity (department or subsidiary), the customer is based on that parent identity.
     *
     * @param  \equal\orm\ObjectManager     $om        Object Manager instance.
     * @param  Array                        $oids      List of objects identifiers.
     * @param  Array                        $values    Associative array mapping fields names with new values tha thave been assigned.
     * @param  String                       $lang      Language (char 2) in which multilang field are to be processed.
     */
    public static function onupdateCustomerIdentityId($om, $oids, $values, $lang) {

        $bookings = $om->read(self::getType(), $oids, [
                'customer_identity_id',
                'customer_identity_id.description',
                'customer_identity_id.has_parent',
                'customer_identity_id.parent_id',
                'customer_nature_id',
                'customer_nature_id.rate_class_id'
            ]);

        if($bookings > 0) {
            foreach($bookings as $oid => $booking) {
                $partner_id = null;
                $identity_id = $booking['customer_identity_id'];
                if($booking['customer_identity_id.has_parent'] && $booking['customer_identity_id.parent_id']) {
                    $identity_id = $booking['customer_identity_id.parent_id'];
                }
                // find the partner that relates to the target identity, if any
                $partners_ids = $om->search('sale\customer\Customer', [
                    ['relationship', '=', 'customer'],
                    ['owner_identity_id', '=', 1],
                    ['partner_identity_id', '=', $identity_id]
                ]);
                if(count($partners_ids)) {
                    $partner_id = reset($partners_ids);
                }
                else {
                    // create a new customer for the selected identity
                    $identities = $om->read('lodging\identity\Identity', $identity_id, ['type_id']);
                    if($identities > 0) {
                        $identity = reset($identities);
                        $partner_id = $om->create('sale\customer\Customer', [
                                'partner_identity_id'   => $identity_id,
                                'customer_type_id'      => $identity['type_id'],
                                'rate_class_id'         => $booking['customer_nature_id.rate_class_id'],
                                'customer_nature_id'    => $booking['customer_nature_id']
                            ]);
                    }
                }
                $values = [
                    'description' => $booking['customer_identity_id.description']
                ];
                if($partner_id) {
                    // will trigger an update of the rate_class for existing booking_lines
                    $values['customer_id'] = $partner_id;
                }
                $om->update(self::getType(), $oid, $values);
            }
        }
    }

    public static function createContacts($om, $oids, $values, $lang) {
        $bookings = $om->read(self::getType(), $oids, [
            'customer_identity_id',
            'contacts_ids'
        ], $lang);

        if($bookings > 0) {
            foreach($bookings as $bid => $booking) {
                $partners_ids = [];
                $existing_partners_ids = [];
                // read all contacts (to prevent importing contacts twice)
                if($booking['contacts_ids'] && count($booking['contacts_ids'] )) {
                    // #todo - should we remove previously assigned contacts ?
                    // $om->delete(Contact::getType(), $booking['contacts_ids'], true);
                    $contacts = $om->read(Contact::getType(), $booking['contacts_ids'], ['partner_identity_id']);
                    $existing_partners_ids = array_map(function($a) { return $a['partner_identity_id'];}, $contacts);
                }
                // if customer has contacts assigned to its identity, import those
                $identity_contacts_ids = $om->search(\lodging\identity\Contact::getType(), [
                        ['owner_identity_id', '=', $booking['customer_identity_id']],
                        ['relationship', '=', 'contact']
                    ]);
                if($identity_contacts_ids > 0) {
                    $contacts = $om->read(\lodging\identity\Contact::getType(), $identity_contacts_ids, ['partner_identity_id']);
                    foreach($contacts as $cid => $contact) {
                        $partners_ids[] = $contact['partner_identity_id'];
                    }
                }
                // append customer identity's own contact
                $partners_ids[] = $booking['customer_identity_id'];
                // keep only partners_ids not present yet
                $partners_ids = array_diff($partners_ids, $existing_partners_ids);
                // create booking contacts
                foreach($partners_ids as $partner_id) {
                    $om->create(Contact::getType(), [
                        'booking_id'            => $bid,
                        'owner_identity_id'     => $booking['customer_identity_id'],
                        'partner_identity_id'   => $partner_id
                    ]);
                }
            }
        }
    }

    /**
     * Handler for updating values relating the customer.
     * Customer and Identity are synced : only the identity can be changes through views, customer always derives from the selected identity.
     * This handler is always triggered by the onupdateCustomerIdentityId method.
     *
     * @param  \equal\orm\ObjectManager     $om        Object Manager instance.
     * @param  Array                        $oids      List of objects identifiers.
     * @param  Array                        $values    Associative array mapping fields names with new values tha thave been assigned.
     * @param  String                       $lang      Language (char 2) in which multilang field are to be processed.
     */
    public static function onupdateCustomerId($om, $oids, $values, $lang) {

        // update rate_class, based on customer
        $bookings = $om->read(self::getType(), $oids, [
            'booking_lines_groups_ids',
            'customer_id.rate_class_id',
        ], $lang);

        if($bookings > 0) {
            foreach($bookings as $bid => $booking) {
                // update bookingline group rate_class_id   (triggers _resetPrices and _updatePriceAdapters)
                if($booking['booking_lines_groups_ids'] && count($booking['booking_lines_groups_ids'])) {
                    if($booking['customer_id.rate_class_id']) {
                        $om->update(BookingLineGroup::getType(), $booking['booking_lines_groups_ids'], ['rate_class_id' => $booking['customer_id.rate_class_id']], $lang);
                    }
                }
            }
        }

        // import contacts from customer
        $om->callonce(self::getType(), 'createContacts', $oids, [], $lang);

        // update auto sale products
        $om->callonce(self::getType(), '_updateAutosaleProducts', $oids, [], $lang);
    }

    public static function onupdateCenterId($om, $oids, $values, $lang) {
        $bookings = $om->read(self::getType(), $oids, ['booking_lines_ids', 'center_id.center_office_id']);

        if($bookings > 0) {
            foreach($bookings as $bid => $booking) {
                $booking_lines_ids = $booking['booking_lines_ids'];
                if($booking_lines_ids > 0 && count($booking_lines_ids)) {
                    $om->callonce('lodging\sale\booking\BookingLine', '_updatePriceId', $booking_lines_ids, [], $lang);
                }
                $om->update(self::getType(), $bid, ['center_office_id' => $booking['center_id.center_office_id']]);
            }
        }
    }

    /**
     * Signature for single object change from views.
     *
     * @param  \equal\orm\ObjectManager     $om        Object Manager instance.
     * @param  Array                        $event     Associative array holding changed fields as keys, and their related new values.
     * @param  Array                        $values    Copy of the current (partial) state of the object (fields depend on the view).
     * @param  String                       $lang      Language (char 2) in which multilang field are to be processed.
     * @return Array    Associative array mapping fields with their resulting values.
     */
    public static function onchange($om, $event, $values, $lang='en') {
        $result = [];

        if(isset($event['date_from'])) {
            if($values['date_to'] < $event['date_from']) {
                $result['date_to'] = $event['date_from'];
            }
        }
        // try to retrieve nature from an identity
        if(isset($event['customer_identity_id'])) {
            // search for a partner that relates to this identity, if any
            $partners_ids = $om->search('sale\customer\Customer', [
                ['relationship', '=', 'customer'],
                ['owner_identity_id', '=', 1],
                ['partner_identity_id', '=', $event['customer_identity_id']]
            ]);
            if(count($partners_ids)) {
                $partners = $om->read('sale\customer\Customer', $partners_ids, ['id', 'name', 'customer_nature_id.id', 'customer_nature_id.name']);
                if($partners > 0) {
                    $partner = reset($partners);
                    $result['customer_id'] = ['id' => $partner['id'], 'name' => $partner['name']];
                    if(isset($partner['customer_nature_id.id']) && $partner['customer_nature_id.id']) {
                        $result['customer_nature_id'] = [
                                'id'    => $partner['customer_nature_id.id'],
                                'name'  => $partner['customer_nature_id.name']
                            ];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Generate one or more groups for products sold automatically.
     * We generate services groups related to autosales when the following fields are updated:
     * customer, date_from, date_to, center_id
     *
     */
    public static function _updateAutosaleProducts($om, $oids, $values, $lang) {
        /*
            remove groups related to autosales that already exist
        */

        $bookings = $om->read(self::getType(), $oids, [
                'id',
                'customer_id.rate_class_id',
                'customer_id.count_booking_12',
                'booking_lines_groups_ids',
                'date_from',
                'date_to',
                'center_id.autosale_list_category_id'
            ], $lang);

        // loop through bookings and create groups for autosale products, if any
        foreach($bookings as $booking_id => $booking) {

            $groups_ids_to_delete = [];
            $booking_lines_groups = $om->read('lodging\sale\booking\BookingLineGroup', $booking['booking_lines_groups_ids'], ['is_autosale'], $lang);
            if($booking_lines_groups > 0) {
                foreach($booking_lines_groups as $gid => $group) {
                    if($group['is_autosale']) {
                        $groups_ids_to_delete[] = -$gid;
                    }
                }
                $om->update(self::getType(), $booking_id, ['booking_lines_groups_ids' => $groups_ids_to_delete], $lang);
            }

            /*
                Find the first Autosale List that matches the booking dates
            */

            $autosale_lists_ids = $om->search('sale\autosale\AutosaleList', [
                ['autosale_list_category_id', '=', $booking['center_id.autosale_list_category_id']],
                ['date_from', '<=', $booking['date_from']],
                ['date_to', '>=', $booking['date_from']]
            ]);

            $autosale_lists = $om->read('sale\autosale\AutosaleList', $autosale_lists_ids, ['id', 'autosale_lines_ids']);
            $autosale_list_id = 0;
            $autosale_list = null;
            if($autosale_lists > 0 && count($autosale_lists)) {
                // use first match (there should always be only one or zero)
                $autosale_list = array_pop($autosale_lists);
                $autosale_list_id = $autosale_list['id'];
                trigger_error("QN_DEBUG_ORM:: match with autosale List {$autosale_list_id}", QN_REPORT_DEBUG);
            }
            else {
                trigger_error("QN_DEBUG_ORM:: no autosale List found", QN_REPORT_DEBUG);
            }
            /*
                Search for matching Autosale products within the found List
            */
            if($autosale_list_id) {
                $operands = [];

                // for now, we only support member cards for customer that haven't booked a service for more thant 12 months
                $operands['count_booking_12'] = $booking['customer_id.count_booking_12'];

                $autosales = $om->read('sale\autosale\AutosaleLine', $autosale_list['autosale_lines_ids'], [
                    'product_id.id',
                    'product_id.name',
                    'has_own_qty',
                    'qty',
                    'scope',
                    'conditions_ids'
                ], $lang);

                // filter discounts based on related conditions
                $products_to_apply = [];

                // filter discounts to be applied on booking lines
                foreach($autosales as $autosale_id => $autosale) {
                    if($autosale['scope'] != 'booking') continue;
                    $conditions = $om->read('sale\autosale\Condition', $autosale['conditions_ids'], ['operand', 'operator', 'value']);
                    $valid = true;
                    foreach($conditions as $c_id => $condition) {
                        if(!in_array($condition['operator'], ['>', '>=', '<', '<=', '='])) {
                            // unknown operator
                            continue;
                        }
                        $operator = $condition['operator'];
                        if($operator == '=') {
                            $operator = '==';
                        }
                        if(!isset($operands[$condition['operand']])) {
                            $valid = false;
                            break;
                        }
                        $operand = $operands[$condition['operand']];
                        $value = $condition['value'];
                        if(!is_numeric($operand)) {
                            $operand = "'$operand'";
                        }
                        if(!is_numeric($value)) {
                            $value = "'$value'";
                        }
                        trigger_error(" testing {$operand} {$operator} {$value}", QN_REPORT_DEBUG);
                        $valid = $valid && (bool) eval("return ( {$operand} {$operator} {$value});");
                        if(!$valid) break;
                    }
                    if($valid) {
                        trigger_error("QN_DEBUG_ORM:: all conditions fullfilled", QN_REPORT_DEBUG);
                        $products_to_apply[$autosale_id] = [
                            'id'            => $autosale['product_id.id'],
                            'name'          => $autosale['product_id.name'],
                            'has_own_qty'   => $autosale['has_own_qty'],
                            'qty'           => $autosale['qty']
                        ];
                    }
                }

                // apply all applicable products
                $count = count($products_to_apply);

                if($count) {
                    // create a new BookingLine Group dedicated to autosale products
                    $group = [
                        'name'          => 'Suppléments obligatoires',
                        'booking_id'    => $booking_id,
                        'rate_class_id' => ($booking['customer_id.rate_class_id'])?$booking['customer_id.rate_class_id']:4,
                        'date_from'     => $booking['date_from'],
                        'date_to'       => $booking['date_to'],
                        'is_autosale'   => true
                    ];
                    if($count == 1) {
                        // in case of a single line, overwrite group name
                        foreach($products_to_apply as $autosale_id => $product) {
                            $group['name'] = $product['name'];
                        }
                    }
                    $gid = $om->create('lodging\sale\booking\BookingLineGroup', $group, $lang);

                    // add all applicable products to the group
                    $order = 1;
                    foreach($products_to_apply as $autosale_id => $product) {
                        // create a line relating to the product
                        $line = [
                            'order'                     => $order++,
                            'booking_id'                => $booking_id,
                            'booking_line_group_id'     => $gid,
                            'product_id'                => $product['id'],
                            'has_own_qty'               => $product['has_own_qty'],
                            'qty'                       => $product['qty']
                        ];
                        $om->create('lodging\sale\booking\BookingLine', $line, $lang);
                    }
                }
            }
            else {
                $date = date('Y-m-d', $booking['date_from']);
                trigger_error("QN_DEBUG_ORM::no matching autosale list found for date {$date}", QN_REPORT_DEBUG);
            }
        }
    }


    public static function canclone($orm, $oids) {
        // prevent cloning bookings
        return ['status' => ['not_allowed' => 'Booking cannot be cloned.']];
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

        $bookings = $om->read(self::getType(), $oids, ['status', 'center_id', 'booking_lines_ids'], $lang);


        if(isset($values['center_id'])) {
            $has_booking_lines = false;
            foreach($bookings as $bid => $booking) {
                // if there are services and the center is already defined (otherwise this is the first assignation and some auto-services might just have been created)
                if($booking['center_id'] && count($booking['booking_lines_ids'])) {
                    $has_booking_lines = true;
                    break;
                }
            }
            if($has_booking_lines) {
                return ['center_id' => ['non_editable' => 'Center cannot be changed once services are attached to the booking.']];
            }
        }

        // identity cannot be changed once the contract has been emitted
        if(isset($values['customer_identity_id'])) {
            foreach($bookings as $bid => $booking) {
                if(!in_array($booking['status'], ['quote', 'option'])) {
                    return ['customer_identity_id' => ['non_editable' => 'Customer cannot be changed once a contract has been emitted.']];
                }
            }
        }

        // if customer nature is missing, make sure the selected customer has one already
        if(isset($values['customer_id']) && !isset($values['customer_nature_id'])) {
            // if we received a customer id, its customer_nature_id must be set
            $customers = $om->read('sale\customer\Customer', $values['customer_id'], [ 'customer_nature_id']);
            if($customers) {
                $customer = reset($customers);
                if(is_null($customer['customer_nature_id'])) {
                    return ['customer_nature_id' => ['missing_mandatory' => 'Unknown nature for this customer.']];
                }
            }
        }

        if(isset($values['booking_lines_ids'])) {
            // trying to add or remove booking lines
            // (lines cannot be assigned to more than one booking)
            $booking = reset($bookings);
            if(!in_array($booking['status'], ['quote'])) {
                $lines = $om->read('lodging\sale\booking\BookingLine', $values['booking_lines_ids'], [ 'booking_line_group_id.is_extra']);
                foreach($lines as $line) {
                    if(!$line['booking_line_group_id.is_extra']) {
                        return ['status' => ['non_editable' => 'Non-extra services cannot be changed for non-quote bookings.']];
                    }
                }
            }
        }

        if(isset($values['booking_lines_groups_ids'])) {
            // trying to add or remove booking line groups
            // groups cannot be assigned to more than one booking
            $booking = reset($bookings);
            if(!in_array($booking['status'], ['quote'])) {
                $booking_lines_groups_ids = array_map( function($id) { return abs($id); }, $values['booking_lines_groups_ids']);
                $groups = $om->read('lodging\sale\booking\BookingLineGroup', $booking_lines_groups_ids, [ 'is_extra']);
                foreach($groups as $group) {
                    if(!$group['is_extra']) {
                        return ['status' => ['non_editable' => 'Non-extra service groups cannot be changed for non-quote bookings.']];
                    }
                }
            }
        }

        return parent::canupdate($om, $oids, $values, $lang);
    }


    /**
     * Resets and recreates booking consumptions from bookinglines and rental units assignements.
     * This method is called upon setting booking status to 'option' or 'confirmed' (#see `option.php`)
     * #memo - consumptions are used in the planning.
     *
     */
    public static function createConsumptions($om, $oids, $values, $lang) {
        // Reset consumptions (updating consumptions_ids will trigger ondetach event).
        $lines = $om->read(self::getType(), $oids, ['consumptions_ids'], $lang);

        foreach($lines as $lid => $line) {
            $om->update(self::getType(), $lid, ['consumptions_ids' => array_map(function($a) { return "-$a";}, $line['consumptions_ids'])]);
        }

        // Get in-memory list of consumptions for all lines.
        $consumptions = $om->call(self::getType(), 'getResultingConsumptions', $oids, [], $lang);

        // Create consumptions objects.
        foreach($consumptions as $consumption) {
            $om->create(Consumption::getType(), $consumption, $lang);
        }
    }


    /**
     * Process BookingLines to create an in-memory list of consumptions objects.
     *
     */
    public static function getResultingConsumptions($om, $oids, $values, $lang) {

        // resulting consumptions objects
        $consumptions = [];

        $bookings = $om->read(self::getType(), $oids, [
                'center_id',
                'booking_lines_groups_ids',
                'sojourn_product_models_ids'
            ], $lang);

        if($bookings > 0) {
            foreach($bookings as $bid => $booking) {

                $groups = $om->read(BookingLineGroup::getType(), $booking['booking_lines_groups_ids'], [
                        'booking_lines_ids',
                        'nb_pers',
                        'nb_nights',
                        'is_event',
                        'is_sojourn',
                        'date_from',
                        'time_from',
                        'time_to',
                        'age_range_assignments_ids',
                        'rental_unit_assignments_ids',
                        'meal_preferences_ids'
                    ],
                    $lang);

                if($groups > 0) {

                    // pass-1 : create consumptions for rental_units
                    foreach($groups as $gid => $group) {
                        // retrieve assigned rental units (assigned during booking)

                        $sojourn_products_models_ids = $om->search(SojournProductModel::getType(), ['booking_line_group_id', '=', $gid]);
                        if($sojourn_products_models_ids <= 0) {
                            continue;
                        }
                        $sojourn_product_models = $om->read(SojournProductModel::getType(), $sojourn_products_models_ids, [
                                'product_model_id',
                                'product_model_id.is_accomodation',
                                'product_model_id.qty_accounting_method',
                                'product_model_id.schedule_offset',
                                'product_model_id.schedule_default_value',
                                'rental_unit_assignments_ids'
                            ]);
                        if($sojourn_product_models <= 0) {
                            continue;
                        }
                        foreach($sojourn_product_models as $spid => $spm) {
                            $rental_units_assignments = $om->read(SojournProductModelRentalUnitAssignement::getType(), $spm['rental_unit_assignments_ids'], ['rental_unit_id','qty']);
                            // retrieve all involved rental units (limited to 2 levels above and 2 levels below)
                            $rental_units = [];
                            if($rental_units_assignments > 0) {
                                $rental_units_ids = array_map(function ($a) { return $a['rental_unit_id']; }, array_values($rental_units_assignments));

                                // fetch 2 levels of rental units identifiers
                                for($i = 0; $i < 2; ++$i) {
                                    $units = $om->read('lodging\realestate\RentalUnit', $rental_units_ids, ['parent_id', 'children_ids', 'can_partial_rent']);
                                    if($units > 0) {
                                        foreach($units as $uid => $unit) {
                                            if($unit['parent_id'] > 0) {
                                                $rental_units_ids[] = $unit['parent_id'];
                                            }
                                            if(count($unit['children_ids'])) {
                                                foreach($unit['children_ids'] as $uid) {
                                                    $rental_units_ids[] = $uid;
                                                }
                                            }
                                        }
                                    }
                                }
                                // read all involved rental units
                                $rental_units = $om->read('lodging\realestate\RentalUnit', $rental_units_ids, ['parent_id', 'children_ids', 'can_partial_rent']);
                            }

                            $nb_nights  = $group['nb_nights'];

                            if($spm['product_model_id.qty_accounting_method'] == 'person') {
                                // #todo - we don't check (yet) for daily variations (from booking lines)
                                // rental_units_assignments.qty should be adapted on a daily basis
                            }

                            list($day, $month, $year) = [ date('j', $group['date_from']), date('n', $group['date_from']), date('Y', $group['date_from']) ];

                            // retrieve default time for consumption
                            list($hour_from, $minute_from, $hour_to, $minute_to) = [12, 0, 13, 0];
                            $schedule_default_value = $spm['product_model_id.schedule_default_value'];
                            if(strpos($schedule_default_value, ':')) {
                                $parts = explode('-', $schedule_default_value);
                                list($hour_from, $minute_from) = explode(':', $parts[0]);
                                list($hour_to, $minute_to) = [$hour_from+1, $minute_from];
                                if(count($parts) > 1) {
                                    list($hour_to, $minute_to) = explode(':', $parts[1]);
                                }
                            }

                            $schedule_from  = $hour_from * 3600 + $minute_from * 60;
                            $schedule_to    = $hour_to * 3600 + $minute_to * 60;

                            // fetch the offset, in days, for the scheduling (applies only on sojourns)
                            $offset = ($group['is_sojourn'])?$spm['product_model_id.schedule_offset']:0;
                            $is_accomodation = $spm['product_model_id.is_accomodation'];

                            $is_first = true;
                            for($i = 0; $i < $nb_nights; ++$i) {
                                $c_date = mktime(0, 0, 0, $month, $day+$i+$offset, $year);
                                $c_schedule_from = $schedule_from;
                                $c_schedule_to = $schedule_to;

                                // first accomodation has to match the checkin time of the sojourn (from group)
                                if($is_first && $is_accomodation) {
                                    $is_first = false;
                                    $diff = $c_schedule_to - $schedule_from;
                                    $c_schedule_from = $group['time_from'];
                                    $c_schedule_to = $c_schedule_from + $diff;
                                }

                                // if day is not the arrival day
                                if($i > 0) {
                                    $c_schedule_from = 0;               // midnight same day
                                }

                                if($i == $nb_nights) {                  // last day
                                    $c_schedule_to = $group['time_to'];
                                }
                                else {
                                    $c_schedule_to = 24 * 3600;         // midnight next day
                                }

                                if($rental_units_assignments > 0) {
                                    foreach($rental_units_assignments as $assignment) {
                                        $rental_unit_id = $assignment['rental_unit_id'];
                                        $consumption = [
                                            'booking_id'            => $bid,
                                            'booking_line_group_id' => $gid,
                                            'center_id'             => $booking['center_id'],
                                            'date'                  => $c_date,
                                            'schedule_from'         => $c_schedule_from,
                                            'schedule_to'           => $c_schedule_to,
                                            'product_model_id'      => $spm['product_model_id'],
                                            'age_range_id'          => null,
                                            'is_rental_unit'        => true,
                                            'is_accomodation'       => $spm['product_model_id.is_accomodation'],
                                            'is_meal'               => false,
                                            'rental_unit_id'        => $rental_unit_id,
                                            'qty'                   => $assignment['qty'],
                                            'type'                  => 'book'
                                        ];
                                        $consumptions[] = $consumption;

                                        // 1) recurse through children : all child units are blocked as 'link'
                                        $children_ids = [];
                                        $children_stack = (isset($rental_units[$rental_unit_id]) && isset($rental_units[$rental_unit_id]['children_ids']))?$rental_units[$rental_unit_id]['children_ids']:[];
                                        while(count($children_stack)) {
                                            $unit_id = array_pop($children_stack);
                                            $children_ids[] = $unit_id;
                                            if(isset($rental_units[$unit_id]) && $rental_units[$unit_id]['children_ids']) {
                                                foreach($units[$unit_id]['children_ids'] as $child_id) {
                                                    $children_stack[] = $child_id;
                                                }
                                            }
                                        }

                                        foreach($children_ids as $child_id) {
                                            $consumption['type'] = 'link';
                                            $consumption['rental_unit_id'] = $child_id;
                                            $consumptions[] = $consumption;
                                        }

                                        // 2) loop through parents : if a parent has 'can_partial_rent', it is partially blocked as 'part', otherwise fully blocked as 'link'
                                        $parents_ids = [];
                                        $unit_id = $rental_unit_id;

                                        while( isset($rental_units[$unit_id]) ) {
                                            $parent_id = $rental_units[$unit_id]['parent_id'];
                                            if($parent_id > 0) {
                                                $parents_ids[] = $parent_id;
                                            }
                                            $unit_id = $parent_id;
                                        }

                                        foreach($parents_ids as $parent_id) {
                                            $consumption['type'] = ($rental_units[$parent_id]['can_partial_rent'])?'part':'link';
                                            $consumption['rental_unit_id'] = $parent_id;
                                            $consumptions[] = $consumption;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // pass-2 : create consumptions for booking lines targeting non-rental_unit products (any other schedulable product)
                    foreach($groups as $gid => $group) {

                        $lines = $om->read(BookingLine::getType(), $group['booking_lines_ids'], [
                                'product_id',
                                'qty',
                                'qty_vars',
                                'product_id.product_model_id',
                                'product_id.has_age_range',
                                'product_id.age_range_id'
                            ],
                            $lang);

                        if($lines > 0 && count($lines)) {

                            // read all related product models at once
                            $product_models_ids = array_map(function($oid) use($lines) {return $lines[$oid]['product_id.product_model_id'];}, array_keys($lines));
                            $product_models = $om->read(\lodging\sale\catalog\ProductModel::getType(), $product_models_ids, [
                                    'type',
                                    'service_type',
                                    'schedule_offset',
                                    'schedule_type',
                                    'schedule_default_value',
                                    'qty_accounting_method',
                                    'has_duration',
                                    'duration',
                                    'is_rental_unit',
                                    'is_accomodation',
                                    'is_meal'
                                ]);

                            // create consumptions according to each line product and quantity
                            foreach($lines as $lid => $line) {

                                if($line['qty'] <= 0) {
                                    continue;
                                }
                                // ignore rental units : these are already been handled for the booking (grouped in SPM rental unit assignments)
                                if($product_models[$line['product_id.product_model_id']]['is_rental_unit']) {
                                    continue;
                                }

                                $product_type = $product_models[$line['product_id.product_model_id']]['type'];
                                $service_type = $product_models[$line['product_id.product_model_id']]['service_type'];
                                $has_duration = $product_models[$line['product_id.product_model_id']]['has_duration'];

                                // consumptions are schedulable services
                                if($product_type != 'service' || $service_type != 'schedulable') {
                                    continue;
                                }

                                // retrieve default time for consumption
                                list($hour_from, $minute_from, $hour_to, $minute_to) = [12, 0, 13, 0];
                                $schedule_default_value = $product_models[$line['product_id.product_model_id']]['schedule_default_value'];
                                if(strpos($schedule_default_value, ':')) {
                                    $parts = explode('-', $schedule_default_value);
                                    list($hour_from, $minute_from) = explode(':', $parts[0]);
                                    list($hour_to, $minute_to) = [$hour_from+1, $minute_from];
                                    if(count($parts) > 1) {
                                        list($hour_to, $minute_to) = explode(':', $parts[1]);
                                    }
                                }
                                $schedule_from  = $hour_from * 3600 + $minute_from * 60;
                                $schedule_to    = $hour_to * 3600 + $minute_to * 60;

                                $is_meal = $product_models[$line['product_id.product_model_id']]['is_meal'];
                                $qty_accounting_method = $product_models[$line['product_id.product_model_id']]['qty_accounting_method'];

                                // #memo - number of consumptions differs for accomodations (rooms are occupied nb_nights + 1, until sometime in the morning)
                                // #memo - sojourns are accounted in nights, while events are accounted in days
                                $nb_products = ($group['is_sojourn'])?$group['nb_nights']:(($group['is_event'])?$group['nb_nights']+1:1);
                                $nb_times = $group['nb_pers'];

                                // adapt nb_pers based on if product from line has age_range
                                if($qty_accounting_method == 'person') {
                                    $age_assignments = $om->read(BookingLineGroupAgeRangeAssignment::getType(), $group['age_range_assignments_ids'], ['age_range_id', 'qty']);
                                    if($line['product_id.has_age_range']) {
                                        foreach($age_assignments as $aid => $assignment) {
                                            if($assignment['age_range_id'] == $line['product_id.age_range_id']) {
                                                $nb_times = $assignment['qty'];
                                            }
                                        }
                                    }
                                }
                                // adapt duration for products with fixed duration
                                if($has_duration) {
                                    $nb_products = $product_models[$line['product_id.product_model_id']]['duration'];
                                }

                                list($day, $month, $year) = [ date('j', $group['date_from']), date('n', $group['date_from']), date('Y', $group['date_from']) ];
                                // fetch the offset, in days, for the scheduling (only applies on sojourns)
                                $offset = ($group['is_sojourn'])?$product_models[$line['product_id.product_model_id']]['schedule_offset']:0;

                                $days_nb_times = array_fill(0, $nb_products, $nb_times);

                                if( $qty_accounting_method == 'person' && ($nb_times * $nb_products) != $line['qty']) {
                                    // $nb_times varies from one day to another : load specific days_nb_times array
                                    $qty_vars = json_decode($line['qty_vars']);
                                    // qty_vars is set and valid
                                    if($qty_vars) {
                                        $i = 0;
                                        foreach($qty_vars as $variation) {
                                            if($nb_products < $i+1) {
                                                break;
                                            }
                                            $days_nb_times[$i] = $nb_times + $variation;
                                            ++$i;
                                        }
                                    }
                                }

                                // $nb_products represent each day of the stay
                                for($i = 0; $i < $nb_products; ++$i) {
                                    $c_date = mktime(0, 0, 0, $month, $day+$i+$offset, $year);
                                    $c_schedule_from = $schedule_from;
                                    $c_schedule_to = $schedule_to;

                                    // create a single consumption with the quantity set accordingly (may vary from one day to another)

                                    $consumption = [
                                        'booking_id'            => $bid,
                                        'booking_line_group_id' => $gid,
                                        'booking_line_id'       => $lid,
                                        'center_id'             => $booking['center_id'],
                                        'date'                  => $c_date,
                                        'schedule_from'         => $c_schedule_from,
                                        'schedule_to'           => $c_schedule_to,
                                        'product_model_id'      => $line['product_id.product_model_id'],
                                        'age_range_id'          => $line['product_id.age_range_id'],
                                        'is_rental_unit'        => false,
                                        'is_accomodation'       => false,
                                        'is_meal'               => $is_meal,
                                        'qty'                   => $days_nb_times[$i],
                                        'type'                  => 'book'
                                    ];
                                    // for meals, we add the age ranges and prefs with the description field
                                    if($is_meal) {
                                        $description = '';
                                        $age_range_assignments = $om->read(BookingLineGroupAgeRangeAssignment::getType(), $group['age_range_assignments_ids'], ['age_range_id.name','qty'], $lang);
                                        $meal_preferences = $om->read(\sale\booking\MealPreference::getType(), $group['meal_preferences_ids'], ['type','pref', 'qty'], $lang);
                                        foreach($age_range_assignments as $oid => $assignment) {
                                            $description .= "<p>{$assignment['age_range_id.name']} : {$assignment['qty']} ; </p>";
                                        }
                                        foreach($meal_preferences as $oid => $preference) {
                                            // #todo : use translation file
                                            $type = ($preference['type'] == '3_courses')?'3 services':'2 services';
                                            $pref = ($preference['pref'] == 'veggie')?'végétarien':(($preference['pref'] == 'allergen_free')?'sans allergène':'normal');
                                            $description .= "<p>{$type} / {$pref} : {$preference['qty']} ; </p>";
                                        }
                                        $consumption['description'] = $description;
                                    }
                                    $consumptions[] = $consumption;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $consumptions;
    }

}