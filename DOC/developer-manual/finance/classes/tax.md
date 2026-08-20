# Tax

## Vat Rule

### Properties

| Property       | Type     | Description                                               | Value(s)         |
|----------------|----------|-----------------------------------------------------------|------------------|
| name           | string   | Label of the group (displayed on invoice)                 |                  |
| rate           | float    | Short description of the group (displayed on invoice)     |                  |
| vat_rule_type  | string   | Kind of operation this rule relates to                    | (purchase, sale) |
| account_id     | many2one | Account which the tax amount relates to                   |                  |

