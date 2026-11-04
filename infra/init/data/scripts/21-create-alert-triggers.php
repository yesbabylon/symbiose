<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use core\alert\MessageModel;
use infra\server\AlertTrigger;
use infra\server\Check;
use infra\server\Instance;
use infra\server\Server;

$definitions = [
    [
        'name'              => 'server.down',
        'label'             => 'Server down',
        'description'       => 'Server is not reachable and may be not running.',
        'type'              => 'infra',
        'with_send_email'   => true,
        'translations'      => [
            'fr' => [
                'label'         => 'Serveur indisponible',
                'description'   => "Le serveur n'est pas joignable et pourrait ne pas être en cours d'exécution."
            ]
        ],
        'object_class'      => Server::class,
        'check_codes'       => ['server-up'],
        'remove_check_codes'=> ['server-b2-api-error'],
        'repetition'        => 2
    ],
    [
        'name'              => 'server.b2-api-error',
        'label'             => 'Server B2 API error',
        'description'       => 'The B2 API returned an error five times in a row for the server.',
        'type'              => 'infra',
        'with_send_email'   => true,
        'translations'      => [
            'fr' => [
                'label'         => 'Erreur API B2 du serveur',
                'description'   => "L'API B2 a renvoyé une erreur cinq fois de suite pour le serveur."
            ]
        ],
        'object_class'      => Server::class,
        'check_codes'       => ['server-b2-api-error'],
        'repetition'        => 5
    ],
    [
        'name'              => 'server.low-resources',
        'label'             => 'Server low resources',
        'description'       => 'Disk: {status_data.instant.dsk_use} | CPU: {status_data.instant.cpu_use} | Ram: {status_data.instant.ram_use} | Total proc: {status_data.instant.total_proc}',
        'type'              => 'infra',
        'variables'         => json_encode([
            'status_data.instant.dsk_use',
            'status_data.instant.cpu_use',
            'status_data.instant.ram_use',
            'status_data.instant.total_proc'
        ]),
        'with_send_email'   => true,
        'translations'      => [
            'fr' => [
                'label'         => 'Ressources serveur faibles',
                'description'   => 'Disque : {status_data.instant.dsk_use} | CPU : {status_data.instant.cpu_use} | RAM : {status_data.instant.ram_use} | Total processus : {status_data.instant.total_proc}'
            ]
        ],
        'object_class'      => Server::class,
        'check_codes'       => [
            'server-disk-usage',
            'server-cpu-usage',
            'server-ram-usage',
            'server-total-processes'
        ],
        'repetition'        => 2
    ],
    [
        'name'              => 'instance.down',
        'label'             => 'Instance down',
        'description'       => "The instance isn't running.",
        'type'              => 'infra',
        'with_send_email'   => true,
        'translations'      => [
            'fr' => [
                'label'         => 'Instance indisponible',
                'description'   => "L'instance n'est pas en cours d'exécution."
            ]
        ],
        'object_class'      => Instance::class,
        'check_codes'       => ['instance-up'],
        'remove_check_codes'=> ['instance-b2-api-error'],
        'repetition'        => 2
    ],
    [
        'name'              => 'instance.b2-api-error',
        'label'             => 'Instance B2 API error',
        'description'       => 'The B2 API returned an error five times in a row for the instance.',
        'type'              => 'infra',
        'with_send_email'   => true,
        'translations'      => [
            'fr' => [
                'label'         => "Erreur API B2 de l'instance",
                'description'   => "L'API B2 a renvoyé une erreur cinq fois de suite pour l'instance."
            ]
        ],
        'object_class'      => Instance::class,
        'check_codes'       => ['instance-b2-api-error'],
        'repetition'        => 5
    ],
    [
        'name'              => 'instance.low-resources',
        'label'             => 'Instance low resources',
        'description'       => 'Disk: {status_data.instant.dsk_use} | CPU: {status_data.instant.cpu_use} | Ram: {status_data.instant.ram_use} | Total proc: {status_data.instant.total_proc}',
        'type'              => 'infra',
        'variables'         => json_encode([
            'status_data.instant.dsk_use',
            'status_data.instant.cpu_use',
            'status_data.instant.ram_use',
            'status_data.instant.total_proc'
        ]),
        'with_send_email'   => true,
        'translations'      => [
            'fr' => [
                'label'         => "Ressources de l'instance faibles",
                'description'   => 'Disque : {status_data.instant.dsk_use} | CPU : {status_data.instant.cpu_use} | RAM : {status_data.instant.ram_use} | Total processus : {status_data.instant.total_proc}'
            ]
        ],
        'object_class'      => Instance::class,
        'check_codes'       => [
            'instance-disk-usage',
            'instance-cpu-usage',
            'instance-ram-usage',
            'instance-total-processes'
        ],
        'repetition'        => 2
    ]
];

foreach($definitions as $definition) {
    $translations = $definition['translations'];
    $object_class = $definition['object_class'];
    $check_codes = $definition['check_codes'];
    $remove_check_codes = $definition['remove_check_codes'] ?? [];
    $repetition = $definition['repetition'];
    unset(
        $definition['translations'],
        $definition['object_class'],
        $definition['check_codes'],
        $definition['remove_check_codes'],
        $definition['repetition']
    );

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

    $checks_ids = [];
    foreach($check_codes as $check_code) {
        $check_ids = Check::search(['code', '=', $check_code])->ids();
        if(count($check_ids) !== 1) {
            throw new Exception("Unable to resolve check '$check_code'.", EQ_ERROR_INVALID_CONFIG);
        }

        $checks_ids[] = reset($check_ids);
    }

    $checks_ids_to_remove = [];
    foreach($remove_check_codes as $check_code) {
        $check_ids = Check::search(['code', '=', $check_code])->ids();
        if(count($check_ids) !== 1) {
            throw new Exception("Unable to resolve check '$check_code'.", EQ_ERROR_INVALID_CONFIG);
        }

        $checks_ids_to_remove[] = -reset($check_ids);
    }

    $message_model_ids = $message_model->ids();
    $message_model_id = reset($message_model_ids);
    $alert_trigger = AlertTrigger::search([
        ['object_class', '=', $object_class],
        ['object_id', '=', null],
        ['message_model_id', '=', $message_model_id]
    ]);
    $alert_trigger_data = [
        'object_class'      => $object_class,
        'repetition'        => $repetition,
        'checks_ids'        => $checks_ids,
        'message_model_id'  => $message_model_id
    ];

    if(count($alert_trigger->ids())) {
        $alert_trigger_data['checks_ids'] = array_merge($checks_ids, $checks_ids_to_remove);
        $alert_trigger->update($alert_trigger_data);
    }
    else {
        AlertTrigger::create($alert_trigger_data);
    }
}
