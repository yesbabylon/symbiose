<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace lodging\core\alert;


class Message extends \core\alert\Message {

    public static function getColumns() {
        return [

            'center_office_id' => [
                'type'              => 'computed',
                'result_type'       => 'many2one',
                'foreign_object'    => 'lodging\identity\CenterOffice',
                'description'       => 'Office the message relates to (for targeting the users).',
                'store'             => true,
                'function'          => 'calcCenterOfficeId'
            ]
        ];
    }

    // We hijack the group_id to target the Center Offices.
    public static function calcCenterOfficeId($om, $oids, $lang) {
        $result = [];
        $messages = $om->read(self::getType(), $oids, ['group_id']);
        foreach($messages as $mid => $message) {
            $result[$mid] = $message['group_id'];
        }
        return $result;
    }

}