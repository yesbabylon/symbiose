<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

$alert_triggers_script = EQ_BASEDIR . '/packages/infra/init/data/scripts/21-create-alert-triggers.php';
if(!is_file($alert_triggers_script)) {
    throw new Exception('default_infra_alert_triggers_script_not_accessible', EQ_ERROR_INVALID_CONFIG);
}

require $alert_triggers_script;
