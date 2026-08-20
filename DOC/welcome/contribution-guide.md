## Symbiose development guidelines

### general naming conventions

*  names of callbacks in .class.php files: computed, default, policies, actions, ...
  * computed fields handler (`function` property): `calc{FieldName}()`
  * field default value (when not using closure) : `default{FieldName}()`
  * policy handler (`function` property) : `policy{PolicyName}()`
  * transition onbefore handler (`onbefore` property) : `onbefore{TransitionName}()`
  * transition onafter handler (`onafter` property) : `onafter{TransitionName}()`
  * onupdate : `onupdate{FieldName}()` - there must be exactly one or zero onupdate callback for a field, and each field should use a callback of its own (do not reuse a same callback for several fields)
* names of Class methods : 
  * oncreate
  * onbeforeupdate
  * onafterupdate
  * onbeforedelete
  * onafterdelete
  * onchange
* names of properties
  * when a field holds a value that comes from (or is meant to be used in) an external software, prefix with `extref_`
* variable names: see https://doc.equal.run/contributing/contribution-guide/#naming-conventions
* names for flags (bool) : Boolean flags indicating a specific feature of the object always start with `is_` or `has_` . examples: has_parent, is_active
* reusable methods, used to compute a result depending on a specific input and that might ave an arbitrary signature, are set as `private`. By convention their name starts with `compute` (e.g. `private function computeConsumptions()`)

### Ambiguous properties

Ambiguous properties are prefixed with the entity name (if the entity name has more than one parts, only the last part is kept: e.g., AccountingChartLine::line_class)

Ambiguous property names are those:
* that have a language meaning (PHP, JS, ...): class, default, interface, public, ...
* ###### that are used in field descriptors (except for `description`): type, usage, default, required, store, function
* that have a specific meaning in view descriptors: layout, sections, mode, groups, items, ...
* that point to a class having the same basename that another field (e.g., product_id => inventory_product_id or catalog_product_id)
* some field can have a special meaning and must be kept ambiguous : name, code, order, date



### Views


#### Inheritance
* Inheritance for constructing views (forms)should only be used on the least likely to change (not more than one level away from the root view).
* A view should never inherit from a view that already inherits from another.


### Templates

HTML templates are considered as views. For now, the Twig engine is used, but this could change in the future. For that reason, templates should only contain layout information and little to no logic.
Of course,  conditions and loops are allowed when necessary, but as a rule of thumb, templates should:

* handle terms translations
* handle values rendering
* contain exclusively HTML / CSS (no script JS or PHP)
* 1 file = 1 view (no inclusions)

### Commits
Using emoji in commits are encouraged

Syntax
https://gitmoji.dev/

Syntax for issues
https://www.webfx.com/tools/emoji-cheat-sheet/

### ERD conventions



