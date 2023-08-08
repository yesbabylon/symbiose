<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace inventory\identity;

/**
 * This class is meant to be used as an interface for other entities (organisation and partner).
 * An identity is either a legal or natural person (Legal persons are Organisations).
 * An organisation usually has several partners of various kind (contact, employee, provider, customer, ...).
 */
class Identity extends \identity\Identity {


    public static function getColumns() {
        return [
        ];
    }
}