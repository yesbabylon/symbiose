# Receivables

A receivable is a billable item waiting to be processed.

It is created after a sale, a subscription entry or a time entry has been validated as billable. It means the customer can be charged for the item, but the system has not yet decided how it will be processed financially.

## Main terms

| Term | Meaning |
|------|---------|
| Sale entry | The commercial item that was sold or performed. A time entry can also become a sale entry when it is billable. |
| Receivable | The amount waiting to be processed after the sale entry is billed. |
| Receivables queue | The waiting list that groups pending receivables for a customer. |
| Invoice line | A line added to a customer invoice from a receivable. |
| Service Account entry | A consumption entry created from a supported time-entry receivable when the service is covered by a Service Account. |

## Statuses

Receivables use three statuses:

| Status | Meaning for the user |
|--------|----------------------|
| Pending | The receivable is waiting in a queue. It has not yet been posted to an invoice or Service Account. |
| Posted | The receivable has been processed. It is linked to an invoice line or a Service Account entry. |
| Cancelled | The receivable is no longer intended to be processed. |

## Typical flow

```text
Sale entry or time entry
  |
  | bill
  v
Receivable (pending)
  |
  +-- post to invoice ---------> Invoice line
  |
  `-- post to Service Account -> Service Account entry
```

The receivable is the step between the work or sale being recognized as billable and the final financial processing.

## Queues

A receivables queue groups pending receivables for a customer. It lets users accumulate several billable items and process them together later.

For example, several time entries for the same customer can stay pending in the queue during the month, then be posted together to a proforma invoice.

A receivable can be moved to another queue only while it is still pending.

## Invoice or Service Account

Most receivables are posted to invoices. When this happens, the system creates invoice lines from the receivable information: product, price, quantity, discount and VAT.

Some time-entry receivables can be posted to a Service Account instead. This is used when the customer has an active Service Account and the performed service must consume points or prepaid service credit rather than appear directly on an invoice.

## Immediate and deferred processing

There is no separate user setting called immediate or deferred on the receivable itself.

- Immediate processing means the receivable is posted right after it is created.
- Deferred processing means the receivable stays pending in its queue until a later user action.

In practice, the queue is what makes deferred processing possible.
