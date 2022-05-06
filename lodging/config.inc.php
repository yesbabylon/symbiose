<?php
namespace config;

define('DEFAULT_APP', 'apps');

/** 
* Constants defined in this section are mandatory but can be modified/re-defined in customs config.inc.php (i.e.: packages/[package_name]/config.inc.php)
*
*/

// flag constant allowing to detect if config has been exported
define('EXPORT_FLAG', true);


/**
* File transfer parameters
*/
// maximum authorized size for file upload (in octet)
// keep in mind that this parameter does not override the PHP 'upload_max_filesize' directive
// so it can be more restrictive but will not be effective if set higher
// ('upload_max_filesize' and 'post_max_size' are PHP_INI_PERDIR directives and must be defined in php.ini)

define('UPLOAD_MAX_FILE_SIZE', 64*1024*1024);		// set upload limit to 64Mo


/**
* Locale parameters
*/
date_default_timezone_set('Europe/Brussels');


