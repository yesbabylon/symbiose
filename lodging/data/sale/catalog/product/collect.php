<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use lodging\sale\catalog\Product;
use sale\catalog\Category;
use lodging\identity\Center;
use sale\price\PriceList;
use sale\price\Price;
use equal\orm\Domain;
use equal\orm\DomainCondition;

list($params, $providers) = announce([
    'description'   => 'Retrieves all products that are currently sellable for a given center and, if related Center Office has defined Product Favorites, return those first.',
    'extends'       => 'core_model_collect',
    'params'        => [
        'entity' =>  [
            'description'       => 'Full name (including namespace) of the class to look into (e.g. \'core\\User\').',
            'type'              => 'string',
            'default'           => 'lodging\sale\catalog\Product'
        ],
        'domain' => [
            'description'   => 'Criterias that results have to match (serie of conjunctions)',
            'type'          => 'array',
            'default'       => []
        ],
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'lodging\identity\Center',
            'description'       => "The center to which the booking relates to.",
            'required'          => true
        ]
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => [ 'context', 'orm' ]
]);

/**
 * @var \equal\php\Context $context
 * @var \equal\orm\ObjectManager $orm
 */
list($context, $orm) = [ $providers['context'], $providers['orm'] ];

$result = [];

$fields = ['id', 'name', 'is_pack', 'sku', 'can_sell'];

/*
    Keep only products that can be sold by the given Center
*/
// retrieve center and related product favorites, if any
$center = Center::id($params['center_id'])
    ->read([
        'id',
        'product_families_ids' => ['product_models_ids'],
        'center_office_id' => ['product_favorites_ids' => ['product_id']]
    ])
    ->first();


if(!$center) {
    throw new Exception("unknown_center", QN_ERROR_UNKNOWN_OBJECT);
}

// retieve Product families from given center
$product_models_ids_map = [];
if(isset($center['product_families_ids']) && $center['product_families_ids'] > 0) {
    foreach($center['product_families_ids'] as $family) {
        foreach($family['product_models_ids'] as $product_model_id) {
            $product_models_ids_map[$product_model_id] = true;
        }
    }
}


$products_ids = Product::search([
        // limit products to ones marked as 'can_sell'
        ['can_sell', '=', true],
        // limit products to the ones part of the center's product families
        ['product_model_id', 'in', array_keys($product_models_ids_map)]
    ])
    ->ids();

// if center office has set some favorites, retrieve related products
$favorites = [];

if(isset($center['center_office_id']['product_favorites_ids'])) {
    $favorites = $center['center_office_id']['product_favorites_ids'];
    $favorites_ids_map = [];
    if($favorites > 0) {
        foreach($favorites as $favorite) {
            $favorites_ids_map[$favorite['product_id']] = true;
        }
    }

    // remove favorites from found products
    $products_ids = array_filter($products_ids, function ($id) use(&$favorites_ids_map) {
                return !isset($favorites_ids_map[$id]);
            }
        );

    // read favorites
    // #memo - ProductFavorite schema specifies the field `order` for sorting
    $favorites = Product::ids(array_keys($favorites_ids_map))
        ->read($fields)
        ->adapt('txt')
        ->get(true);
}

// read products (without favorites)
$products = Product::ids($products_ids)
    ->read($fields)
    ->adapt('txt')
    ->get(true);

// sort products by name (on ascending order)
usort($products, function($a, $b) {return strcmp($a['name'], $b['name']);});

// return favorites + remaining products
$products_list = array_merge($favorites, $products);

// filter results according to received domain
$domain = new Domain($params['domain']);
$domain->addCondition(new DomainCondition('can_sell', '=', true));

foreach($products_list as $index => $product) {
    if($domain->evaluate($product)) {
        $result[] = $product;
    }
}

$context->httpResponse()
        ->body($result)
        ->send();