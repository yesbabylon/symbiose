<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\server;


class InstanceStatus extends Status {

    public static function getColumns(): array {
        return [

            'instance_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\server\Instance',
                'ondelete'          => 'cascade',
                'description'       => "Instance concerned by the status.",
                'help'              => "A status can either concern an instance or a server."
            ]

        ];
    }
}
