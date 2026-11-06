<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

$checks_script = EQ_BASEDIR . '/packages/infra/init/data/scripts/20-create-server-checks.php';
if(!is_file($checks_script)) {
    throw new Exception('default_infra_checks_script_not_accessible', EQ_ERROR_INVALID_CONFIG);
}

require $checks_script;
