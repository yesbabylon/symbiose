<?php

include_once ABSPATH . '/wp-load.php';

// announce script and fetch parameters values
list( $params, $providers ) = eQual::announce( [
	'description' => "Sign a user out for eQual and WordPress.",
	'params'      => [],
	'constants'   => [ 'ROOT_APP_URL', 'AUTH_TOKEN_HTTPS' ],
	'response'    => [
		'content-type'  => 'application/json',
		'charset'       => 'utf-8',
		'accept-origin' => '*'
	]
] );

list( $context ) = [ $providers['context'] ];

wp_clear_auth_cookie();

$context->httpResponse()
        ->cookie( 'access_token', '', [
	        'expires'  => time(),
	        'httponly' => true,
	        'secure'   => constant( 'AUTH_TOKEN_HTTPS' ),
	        'domain'   => parse_url( constant( 'ROOT_APP_URL' ), PHP_URL_HOST )
        ] )
        ->status( 204 )
        ->send();