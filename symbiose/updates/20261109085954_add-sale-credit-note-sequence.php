<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use symbiose\setting\Setting;

Setting::assert_sequence('sale', 'accounting', 'credit_note.sequence', 1, ['organization_id' => 1]);
