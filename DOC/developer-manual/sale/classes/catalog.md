# Catalog classes

## Product Model

Product models act as common denominator for products variants (referred to as "products").
These objects are used for catalogs generation: for instance, if a picture is related to a product, it is associated on the product model level.
A product model has at minimum one variant, which means at minimum one SKU.

### Properties

| Property                   | Type      | Description                                                                             | Value(s)                |
|----------------------------|-----------|-----------------------------------------------------------------------------------------|-------------------------|
| name                       | string    | Name of the product model (used for all variants)                                       |                         |
| family_id                  | many2one  | Product Family which current product belongs to                                         |                         |
| selling_accounting_rule_id | many2one  | Accounting rule to use in case of sell                                                  |                         |
| buying_accounting_rule_id  | many2one  | Accounting rule to use in case of purchase                                              |                         |
| stat_section_id            | many2one  | Statistics section to which relates the product, if any                                 |                         |
| can_buy                    | boolean   | Can this product be purchased                                                           |                         |
| can_sell                   | boolean   | Can this product be sold                                                                |                         |
| is_pack                    | boolean   | Is the product a bundle of other products                                               |                         |
| has_own_price              | boolean   | Has the pack its own price, or do we use each sub-product price                         |                         |
| type                       | string    | Is the product a consumable or a service                                                | (consumable, service)   |
| consumable_type            | string    | Is the consumable product storable                                                      | (simple, storable)      |
| service_type               | string    | Is the service product schedulable                                                      | (simple, schedulable)   |
| schedule_type              | string    | Is the service schedulable on a specific time or on a time range                        | (time, timerange)       |
| schedule_default_value     | string    | Default value of the schedule according to type (time: '9:00', timerange: '9:00-10:00') |                         |
| schedule_offset            | integer   | Default number of days to set-off the service from a sojourn start date                 |                         |
| tracking_type              | string    | How is the stored consumable product tracked                                            | (none, batch, sku, upc) |
| description_delivery       | string    | Description for delivery notes                                                          |                         |
| description_receipt        | string    | Description for reception vouchers                                                      |                         |
| groups_ids                 | many2many | Linked groups                                                                           |                         |
| categories_ids             | many2many | Linked categories                                                                       |                         |
| products_ids               | one2many  | Product variants that are related to this model                                         |                         |

## Product

A product is a variant of a product model, it is an item that can be invoiced to customers.
It's described by name, description, price and vat rate.

### Properties

| Property               | Type      | Description                                                  | Value(s) |
|------------------------|-----------|--------------------------------------------------------------|----------|
| name                   | string    | The full name of the product (label + sku)                   |          |
| label                  | string    | Human readable memo for identifying the product              |          |
| sku                    | string    | Stock Keeping Unit code for internal reference               |          |
| ean                    | string    | IAN/EAN code for barcode generation                          |          |
| description            | string    | Description of the variant (specifics)                       |          |
| product_model_id       | many2one  | Product Model of this variant                                |          |
| family_id              | many2one  | Product Family which current product belongs to (from model) |          |
| is_pack                | boolean   | Is the product a pack? (from model)                          |          |
| has_own_price          | boolean   | Product is a pack with its own price (from model)            |          |
| pack_lines_ids         | one2many  | Products that are bundled in the pack                        |          |
| product_attributes_ids | one2many  | Attributes set for the product                               |          |
| prices_ids             | one2many  | Prices that are related to this product                      |          |
| stat_section_id        | many2one  | Statistics section (overloads the model one, if any)         |          |
| can_buy                | boolean   | Can this product be purchased? (from model)                  |          |
| can_sell               | boolean   | Can this product be sold? (from model)                       |          |
| groups_ids             | many2many | Linked groups                                                |          |
| subscriptions_ids      | one2many  | The subscriptions needed for the product                     |          |

## Product Attribute

A product attribute corresponds to the value of an attribute available for a product of a given family.
It is equivalent to the M2M table between product and option, the possible values for the attributes are limited by option value.

Should be unique by product and option.

### Properties

| Property         | Type     | Description                                                | Value(s) |
|------------------|----------|------------------------------------------------------------|----------|
| product_id       | many2one | The Product this Attribute belongs to                      |          |
| product_model_id | many2one | The Product Model this Attribute belongs to (from product) |          |
| option_id        | many2one | Product Option this attribute relates to                   |          |
| option_value_id  | many2one | Value of this attribute for the selected option            |          |

## Option

A product option is a characteristic of the product to witch a value can be attributed.

Should be unique by name and product model.

### Properties

| Property          | Type     | Description                               | Value(s) |
|-------------------|----------|-------------------------------------------|----------|
| name              | string   | Name of the option                        |          |
| description       | string   | Short description of the option           |          |
| product_model_id  | many2one | Product Model this option belongs to      |          |
| option_values_ids | one2many | Option values that belongs to this option |          |

## Option Value

Option values are the possible values to which an option, for a given product attribute, can be set to.

### Properties

| Property    | Type     | Description                                        | Value(s) |
|-------------|----------|----------------------------------------------------|----------|
| name        | string   | Name of the option value                           |          |
| value       | string   | The choice (possible value) for the related option |          |
| description | string   | Short description of the value                     |          |
| option_id   | many2one | Product Option this value relates to               |          |

## Family

A product family groups products under the same brand or category,
supporting hierarchical structures with parent and child relationships to organize products efficiently.

### Properties

| Property           | Type     | Description                                                   | Value(s) |
|--------------------|----------|---------------------------------------------------------------|----------|
| name               | string   | Name of the product family                                    |          |
| children_ids       | one2many | Product families that belongs to current family, if any       |          |
| parent_id          | many2one | Product Family which current family belongs to, if any        |          |
| path               | string   | Full path of the family with ancestors (from name and parent) |          |
| product_models_ids | one2many | Product models which current product belongs to the family    |          |

## Category

Product categories allow to group products in arbitrary ways.
Categories are not related to families nor groups.

### Properties

| Property           | Type      | Description                                                   | Value(s) |
|--------------------|-----------|---------------------------------------------------------------|----------|
| name               | string    | Name of the product category (used for all variants)          |          |
| code               | string    | Unique code of the category (to ease searching)               |          |
| description        | string    | Short string describing the purpose and usage of the category |          |
| product_models_ids | many2many | List of product models assigned to this category              |          |
| booking_types_ids  | many2many | List of booking types assigned to this category               |          |
