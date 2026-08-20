# Packages 

## Structure

An application is divided in several parts, stored in a package folder located under the `/packages` directory.
Each package might contain the following folders (underlined folders are mandatory).

Each **package** is defined as follows:

```
package_name
├── classes
│   └── */*.class.php
├── actions
│   └── */*.php
├── apps
│   └── */*.php
├── data
│   └── */*.php
├── tests
│   └── */*.php
├── init
│   └── *.json
├── views
│   ├── 
│   └── *.json
├── i18n
│   └── *
│       └── *.json
├── uml
│   └── *.equml
├── assets
│   └── *.*
├── manifest.json         
├── config.json
└── readme.md
```

| **FOLDER** | **ROLE**                                                                | **URI KEY** | **EXAMPLES**                                                               |
|------------|-------------------------------------------------------------------------|-------------|----------------------------------------------------------------------------|
| classes    | model                                                                   |             | `core\User.class.php`, `core\Group.class.php`, `core\Permission.class.php` |
| actions    | action handler (controller)                                             | do          | core_manage, core_utils                                                    |
| apps       | applications related to the package                                     |             | auth, apps                                                                 |
| data       | data provider                                                           | get         | core_objects_browse, core_user_lang                                        |
| test       | test units                                                              | do          | `default.php`                                                              |
| init       | initialize the package with data (**requires**:`import=true`) or routes | do          | `core_Group.json`                                                          |
| views      | templates                                                               |             | `User.form.default.json`, `User.list.default.json`                         |
| i18n       | translations                                                            |             | `User.json`                                                                |
| uml        | uml files                                                               |             | `overview.or.equml`                                                        |
| assets     | static html                                                             |             | static content, javascripts, stylesheets, images                           |

##### manifest.json

The manifest is a file containing information about the package and its apps:

```
{
    name: string
    depends_on: []
    apps: [
    {    
        name: string
        description: string
        url: string
        icon: string
        color: string
        access: {
            groups: []
        }
    }
}
```

**Package**

| **PROPERTY** | **ROLE**                                           | **EXAMPLES**          |
|--------------|----------------------------------------------------|-----------------------|
| name         | (mandatory) name of the package                    | `"core", "finance"`   |
| depends_on   | packages that need to be instanciated preventively | `["core", "finance"]` |
| apps         | applications related to the package                | `auth, apps`          |

**Apps**

| **PROPERTY**     | **ROLE**                                    | **EXAMPLES**           |
|------------------|---------------------------------------------|------------------------|
| name             | name of the application                     | `APPS_APP_SETTINGS`    |
| description      | description of the application              |                        |
| url              | url of the application                      | `/auth`                |
| icon (optional)  | material icons representing the application | `settings`             |
| color (optional) | color attributed to the application         | `#FF9741`              |
| access/groups    | groups giving access to the application     | `setting.default.user` |


# All the packages

## core

The core package is the starter pack of symbiose. Pretty much every other package uses the classes built in core, by extending (*reusing the classes built in core with their parameters and adding new parameters*), or considering that every other package is than lacking the classes initiated in core. 

A few examples of the classes initiated in core: 

- User.class.php
- Translation.class.php
- Permission.class.php
- etc.

Those classes appear necessary for the functioning of any website.

In conclusion, this package needs to be instantiated first and to achieve that, it uses an initiator controller : `core\actions\init\package`.  The controller has to parameters: `import`& `compile`, `import` ables the import of data (from the `init folder`) to the DB and `compile` ables the compilation of the package that will be moved to the `public` folder. 

Symbiose gives an instantiating order of the packages, this order can be found inside the `manifest.json` file, necessary to every package at the `depends_on` property.

Example from the `sale` package : 

```
"depends_on": [
    "core",
    "identity",
    "finance"
]
```

In this example, the packages : `core, identity & finance` are instantiated before the `sale` package and in this order (every time one of these packages is instantiated, the controller looks at their own `manifest.json file`).

### controllers

#### actions

| **CONTROLLER**           | **ROLE**                                                                                                            |
|--------------------------|---------------------------------------------------------------------------------------------------------------------|
| decrypt                  | Decrypts given cipher using private cipher key                                                                      |
| encrypt                  | Encrypts given value using private cipher key                                                                       |
| **alert**                |                                                                                                                     |
| alert_dismiss            | Provide a fully loaded tree for a given CashdeskSession                                                             |
| **config**               |                                                                                                                     |
| config_save-model        | Translate an entity definition to a PHP file and store it in related package dir.                                   |
| **cron**                 |                                                                                                                     |
| cron_run                 | Run a batch of scheduled task. Expected to be run with CLI `$ ./equal.run --do=cron_run                             |
| **group**                |                                                                                                                     |
| group_grant              | Grant additional privilege to given group                                                                           |
| group_revoke             | Revoke privilege from a given group                                                                                 |
| **init**                 |                                                                                                                     |
| init_db                  | Create a database according to the configuration                                                                    |
| init_package             | Initialise database for given package. If no package is given, initialize core package                              |
| **model**                |                                                                                                                     |
| model_archive            | mark the given object(s) as archived                                                                                |
| model_clone              | Create a new object by cloning an existing object                                                                   |
| model_create             | Create a new object using given fields values                                                                       |
| model_delete             | Deletes the given object(s)                                                                                         |
| model_onchange           | Transform an object being edited in a view, according to the onchange method of the entity, if any                  |
| model_update             | Update (fully or partially) the given object                                                                        |
| **spool**                |                                                                                                                     |
| spool_run                | Send emails that are currently in spool queue                                                                       |
| **test**                 |                                                                                                                     |
| test_db-access           | Tests access to the database.\nIn case of success, the script simply terminates with a status code of 0 (no output) |
| test_db-connectivity     | Checks current installation directories integrity                                                                   |
| test_package-consistency | TThis script tests the given package and returns a report about found errors (if any)                               |
| test_package             | Check presence of test units for given package and run them, if any                                                 |
| test_smtp-connectivity   | Checks current installation directories integrity                                                                   |
| **user**                 |                                                                                                                     |
| user_confirm             | Validate a user subscription                                                                                        |
| user_create              | Creates a new user account based in given credentials and details                                                   |
| user_grant               | Grant additional privilege to given user                                                                            |
| user_oauth               | Attempt to auth a user from an external social network                                                              |
| user_pass-recover        | Send password recovery instructions to current user                                                                 |
| user_pass-update         | Updates the password related to a user account                                                                      |
| user_revoke              | Revoke privilege from a given user                                                                                  |
| user_signin              | Attempts to log a user in                                                                                           |
| user_signup              | Attempt to register a new user                                                                                      |
| **utils**                |                                                                                                                     |
| user_update              | Updates a user account based on given details                                                                       |
| utils_sqldesigner_update | Returns the schema of given class (model)                                                                           |

#### data

| **CONTROLLER**           | **ROLE**                                                                                                              |
|--------------------------|-----------------------------------------------------------------------------------------------------------------------|
| appinfo                  | Returns descriptor of current Settings, specific to current User                                                      |
| installed-apps           | Returns a map with the descriptors of the installed apps (depending on initialized packages)                          |
| userinfo                 | Returns descriptor of current User, based on received access_token                                                    |
| **auth**                 |                                                                                                                       |
| auth_refresh             | Attempts to renew an access token that is about to expire (but still valid)                                           |
| **config**               |                                                                                                                       |
| config_apis              | Returns a list of existing API (string identifiers)                                                                   |
| config_classes           | Returns the list of classes defined in specified package                                                              |
| config_controllers       | Returns the list of controllers defined in given package                                                              |
| config_i18n-menu         | Retrieves the translation values related to the specified menu                                                        |
| config_i18n              | Retrieves the translation values related to the specified entity                                                      |
| config_namespaces        | Returns the list of namespaces defined in specified package                                                           |
| config_packages          | Returns the list of public packages in current installation                                                           |
| config_routes            | Returns existing routes for a given API, along with their description and expected parameters                         |
| config_settings          | Retrieve configuration settings according to package and section filters (support wildcards support) and current user |
| config_types             | Returns schema of available types and related possible attributes                                                     |
| **model**                |                                                                                                                       |
| model_chart              | Returns a list of entites according to given domain (filter), start offset, limit and order                           |
| model_collect            | Returns a list of entites according to given domain (filter), start offset, limit and order                           |
| model_export-all-json    | Retrieve the list of all objects of a given model, and output it as a JSON collection suitable for import             |
| model_export-chart-xls   | Returns a view populated with a collection of objects, and outputs it as an XLS spreadsheet                           |
| model_export-pdf         | Returns a view populated with a collection of objects and outputs it as a PDF document                                |
| model_export-xls         | Returns a view populated with a collection of objects, and outputs it as an XLS spreadsheet                           |
| model_menu               | Returns the JSON menu related to a package, given a menu ID                                                           |
| model_read               | Returns values map of the specified fields for object matching given class and identifier                             |
| model_schema             | Returns the schema of given class (model) in JSON                                                                     |
| model_search             | 'Returns a list of identifiers of a given entity, according to given domain (filter), start offset, limit and order   |
| model_view-help          | Returns the JSON view related to an entity (class model), given a view ID (<type.name>)                               |
| model_view               | Returns the JSON view related to an entity (class model), given a view ID (<type.name>)                               |
| **user**                 |                                                                                                                       |
| user_groups              | Grant additional privilege to given user                                                                              |
| user_rights              | Grant additional privilege to given user                                                                              |
| **utils**                |                                                                                                                       |
| utils_sqldesigner_schema | Returns the schema of given class (model)                                                                             |
| utils_git-revision       | Provide a unique identifier of the current git revision.\n                                                            |
| utils_sql-schema         | Returns the schema of the specified package in standard SQL ('CREATE' statements with 'IF NOT EXISTS' clauses)        |

## calendar

This package instantiates `Holiday & HolidayYear`classes.

## communication

This package instantiates classes used as templates/attachments and is very useful for any mailing service or anything related to messages.

## documents

This package ables the use of documents for your application, with the possibility to import a large amount of different file extensions.  

### controllers

#### actions

| **CONTROLLER** | **ROLE**                                                                    |
|----------------|-----------------------------------------------------------------------------|
| document       | Return raw data (with original MIME) of a document identified by given hash |

## finance

This package takes care of the accounting part of your application, everything that concerns `accounting, stats & tax`. 

## identity

This package instantiates all the identities needed for the functioning of any business, ex: `Address, Establishment, Identity, IdentityType, Partner, User`.

### controllers

#### data

| **CONTROLLER** | **ROLE**                                                           |
|----------------|--------------------------------------------------------------------|
| userinfo       | Returns descriptor of current User, based on received access_token |

## inventory

This package instantiates products used by the business.

### controllers

#### actions

| **CONTROLLER**         | **ROLE**                                                                                                                                                                                  |
|------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **subscription**       |                                                                                                                                                                                           |
| add-subscription-entry | Creates a subscription entry for the current period of subscription                                                                                                                       |
| shift-period           | Shifts subscription *date_from* and *date_to* fields (current period) to next subscription period                                                                                         |
| update-expirations     | Sets expired subscriptions *is_expired* field to true <br/> Sets upcoming expiry subscriptions *has_upcoming_expiry* field to true <br/> (*Should be executed at the start of every day*) |

#### data

| **CONTROLLER**       | **ROLE**                                                                                               |
|----------------------|--------------------------------------------------------------------------------------------------------|
| access-collect       | Returns a list of accesses according to given domain (filter), start offset, limit and order           |
| product-collect      | Returns a list of inventory products according to given domain (filter), start offset, limit and order |
| server-collect       | Returns a list of servers according to given domain (filter), start offset, limit and order            |
| service-collect      | Returns a list of services according to given domain (filter), start offset, limit and order           |
| software-collect     | Returns a list of softwares according to given domain (filter), start offset, limit and order          |
| subscription-collect | Returns a list of subscriptions according to given domain (filter), start offset, limit and order      |
| **sale**             |                                                                                                        |
| customer-collect     | Returns a list of sale customers according to given domain (filter), start offset, limit and order     |

## realestate

This package instantiates anything related to realestate.

## sale

This package takes care of the sales part of your application, from a point of sale, to the customers, price, etc.

### controllers

#### data

| **CONTROLLER**   | **ROLE**                                      |
|------------------|-----------------------------------------------|
| pos_order_tree   | Provide a fully loaded tree for a given order |
| pos_session_tree | Mark a contract as sent to the customer       |


#### actions

| **CONTROLLER**       | **ROLE**                                                     |
|----------------------|--------------------------------------------------------------|
| contract_cancelled   | Sets contract as cancelled                                   |
| contract_sent        | Mark a contract as sent to the customer                      |
| contract_signed      | Mark a contract as signed (signed version has been received) |
| pos_payment_validate | Validate a partial payment of a cashdesk order               |

## stats

This package instantiates anything related to statistics.


## timetrack

This package records time entries of employees, which can be used to invoice customers.

### controllers

#### actions

| **CONTROLLER** | **ROLE**                                                       |
|----------------|----------------------------------------------------------------|
| **timeentry**  |                                                                |
| create_quick   | Create a time entry only requesting the project and the origin |

#### data

| **CONTROLLER**    | **ROLE**                                                                                                                                                                                                   |
|-------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| timeentry-collect | Returns a list of time entries according to given domain (filter), start offset, limit and order <br/> *Filters*: description, user_id, date, customer_id, project_id, origin, has_receivable, is_billable |
