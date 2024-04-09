<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/

use equal\auth\AuthenticationManager;

include_once ABSPATH . '/wp-load.php';

// announce script and fetch parameters values
list( $params, $providers ) = eQual::announce( [
	'description' => "Attempts to log a user in eQual and WordPress.",
	'params'      => [
		'login'    => [
			'description' => "user name",
			'type'        => 'string',
			'required'    => true
		],
		'password' => [
			'description' => "user password",
			'type'        => 'string',
			'required'    => true
		]
	],
	'response'    => [
		'content-type'  => 'application/json',
		'charset'       => 'utf-8',
		'accept-origin' => '*'
	],
	'providers'   => [ 'context', 'auth', 'orm' ],
	'constants'   => [ 'ROOT_APP_URL', 'AUTH_ACCESS_TOKEN_VALIDITY', 'AUTH_REFRESH_TOKEN_VALIDITY', 'AUTH_TOKEN_HTTPS' ]
] );

eQual::run( 'do', 'user_signin', $params );

$providers = eQual::inject( [ 'auth' ] );

/** @var AuthenticationManager $auth */
$auth = $providers['auth'];

if ( $auth->userId() ) {
	$eq_user = wordpress\User::search( [ 'id', '=', $auth->userId() ] )->read( [
		'wordpress_user_id',
		'groups_ids'
	] )->first( true );

	$eq_userGroups = \core\Group::search( [ 'id', 'in', $eq_user['groups_ids'] ] )->read( [ 'name' ] )->get( true );

	$eq_user['groups'] = array_values( array_map( function ( $group ) {
		return $group['name'];
	}, $eq_userGroups ) );

	if ( in_array( 'admins', $eq_user['groups'] ) ) {
		$wpUser = get_user_by( 'id', 1 );
	} elseif ( empty( $eq_user['wordpress_user_id'] ) ) {
		return;
	} else {
		$wpUser = get_user_by( 'id', $eq_user['wordpress_user_id'] );
	}

	if ( $wpUser ) {
		wp_set_current_user( $wpUser->ID );
		wp_set_auth_cookie( $wpUser->ID );
		do_action( 'wp_login', $wpUser->user_login, $wpUser );
	}
}