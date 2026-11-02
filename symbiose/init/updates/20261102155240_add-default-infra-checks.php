<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\server\Check;

$checks_file = EQ_BASEDIR . '/packages/infra/init/data/infra_server_Check.json';
$checks_json = file_get_contents($checks_file);

if($checks_json === false) {
    throw new Exception('default_infra_checks_not_accessible', EQ_ERROR_INVALID_CONFIG);
}

$definitions = json_decode($checks_json, true, 512, JSON_THROW_ON_ERROR);
$checks_data = null;

if(!is_array($definitions)) {
    throw new Exception('invalid_default_infra_checks', EQ_ERROR_INVALID_CONFIG);
}

foreach($definitions as $definition) {
    if(($definition['name'] ?? null) === 'infra\\server\\Check') {
        $checks_data = $definition['data'] ?? null;
        break;
    }
}

if(!is_array($checks_data)) {
    throw new Exception('invalid_default_infra_checks', EQ_ERROR_INVALID_CONFIG);
}

foreach($checks_data as $check_data) {
    if(!is_array($check_data)) {
        throw new Exception('invalid_default_infra_check', EQ_ERROR_INVALID_CONFIG);
    }

    $code = $check_data['code'] ?? null;

    if(!is_string($code) || $code === '') {
        throw new Exception('invalid_default_infra_check_code', EQ_ERROR_INVALID_CONFIG);
    }

    $check = Check::search(['code', '=', $code]);
    $check_ids = $check->ids();

    if(count($check_ids) > 1) {
        throw new Exception(
            "Several infra checks use code '{$code}'.",
            EQ_ERROR_CONFLICT_OBJECT
        );
    }

    if(empty($check_ids)) {
        Check::create($check_data);
    }
}
