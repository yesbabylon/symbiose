<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\sale\booking\BookingLineGroup;
use lodging\sale\booking\SojournProductModel;
use lodging\sale\booking\SojournProductModelRentalUnitAssignement;

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


/*
Mises à jour SQL préalables : 
SQL : 


* bookinglinerentalunitassignment
ajout champ product_model (copie booking_line)

UPDATE `lodging_sale_booking_bookinglinerentalunitassignement` SET `product_model_id`= (select `product_model_id` from `sale_catalog_product` where `sale_catalog_product`.`id` = (select `product_id` from `sale_booking_bookingline` where `sale_booking_bookingline`.`id` = `lodging_sale_booking_bookinglinerentalunitassignement`.`booking_line_id`))


* recopier la table vers deux tables lodging_sale_booking_sojournproductmodel et lodging_sale_booking_sojournproductmodelrentalunitassignement
* supprimer les clés d'unités


dans lodging_sale_booking_sojournproductmodel 
* supprimer colonne booking_line_id

dans lodging_sale_booking_sojournproductmodelrentalunitassignement
* dupliquer la colonne ID vers sojourn_product_model_id
* supprimer colonne booking_line_id



 */


$booking_line_groups = BookingLineGroup::search([])->read(['sojourn_product_models_ids'])->get();


foreach($booking_line_groups as $gid => $group) {
    $map = [];
    $product_models = SojournProductModel::ids($group['sojourn_product_models_ids'])->read(['rental_unit_assignments_ids', 'product_model_id'])->get();

    // keep track of the first SPM for each product_model
    foreach($product_models as $mid => $model) {
        if(!isset($map[$model['product_model_id']])) {
            $map[$model['product_model_id']] = $mid;
        }
    }
    /*
    regrouper les lignes avec booking_line_group_id et product_model_id identique

    assigner à un même sojourn_product_model tous les sojournProductModelRentalUnitAssignement dont le sojourn_prodct_model_id.product_model_id sont égaux

    #memo : we need to temporatily disable SojournProductModelRentalUnitAssignement::canupdate($om, $oids, $values, $lang=DEFAULT_LANG)

    */
    foreach($product_models as $model) {
        $assignments = SojournProductModelRentalUnitAssignement::ids($model['rental_unit_assignments_ids'])->read(['sojourn_product_model_id' => ['id', 'product_model_id']])->get();
        foreach($assignments as $aid => $assignment) {
            $product_model_id = $assignment['sojourn_product_model_id']['product_model_id'];

            if($assignment['sojourn_product_model_id']['id'] != $map[$product_model_id]) {
                SojournProductModelRentalUnitAssignement::id($aid)->update(['sojourn_product_model_id' => $map[$product_model_id]]);
            }
        }
    }

    // pass 2 : remove empty SPMs
    $product_models = SojournProductModel::ids($group['sojourn_product_models_ids'])->read(['rental_unit_assignments_ids'])->get();
    foreach($product_models as $mid => $model) {
        if(count($model['rental_unit_assignments_ids']) == 0) {
            SojournProductModel::id($mid)->delete(true);
        }
    }

}

// reset computed fields
SojournProductModel::search()->update(['qty' => null, 'is_accomodation' => null])->get();

$context->httpResponse()
        ->status(204)
        ->send();