<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\catalog\Product;
use sale\catalog\ProductAttribute;

// announce script and fetch parameters values
list($params, $providers) = announce([
    'description'	=>	"Send an instant email with given details with a booking contract as attachment.",
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

$products = Product::search(['can_sell', '=', true])->read(['is_pack', 'sku', 'product_attributes_ids'])->get();

// A => pas de tranche d'âge
$range_map = [
    '0_3'	=> 5,
    '3_6'	=> 4,
    '3_12'	=> 3,
    '6_12'	=> 3,
    '26_99'	=> 1,
    '12_26'	=> 2,
    'M12'	=> 3,
    'M26'	=> 2,
    'M16'	=> 3,
    '16_99'	=> 1
];

foreach($products as $pid => $product) {
    // ignore packs
    if($product['is_pack']) {
        continue;
    }
    // use SKU to search for "All" age indicator
    if(substr($product['sku'], -2) == '-A') {
        Product::id($pid)->update(['has_age_range' =>  false, 'age_range_id' => null]);
        continue;
    }
    $attributes = ProductAttribute::ids($product['product_attributes_ids'])->read(['option_value_id' => ['option_id', 'value']])->get();
    foreach($attributes as $aid => $attribute) {
        if($attribute['option_value_id'] && $attribute['option_value_id']['option_id'] == 1) {
            if($attribute['option_value_id']['value'] == 'A') {
                Product::id($pid)->update(['has_age_range' =>  false, 'age_range_id' => null]);
            }
            else {
                Product::id($pid)->update(['has_age_range' =>  true, 'age_range_id' => $range_map[$attribute['option_value_id']['value']]]);
            }
        }
    }
}

$context->httpResponse()
        ->status(204)
        ->send();