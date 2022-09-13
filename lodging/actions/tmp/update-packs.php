<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\catalog\Product;
use sale\catalog\PackLine;

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

$products = Product::search([['can_sell', '=', true], ['is_pack', '=', true]])->read(['pack_lines_ids' => ['child_product_id' => 'product_model_id']])->get();

/*
    (on ne modifie pas les packs mais on ajoute des infos et on rend invisibles les anciennes infos)

    pour chaque ligne de pack,
    assigner le child_product_model_id correspondante au product_model_id du  child_product_id actuel
*/

foreach($products as $pid => $product) {
    foreach($product['pack_lines_ids'] as $lid => $line) {
        PackLine::id($lid)->update(['child_product_model_id' => $line['child_product_id']['product_model_id']]);
    }
}

$context->httpResponse()
        ->status(204)
        ->send();