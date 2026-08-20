# Receivable classes

## Receivable

A receivable represent a good or a service that has been sold to a customer, and whose amount must be received.

### Properties

| Property             | Type     | Description                                                                         | Value(s)                       |
|----------------------|----------|-------------------------------------------------------------------------------------|--------------------------------|
| receivables_queue_id | many2one | The parent queue the receivable is attached to                                      |                                |
| customer_id          | many2one | The customer to who refers the item                                                 |                                |
| date                 | date     | Creation date of the receivable                                                     |                                |
| name                 | string   | Default label of the line (from product)                                            |                                |
| description          | string   | Description of the receivable                                                       |                                |
| status               | string   | Status of the receivable                                                            | (pending, invoiced, cancelled) |
| sale_entry_id        | many2one | The sale entry at the origin of the receivable                                      |                                |
| product_id           | many2one | The product (SKU) the receivable relates to                                         |                                |
| price_id             | many2one | The price the receivable relates to (from product and date)                         |                                |
| unit_price           | float    | Unit price of the product related to the receivable (from price)                    |                                |
| vat_rate             | float    | VAT rate to be applied                                                              |                                |
| qty                  | float    | Quantity of product                                                                 |                                |
| free_qty             | integer  | Free quantity of product, if any                                                    |                                |
| discount             | float    | Total amount of discount to apply, if any                                           |                                |
| total                | float    | Total tax-excluded price of the receivable (from qty, price, free qty and discount) |                                |
| price                | float    | Final tax-included price of the receivable (from total and vat rate)                |                                |
| invoice_id           | many2one | Invoice the receivable is related to                                                |                                |
| invoice_line_id      | many2one | The invoice line that has been generated based on the item                          |                                |

## Receivable Queue

A receivable queue is created for each customer and represent the list of items (receivables) that are waiting to be put on an invoice.
A customer can have multiple receivable queues to ease invoicing process.
The default name of the queue is the name of the customer.

### Properties

| Property                  | Type     | Description                                                   | Value(s)            |
|---------------------------|----------|---------------------------------------------------------------|---------------------|
| customer_id               | many2one | The customer the queue refers to                              |                     |
| name                      | string   | The name of the receivables queue (from customer)             |                     |
| receivables_ids           | one2many | The receivables attached to the queue                         |                     |
| pending_receivables_ids   | one2many | The pending receivables attached to the queue                 |                     |
| invoiced_receivables_ids  | one2many | The invoiced receivables attached to the queue                |                     |
| pending_receivables_count | integer  | Quantity of pending receivables attached to the queue         |                     |
| projects_ids              | one2many | Timetrack projects that use this queue for their sale entries |                     |
