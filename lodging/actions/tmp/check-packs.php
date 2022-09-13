<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
use sale\catalog\ProductModel;
use sale\catalog\PackLine;

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

$models = ProductModel::search([['can_sell', '=', true], ['is_pack', '=', true]])->read(['name', 'products_ids' => ['sku']])->get();

/*
    y a t-til des packs qui n'ont pas de variante "tous les âges" ?

    -> prendre les modèles de produits is_pack
*/

$result = [];
foreach($models as $oid => $model) {
    $found = false;
    foreach($model['products_ids'] as $pid => $product) {
        if(substr($product['sku'], -2) == '-A') {
            $found = true;
        }
    }
    if(count($model['products_ids']) && !$found) {
        $result[] = [$oid => $model['name']];
    }
}

$context->httpResponse()
        ->body($result)
        ->send();


/*

modèles de PACKS sans pack existant (product)
[
    {
        "13": "B&B"
    },
    {
        "17": "B&B Confort"
    },
    {
        "139": "Chambre confort 1 personne"
    },
    {
        "140": "Chambre confort 2 personnes"
    },
    {
        "141": "Chambre confort 4 personnes"
    },
    {
        "142": "Chambre confort Famille"
    },
    {
        "229": "WE Balades et Terroir - Enfant"
    },
    {
        "527": "Chambre base 1 personne"
    },
    {
        "528": "Chambre base 2 personnes"
    },
    {
        "529": "Chambre base 4 personnes"
    },
    {
        "530": "Chambre base Famille"
    },
    {
        "538": "Dortoir B&B"
    },
    {
        "539": " Dortoir DP"
    },
    {
        "540": "Dortoir PC"
    },
    {
        "559": "Chambre 2 personnes"
    },
    {
        "589": "Pension compl\u00e8te Kazou 2020"
    },
    {
        "591": "Package OT"
    },
    {
        "594": "Package MT"
    },
    {
        "672": "DP dortoir"
    },
    {
        "673": "PC dortoir"
    },
    {
        "1068": "S\u00e9jour Wonderbox 69,90\u20ac"
    }
]


PACKS avec packs mais sans pack généraique (-A)
[
    {
        "229": "WE Balades et Terroir - Enfant"
    },
    {
        "589": "Pension compl\u00e8te Kazou 2020"
    }
]

*/