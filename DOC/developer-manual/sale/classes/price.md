# Price classes

## Price

A price is an amount of money that a customer has to pay for a product/service.
It's described by an amount, a vat rate, an accounting rule and is part of a price list.

### Properties

| Property              | Type     | Description                                                          | Value(s)           |
|-----------------------|----------|----------------------------------------------------------------------|--------------------|
| name                  | string   | The display name of the price (from product and price list)          |                    |
| price                 | float    | Tax excluded price                                                   |                    |
| price_vat             | float    | Tax included price (from price and vat rate)                         |                    |
| price_type            | string   | If computed a calculation method is used to compute the price amount | (direct, computed) |
| calculation_method_id | string   | Method to use for price computation                                  |                    |
| price_list_id         | many2one | The Price List the price belongs to                                  |                    |
| is_active             | boolean  | Is the price currently applicable (from price list)                  |                    |
| accounting_rule_id    | many2one | Selling accounting rule                                              |                    |
| product_id            | many2one | The Product (sku) the price applies to                               |                    |
| vat_rate              | float    | VAT rate applied on the price (from accounting rule)                 |                    |

## Price List

A price list allows to easily manage prices, to create a period of validity for them.
Prices of active lists will be proposed to the users.

### Properties

| Property               | Type     | Description                                    | Value(s)                             |
|------------------------|----------|------------------------------------------------|--------------------------------------|
| name                   | string   | Short label to ease identification of the list |                                      |
| date_from              | date     | Start of validity period                       |                                      |
| date_to                | date     | End of validity period                         |                                      |
| duration               | integer  | Price list validity duration, in days          |                                      |
| status                 | string   | Status of the list                             | (pending, published, paused, closed) |
| is_active              | boolean  | Is the price list currently applicable         |                                      |
| prices_count           | integer  | Number of prices defined in list               |                                      |
| prices_ids             | one2many | Prices that are related to this list, if any   |                                      |
| price_list_category_id | many2one | Category this list is related to, if any       |                                      |
