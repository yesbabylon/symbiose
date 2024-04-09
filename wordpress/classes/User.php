<?php

namespace wordpress;

class User extends \core\User {
	public static function getName(): string {
		return 'User';
	}

	public static function getColumns(): array {
		return [
			'wordpress_user_id' => [
				'type'        => 'int',
				'description' => 'The ID of the WordPress user',
				'required'    => false
			]
		];
	}
}