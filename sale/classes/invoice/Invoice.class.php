<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\invoice;

class Invoice extends \finance\accounting\Invoice {

    public static function getDescription() {
        return "A sale invoice is a legal document issued after some goods have been sold to a purchaser.";
    }

}