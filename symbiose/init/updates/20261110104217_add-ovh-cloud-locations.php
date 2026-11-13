<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

$cloud_locations_script = EQ_BASEDIR . '/packages/infra/init/data/scripts/30-create-ovh-cloud-locations.php';
if(!is_file($cloud_locations_script)) {
    throw new Exception('ovh_cloud_locations_script_not_accessible', EQ_ERROR_INVALID_CONFIG);
}

require $cloud_locations_script;
