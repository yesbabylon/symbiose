<?php

list( $params, $providers ) = eQual::announce( [
	'description' => "Update (fully or partially) the given object.",
	'params'      => [
		'id'     => [
			'description' => 'Unique identifier of the object to update.',
			'type'        => 'integer',
			'default'     => 0
		],
		'fields' => [
			'description' => 'Associative array mapping fields to be updated with their related values.',
			'type'        => 'array',
			'default'     => []
		],
		'update_wp' => [
			'description' => 'Flag for updating the WordPress user.',
			'type'        => 'boolean',
			'default'     => true
		]
	],
	'response'    => [
		'content-type'  => 'application/json',
		'charset'       => 'UTF-8',
		'accept-origin' => '*'
	],
	'providers'   => []
] );

$fields   = array_keys( $params['fields'] );
$fields[] = 'wordpress_user_id';

$eq_user        = \wordpress\User::id( $params['id'] )->read( $fields );
$eq_user_groups = \core\Group::search( [ 'id', 'in', $eq_user['groups_ids'] ] )->read( [ 'name' ] )->get( true );

$eq_user_groups = array_values( array_map( function ( $group ) {
	return $group['name'];
}, $eq_user_groups ) );


if ( ! $eq_user->first( true ) ) {
	throw new Exception( "user_not_found", QN_ERROR_INVALID_USER );
}

$eq_user
	->update( $params['fields'] )
	->read( $fields );

$eq_user = $eq_user->read( $fields )->first( true );

if (
	$params['update_wp'] &&
	! empty( $eq_user['wordpress_user_id'] ) &&
	in_array( 'admins', $eq_user_groups )
) {

	$wp_user = get_user_by( 'id', $eq_user['wordpress_user_id'] );

	if ( $wp_user ) {
		foreach ( $params['fields'] as $key => $value ) {
			switch ( $key ) {
				case 'login':
					$wp_user->user_login = $value;
					$wp_user->user_email = $value;
					break;
				case 'password':
					$wp_user->user_pass = $value;
					break;
				case 'username':
					$wp_user->user_nicename = $value;
					$wp_user->display_name  = $value;
					break;
			}
		}

		$wpUpdateUserResponse = wp_update_user( $wp_user );

		if ( is_wp_error( $wpUpdateUserResponse ) ) {
			throw new Exception( $wpUpdateUserResponse->get_error_message(), QN_ERROR_INVALID_PARAM );
		} else {
			// If the password is updated, we need to re-authenticate the user because
			// WordPress use the password to generate the cookie for maintaining the authentication up.
			// So, we need to re-authenticate the user with the new password.
			// Like described in the WordPress documentation
			if ( in_array( 'password', array_keys( $params['fields'] ) ) ) {
				wp_set_current_user( $wp_user->ID );
				wp_set_auth_cookie( $wp_user->ID );
			}
		}
	}
}

