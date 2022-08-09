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


list($params, $providers) = announce([
    'description'   => 'Retrieves all products that are currently sellable at POS for a given center.',
    'extends'       => 'core_model_collect',
    'params'        => [
        'entity' =>  [
            'description'   => 'Full name (including namespace) of the class to look into (e.g. \'core\\User\').',
            'type'          => 'string',
            'default'       => 'lodging\sale\catalog\Product'
        ],
        'center_id' => [
            'type'              => 'many2one',
            'foreign_object'    => 'lodging\identity\Center',
            'description'       => "The center to which the booking relates to.",
            'required'          => true
        ],
        'filter' => [
            'type'              => 'string',
            'default'           => ''
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

/*
    Retrieve all models belonging to the POS category
*/
$category = Category::search([['code', '=', 'POS']])->read(['id', 'code', 'product_models_ids'])->first();

/* 
    Fetch all products belonging to the targeted models
*/
$products_ids = Product::search([
        ['name', 'ilike', '%'.$params['filter'].'%'],
        ['can_sell', '=', true],
        ['product_model_id', 'in', $category['product_models_ids'] ]
    ])
    ->ids();

/* 
    Keep only products for which a price can be retrieved
*/
// retrieve pricelist catégory from center
$center = Center::id($params['center_id'])->read(['price_list_category_id'])->first();

// find the first Price List that matches the criteria from the order with (shortest duration first)
$price_lists_ids = PriceList::search([
        ['price_list_category_id', '=', $center['price_list_category_id']],
        ['date_from', '<=', time()],
        ['date_to', '>=', time()],
        ['status', '=', 'published'],
        ['is_active', '=', true]
    ],
    ['sort' => ['duration' => 'asc']])
    ->ids();

if(count($price_lists_ids)) {    
    // get all prices for first found price list
    $prices = Price::search([
                        ['price_list_id', '=', $price_lists_ids[0]],
                        ['product_id', 'in', $products_ids]
                    ])
                    ->read(['product_id'])
                    ->get();
    // retrieve matching products ids
    $products_ids = array_map(function($a) {return $a['product_id'];}, $prices);
    // read products from matching ids
    $result = Product::ids($products_ids)
                    ->read(['sku', 'label', 'product_model_id' => ['name']])
                    ->get(true);
}

$context->httpResponse()
        ->body($result)
        ->send();