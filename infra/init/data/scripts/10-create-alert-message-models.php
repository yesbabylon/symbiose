<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\alert\MessageModel;

$definition = [
    'name'          => 'infra.subscription.check.expiration',
    'label'         => 'Subscription expired',
    'description'   => 'Subscription expired or upcoming expiration.',
    'type'          => 'infra'
];
$translations = [
    'fr' => [
        'label'         => 'Abonnement expiré',
        'description'   => "Abonnement expiré ou sur le point d'expirer."
    ]
];

$message_model = MessageModel::search(['name', '=', $definition['name']]);
if(count($message_model->ids())) {
    $message_model->update($definition, 'en');
}
else {
    $message_model = MessageModel::create($definition, 'en');
}

foreach($translations as $lang => $translation) {
    $message_model->update($translation, $lang);
}
