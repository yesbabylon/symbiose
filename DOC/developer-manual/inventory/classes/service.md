
# Service
  
## Subscription

### Properties

| Property                             | Type     | Description                                                                | Value(s) |
|--------------------------------------|----------|----------------------------------------------------------------------------|----------|
| service_id                           | many2one | Service attached to a subscription                                         |          |
| is_internal                          | boolean  | Is the subscription for own organisation (computed from service)           |          |
| has_external_provider                | boolean  | The subscription has an external provider (computed from service)          |          |
| service_provider_id                  | many2one | The service provider to which the service belongs  (computed from service) |          |
| subscription_entries_ids             | one2many | Subscription entries of the subscription                                   |          |
| **SaleSubscription**                 |          |                                                                            |          |
| customer_id                          | many2one | The Customer concerned by the subscription  (computed from service)        |          |
| is_billable                          | boolean  | Can be billed to the customer  (computed from service)                     |          |

## Subscription Entry

### Properties

| Property                              | Type     | Description                                                                              | Value(s) |
|---------------------------------------|----------|------------------------------------------------------------------------------------------|----------|
| has_external_provider                 | boolean  | The subscription has an external provider (computed from subscription)                   |          |
| service_provider_id                   | many2one | The service provider to which the service belongs  (computed from subscription)          |          |
| **SaleSubscriptionEntry**          |          |                                                                                          |          |
| object_class                          | string   | Class of the object object_id points to                                                  |          |
| subscription_id                       | many2one | Identifier of the subscription the subscription entry originates from  (computed from )  |          |

## Service

### Properties

| Property              | Type      | Description                                                                       | Value(s) |
|-----------------------|-----------|-----------------------------------------------------------------------------------|----------|
| name                  | string    | The label  of the service. (computed from Product and Service Model)              |          |
| description           | string    | Information about a service                                                       |          |
| has_subscription      | boolean   | The service has a subscription (computed from Product Model)                      |          |
| is_billable           | boolean   | The service is billable (computed from Product Model)                             |          |
| is_auto_renew         | boolean   | The service is auto renew (computed from Product Model)                           |          |
| has_external_provider | boolean   | The service has external provider (computed from Product Model)                   |          |
| service_provider_id   | many2one  | The service provider to which the service belongs (computed from Product Model)   |          |
| product_id            | many2one  | The product to which the service belongs                                          |          |
| is_internal           | boolean   | The product of the service is internal (computed from Product)                    |          |
| customer_id           | many2one  | The customer associated with the product (computed from Product)                  |          |
| details_ids           | one2many  | List of details about the service                                                 |          |
| subscriptions_ids     | one2many  | The subscriptions about the service belongs                                       |          |
| accesses_ids          | one2many  | Access to connect to the service                                                  |          |
| softwares_ids         | one2many  | Software associated with the service                                              |          |


## Service Model

### Properties

| Property              | Type      | Description                                                           | Value(s) |
|-----------------------|-----------|-----------------------------------------------------------------------|----------|
| name                  | string    | Unique identifier of the service model. (ex: Google API, mailtrap.io) |          |
| description           | string    | Information about a service model                                     |          |
| has_subscription      | boolean   | The service model has a subscription                                  |          |
| is_billable           | boolean   | The service model is billable                                         |          |
| is_auto_renew         | boolean   | The service model is auto renew                                       |          |
| has_external_provider | boolean   | The service model has external provider                               |          |
| service_provider_id   | many2one  | The service provider to which the service model belongs               |          |
| services_ids          | one2many  | The list of services associated with the service model                |          |

## Service Provider

### Properties

| Property                     | Type          | Description                                    | Value(s) |
|------------------------------|---------------|------------------------------------------------|----------|
| login_url                    | string        | Url for signing in                             |          |
| service_provider_category_id | many2one      | Category attached the service provider         |          |
| services_ids                 | one2many      | Services of the provider                       |          |

## Service Provider Category

### Properties

| Property                | Type     | Description                                         | Value(s) |
|-------------------------|----------|-----------------------------------------------------|----------|
| name                    | string   | Name of the service provider category               |          |
| description             | string   | Short presentation of the service provider category |          |
| services_providers_ids  | one2many | Service provider to the category                    |          |

## Detail

### Properties

| Property           | Type        | Description                                               | Value(s) |
|--------------------|-------------|-----------------------------------------------------------|----------|
| name               | string      | Unique identifier of the detail (ex: code, key, nic, ...) |          |
| description        | string      | Short presentation of the detail element                  |          |
| value              | string      | Server attached to the product                            |          |
| service_id         | many2one    | Service attached to the detail                            |          |
| detail_category_id | many2one    | Detail Category attached to the detail                    |          |

## Detail Category

### Properties

| Property                | Type        | Description                                 | Value(s) |
|-------------------------|-------------|---------------------------------------------|----------|
| name                    | string      | Name of the detail category                 |          |
| description             | string      | Short presentation of the detail category   |          |
| details_ids             | one2many    | The details of the detail category          |          |


