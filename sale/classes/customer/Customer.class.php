<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\customer;

use identity\Identity;

class Customer extends \identity\Partner {

    public function getTable() {
        return 'sale_customer_customer';
    }

    public static function getName() {
        return 'Customer';
    }

    public static function getDescription() {
        return "A customer is a partner with whom the company carries out commercial sales operations.";
    }

    public static function getColumns() {

        return [

            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "Rate class that applies to the customer.",
                'help'              => "If partner is a customer, it can be assigned to a rate class.",
                'default'           => 1,
                'readonly'          => true
            ],

            'customer_nature_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\CustomerNature',
                'description'       => 'Nature of the customer (map with rate classes).',
                'onupdate'          => 'onupdateCustomerNatureId'
            ],

            'customer_type_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\CustomerType',
                'description'       => "Type of customer (map with rate classes). Defaults to 'individual'.",
                'help'              => "If partner is a customer, it can be assigned a customer type",
                'default'           => 1
            ],

            'relationship' => [
                'type'              => 'string',
                'default'           => 'customer',
                'description'       => 'Force relationship to Customer'
            ],

            'address' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcAddress',
                'description'       => 'Main address from related Identity.'
            ],

            'ref_account' => [
                'type'              => 'string',
                'usage'             => 'uri/urn.iban',
                'description'       => 'Arbitrary reference account number for identifying the customer in external accounting softwares.',
                'readonly'          => true
            ],

            'receivables_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\receivable\Receivable',
                'foreign_field'     => 'customer_id',
                'description'       => 'List receivables of the customers.'
            ],

            'sales_entries_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\SaleEntry',
                'foreign_field'     => 'customer_id',
                'description'       => 'List sales entries of the customers.'
            ],

            'bookings_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\Booking',
                'foreign_field'     => 'booking_id',
                'description'       => 'List bookings of the customers.'
            ],

            'products_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\Product',
                'foreign_field'     => 'customer_id',
                'description'       => 'List products of the customers.'
            ],

            'softwares_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\Software',
                'foreign_field'     => 'customer_id',
                'description'       => 'List softwares of the customers.'
            ],

            'services_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\service\Service',
                'foreign_field'     => 'customer_id',
                'description'       => 'List services of the customers.'
            ],

            'subscriptions_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'inventory\service\Subscription',
                'foreign_field'     => 'customer_id',
                'description'       => 'List subscriptions of the customers.'
            ],

            'invoices_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\accounting\invoice\Invoice',
                'foreign_field'     => 'customer_id',
                'description'       => 'List invoices of the customers.'
            ],

            'customer_external_ref' => [
                'type'              => 'string',
                'description'       => 'External reference for the customer, if any.'
            ],

            'flag_latepayer' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => 'Mark the customer as bad payer.'
            ],

            'flag_damage' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => 'Mark the customer with a damage history.'
            ],

            'flag_nuisance' => [
                'type'              => 'boolean',
                'default'           => false,
                'description'       => 'Mark the customer with a disturbances history.'
            ],

            'is_tour_operator' => [
                'type'              => 'boolean',
                'description'       => 'Mark the customer as a Tour Operator.',
                'default'           => false
            ]

        ];
    }

    public static function onupdateCustomerNatureId($self) {
        $self->read(['customer_nature_id' => ['rate_class_id', 'customer_type_id']]);
        foreach($self as $id => $customer) {
            if($customer['customer_nature_id']) {
                self::id($id)->update([
                        'rate_class_id' => $customer['customer_nature_id']['rate_class_id'],
                        'customer_type_id' => $customer['customer_nature_id']['customer_type_id']
                    ]);
            }
        }
    }

    public static function calcAddress($self) {
        $result = [];
        $self->read(['address_street', 'address_city']);
        foreach($self as $id => $customer) {
            $result[$id] = "{$customer['address_street']} {$customer['address_city']}";
        }
        return $result;
    }

    public static function onafterupdate($self, $values) {
        parent::onafterupdate($self, $values);

        $self->read(['partner_identity_id' => ['id', 'customer_id']]);
        foreach($self as $id => $customer) {
            if(is_null($customer['partner_identity_id']['customer_id'])) {
                Identity::id($customer['partner_identity_id']['id'])->update(['customer_id' => $id]);
            }
        }
    }

}
