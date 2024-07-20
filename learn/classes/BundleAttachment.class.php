<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace learn;

use equal\orm\Model;

class BundleAttachment extends Model {

    public static function getColumns(): array
    {
        return [
            'name' => [
                'type'              => 'string',
                'multilang'         => true
            ],

            'url' => [
                'type'              => 'string',
                'usage'             => 'uri/url',
                'multilang'         => true,
                'computed'          => true,
                'function'          => 'getLink'
            ],

            'bundle_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'learn\Bundle',
                'description'       => 'Bundle the attachment relates to.',
                'ondelete'          => 'cascade'         // delete bundle when parent bundle is deleted
            ]

        ];
    }

	public static function getLink(): string {
		return 'https://example.com';
	}

}