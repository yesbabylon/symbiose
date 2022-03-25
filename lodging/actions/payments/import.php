<?php
/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

use lodging\identity\CenterOffice;
use lodging\sale\booking\BankStatement;
use lodging\sale\booking\BankStatementLine;

list($params, $providers) = announce([
    'description'   => "Import a Bank statements file and return the list of created statements. Already existing statements will be ignored.",
    'params'        => [
        'data' =>  [
            'description'   => 'TXT file holding the data to import as statements.',
            'type'          => 'file',
            'required'      => true
        ]
    ],
    'access' => [
        'visibility'        => 'public',
        'groups'            => ['sale.default.user'],
    ],
    'response'      => [
        'content-type'  => 'application/json',
        'charset'       => 'utf-8',
        'accept-origin' => '*'
    ],
    'providers'     => ['context', 'orm', 'auth']
]);


list($context, $orm, $auth) = [$providers['context'], $providers['orm'], $providers['auth']];

$user_id = $auth->userId();

if($user_id <= 0) {
    // restricted to identified users
    throw new Exception('unknown_user', QN_ERROR_NOT_ALLOWED);
}

// parse the CODA data
$data = eQual::run('get', 'lodging_payments_coda-parse', ['data' => $params['data']]);

$result = [];

$statements = $data;

foreach($statements as $statement) {

    $iban = BankStatement::_convert_to_iban($statement['account']['number']);

    $center_office = CenterOffice::search(['bank_account_iban', '=', $iban])->read(['id'])->first();

    if(!$center_office) {
        // restricted to identified users
        throw new Exception('unknown_account_number', QN_ERROR_INVALID_PARAM);
    }

    $fields = [
        'raw_data'              => $params['data'],
        'date'                  => $statement['date'],
        'old_balance'           => $statement['old_balance'],
        'new_balance'           => $statement['new_balance'],
        'bank_account_number'   => $statement['account']['number'],
        'bank_account_bic'      => $statement['account']['bic'],
        'center_office_id'      => $center_office['id']
    ];

    try {
        // unique constraint on ['date', 'old_balance', 'new_balance']
        $bank_statement = BankStatement::create($fields)->adapt('txt')->first();

        $result[] = $bank_statement;
    
        foreach($statement['transactions'] as $transaction) {

            try {
                $fields = [
                    'bank_statement_id'     => $bank_statement['id'],
                    'date'                  => $statement['date'],
                    'amount'                => $transaction['amount'],
                    'account_holder'        => $transaction['account']['name'],
                    // should be an IBAN (though could theorically not be)
                    'account_iban'          => $transaction['account']['number'],   
                    'message'               => $transaction['message'],
                    'structured_message'    => $transaction['structured_message'],
                    'center_office_id'      => $center_office['id']                    
                ];
                BankStatementLine::create($fields);
            }
            catch(Exception $e) {
                // ignore duplicates (not created)
                // we cannot stop the process : as there might be several statements
            }    
    
        }        
    }
    catch(Exception $e) {
        throw new Exception('already_imported', QN_ERROR_CONFLICT_OBJECT);
    }
}


/*
// try to resolve payments 


si la statement_line dispose d'un structured_message 

	rechercher parmi les booking_funding non (totalement) payés (is_paid = false)
	avec payment_reference == structured_message 

	=> statement_line_id, booking_funding_id


(
	si funding.due_amount == statement_line.amount

	sinon
		si funding.due_amount < statement_line.amount
			créer deux payments (un devra être remboursé)

		si funding.due_amount > statement_line.amount		
			créer un payment (funding pas encore marqué comme payé)
)

sinon

	rechercher parmi les booking_funding non (totalement) payés (is_paid = false)
	pour un centre donné (center_id == center_id correspondant au code IBAN du statement de la statement_line)

	pour chaque funding candidat, lire l'iban du partner (customer_id) associé
	si customer_id.partner_identity_id.bank_account_iban == statement_line.account_iban

	si funding.due_amount == statement_line.amount

	=> statement_line_id, booking_funding_id

	(sinon on n'est pas certain de la raison du paiement)

*/

$context->httpResponse()
        ->status(200)
        ->body($result)
        ->send();