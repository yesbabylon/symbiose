<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\booking;

class Repair extends Consumption {

    public static function getName() {
        return 'Repair';
    }

    public static function getDescription() {
        return "A repair is an event that relates to a scheduled repairing. Repairs and Consumptions are handled the same way in the Planning.";
    }

    public static function getColumns() {
        return [

            'repairing_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'sale\booking\Repairing',
                'description'       => 'The booking the comsumption relates to.',
                'readonly'          => true,                
                'ondelete'          => 'cascade'        // delete repair when parent repairing is deleted
            ],

            'type' => [
                'type'              => 'string',
                'description'       => "The reason the unit is unavailable (always 'out-of-order').",
                'selection'         => [
                    'ooo'                              // out-of-order (repair & maintenance)
                ],
                'readonly'          => true,
                'default'           => 'ooo'
            ],

            'is_rental_unit' => [
                'type'              => 'boolean',
                'description'       => 'Does the consumption relate to a rental unit?',
                'default'           => true
            ],

            'rental_unit_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'realestate\RentalUnit',
                'description'       => "The rental unit the consumption is assigned to."
            ]
 
        ];
    }


    /**
     * Check wether an object can be deleted, and perform some additional operations if necessary.
     * This method can be overriden to define a more precise set of tests.
     * 
     * @param  object   $om         ObjectManager instance.
     * @param  array    $oids       List of objects identifiers.
     * @return boolean  Returns an associative array mapping fields with their error messages. An empty array means that object has been successfully processed and can be deleted.
     */
    public static function candelete($om, $oids) {
        $lines = $om->read(get_called_class(), $oids, ['date']);

        if($lines > 0) {
            foreach($lines as $line) {
                if($line['date'] <= time()) {
                    return ['date' => ['non_removeable' => 'Passed repairs must be kept for history.']];
                }
            }
        }

        return parent::candelete($om, $oids);
    }    

}