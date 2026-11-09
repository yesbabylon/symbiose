<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2026
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

['db' => $db_connector] = eQual::inject(['db']);

$db = $db_connector->connect();

if(!$db) {
    throw new Exception('missing_database', EQ_ERROR_INVALID_CONFIG);
}

if(!$db instanceof \equal\db\DBManipulatorMySQL) {
    throw new Exception('unsupported_dbms', EQ_ERROR_INVALID_CONFIG);
}

$sale_table = 'sale_subscription_subscription';
$infra_table = 'infra_service_subscription';

$existing_tables = array_fill_keys(array_map('strtolower', $db->getTables()), true);
if(!isset($existing_tables[$sale_table])) {
    return;
}

$sale_columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($sale_table)), true);
$infra_columns = isset($existing_tables[$infra_table])
    ? array_fill_keys(array_map('strtolower', $db->getTableColumns($infra_table)), true)
    : [];

$sql_literal = static function(string $value): string {
    return 'CONVERT(0x'.bin2hex($value).' USING utf8mb4) COLLATE utf8mb4_unicode_ci';
};

$legacy_model = 'infra\service\Subscription';
$sale_model = 'sale\subscription\Subscription';
$legacy_model_literal = $sql_literal($legacy_model);
$sale_model_literal = $sql_literal($sale_model);

$legacy_count_result = $db->sendQuery(
    "SELECT COUNT(*) AS row_count
     FROM `{$sale_table}`
     WHERE `model` = {$legacy_model_literal};"
);
$legacy_count_row = $db->fetchArray($legacy_count_result);
$legacy_count = (int) ($legacy_count_row['row_count'] ?? 0);

if($legacy_count > 0) {
    if(!isset($existing_tables[$infra_table])) {
        throw new Exception(
            "Subscription migration requires table '{$infra_table}'. Initialize the infra package first.",
            EQ_ERROR_INVALID_CONFIG
        );
    }

    foreach(['id', 'model', 'sale_subscription_id'] as $required_column) {
        if(!isset($infra_columns[$required_column])) {
            throw new Exception(
                "Subscription migration requires column '{$infra_table}.{$required_column}'.",
                EQ_ERROR_INVALID_CONFIG
            );
        }
    }

    $linked_sale_ids = [];
    if(isset($sale_columns['inventory_subscription_id'])) {
        $duplicate_links_result = $db->sendQuery(
            "SELECT `inventory_subscription_id`, COUNT(*) AS row_count
             FROM `{$sale_table}`
             WHERE `model` = {$sale_model_literal}
               AND `inventory_subscription_id` IS NOT NULL
               AND `inventory_subscription_id` > 0
             GROUP BY `inventory_subscription_id`
             HAVING COUNT(*) > 1;"
        );

        if($db->fetchArray($duplicate_links_result)) {
            throw new Exception(
                'Cannot split subscriptions: several sale subscriptions reference the same service subscription.',
                EQ_ERROR_CONFLICT_OBJECT
            );
        }

        $links_result = $db->sendQuery(
            "SELECT `id`, `inventory_subscription_id`
             FROM `{$sale_table}`
             WHERE `model` = {$sale_model_literal}
               AND `inventory_subscription_id` IS NOT NULL
               AND `inventory_subscription_id` > 0;"
        );
        while($link = $db->fetchArray($links_result)) {
            $linked_sale_ids[(int) $link['inventory_subscription_id']] = (int) $link['id'];
        }
    }

    $copy_fields = array_values(array_filter(
        array_intersect(array_keys($infra_columns), array_keys($sale_columns)),
        static fn(string $field): bool => $field !== 'sale_subscription_id'
    ));

    $legacy_rows_result = $db->sendQuery(
        "SELECT *
         FROM `{$sale_table}`
         WHERE `model` = {$legacy_model_literal}
         ORDER BY `id`;"
    );

    $db->sendQuery('START TRANSACTION;');

    try {
        while($legacy_row = $db->fetchArray($legacy_rows_result)) {
            $legacy_id = (int) $legacy_row['id'];
            $sale_subscription_id = $linked_sale_ids[$legacy_id] ?? $legacy_id;

            $target_result = $db->sendQuery(
                "SELECT `id`, `sale_subscription_id`
                 FROM `{$infra_table}`
                 WHERE `id` = {$legacy_id};"
            );
            $target_row = $db->fetchArray($target_result);

            if($target_row) {
                $current_sale_subscription_id = (int) ($target_row['sale_subscription_id'] ?? 0);
                if(
                    $current_sale_subscription_id > 0
                    && $current_sale_subscription_id !== $sale_subscription_id
                ) {
                    throw new Exception(
                        "Cannot migrate service subscription '{$legacy_id}': it is already linked to another sale subscription.",
                        EQ_ERROR_CONFLICT_OBJECT
                    );
                }

                if($current_sale_subscription_id === 0) {
                    $db->setRecords(
                        $infra_table,
                        [$legacy_id],
                        ['sale_subscription_id' => $sale_subscription_id]
                    );
                }
            }
            else {
                $target_values = [];
                foreach($copy_fields as $field) {
                    $target_values[$field] = $legacy_row[$field] ?? null;
                }
                $target_values['model'] = $legacy_model;
                $target_values['sale_subscription_id'] = $sale_subscription_id;

                $db->addRecords(
                    $infra_table,
                    array_keys($target_values),
                    [array_values($target_values)]
                );
            }

            if(isset($sale_columns['infra_subscription_id'])) {
                $db->setRecords(
                    $sale_table,
                    [$sale_subscription_id],
                    ['infra_subscription_id' => $legacy_id]
                );
            }

            if(isset($linked_sale_ids[$legacy_id])) {
                $db->deleteRecords($sale_table, [$legacy_id]);
            }
            else {
                $db->setRecords(
                    $sale_table,
                    [$legacy_id],
                    ['model' => $sale_model]
                );
            }
        }

        $db->sendQuery('COMMIT;');
    }
    catch(Throwable $throwable) {
        $db->sendQuery('ROLLBACK;');
        throw $throwable;
    }
}

$entry_table = 'sale_saleentry';
if(isset($existing_tables[$entry_table])) {
    $entry_columns = array_fill_keys(array_map('strtolower', $db->getTableColumns($entry_table)), true);
    $entry_model = 'infra\service\SubscriptionEntry';
    $entry_model_literal = $sql_literal($entry_model);
    $pending_service_link_condition = isset($entry_columns['service_subscription_id'])
        ? ' AND (`service_subscription_id` IS NULL OR `service_subscription_id` = 0)'
        : '';

    $entry_count_result = $db->sendQuery(
        "SELECT COUNT(*) AS row_count
         FROM `{$entry_table}`
         WHERE `model` = {$entry_model_literal}
           AND `subscription_id` IS NOT NULL
           AND `subscription_id` > 0
           {$pending_service_link_condition};"
    );
    $entry_count_row = $db->fetchArray($entry_count_result);
    $entry_count = (int) ($entry_count_row['row_count'] ?? 0);

    if($entry_count > 0) {
        if(!isset($existing_tables[$infra_table])) {
            throw new Exception(
                "Subscription entry migration requires table '{$infra_table}'.",
                EQ_ERROR_INVALID_CONFIG
            );
        }

        if(!isset($entry_columns['service_subscription_id'])) {
            throw new Exception(
                "Subscription entry migration requires column '{$entry_table}.service_subscription_id'.",
                EQ_ERROR_INVALID_CONFIG
            );
        }

        $entries_result = $db->sendQuery(
            "SELECT `id`, `subscription_id`
             FROM `{$entry_table}`
             WHERE `model` = {$entry_model_literal}
               AND `subscription_id` IS NOT NULL
               AND `subscription_id` > 0
               {$pending_service_link_condition}
             ORDER BY `id`;"
        );

        $db->sendQuery('START TRANSACTION;');

        try {
            while($entry = $db->fetchArray($entries_result)) {
                $service_subscription_id = (int) $entry['subscription_id'];
                $service_subscription_result = $db->sendQuery(
                    "SELECT `sale_subscription_id`
                     FROM `{$infra_table}`
                     WHERE `id` = {$service_subscription_id};"
                );
                $service_subscription = $db->fetchArray($service_subscription_result);
                $sale_subscription_id = (int) ($service_subscription['sale_subscription_id'] ?? 0);

                if($sale_subscription_id <= 0) {
                    throw new Exception(
                        "Cannot migrate subscription entry '{$entry['id']}': its service subscription has no commercial subscription.",
                        EQ_ERROR_CONFLICT_OBJECT
                    );
                }

                $db->setRecords(
                    $entry_table,
                    [(int) $entry['id']],
                    [
                        'service_subscription_id' => $service_subscription_id,
                        'subscription_id'         => $sale_subscription_id
                    ]
                );
            }

            $db->sendQuery('COMMIT;');
        }
        catch(Throwable $throwable) {
            $db->sendQuery('ROLLBACK;');
            throw $throwable;
        }
    }
}

// These columns only existed because infra\service\Subscription inherited the
// sale model and shared its physical table. All service data now lives in the
// dedicated infra table.
foreach([
    'inventory_subscription_id',
    'service_id',
    'is_internal',
    'has_external_provider',
    'service_provider_id'
] as $obsolete_column) {
    if(isset($sale_columns[$obsolete_column])) {
        $db->sendQuery(
            "ALTER TABLE `{$sale_table}` DROP COLUMN `{$obsolete_column}`;"
        );
    }
}
