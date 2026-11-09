# Business package

The `business` package is the shared business kernel of the application. It
contains stable, low-level business concepts that are reused and specialized by
several domain packages.

It sits between the eQual framework and vertical business packages:

```text
core
  |
business
  |
sale / purchase / infra / hr / realestate / other domain packages
```

`core` remains exclusively responsible for framework and low-level technical
features. The `symbiose` package remains an application assembly package and
must not become the owner of shared business models.

## Admission criteria

A class belongs in `business` only when all of the following are true:

1. It represents a business concept, invariant, or lifecycle rather than a
   technical helper.
2. Its semantics are stable and meaningful outside a single vertical package.
3. It is already shared by, or is intentionally designed to be specialized by,
   at least two domain packages.
4. It can be defined without depending on a consuming domain package.
5. It provides more than code deduplication: consumers share the same meaning
   and business invariants.

The following do not belong in this package:

- framework primitives, ORM internals, storage utilities, or generic technical
  helpers;
- fields and workflows that only make sense in one domain;
- application menus, dashboards, or other assembly concerns;
- convenience base classes created for a single consumer;
- registries that explicitly reference subclasses from consuming packages.

When a concept is cohesive enough to justify its own reusable package, it
should be extracted instead of allowing `business` to become a catch-all
package.

## Dependency rules

The package depends on `core` and should keep additional dependencies to an
absolute minimum. In particular:

- `business` must never depend on `sale`, `purchase`, `infra`, `hr`,
  `realestate`, or another consuming domain package;
- consuming packages may depend on `business`;
- an abstraction must not know or enumerate its concrete subclasses;
- cross-package synchronization and reverse links remain the responsibility of
  the consuming packages;
- adding a dependency requires that the dependency be relevant to every
  consumer of the affected abstraction.

These rules keep the dependency graph directed and prevent hidden circular
dependencies.

## Abstractions and concrete models

Abstract ORM classes use the `Abstract` suffix and are not instantiated
directly. They define only the schema and invariants that have exactly the same
meaning for every concrete implementation.

Concrete models inherit an abstraction and add their own relations, policies,
actions, hooks, and computed fields:

```php
namespace example\subscription;

use business\subscription\SubscriptionAbstract;

class Subscription extends SubscriptionAbstract {

    public static function getColumns(): array {
        return [
            // Domain-specific fields only.
        ];
    }
}
```

The eQual model inheritance mechanism combines inherited and concrete model
fields. A concrete class may override an inherited field only when it preserves
the field's shared meaning. If the meaning differs, the field belongs in the
concrete package under a domain-specific name.

Abstract models do not own default views, menus, routes, controllers, or seed
data. Concrete packages provide those assets and translate all fields exposed
to their users.

## Subscription abstraction

`business\subscription\SubscriptionAbstract` defines the common lifecycle of a
subscription:

- a human-readable name and description;
- the current period start and end dates;
- the nominal period duration;
- whether renewal is intended to be automatic;
- derived expiration and upcoming-expiry indicators.

It deliberately does not define:

- a customer, supplier, service provider, beneficiary, or other party;
- a subscribed service, catalog product, price, quantity, or billing mode;
- subscription entries, invoices, receivables, or purchase documents;
- renewal scheduling or the operation that performs a renewal;
- links or synchronization with another concrete subscription class.

Those responsibilities belong to concrete packages. For example, `sale` may
interpret renewal as creating a billable period, while `infra` may interpret it
as extending access to an externally provided service. The shared abstraction
describes the lifecycle but does not equate these workflows.

The default upcoming-expiry window is thirty days. A concrete class may
override `UPCOMING_EXPIRY_DELAY` when its whole domain uses a different window.

## Change checklist

Before adding or changing an abstraction, verify that:

1. at least two concrete domains use the same semantics;
2. no consuming package is referenced by the abstraction;
3. domain-specific workflow remains in the consuming package;
4. existing concrete models can adopt the change without contradictory field
   meanings;
5. the package dependency graph remains acyclic;
6. the README is updated when the package boundary or operating rules change.
