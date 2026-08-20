# Receivable actions

## Invoice

Invoice pending receivable, a default invoice can be selected, all receivables from that invoice\'s customer will be added to it.

### Params

| Param                   | Type     | Required | Description                                                                                     | Value(s) |
|-------------------------|----------|:--------:|-------------------------------------------------------------------------------------------------|----------|
| id                      | integer  |          | Identifier of the targeted sale entry                                                           |          |
| ids                     | one2many |          | Identifier of the targeted sale entries                                                         |          |
| invoice_id              | many2one |          | Invoice to which the lines must be added. If left empty a new invoice proforma will be created. |          |
| invoice_line_group_name | string   |          | Name of the invoice line group.                                                                 |          |

### Uml

```puml
@startuml

start

:Invoice;

:Get receivables;

:Create invoice;

:Create invoice line group;

:Create invoice lines;

stop

@enduml
```

## Bulk invoice

Invoice all pending receivables on customers' default invoices.

### Uml

```puml
@startuml

start

:Invoice;

:Get all pending receivables;

:Invoice receivables;

stop

@enduml
```
