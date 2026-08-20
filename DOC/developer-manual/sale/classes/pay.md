# Pay classes

## Bank Statement

A bank statement summarizes a bank account financial transactions over a period.
It includes details of deposits, withdrawals, fees, and the account balance.

### Properties

| Property            | Type     | Description                                                                     | Value(s)              |
|---------------------|----------|---------------------------------------------------------------------------------|-----------------------|
| name                | name     | Display name of bank statement                                                  |                       |
| raw_data            | binary   | Original file used for creating the statement                                   |                       |
| date                | date     | Date the statement was received                                                 |                       |
| old_balance         | float    | Account balance before the transactions                                         |                       |
| new_balance         | float    | Account balance after the transactions                                          |                       |
| statement_lines_ids | one2many | The lines that are assigned to the statement                                    |                       |
| status              | string   | Status of the statement (depending on lines)                                    | (pending, reconciled) |
| is_exported         | boolean  | Flag for marking statement as exported (for import in external tool)            |                       |
| bank_account_number | string   | Original number of the account (as provided in the statement might not be IBAN) |                       |
| bank_account_bic    | string   | Bank Identification Code of the account                                         |                       |
| bank_account_iban   | string   | IBAN representation of the account number                                       |                       |

## Bank Statement Line

A bank statement line represents one financial transaction on a bank account.
It is a part of a bank statement.

### Properties

| Property           | Type     | Description                                                             | Value(s)                                  |
|--------------------|----------|-------------------------------------------------------------------------|-------------------------------------------|
| bank_statement_id  | many2one | The bank statement the line relates to                                  |                                           |
| payments_ids       | one2many | The list of payments this line relates to                               |                                           |
| date               | datetime | Date at which the statement was issued                                  |                                           |
| message            | string   | Message from the payer (or ref from the bank)                           |                                           |
| structured_message | string   | Structured message, if any                                              |                                           |
| customer_id        | many2one | The customer the payment relates to, if known                           |                                           |
| amount             | float    | Amount of the transaction                                               |                                           |
| remaining_amount   | float    | Amount that still needs to be assigned to payments                      |                                           |
| account_iban       | string   | IBAN which the payment originates from                                  |                                           |
| account_holder     | string   | Name of the Person whom the payment originates                          |                                           |
| is_suspense        | boolean  | Origin is unknown (or unsure) and line has been put on suspense account |                                           |
| status             | string   | Current status of the line                                              | (pending, ignored, reconciled, to_refund) |

## Funding

A funding is an amount of money that a customer ows to your organisation.
It can be an installment or an invoice.

### Properties

| Property            | Type     | Description                                                                          | Value(s)               |
|---------------------|----------|--------------------------------------------------------------------------------------|------------------------|
| name                | string   | Display name of funding (from payment deadline and due amount)                       |                        |
| description         | string   | Optional description to identify the funding                                         |                        |
| payments_ids        | one2many | Customer payments of to the funding                                                  |                        |
| funding_type        | string   | Is the funding an instalment or the final invoice                                    | (installment, invoice) |
| order               | integer  | Order by which the funding have to be sorted when presented                          |                        |
| due_amount          | float    | Amount expected for the funding (computed based on VAT incl. price)                  |                        |
| due_date            | date     | Deadline before which the funding is expected                                        |                        |
| issue_date          | date     | Date at which the request for payment has to be issued                               |                        |
| paid_amount         | float    | Total amount that has been received (can be greater than due amount) (from payments) |                        |
| is_paid             | boolean  | Has the full payment been received (from due amount and paid amount)                 |                        |
| amount_share        | float    | Share of the payment over the total due amount                                       |                        |
| payment_deadline_id | many2one | The deadline model used for creating the funding, if any                             |                        |
| invoice_id          | many2one | The invoice targeted by the funding, if any                                          |                        |
| payment_reference   | string   | Message for identifying the purpose of the transaction                               |                        |

## Payment

A payment is an amount of money that was paid by a customer for a product or service.
It can origin form the cashdesk or a bank transfer. If it is from a bank transfer it is linked to a bank statement line.

### Properties

| Property          | Type     | Description                                                   | Value(s)                                  |
|-------------------|----------|---------------------------------------------------------------|-------------------------------------------|
| partner_id        | many2one | The partner to whom the booking relates                       |                                           |
| amount            | float    | Amount paid (whatever the origin)                             |                                           |
| communication     | string   | Message from the payer                                        |                                           |
| receipt_date      | datetime | Time of reception of the payment                              |                                           |
| payment_origin    | string   | Origin of the received money                                  | (cashdesk, bank)                          |
| payment_method    | string   | The method used for payment at the cashdesk                   | (voucher, cash, bank_card, wire_transfer) |
| operation_id      | many2one | The operation the payment relates to                          |                                           |
| statement_line_id | many2one | The bank statement line the payment relates to                |                                           |
| voucher_ref       | string   | The reference of the voucher the payment relates to           |                                           |
| funding_id        | many2one | The funding the payment relates to, if any                    |                                           |
| invoice_id        | many2one | The invoice targeted by the payment, if any                   |                                           |
| is_exported       | boolean  | Mark the payment as exported (part of an export to elsewhere) |                                           |
| status            | string   | Current status of the payment                                 | (pending,paid)                            |

## Payment Terms

Payment terms of an invoice specify the conditions under which your organisation will accept payment from customers.
They include the payment due date, any discounts for early payment, and penalties for late payment.

### Properties

| Property    | Type    | Description                                              | Value(s)              |
|-------------|---------|----------------------------------------------------------|-----------------------|
| name        | string  | Short memo of the terms                                  |                       |
| description | string  | Description of the terms (1 sentence, displayed in docs) |                       |
| delay_from  | string  | Event from which the delay is relative to                | (created, next_month) |
| delay_count | integer | Number of days before reaching the deadline              |                       |
| is_active   | boolean | Can these terms be used                                  |                       |
