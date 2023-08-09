<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\customer;

class Customer extends \identity\Partner {

    public function getTable() {
        return 'sale_customer_customer';
    }

    public static function getName() {
        return 'Customer';
    }

    public static function getDescription() {
        return "A customer is a partner from who originates one or more bookings.";
    }

    public static function getColumns() {

        return [

            'rate_class_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\customer\RateClass',
                'description'       => "Rate class that applies to the customer.",
                'help'              => "If partner is a customer, it can be assigned to a rate class.",
                'visible'           => ['relationship', '=', 'customer'],
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
                'visible'           => ['relationship', '=', 'customer'],
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

    /**
     * Computes the number of bookings made by the customer during the last two years.
     *
     */
    public static function calcAddress($self) {
        $result = [];
        $self->read(['partner_identity_id' => ['address_street', 'address_city']]);
        foreach($self as $id => $customer) {
            $result[$id] = "{$customer['partner_identity_id']['address_street']} {$customer['partner_identity_id']['address_city']}";
        }
        return $result;
    }

}