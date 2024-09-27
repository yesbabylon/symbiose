# Contributing to Symbiose

> [!NOTE]
> To update this file, please refer to a
> [source file](https://github.com/yesbabylon/symbiose/blob/dev-2.0/CONTRIBUTE.md)

We love your input and new contributions to Symbiose are most welcome!

We want to make contributing to this project as easy and transparent as possible, whether it's:

- Reporting a bug
- Discussing the current state of the code
- Submitting a fix
- Proposing new features
- Becoming a maintainer

This document contains notes and guidelines on how to contribute to Symbiose.

## Joining the project

Active committers and contributors are invited to introduce themselves and request commit access to the project on the
Discord [#join](https://discord.gg/65WcBQFVg6) channel. If you think you can help, we'd love to have you!

## First-time contributors

We've tagged some issues to make it easy to get started :smile:
[Good first issues](https://github.com/yesbabylon/symbiose/labels/good%20first%20issue)

If you're interested in working on an issue, make sure it has either a `good-first-issue` label added. Add a comment on
the issue and wait for the issue to be assigned before you start working on it (this helps to avoid multiple people
working on similar issues).

## Bugs and Issues

Please report these on our [GitHub page](https://github.com/yesbabylon/symbiose/issues). Please do not use issues for
support requests: for help using Symbiose, please consider Stack Overflow.

Well-structured, detailed bug reports are hugely valuable for the project.

Guidelines for reporting bugs:

* Check the issue search to see if it has already been reported.
* Isolate the problem to a simple test case.
* Please include a code snippet that demonstrates the bug. If filing a bug against master, you may reference the latest
  code using the URL of the repository of the branch (for example,
  [](https://github.com/yesbabylon/symbiose/blob/master/eq.lib.php) - changing the filename to point at the file you
  need
  as appropriate).
* Please provide any additional details associated with the bug, if it's generic or only happens with a certain
  configuration or data set.

## We Develop with GitHub

We use GitHub to host code, to track issues and feature requests, as well as for accepting pull requests.

## Any contributions you make will be under the LGPL 3.0 Software License

In short, when you submit code changes, your submissions are understood to be under the
same [LGPL License](https://www.gnu.org/licenses/lgpl-3.0.en.html) that covers the project. Feel free to contact the
maintainers if that's a concern.

## Symbiose development guidelines

### general naming conventions

* names of callbacks in .class.php files: computed, default, policies, actions, ...
* computed fields handler (`function` property): `calc{FieldName}()`
* field default value (when not using closure) : `default{FieldName}()`
* policy handler (`function` property) : `policy{PolicyName}()`
* transition onbefore handler (`onbefore` property) : `onbefore{TransitionName}()`
* transition onafter handler (`onafter` property) : `onafter{TransitionName}()`
* onupdate : `onupdate{FieldName}()` - there must be exactly one or zero onupdate callback for a field, and each field
  should use a callback of its own (do not reuse a same callback for several fields)
* names of Class methods :
    * oncreate
    * onbeforeupdate
    * onafterupdate
    * onbeforedelete
    * onafterdelete
    * onchange
* names of properties
    * when a field holds a value that comes from (or is meant to be used in) external software, prefix with `extref_`
* variable names: see https://doc.equal.run/contributing/contribution-guide/#naming-conventions
* names for flags (bool): Boolean flags indicating a specific feature of the object always start with `is_` or `has_` .
  examples: has_parent, is_active
* reusable methods, used to compute a result depending on a specific input and that might ave an arbitrary signature,
  are set as `private`. By convention, their name starts with `compute` (e.g. `private function computeConsumptions()`)

### Ambiguous properties

Ambiguous properties are prefixed with the entity name (if the entity name has more than one part, only the last part is
kept: for example, AccountingChartLine::line_class)

Ambiguous property names are those:

* that has a language meaning (PHP, JS, ...): class, default, interface, public, ...
* ###### that are used in field descriptors (except for `description`): type, usage, default, required, store, function
* that has a specific meaning in view descriptors: layout, sections, mode, groups, items, ...
* that point to a class having the same basename that another field (for example, product_id => inventory_product_id or
  catalog_product_id)
* some fields can have a special meaning and must be kept ambiguous: name, code, order, date

### Views

#### Inheritance

* Inheritance for constructing views (forms)should only be used on the least likely to change (not more than one level
  away from the root view).
* A view should never inherit from a view that already inherits from another.

### Templates

HTML templates are considered as views. For now, the Twig engine is used, but this could change in the future. For that
reason, templates should only contain layout information and little to no logic.
Of course, conditions and loops are allowed when necessary, but as a rule of thumb, templates should:

* handle terms translations
* handle values rendering
* contain exclusively HTML / CSS (no script JS or PHP)
* 1 file = 1 view (no inclusions)

### Commits

Using emoji in commits is encouraged

Syntax
[](https://gitmoji.dev/)

Syntax for issues
[](https://www.webfx.com/tools/emoji-cheat-sheet/)

## Submitting Pull Requests

We use [GitHub Flow](https://guides.github.com/introduction/flow/index.html), so all code changes happen through Pull
Requests.
Pull requests are the best way to propose changes to the codebase (we
use [GitHub Flow](https://guides.github.com/introduction/flow/index.html)). We actively welcome your pull requests:

1. Fork the repo and create your branch from
   `master` [](https://github.com/yesbabylon/symbiose/fork)
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create a new Pull Request

### Guidelines for submitting a pull request

* Before opening a PR for additions or changes, please discuss those by filing an issue
  on [GitHub](https://github.com/yesbabylon/symbiose/issues) or asking about it
  on [Discord](https://discord.gg/BNCPYxD9kk) (#general channel). This will save you development time by getting
  feedback upfront and make review faster by giving the maintainers more context and details.
* Before submitting a PR, ensure that the code works with all PHP versions that we support (currently PHP 7.0 to PHP
  7.4); that the test suite passes and that your code lints.
* If you've changed some behavior, update the 'description' and 'help' attributes (when present).
* If you are going to submit a pull request, please fork from `master`, and submit your pull request back as a
  fix/feature branch referencing the GitHub issue number
* Please include a Unit Test to verify that a bug exists, and that this PR fixes it.
* Please include a Unit Test to show that a new Feature works as expected.
* Please don't "bundle" several changes into a single PR; submit one PR for each discrete change and/or fix.

### Helpful resources

* [Helpful article about forking](https://help.github.com/articles/fork-a-repo/ "Forking a GitHub repository")
* [Helpful article about pull requests](https://help.github.com/articles/using-pull-requests/ "Pull Requests")

## Submitting a package or a library

### Guidelines for submitting a package

* Check that your package will pass the consistency tests (`$ ./equal.run --do=test_package-consistency`).
* Make sure your package comes with unit tests (in the `packages/{your_package}/tests/`) and that classes and
  controllers have descriptions and helpers.

## Submitting Symbiose core contributions

### Guidelines

* All new development should be on feature/fix branches, which are then merged to the `master` branch once stable and
  approved; so the `master` branch is always the most up-to-date, working code
* Avoid breaking changes unless there is an upcoming major release, which is infrequent. We encourage people to write
  distinct libraries and/or packages for most new advanced features, and care a lot about backwards compatibility.

## Unit Tests

When writing Unit Tests, please:

* Always try to write Unit Tests for both the happy and unhappy scenarios.
* Put all assertions in the Test itself, not in external classes or functions (even if this means code duplication
  between tests).
* Always try to split the test logic into ``AAA`` callbacks `arrange()`, `act()`, `assert()` and `rollback()` for each
  Test.
* If you change any global settings, make sure that you reset to the default in the `rollback()`.
* Don't overcomplicate test code by testing several behaviors in the same test.

This makes it easier to see exactly what is being tested when reviewing the PR. I want to be able to see it in the PR,
not have to hunt in other unchanged classes to see what the test is doing.
