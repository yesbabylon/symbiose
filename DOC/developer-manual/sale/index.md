# Sale

This package allows to manage a catalog of products and prices.
It also provides a workflow to sell products or services to customers and generate invoices.

## General conventions

In Symbiose, the goal is to clearly separate stages that represent different responsibilities, so that a single status such as `billed` or `invoiced` is not used with several meanings.

- **Business lifecycle:** indicates where a service stands in its operational process: recorded, confirmed, approved, and so on.
- **Charging or allocation method:** indicates how the financial value of the service is handled: direct invoicing, debiting a Service Account, using prepaid credit, and so on.
- **Commercial issuance:** concerns the document sent to the customer, such as an invoice progressing from draft to approved and then issued.
- **Accounting integration:** indicates when the transaction is actually recorded in the accounts, typically with a status such as `posted`.

This separation makes workflows easier to understand and, most importantly, avoids confusing **"the service has been approved,"** **"it has been assigned to a charging method,"** **"an invoice has been issued,"** and **"the accounting entry has been posted."**

| Term | Reserved for |
| --- | --- |
| `confirmed` | Confirmation of information by its author |
| `approved` | Approval by an authorized person |
| `charged` | Conversion of a service into an amount to be charged |
| `allocated` | Assignment of an amount to a processing mechanism |
| `invoiced` | Effective inclusion in an invoice |
| `debited` | Consumption of an account or credit |
| `issued` | Official issuance of a commercial document |
| `posted` | Integration into the accounts |
| `paid` | Effective receipt of payment |
| `settled` | Final settlement of a financial obligation or item |

## Catalog

### Product Model

Product models act as common denominator for products variants.
These objects are used for catalogs generation: for instance, if a picture is related to a product, it is associated on the product model level.
A product model has at least one variant, which means at least one SKU.

### Product

A product is a variant of a product model, it is an item that can be invoiced to customers.
It's described by name, description, price and vat rate.
Each product has a unique stock keeping unit (SKU).

### Product Attribute

A product attribute corresponds to the value of an attribute available for a product of a given family.
It is the link between product and option, the possible values for the attributes are limited by option value.

### Option

A product option is a characteristic of the product to witch a value can be attributed.
Examples of product options: color, weight...

### Option Value

Option values are the possible values to which an option, for a given product attribute, can be set to.
Examples of product option values for color option: blue, green, red...

### Family

A product family groups products under the same brand or category.
It supports hierarchical structures with parent and child relationships.

### Category

Product categories allow to group products in arbitrary ways, they are not related to families.

## Price

### Price

A price is an amount of money that a customer has to pay for a product/service.
It's described by an amount, a vat rate, an accounting rule and is part of a price list.

### Price List

A price list allows to easily manage prices, to create a period of validity for them.
Prices of active lists will be proposed to the users.

## Customer

### Customer

A customer is an individual or entity that purchases goods or services from your business. 
It's described by name, contact details, and address to facilitate transactions.

### Customer Type

Types of customer are for example: Individual, Self-Employed, Company, Non-profit organizations or Public Administration.
They are used to apply specific vat rate when items/services are sold to a type of user.

### Customer Nature

A customer nature is an additional way of classifying customers, they can be linked to a customer type.
They are used to apply specific vat rate when items/services are sold to a type of user.

### Contact

Customer contacts are persons, external to the organisation, that represent the customer or provide a link for information about the customer.

### Age Range

Age ranges allow to assign consumptions relating to a booking according to the hosts composition of this booking.

### Rate Class

Rate classes are assigned to customers and allow to assign prices adapters on products/services bought by those.

## Sale entry

A Sale Entry represent a sale made to a customer.
A receivable is generated form a sale entry.

## Receivable

### Receivable

A receivable represents a billable commercial amount generated from a sale entry.
It is not an accounting receivable by itself: while pending, it waits in a queue until it is posted either to an invoice line or, when supported, to a Service Account entry.

### Receivable Queue

A receivable queue is a list of pending receivables from a specific customer.
A customer can have multiple queues, which allows receivables to be processed separately.

## Invoice

### Invoice

An invoice is a document issued by a seller to a buyer detailing the products or services provided and the amount owed.
It includes key information such as the invoice number, date of issuance, description of goods or services, quantities, prices, total amount due, payment terms, and due date.
The invoice serves as a formal request for payment and is an essential component of the sales and accounting process, ensuring both parties have a clear record of the transaction.

### Invoice Line

An invoice line represent one product sold on the invoice, it's described by a label, quantity, free quantity, discount, price and vat rate.
An invoice needs at least one line to be valid.

### Invoice Line Group

An invoice line group allows to group related invoice lines.
When an invoice pdf is generated it's possible to show all lines detailed or only the groups.

## Subscription

### Subscription

A subscription is a recurring payment model where a customer pays regularly, typically monthly or annually, to access a product or service.
An internal subscription is used by your business, so it can't be invoiced to customers.

### Subscription Entry

A subscription entry represents one period of a subscription.
Like a sale entry a receivable can be generated from it.

## Pay

### Bank Statement

A bank statement summarizes a bank account financial transactions over a period.
It includes details of deposits, withdrawals, fees, and the account balance.

### Bank Statement Line

A bank statement line represents one financial transaction on a bank account.
It is a part of a bank statement.

### Funding

A funding is an amount of money that a customer ows to your organisation.
It can be an installment or an invoice.

### Payment

A payment is an amount of money that was paid by a customer for a product or service.
It can origin form the cashdesk or a bank transfer. If it is from a bank transfer it is linked to a bank statement line.

### Payment Terms

Payment terms of an invoice specify the conditions under which your organisation will accept payment from customers.
They include the payment due date, any discounts for early payment, and penalties for late payment.
