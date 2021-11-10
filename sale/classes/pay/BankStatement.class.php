<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace sale\pay;
use equal\orm\Model;

class BankStatement extends Model {

    public static function getColumns() {

        return [
            'name' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'sale\booking\Funding::getDisplayName',
                'store'             => true
            ],
        
            'reference_name' => [
                'type'              => 'string',
                'description'       => 'Reference of the statement.',
            ],

            'date' => [
                'type'              => 'date',
                'description'       => 'Date the statement was received.',
                'required'          => true
            ],

            'old_balance' => [
                'type'              => 'float',
                'description'       => 'Account balance before the transactions.'
            ],

            'new_balance' => [
                'type'              => 'float',
                'description'       => 'Account balance after the transactions.'
            ],

            'statement_lines_ids' => [
                'type'              => 'one2many',
                'foreign_object'    => 'sale\pay\BankStatementLine',
                'foreign_field'     => 'bank_statement_id',
                'description'       => 'The lines that are assigned to the statement.'
            ],

            // #memo - CODA statements comes with BBAN numbers for reference account    
            'bank_account_number' => [
                'type'              => 'string',
                'description'       => 'Number of the account (as provided in the statement).'
            ],

            'bank_account_bic' => [
                'type'              => 'string',
                'description'       => 'Bank Identification Code of the account.'
            ],

            'bank_account_iban' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'sale\booking\Funding::getBankAccountIban',
                'description'       => 'IBAN representation of the account number.',
                'store'             => true
            ]
    
        ];
    }

    public static function getDisplayName($om, $oids, $lang) {
        $result = [];
        $statements = $om->read(get_called_class(), $oids, ['bank_account_number', 'date', 'old_balance', 'new_balance']);
        foreach($statements as $oid => $statement) {
            $result[$oid] = sprintf("%s - %s - %s - %s", $statement['bank_account_number'], date('Ymd', $statement['date']), $statement['old_balance'], $statement['new_balance']);
        }
        return $result;
    }

    public static function getBankAccountIban($om, $oids, $lang) {
        $result = [];
        $statements = $om->read(get_called_class(), $oids, ['bank_account_number', 'bank_account_bic']);

        /*
            create numeric code of the target country 
        */

        // #todo - adapt based on settings
        $country_code = 'BE';

        $code_alpha = $country_code;
        $code_num = '';
        
        for($i = 0; $i < strlen($code_alpha); ++$i) {
            $letter = substr($code_alpha, $i, 1);
            $order = ord($letter) - ord('A');
            $code_num .= '1'.$order;
        }

        foreach($statements as $oid => $statement) {
            // account number has IBAN format
            if(substr($statement['bank_account_number'], 0, 2) == $country_code) {
                $result[$oid] = $statement['bank_account_number'];
            }
            // convert to IBAN
            else {
                $check_digits = substr($statement['bank_account_number'], -2);
                $dummy = intval($check_digits.$check_digits.$code_num.'00');
                $control = 98 - ($dummy % 97);
                $result[$oid] = sprintf("BE%s%s", $control, $statement['bank_account_number']);    
            }
        }
        return $result;
    }

}