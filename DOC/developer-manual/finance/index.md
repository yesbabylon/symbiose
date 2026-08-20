# Finance
This package handles the accounting aspect of your application, covering everything related to accounting, statistics, and taxes. It also enables you to effectively manage both sales and purchase invoices.

* Invoice, Invoice Line, Invoice Line Group
* Accounting Rule, Accounting Entry, and Analytics
* Statistics
* Tax

## Invoice
The Invoice class manages payments and accounting entries, tracking invoice statuses, computing pricing, and recording relevant data. 
It facilitates creation of both purchase and sales invoices, monitors payment statuses, and generates credit notes.

## Invoice Line
Invoice lines detail the products and quantities included in an invoice, providing a comprehensive breakdown of the transaction.

## Invoice Line Group
Invoice line groups are associated with an invoice and are designed to consolidate multiple invoice lines.

## Vat Rule
VAT rules specify the applicable VAT rate for a given type of transaction.

## Account Chart
Chart of Accounts is an organizational list that encompasses all of a company's financial accounts.

## Account Chart Line
A chart of accounts line holds information related to a specific account.

## Accounting Entry
Accounting entries translate the invoice lines into entries that must be created in the accounting books.

## Accounting Journal
An accounting journal is a record of accounting entries organized by their nature.

## Accounting Rule
Accounting rules allow the specification of which account an operation should be recorded in.

## Analytic Chart
The analytical chart of accounts allows the allocation of incomes and expenses independently from the chart of accounts.

## Analytic Section
Analytic sections allow the grouping of spendings and revenues independently from the chart of accounts.

## Stat Chart
The Chart of Accounts is an organizational list that includes all the company's financial accounts.

## Stat Section
Statistics sections allow the generation of views by grouping sales in an arbitrary manner, independent of the Chart of Accounts and the Analytical Chart of Accounts.
