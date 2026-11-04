<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use infra\server\Check;
use infra\server\Instance;
use infra\server\Server;

$definitions = [
    [
        'code'          => 'server-up',
        'description'   => 'Example server check: the server must be reachable.',
        'translations'  => [
            'fr' => [
                'description' => 'Exemple de contrôle serveur : le serveur doit être joignable.'
            ]
        ],
        'object_class'  => Server::class,
        'status_path'   => 'state.up',
        'operator'      => 'eq',
        'check_value'   => 'true',
        'value'         => ''
    ],
    [
        'code'          => 'server-b2-api-error',
        'description'   => 'Example B2 server check: the API response must not contain an error.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle serveur B2 : la réponse de l'API ne doit pas contenir d'erreur."
            ]
        ],
        'object_class'  => Server::class,
        'status_path'   => 'error',
        'operator'      => 'not_exists',
        'check_value'   => '',
        'value'         => ''
    ],
    [
        'code'          => 'server-total-processes',
        'description'   => 'Example server check: adapt the maximum process count to the server workload.',
        'translations'  => [
            'fr' => [
                'description' => 'Exemple de contrôle serveur : adaptez le nombre maximal de processus à la charge de travail du serveur.'
            ]
        ],
        'object_class'  => Server::class,
        'status_path'   => 'instant.total_proc',
        'operator'      => 'lte',
        'check_value'   => '500',
        'value'         => ''
    ],
    [
        'code'          => 'server-ram-usage',
        'description'   => 'Example server check: RAM usage must not exceed 90%.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle serveur : l'utilisation de la RAM ne doit pas dépasser 90 %."
            ]
        ],
        'object_class'  => Server::class,
        'status_path'   => 'instant.ram_use',
        'operator'      => 'lte',
        'check_value'   => '90',
        'value'         => ''
    ],
    [
        'code'          => 'server-cpu-usage',
        'description'   => 'Example server check: CPU usage must not exceed 90%.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle serveur : l'utilisation du CPU ne doit pas dépasser 90 %."
            ]
        ],
        'object_class'  => Server::class,
        'status_path'   => 'instant.cpu_use',
        'operator'      => 'lte',
        'check_value'   => '90',
        'value'         => ''
    ],
    [
        'code'          => 'server-disk-usage',
        'description'   => 'Example server check: disk usage must not exceed 90%.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle serveur : l'utilisation du disque ne doit pas dépasser 90 %."
            ]
        ],
        'object_class'  => Server::class,
        'status_path'   => 'instant.dsk_use',
        'operator'      => 'lte',
        'check_value'   => '90',
        'value'         => ''
    ],
    [
        'code'          => 'server-b2-mysql-memory',
        'description'   => 'Example B2 server check: MySQL memory usage must not exceed 90%.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle serveur B2 : l'utilisation de la mémoire par MySQL ne doit pas dépasser 90 %."
            ]
        ],
        'object_class'  => Server::class,
        'status_path'   => 'instant.mysql_mem',
        'operator'      => 'lte',
        'check_value'   => '90',
        'value'         => ''
    ],
    [
        'code'          => 'server-b2-apache-memory',
        'description'   => 'Example B2 server check: Apache memory usage must not exceed 90%.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle serveur B2 : l'utilisation de la mémoire par Apache ne doit pas dépasser 90 %."
            ]
        ],
        'object_class'  => Server::class,
        'status_path'   => 'instant.apache_mem',
        'operator'      => 'lte',
        'check_value'   => '90',
        'value'         => ''
    ],
    [
        'code'          => 'server-b2-nginx-memory',
        'description'   => 'Example B2 server check: Nginx memory usage must not exceed 90%.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle serveur B2 : l'utilisation de la mémoire par Nginx ne doit pas dépasser 90 %."
            ]
        ],
        'object_class'  => Server::class,
        'status_path'   => 'instant.nginx_mem',
        'operator'      => 'lte',
        'check_value'   => '90',
        'value'         => ''
    ],
    [
        'code'          => 'server-b2-apache-processes',
        'description'   => 'Example B2 server check: at least one Apache process must be running.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle serveur B2 : au moins un processus Apache doit être en cours d'exécution."
            ]
        ],
        'object_class'  => Server::class,
        'status_path'   => 'instant.apache_proc',
        'operator'      => 'gte',
        'check_value'   => '1',
        'value'         => ''
    ],
    [
        'code'          => 'server-b2-nginx-processes',
        'description'   => 'Example B2 server check: at least one Nginx process must be running.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle serveur B2 : au moins un processus Nginx doit être en cours d'exécution."
            ]
        ],
        'object_class'  => Server::class,
        'status_path'   => 'instant.nginx_proc',
        'operator'      => 'gte',
        'check_value'   => '1',
        'value'         => ''
    ],
    [
        'code'          => 'server-b2-mysql-processes',
        'description'   => 'Example B2 server check: at least one MySQL process must be running.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle serveur B2 : au moins un processus MySQL doit être en cours d'exécution."
            ]
        ],
        'object_class'  => Server::class,
        'status_path'   => 'instant.mysql_proc',
        'operator'      => 'gte',
        'check_value'   => '1',
        'value'         => ''
    ],
    [
        'code'          => 'server-k2-backup-tokens',
        'description'   => 'Example K2 server check: at least one backup token must be available.',
        'translations'  => [
            'fr' => [
                'description' => 'Exemple de contrôle serveur K2 : au moins un jeton de sauvegarde doit être disponible.'
            ]
        ],
        'object_class'  => Server::class,
        'status_path'   => 'instant.backup_tokens_qty',
        'operator'      => 'gte',
        'check_value'   => '1',
        'value'         => ''
    ],
    [
        'code'          => 'server-k2-backup-disk-usage',
        'description'   => 'Example K2 server check: backup disk usage must not exceed 90%.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle serveur K2 : l'utilisation du disque de sauvegarde ne doit pas dépasser 90 %."
            ]
        ],
        'object_class'  => Server::class,
        'status_path'   => 'instant.backups_disk',
        'operator'      => 'lte',
        'check_value'   => '90',
        'value'         => ''
    ],
    [
        'code'          => 'instance-up',
        'description'   => 'Example instance check: the server must be reachable and the instance running.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle d'instance : le serveur doit être joignable et l'instance doit être en cours d'exécution."
            ]
        ],
        'object_class'  => Instance::class,
        'status_path'   => 'state.up',
        'operator'      => 'eq',
        'check_value'   => 'true',
        'value'         => ''
    ],
    [
        'code'          => 'instance-b2-api-error',
        'description'   => 'Example B2 instance check: the API response must not contain an error.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle d'instance B2 : la réponse de l'API ne doit pas contenir d'erreur."
            ]
        ],
        'object_class'  => Instance::class,
        'status_path'   => 'error',
        'operator'      => 'not_exists',
        'check_value'   => '',
        'value'         => ''
    ],
    [
        'code'          => 'instance-not-in-maintenance',
        'description'   => 'Example B2 instance check: maintenance mode must be disabled.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle d'instance B2 : le mode maintenance doit être désactivé."
            ]
        ],
        'object_class'  => Instance::class,
        'status_path'   => 'state.maintenance',
        'operator'      => 'eq',
        'check_value'   => 'false',
        'value'         => ''
    ],
    [
        'code'          => 'instance-total-processes',
        'description'   => 'Example instance check: adapt the maximum process count to the instance workload.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle d'instance : adaptez le nombre maximal de processus à la charge de travail de l'instance."
            ]
        ],
        'object_class'  => Instance::class,
        'status_path'   => 'instant.total_proc',
        'operator'      => 'lte',
        'check_value'   => '200',
        'value'         => ''
    ],
    [
        'code'          => 'instance-ram-usage',
        'description'   => 'Example instance check: RAM usage must not exceed 90%.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle d'instance : l'utilisation de la RAM ne doit pas dépasser 90 %."
            ]
        ],
        'object_class'  => Instance::class,
        'status_path'   => 'instant.ram_use',
        'operator'      => 'lte',
        'check_value'   => '90',
        'value'         => ''
    ],
    [
        'code'          => 'instance-cpu-usage',
        'description'   => 'Example instance check: CPU usage must not exceed 90%.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle d'instance : l'utilisation du CPU ne doit pas dépasser 90 %."
            ]
        ],
        'object_class'  => Instance::class,
        'status_path'   => 'instant.cpu_use',
        'operator'      => 'lte',
        'check_value'   => '90',
        'value'         => ''
    ],
    [
        'code'          => 'instance-disk-usage',
        'description'   => 'Example instance check: disk usage must not exceed 90%.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle d'instance : l'utilisation du disque ne doit pas dépasser 90 %."
            ]
        ],
        'object_class'  => Instance::class,
        'status_path'   => 'instant.dsk_use',
        'operator'      => 'lte',
        'check_value'   => '90',
        'value'         => ''
    ],
    [
        'code'          => 'instance-php-version',
        'description'   => 'Example Equal instance check: PHP must use the expected version.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle d'instance eQual : PHP doit utiliser la version attendue."
            ]
        ],
        'object_class'  => Instance::class,
        'status_path'   => 'config.php.version',
        'operator'      => 'eq',
        'check_value'   => '8.3.36',
        'value'         => ''
    ],
    [
        'code'          => 'instance-php-build-date',
        'description'   => 'Example Equal instance check: PHP must have been built on or after the expected date.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle d'instance eQual : PHP doit avoir été compilé à la date attendue ou après celle-ci."
            ]
        ],
        'object_class'  => Instance::class,
        'status_path'   => 'config.php.build_date',
        'operator'      => 'gte',
        'check_value'   => '2026-10-21T15:56:44+00:00',
        'value'         => ''
    ],
    [
        'code'          => 'instance-equal-branch',
        'description'   => 'Example Equal instance check: the Equal framework source must use the expected branch.',
        'translations'  => [
            'fr' => [
                'description' => "Exemple de contrôle d'instance eQual : les sources du framework eQual doivent utiliser la branche attendue."
            ]
        ],
        'object_class'  => Instance::class,
        'status_path'   => 'config.sources.0.branch',
        'operator'      => 'eq',
        'check_value'   => '2.0.2',
        'value'         => ''
    ]
];

foreach($definitions as $definition) {
    $translations = $definition['translations'];
    unset($definition['translations']);

    $check = Check::search(['code', '=', $definition['code']]);
    if(count($check->ids())) {
        $check->update($definition, 'en');
    }
    else {
        $check = Check::create($definition, 'en');
    }

    foreach($translations as $lang => $translation) {
        $check->update($translation, $lang);
    }
}
