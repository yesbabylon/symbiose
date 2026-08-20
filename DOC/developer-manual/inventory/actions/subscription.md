# Subscription actions

## Check Expiration

Check if the subscription is expired or about to expire. 
If either of these fields is true, there will be an alert; otherwise, the alert will disappear.

### Params

| Param | Type    | Required | Description             |
|-------|---------|:--------:|-------------------------|
| id    | integer |    x     | ID of the subscription  |

### Uml

```puml
@startuml

start

if (Does the given subscription exist?) then (yes)
    if (Is the subscription expired or about to expire?) then (yes)
      :Create alert;
    else(not)
      :Disappear alert;
    endif
else (no)
  stop
endif

stop

@enduml
```


## Bulk Expiration

Update and check if the subscription is expired or about to expire.

### Params

| Param | Type     | Required | Description                        |
|-------|----------|:--------:|------------------------------------|
| ids   | many2one |          | List of subscription identifiers   |

### Uml

```puml
@startuml
start
if (Are the subscription identifiers not empty?) then (yes)
    :Get subscriptions that are expired or about to expire;
    :Update subscriptions that should be updated (action);
    :Get subscriptions that should have alerts;
    repeat :For each subscription;
        :Check expiration of the subscription (action);
    repeat while (next)
    stop
else (no)
  stop
@enduml

```