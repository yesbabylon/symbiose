<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace infra\server;


class ServerStatus extends Status {

    public static function getColumns(): array {
        return [

            'server_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'infra\server\Server',
                'ondelete'          => 'cascade',
                'description'       => "Server concerned by the status.",
                'help'              => "A status can either concern a server or an instance."
            ]

        ];
    }
}
