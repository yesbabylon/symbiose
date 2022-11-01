<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

class Contract extends \sale\contract\Contract {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'computed',
                'function'          => 'calcName',
                'result_type'       => 'string',
                'store'             => true,
                'description'       => 'The display name of the contract.'
            ],

            'booking_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Booking',
                'description'       => 'Booking the contract relates to.',
                'required'          => true
            ],

            'contract_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\booking\ContractLine',
                'foreign_field'     => 'contract_id',
                'description'       => 'Contract lines that belong to the contract.',
                'ondetach'          => 'delete'
            ]            

        ];
    }

    public static function calcName($om, $oids, $lang) {
        $result = [];

        $res = $om->read(get_called_class(), $oids, ['booking_id', 'customer_id.name', 'booking_id.name']);
        foreach($res as $oid => $odata) {
            $ids = $om->search(get_called_class(), ['booking_id', '=', $odata['booking_id']]);
            $result[$oid] = sprintf("%s - %s - %d", $odata['customer_id.name'], $odata['booking_id.name'], count($ids));
        }
        return $result;
    }


    /**
     * Check wether an object can be updated, and perform some additional operations if necessary.
     * This method can be overriden to define a more precise set of tests.
     *
     * @param  object   $om         ObjectManager instance.
     * @param  array    $oids       List of objects identifiers.
     * @param  array    $values     Associative array holding the new values to be assigned.
     * @param  string   $lang       Language in which multilang fields are being updated.
     * @return array    Returns an associative array mapping fields with their error messages. En empty array means that object has been successfully processed and can be updated.
     */
    public static function canupdate($om, $oids, $values, $lang='en') {
        // only status can be updated
        if(count($values) > 1 || !isset($values['status'])) {
            return ['status' => ['not_allowed' => 'Contract cannot be manually updated.']];
        }
        return parent::canupdate($om, $oids, $values, $lang);
    }
}