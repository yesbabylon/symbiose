# Invoice actions

## Cancel

Cancel one or multiple invoices, can keep the linked receivables by setting them back to pending status or can cancel them.

### Params

| Param            | Type     | Required | Description                                                           | Value(s) |
|------------------|----------|:--------:|-----------------------------------------------------------------------|----------|
| id               | integer  |          | Identifier of the targeted invoice                                    |          |
| ids              | one2many |          | Identifier of the targeted invoices                                   |          |
| keep_receivables | boolean  |          | If true sets receivables back to pending, else sets them to cancelled |          |

## Download pdf

Download a pdf version of an invoice, a display mode can be specified.

Display modes:
- simple: displays all lines without groups
- detailed: displays all lines by group
- grouped: displays only groups by vat rate

### Params

| Param | Type     | Required | Description                        | Value(s)                    |
|-------|----------|:--------:|------------------------------------|-----------------------------|
| id    | integer  |    X     | Identifier of the targeted invoice |                             |
| mode  | boolean  |          | Display mode                       | (simple, detailed, grouped) |
