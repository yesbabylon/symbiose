<?php

use equal\orm\ObjectManager;
use equal\php\Context;

list( $params, $providers ) = eQual::announce( [
	'description' => "Generate a configuration file based on a set of params.",
	'response'    => [
		'content-type'  => 'text/plain',
		'charset'       => 'UTF-8',
		'accept-origin' => '*'
	],
	'params'      => [
		'dbms'        => [
			'description' => 'DMBS software brand.',
			'type'        => 'string',
			'selection'   => [
				'MYSQL',
				'SQLSRV',
				'MARIADB',
				'SQLITE',
				'POSTGRESQL'
			]
		],
		'db_host'     => [
			'description' => 'The host of the database.',
			'type'        => 'string'
		],
		'db_port'     => [
			'description' => 'The port of the database.',
			'type'        => 'integer'
		],
		'db_name'     => [
			'description' => 'The name of the database.',
			'type'        => 'string'
		],
		'db_username' => [
			'description' => 'The username of the database.',
			'type'        => 'string'
		],
		'db_password' => [
			'description' => 'The password of the database.',
			'type'        => 'string'
		],
		'app_url'     => [
			'description' => 'The URL of the application.',
			'type'        => 'string'
		]
	],
	'providers'   => [ 'context', 'orm' ]
] );

/**
 * @var Context $context
 * @var ObjectManager $orm
 */
list( $context, $orm ) = [ $providers['context'], $providers['orm'] ];

// Define the configuration content
$config_content = [
	"DB_DBMS"                    => $params['dbms'],
	"DB_HOST"                    => $params['db_host'],
	"DB_PORT"                    => $params['db_port'],
	"DB_USER"                    => $params['db_username'],
	"DB_PASSWORD"                => $params['db_password'],
	"DB_NAME"                    => $params['db_name'],
	"ROOT_APP_URL"               => $params['app_url'],
	"AUTH_SECRET_KEY"            => bin2hex( random_bytes( 32 ) ),
	"AUTH_ACCESS_TOKEN_VALIDITY" => "1d",
	"USER_ACCOUNT_DISPLAYNAME"   => "nickname"
];

// Convert the configuration content to JSON format
$config_json = json_encode( $config_content, JSON_UNESCAPED_SLASHES );

// Define the file path
$file_path = 'config/config.json';

// Check if the file is writable or if the folder is writable
if ( ! is_writable( $file_path ) && ! is_writable( dirname( $file_path ) ) ) {
	// Handle error: File or directory is not writable
	throw new Exception( 'Error: Unable to write to file or directory.', QN_ERROR_NOT_ALLOWED );
}

// Store the file and overwrite if it already exists
file_put_contents( $file_path, $config_json );

// Send the response
$context
	->httpResponse()
	->setStatus( 201 )
	->setBody( [ 'message' => 'Configuration file generated successfully.' ] )
	->setContentType( 'application/json' );

