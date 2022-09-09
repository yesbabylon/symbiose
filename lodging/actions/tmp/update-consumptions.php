<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\sale\booking\Consumption;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"",
    'params' 		=>	[
    ],
    'access' => [
        'visibility'        => 'private'
    ],
    'response'      => [
        'content-type'      => 'application/json',
        'charset'           => 'utf-8',
        'accept-origin'     => '*'
    ],
    'providers'     => ['context']
]);


// initalise local vars with inputs
list($context) = [ $providers['context'] ];


$consumptions = Consumption::search([])->read(['product_id' => ['has_age_range', 'age_range_id', 'product_model_id']])->get();


foreach($consumptions as $oid => $consumption) {
    $values = [
        'product_model_id' => $consumption['product_id']['product_model_id']
    ];
    if($consumption['product_id']['has_age_range']) {
        $values['age_range_id'] = $consumption['product_id']['age_range_id'];
    }
    Consumption::id($oid)->update($values);
}


$context->httpResponse()
        ->status(204)
        ->send();