# Identity and identity facets

## Overview

Several business objects can represent the same natural or legal person. For example, one company may simultaneously be a customer and a supplier. Symbiose models that company as one canonical `identity\Identity` and gives it separate business roles, called **identity facets**.

```text
                         Identity
                            |
          +-----------------+-----------------+
          |                 |                 |
       Customer          Supplier          Contact
```

Consequently, a company that is both a customer and a supplier has one `Identity`, one `Customer`, and one `Supplier`. It does not have two unrelated identities.

The central invariant is:

> One real person or organization corresponds to one `Identity`, regardless of the number of roles it has in the ERP.

`Identity` is the source of truth for intrinsic data such as names, legal identifiers, contact details, language, the main address, and the main bank account. A facet owns only the data specific to its role, such as customer payment conditions or supplier settings.

## Class model

`identity\IdentityAbstract` defines the common identity schema. Both the canonical identity and the role projections inherit this schema, but they are separate ORM objects stored in separate tables.

```text
IdentityAbstract
|
+-- Identity                  canonical record
+-- IdentityFacet             common facet behavior
    |
    +-- Organization
    +-- Contact
    +-- sale\customer\Contact
    +-- hr\employee\Employee
    +-- sale\customer\Customer
    +-- purchase\supplier\Supplier
```

The current generic facet registry is declared by `IdentityAbstract::MAP_FIELDS_FACETS`. It maps each explicit backlink on `Identity` to its facet class:

| Identity backlink | Facet class |
| --- | --- |
| `organization_id` | `identity\Organization` |
| `contact_id` | `identity\Contact` |
| `customer_contact_id` | `sale\customer\Contact` |
| `employee_id` | `hr\employee\Employee` |
| `customer_id` | `sale\customer\Customer` |
| `supplier_id` | `purchase\supplier\Supplier` |

Each facet has the reciprocal `identity_id` field:

```text
Facet.identity_id = Identity.id
        ^                  |
        |                  v
        +---- Identity.<facet>_id
```

The concrete facet classes enforce a unique `identity_id`, so an `Identity` can have at most one facet of each type. A contextual relationship that can occur several times, such as a contact working for several organizations, should instead be represented by a separate relation entity.

`identity\Partner` is one such relation entity in the current codebase: it links an `owner_identity_id` to a `partner_identity_id`. It does not extend `IdentityFacet`, is not part of `MAP_FIELDS_FACETS`, and retains its own legacy synchronization behavior. The generic rules on this page apply only to registered `IdentityFacet` classes unless a model explicitly implements an equivalent contract.

## Canonical and role-specific fields

Only fields listed in `IdentityAbstract::COMMON_IDENTITY_FIELDS` participate in generic synchronization. The current list covers:

- identity type and legal identifiers;
- legal, short, first, and last names;
- nationality, gender, title, date of birth, and preferred language;
- the main postal address;
- email addresses, telephone numbers, mobile, fax, and website;
- main bank account details;
- image and signature.

The exact constant is authoritative when adding or removing a shared field.

Fields that are not in this list remain local to their model. Updating a customer-only field, for example, neither changes `Identity` nor any other facet. Stored projections such as `name`, `type`, and `address` are derived from synchronized values rather than being canonical input fields.

Although common fields are physically present in facet tables, their values are local projections. This duplication preserves polymorphic ORM queries, views, and access rules; it does not create several independent sources of truth.

## Establishing the link

The final state must contain both sides of the relationship:

```text
Customer.identity_id = 42
Identity #42.customer_id = Customer.id
```

The ORM hooks maintain these reciprocal links whether the relationship is established from `Facet.identity_id` or from an `Identity` backlink. Reassigning a facet updates the old and new backlinks and then realigns the facet with its new canonical identity.

### Creating a facet with an explicit identity

When `identity_id` is supplied, the facet is attached to that existing identity. Common fields explicitly supplied in the same operation are first applied to the target `Identity`; all other common fields come from that `Identity` when the facet is synchronized.

For example:

```php
Customer::create([
    'identity_id' => $identity_id,
    'email'       => 'new@example.com'
]);
```

The explicit email becomes canonical. By contrast, creating the same facet with only `identity_id` copies every common value from the existing `Identity` to the new facet.

### Creating a facet without an identity

When a facet is created without `identity_id`, `IdentityFacet::onafterinstantiate()` tries to resolve an existing identity using the following strong identifiers, in order:

1. `vat_number`;
2. `registration_number`;
3. `citizen_identification`.

Empty identifiers are ignored. The first match is used. The existing identity remains canonical, so its common values replace the new facet's local projections.

If no match exists, a new `Identity` is created and initialized with the facet's common values. The new identity then immediately becomes canonical, the reciprocal backlink is set, and the facet is synchronized from it.

This initialization is the only phase in which an unlinked facet seeds a new canonical identity:

```text
unlinked Facet --common values--> new Identity
                                     |
                                     +--> linked Facet
```

Identity resolution deliberately uses only the three implemented strong identifiers. Names, email addresses, telephone numbers, and postal addresses are not automatic matching keys because they are not sufficiently reliable.

### Reassigning a facet

Changing only `identity_id` makes the target `Identity` authoritative for every common field:

```php
Customer::id($customer_id)->update([
    'identity_id' => $target_identity_id
]);
```

If the same update explicitly includes common fields, those fields update the target identity while the remaining common fields still come from the target:

```php
Customer::id($customer_id)->update([
    'identity_id' => $target_identity_id,
    'email'       => null
]);
```

Here `email = null` is intentional input. It clears the canonical email and is propagated to the other linked facets.

## Synchronization flow

All generic synchronization passes through `Identity`; facets never synchronize directly with one another.

```text
changed Facet
     |
     v
  Identity
     |
     +----------+----------+
     v          v          v
 Customer    Supplier   Employee
```

### Updating an Identity

After an `Identity` update, `Identity::onafterupdate()` intersects the operation's values with `COMMON_IDENTITY_FIELDS`. It propagates only those explicitly updated common fields to every linked facet found through `MAP_FIELDS_FACETS`.

Null is a value, not an instruction to skip synchronization. Therefore, clearing a canonical field also clears its projection on every linked facet.

Facet writes performed during this redistribution have ORM events disabled. This prevents a propagated update from starting the reverse synchronization path and creating a loop.

### Updating a facet

After a facet update, `IdentityFacet::onafterupdate()` extracts only the common fields present in that update and writes their resulting values to `Facet.identity_id`. Updating a role-specific field has no synchronization effect.

The canonical `Identity` update then follows its normal path and redistributes the values to every linked facet. In other words:

```text
Facet.onafterupdate()
        |
        v
Identity.update()
        |
        v
Identity.onafterupdate()
        |
        v
all linked facets
```

This two-step path guarantees that a change made through any facet becomes a canonical change before it reaches another facet.

### Manual realignment

Every class extending `IdentityFacet` exposes the ORM action `sync_from_identity`. It replaces all common fields on the selected facet with the current canonical values:

```php
Customer::id($customer_id)->do('sync_from_identity');
```

Unlike ordinary update propagation, this action synchronizes the complete `COMMON_IDENTITY_FIELDS` set, not only recently modified fields. It is intended for imports, migrations, bulk operations, or repairing a projection that was changed while ORM events were disabled.

The action does nothing when the facet has no valid `identity_id`. Its internal write disables ORM events to avoid a reverse update.

## Synchronization summary

| Situation | Source | Target | Common values used |
| --- | --- | --- | --- |
| Update `Identity` | `Identity` | All linked facets | Only fields explicitly updated |
| Update a facet | Facet | `Identity`, then all linked facets | Only fields explicitly updated |
| Create or attach with only `identity_id` | Existing `Identity` | Attached facet | All common fields |
| Create or attach with explicit common fields | Facet input | Target `Identity`, then linked facets | Explicit fields; other fields come from the identity |
| Create without a resolvable identity | New facet | New `Identity` | All common fields used for initialization |
| Run `sync_from_identity` | `Identity` | Selected facet | All common fields |

## The `User` compatibility projection

`identity\User` is associated with `Identity`, but it is not a generic `IdentityFacet` and does not inherit all common identity fields. It has a dedicated `identity_id` and the separate `Identity.user_id` backlink.

Only this limited mapping is synchronized:

| Identity | User |
| --- | --- |
| `firstname` | `firstname` |
| `lastname` | `lastname` |
| `lang_id.code` | `language` |

Changes to these user fields are written to the linked `Identity`. Changes to `Identity.firstname`, `Identity.lastname`, or `Identity.lang_id` are copied back to the linked user. `User` also exposes its own `sync_from_identity` action.

Attaching a user maintains `Identity.user_id`, but the generic identity creation and strong-identifier resolution described for `IdentityFacet` do not apply to `User`.

## Lifecycle safeguards

The synchronization hooks do not delete the canonical identity when a facet is removed. In the other direction, `Identity::candelete()` rejects deletion while any registered facet backlink or `user_id` remains active. Consumers should explicitly resolve or detach roles before deleting an identity.

Duplicate identities must likewise be merged explicitly. If both identities already have the same facet type, the conflict must be resolved before the records can satisfy the one-facet-per-type invariant.

## Adding a new facet type

A new generic identity facet should follow the complete contract:

1. extend `identity\IdentityFacet`;
2. make `identity_id` unique for that facet type;
3. add a typed backlink field to `identity\Identity`;
4. register that backlink and class in `IdentityAbstract::MAP_FIELDS_FACETS`;
5. keep intrinsic fields in `COMMON_IDENTITY_FIELDS` and role-specific fields on the facet;
6. test creation, identity resolution, reciprocal links, reassignment, null propagation, and manual realignment.

This explicit registry is intentional. It keeps ORM relations strongly typed, makes view joins and domains straightforward, and makes the synchronization cascade deterministic.
