# Receivable classes

## Receivable

A `Receivable` represents a commercial amount resulting from a sale or service that has been validated as billable, but whose financial destination has not yet been determined.

It is an intermediate object between the commercial lifecycle of a `SaleEntry` and the financial processing of that sale. It is not an accounting receivable by itself and does not generate an accounting entry directly. A pending receivable can later be processed either as an invoice line or, for supported time entries, as a Service Account entry.

### Lifecycle position

The source of a receivable is a `SaleEntry`, or an entity exposing the `SaleEntry` behavior, such as `timetrack\TimeEntry` or `sale\subscription\SubscriptionEntry`.

```text
pending
  |
  v
ready
  |
  v
validated
  |
  | bill
  v
billed
```

When the `bill` transition is executed from `validated`, the system creates a `Receivable` before moving the source `SaleEntry` to `billed`.

```text
SaleEntry
  |
  | validate
  v
validated
  |
  | bill
  v
Receivable (pending)
  |
  `-- SaleEntry -> billed
```

Creation is skipped for internal entries and for entries explicitly marked as non-billable. Once the receivable is generated, the originating sale entry is linked to it through `receivable_id` and reaches the end of its editable commercial lifecycle.

### Creation logic

The receivable stores its origin with:

```text
origin_object_class
origin_object_id
```

This allows the same model to reference a base `sale\SaleEntry`, a `timetrack\TimeEntry`, a `sale\subscription\SubscriptionEntry`, or another class extending the sale entry behavior.

The receivable then computes and stores the commercial snapshot needed for later processing:

- customer;
- product and price;
- unit price and VAT rate;
- quantity and free quantity;
- discount;
- total excluding VAT;
- final price including VAT.

Before creating a new receivable for a source object, the current implementation removes any previous receivable associated with the same `origin_object_class` and `origin_object_id`.

### Statuses

The current statuses are:

| Status    | Meaning                                                                  |
|-----------|--------------------------------------------------------------------------|
| pending   | Waiting to be processed.                                                 |
| posted    | Processed through an invoice line or a Service Account entry.            |
| cancelled | Cancelled and no longer intended to be processed.                        |

### Financial processing

A pending receivable has two possible financial destinations:

```text
                         +--> InvoiceLine
                         |
SaleEntry -> Receivable -+
                         |
                         +--> ServiceAccountEntry
```

The destination is not decided when the receivable is created. It is decided when a pending receivable is posted.

### Posting to an invoice

The `post_invoice` action creates `InvoiceLine` objects from pending receivables.

When an invoice is explicitly supplied, it is used for receivables that belong to the same customer. Otherwise the system searches for an existing `proforma` invoice for the customer and creates a new one if none exists.

The generated invoice line copies the commercial information carried by the receivable: product, price, quantity, free quantity, discount, unit price and VAT rate. Receivables can be grouped on the invoice through `invoice_group`; when no group is provided, an `Additional Services (YYYY-MM-DD)` group is used.

After posting, the receivable references both the invoice and the generated invoice line, and its status becomes `posted`.

### Posting to a Service Account

The `post_service_account` action creates `ServiceAccountEntry` objects from pending receivables. In the current implementation this path is intended for receivables originating from `timetrack\TimeEntry`.

The operation requires:

- a `pending` receivable;
- an origin object class equal to `timetrack\TimeEntry`;
- a customer;
- a positive billed duration on the time entry;
- an active Service Account belonging to the same customer.

A Service Account may be explicitly selected. Otherwise, the system searches for an active Service Account for the customer.

The generated `ServiceAccountEntry` receives the receivable origin, description and date, plus time-entry data such as travel time, on-site flag, helpdesk flag and priority. Its points are computed by the Service Account entry model. After posting, the receivable references both the Service Account and the generated Service Account entry, and its status becomes `posted`.

### Immediate and deferred invoicing

There is no separate immediate or deferred mode stored on a receivable.

- Immediate invoicing means posting the receivable to an invoice as soon as it is created.
- Deferred invoicing means leaving it in `pending` status in a `ReceivablesQueue` until a later posting operation.

The queue is therefore the mechanism used to accumulate billable items before they are grouped into invoices or sent to Service Accounts.

### Negative amounts and corrections

The `Receivable` model does not enforce positive-only monetary values. A negative commercial amount can therefore be carried through the invoice path and produce a negative invoice line, depending on the originating sale data.

The Service Account path is stricter: it only accepts time-entry receivables with a strictly positive billed duration. A negative receivable is not converted into a Service Account credit or correction by `post_service_account`.

### Properties

| Property                 | Type     | Description                                                                           | Value(s)                     |
|--------------------------|----------|---------------------------------------------------------------------------------------|------------------------------|
| receivables_queue_id     | many2one | The parent queue the receivable is attached to.                                       |                              |
| customer_id              | computed | The customer the item refers to, computed from the origin object.                     |                              |
| date                     | datetime | Creation date of the receivable.                                                     |                              |
| name                     | computed | Default label of the line, computed from the origin object.                          |                              |
| description              | computed | Description of the receivable, computed from the origin object.                      |                              |
| status                   | string   | Status of the receivable.                                                            | pending, posted, cancelled   |
| origin_object_class      | string   | Entity class that the receivable originates from.                                    | sale\SaleEntry, timetrack\TimeEntry, sale\subscription\SubscriptionEntry |
| origin_object_id         | integer  | Identifier of the originating commercial object.                                     |                              |
| sale_entry_id            | computed | Origin as a `sale\SaleEntry`, when applicable.                                       |                              |
| time_entry_id            | computed | Origin as a `timetrack\TimeEntry`, when applicable.                                  |                              |
| subscription_entry_id    | computed | Origin as a `sale\subscription\SubscriptionEntry`, when applicable.                  |                              |
| invoice_group            | string   | Optional grouping label used when posting to an invoice.                             |                              |
| product_id               | computed | The product (SKU) the receivable relates to.                                         |                              |
| price_id                 | computed | The price the receivable relates to.                                                 |                              |
| unit_price               | computed | Unit price of the product related to the receivable.                                 |                              |
| vat_rate                 | computed | VAT rate to be applied.                                                              |                              |
| qty                      | computed | Quantity of product.                                                                 |                              |
| free_qty                 | computed | Free quantity of product, if any.                                                    |                              |
| discount                 | computed | Total rate of discount to apply, if any.                                             |                              |
| total                    | computed | Total tax-excluded price of the receivable.                                          |                              |
| price                    | computed | Final tax-included price of the receivable.                                          |                              |
| invoice_id               | many2one | Invoice on which the receivable has been posted.                                     |                              |
| invoice_line_id          | many2one | Invoice line generated from the receivable.                                          |                              |
| service_account_id       | many2one | Service Account on which the receivable has been posted.                             |                              |
| service_account_entry_id | many2one | Service Account entry generated from the receivable.                                 |                              |

## ReceivablesQueue

A `ReceivablesQueue` groups the receivables of a customer. It is the waiting area for receivables that still need to be posted.

```text
Customer
  |
  v
ReceivablesQueue
  |
  +-- Receivable (pending)
  +-- Receivable (pending)
  `-- Receivable (posted)
```

When a sale entry explicitly specifies a queue, that queue is used. Otherwise, the system searches for an existing queue associated with the customer. If none exists, a new queue is automatically created.

A receivable can only be moved to another queue while its status is `pending`.

### Properties

| Property                  | Type     | Description                                                   | Value(s) |
|---------------------------|----------|---------------------------------------------------------------|----------|
| customer_id               | many2one | The customer the queue refers to.                             |          |
| name                      | computed | The name of the receivables queue, computed from the customer. |          |
| receivables_ids           | one2many | The receivables attached to the queue.                        |          |
| pending_receivables_ids   | one2many | The pending receivables attached to the queue.                |          |
| posted_receivables_ids    | one2many | The posted receivables attached to the queue.                 |          |
| pending_receivables_count | computed | Quantity of pending receivables attached to the queue.        |          |
| projects_ids              | one2many | Timetrack projects that use this queue for their sale entries. |          |
