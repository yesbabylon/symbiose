# Subscription actions

## Add Subscription Entry

Generate a new subscription entry from a subscription current period.

### Params

| Param      | Type     | Required | Description                             | Value(s) |
|------------|----------|:--------:|-----------------------------------------|----------|
| id         | integer  |          | Identifier of the targeted subscription |          |

### Uml

```puml
@startuml

start

:Add subscription entry;

:Get subscription;

if (Is internal?) then (yes)
    stop
endif

if (Is missing sale information?) then (yes)
    stop
endif

if (Already has an entry for current period?) then (yes)
    stop
endif

:Create subscription entry;

stop

@enduml
```

## Shift period

Shift the subscription to the next period depending on the duration type.
The duration type are monthly, quarterly, half-yearly or yearly.

### Params

| Param      | Type     | Required | Description                             | Value(s) |
|------------|----------|:--------:|-----------------------------------------|----------|
| id         | integer  |          | Identifier of the targeted subscription |          |

### Uml

```puml
@startuml

start

:Ship period;

:Get subscription;

:Modify date_from and date_to;

stop

@enduml
```

## Update expiration

Reset expiration columns "is_expired" and "has_upcoming_expiry" depending on current date and fields "date_from" & "date_to".
This action is meant to be run on each start of day to keep the expiration columns valid.
Field has_upcoming_expiry is reset if the subscription is going to expire in less than 30 days.

The fields "is_expired" and "has_upcoming_expiry" are reset, instead of set to true, because they are computed fields.

### Params

| Param | Type     | Required | Description                               | Value(s) |
|-------|----------|:--------:|-------------------------------------------|----------|
| ids   | one2many |    X     | Identifiers of the targeted subscriptions |          |

### Uml

```puml
@startuml

start

:Update expiration;

:Reset "is_expired" field of subscriptions that should be expired;

:Reset "has_upcoming_expiry" field of subscriptions that are upcoming expiry;

stop

@enduml
```

## Check expiration

This action is used to alert users to check the expiration of a subscription.
If a subscription is expired or has upcoming expiry it generates a check expiration alert, else it discards the alert.

### Params

| Param | Type     | Required | Description                             | Value(s) |
|-------|----------|:--------:|-----------------------------------------|----------|
| id    | integer  |    X     | Identifier of the targeted subscription |          |

### Uml

```puml
@startuml

start

:Check expiration;

:Get subscription;

if (Is expired or has upcoming expiry?) then (yes)
    :Dispatch check expiration alert;
    
    stop
else
    :Cancel check expiration alert;
    
    stop
endif

@enduml
```

## Bulk expiration

Re-compute expiration columns "is_expired" and "has_upcoming_expiry" of subscriptions.
It also checks expirations alerts of subscriptions.

If empty ids given the action target all subscriptions.

### Params

| Param | Type     | Required | Description                              | Value(s) |
|-------|----------|:--------:|------------------------------------------|----------|
| ids   | one2many |          | Identifier of the targeted subscriptions |          |

### Uml

```puml
@startuml

start

:Bulk expiration;

:Update expiration (action);

:Trigger compute of "is_expired" and "has_upcoming_expiry";

:Check expiration (action);

stop

@enduml
```
