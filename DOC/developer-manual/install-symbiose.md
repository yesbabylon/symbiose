## Install Symbiose

### Structure 

Symbiose is a set of business packages meant to cover standard needs and duties, any business has to deal with (such as an Address Book for customer and providers, Tasks logging, HR management, Invoicing, and so on). 

See below for the full list of existing packages.

Each package contains its own classes, controllers, views and applications. Custom packages can be developed by inheriting some (of all) features from standard packages.  

All packages depend on the `core` package from eQual. That package is shipped with Symbiose at each new release. For that reason, the versions of eQual and Symbiose have to match.

![Structure.drawio](../_assets/img/Structure.drawio.svg)

### Prerequisite

Symbiose requires [eQual framework](https://github.com/cedricfrancoys/equal).

Clone with Git.

```ssh
$ git clone https://github.com/equalframework/equal.git
```

### Install

1.- Go to equal directory .

```ssh
$ cd equal
```

2.-  Remove folder `packages` 

```ssh
$ rm -rf packages
```

 3.- Clone Symbiose.

```ssh
$ git clone https://github.com/yesbabylon/symbiose.git packages
```

### Packages

 Here are the different Symbiose packages.

```
packages
├── core (from eQual framework)
├── identity
├── documents
├── finance
├── sale
├── inventory
├── calendar
├── communication
├── support
├── timetrack
├── [...]
```

### Create a custom package with Symbiose

1. Create the new empty package (for instance`mypackage`) by making a new folder within the `/packages` folder,  here an example with Git.
    ```ssh
    $ mkdir ./packages/mypackage
    ```

2. Populate the package with classes, controllers, views, translations, and applications (see eQual doc for more details).

3. Initialize the package
    ```ssh
    $ ./equal.run --do=init_package --package=mypackage
    ```
