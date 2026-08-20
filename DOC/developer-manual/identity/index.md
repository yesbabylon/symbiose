# Identity and Relation Entities

The core idea is that an **Identity** represents a unique entity, associated with a series of roles or *relations*.

These relations are business entities that are conceptually tied to the same identity:

- Contact  
- Prospect  
- Customer  
- Supplier  
- Employee  
- Subsidiary Branch
- Organization  

Each of these relation entities holds its own copy of certain fields (such as name, address, email, phone, etc.) from the associated Identity.

- If a relation object is modified, the corresponding changes must be synchronized with the Identity and, in turn, with all other related entities.
- If the Identity is modified, the change must be reflected across all linked relations.



## Bidirectional Synchronization 

eQual implements a **bidirectional synchronization system** that:

- Centralizes shared data (contact info, name, etc.)
- Prevents data inconsistencies or desynchronization
- Maintains business entities as independent (each with its own table)
- Preserves fine-grained control over ACLs and roles per entity type



## Synchronization Rules

Business classes that are conceptually linked to an Identity (such as `Customer`, `Supplier`, `User`, etc.) inherit from a common `Identity` class *at the logic level*, but use *separate tables* in the database.

The `Identity` class also has its own dedicated table.

Each relation object contains a field `identity_id` pointing to its associated Identity.

- When a relation object is modified, its updated fields (such as address, email, etc.) are propagated to the associated Identity via `identity_id`.

- When the Identity is modified (either directly or indirectly), all relation objects referencing it are updated to reflect the new values for shared fields.
