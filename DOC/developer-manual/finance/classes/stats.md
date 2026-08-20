# Statistics 

## Stat Chart

### Properties

| Property          | Type        | Description                                  | Value(s) |
|-------------------|-------------|----------------------------------------------|----------|
| name              | alias       | Name of the chart of accounts                |          |
| organisation_id   | many2one    | The organisation the chart belongs to        |          |
| stat_sections_ids | one2many    | Sections that are related to this stat chart |          |

## Stat Section

### Properties

| Property        | Type     | Description                                      | Value(s) |
|-----------------|----------|--------------------------------------------------|----------|
| name            | alias    | Name of the section of accounts (from code)      |          |
| code            | string   | Unique code identifying the section              |          |
| description     | string   | Short description of the section                 |          |
| label           | string   | The label of the section                         |          |
| stat_chart_id   | many2one | The stats chart the line belongs to              |          |


