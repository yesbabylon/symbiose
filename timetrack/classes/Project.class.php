<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2024
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

namespace timetrack;

use equal\orm\Model;
use sale\receivable\ReceivablesQueue;

class Project extends Model {

    public static function getName(): string {
        return 'Project';
    }

    public static function getDescription(): string {
        return 'A project is linked to a customer and time entries.'
            .' It organises time entries and allows to configure sale models to auto apply sale related fields of a time entry.';
    }

    public static function getColumns(): array {
        return [

            'name' => [
                'type'            => 'string',
                'description'     => 'Name of the project.',
                'required'        => true,
                'unique'          => true
            ],

            'description' => [
                'type'            => 'string',
                'description'     => 'Description of the project.'
            ],

            'customer_id' => [
                'type'            => 'many2one',
                'foreign_object'  => 'sale\customer\Customer',
                'description'     => 'Which customer is the project for.'
            ],

            'instance_id' => [
                'type'            => 'many2one',
                'foreign_object'  => 'inventory\server\Instance',
                'description'     => 'The instance hosting the project.'
            ],

            'time_entry_sale_model_id' => [
                'type'            => 'many2one',
                'foreign_object'  => 'timetrack\TimeEntrySaleModel',
                'foreign_field'   => 'projects_ids',
                'required'        => true
            ],

            'receivable_queue_id' => [
                'type'            => 'many2one',
                'foreign_object'  => 'sale\receivable\ReceivablesQueue',
                'foreign_field'   => 'projects_ids',
                'domain'          => ['customer_id', '=', 'object.customer_id']
            ]

        ];
    }

    public static function onchange($event, $values) {
        $result = [];
        if(key_exists('customer_id', $event)) {
            if(is_null($event['customer_id'])) {
                $result['receivable_queue_id'] = null;
            }
        }
        return $result;
    }

    public static function canupdate($self, $values) {
        $self->read(['customer_id', 'receivable_queue_id']);
        foreach($self as $project) {
            if(!isset($values['customer_id']) && !isset($values['receivable_queue_id'])) {
                continue;
            }

            $customer_id = $values['customer_id'] ?? $project['customer_id'];
            $receivable_queue_id = $values['receivable_queue_id'] ?? $project['receivable_queue_id'];
            if($receivable_queue_id) {
                if(!$customer_id) {
                    return ['customer_id' => ['missing' => 'Customer must be set to set receivable queue.']];
                }

                $queue = ReceivablesQueue::id($receivable_queue_id)
                    ->read(['customer_id'])
                    ->first();

                if($customer_id !== $queue['customer_id']) {
                    return ['receivable_queue_id' => ['invalid' => 'Receivable queue not matching customer.']];
                }
            }
        }

        return parent::canupdate($self, $values);
    }
}
