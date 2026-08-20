# Customer classes

## Customer

Customer extends identity\Partner class, some of its fields are duplicates of identity\Identity fields.
Those fields can be modified either in Customer or Identity, the data is automaticaly kept up to date between the two tables.

### Properties

| Property              | Type     | Description                                                                                      | Value(s) |
|-----------------------|----------|--------------------------------------------------------------------------------------------------|----------|
| rate_class_id         | many2one | Rate class that applies to the customer                                                          |          |
| customer_nature_id    | many2one | Nature of the customer (map with rate classes)                                                   |          |
| customer_type_id      | many2one | Type of customer (map with rate classes). Defaults to 'individual'                               |          |
| relationship          | string   | Force relationship to Customer                                                                   | customer |
| address               | address  | Main address from related Identity (from address street and city)                                |          |
| ref_account           | string   | Arbitrary reference account number for identifying the customer in external accounting softwares |          |
| receivables_ids       | one2many | List receivables of the customers                                                                |          |
| sales_entries_ids     | one2many | List sales entries of the customers                                                              |          |
| bookings_ids          | one2many | List bookings of the customers                                                                   |          |
| products_ids          | one2many | List products of the customers                                                                   |          |
| softwares_ids         | one2many | List softwares of the customers                                                                  |          |
| services_ids          | one2many | List services of the customers                                                                   |          |
| subscriptions_ids     | one2many | List subscriptions of the customers                                                              |          |
| invoices_ids          | one2many | List invoices of the customers                                                                   |          |
| customer_external_ref | string   | External reference for the customer, if any                                                      |          |
| flag_latepayer        | boolean  | Mark the customer as bad payer                                                                   |          |
| flag_damage           | boolean  | Mark the customer with a damage history                                                          |          |
| flag_nuisance         | boolean  | Mark the customer with a disturbances history                                                    |          |
| is_tour_operator      | boolean  | Mark the customer as a Tour Operator                                                             |          |

## Customer Nature

A customer nature is an additional way of classifying customers, they can be linked to a customer type.
They are used to apply specific vat rate when items/services are sold to a type of user.

### Properties

| Property         | Type     | Description                                             | Value(s) |
|------------------|----------|---------------------------------------------------------|----------|
| name             | string   | Display name of customer nature                         |          |
| code             | string   | Mnemonic of the customer nature                         |          |
| description      | string   | Short description of the customer nature                |          |
| rate_class_id    | many2one | The rate class that applies to customers of this nature |          |
| customer_type_id | many2one | The customer type the nature relates to                 |          |

## Customer Type

Types of customer are for example: Individual, Self-Employed, Company, Non-profit organizations or Public Administration.
They are used to apply specific vat rate when items/services are sold to a type of user.

### Properties

| Property      | Type     | Description                                          | Value(s) |
|---------------|----------|------------------------------------------------------|----------|
| name          | string   | Display name of the customer type                    |          |
| description   | string   | Short description of the customer type               |          |
| rate_class_id | many2one | The rate class that applies to this type of customer |          |

## Contact

Customer contact extends identity\Contact and force is internal to false because it's not a direct contact of the organisations.
A customer id field is added to create the link with a customer.

| Property     | Type     | Description                                                                           | Value(s) |
|--------------|----------|---------------------------------------------------------------------------------------|----------|
| is_internal  | string   | The partnership relates to (one of) the organization(s) from the current installation | false    |
| customer_id  | many2one | Customer the contact relates to                                                       |          |
