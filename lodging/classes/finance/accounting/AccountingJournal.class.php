<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\finance\accounting;


class AccountingJournal extends \finance\accounting\AccountingJournal {

    public static function getColumns() {
        return [

            'index' => [
                'type'              => 'integer',
                'description'       => 'Counter for payments exports.',
                'default'           => 120000
            ],

            'center_office_id' => [
                'type'              => 'many2one',
                'foreign_object'    => \lodging\identity\CenterOffice::getType(),
                'description'       => 'Management Group the accounting journal belongs to.',
                'onupdate'          => 'updateCenterOfficeId'
            ]

        ];
    }

    /**
     * Handler for updating values relating the customer.
     * Sets the organisation_id accordingly to the Center Office.
     *
     * @param  \equal\orm\ObjectManager     $om        Object Manager instance.
     * @param  Array                        $oids      List of objects identifiers.
     * @param  Array                        $values    Associative array mapping fields names with new values tha thave been assigned.
     * @param  String                       $lang      Language (char 2) in which multilang field are to be processed.
     */
    public static function updateCenterOfficeId($om, $oids, $values, $lang) {

        $journals = $om->read(__CLASS__, $oids, ['center_office_id.organisation_id'], $lang);
        if($journals > 0) {
            foreach($journals as $oid => $odata) {
                $om->update(self::getType(), $oid, ['organisation_id' => $odata['center_office_id.organisation_id']], $lang);
            }
        }
    }

}