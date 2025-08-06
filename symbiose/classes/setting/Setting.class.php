<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace symbiose\setting;

class Setting extends \core\setting\Setting {

    protected static function getSelectorKeys() {
        return ['user_id', 'organisation_id'];
    }

}
