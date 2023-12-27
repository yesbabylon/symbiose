# Qursus
[![License: LGPL v3](https://img.shields.io/badge/License-LGPL%20v3-blue.svg)](https://www.gnu.org/licenses/lgpl-3.0)
[![Maintainer](https://img.shields.io/badge/maintainer-yesbabylon-blue)](https://github.com/yesbabylon)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat-square)](https://github.com/cedricfrancoys/equal/pulls)
<!-- ![eQual - Create great Apps, your way!](https://github.com/equalframework/equal/blob/master/public/assets/img/equal_logo.png?raw=true) -->

<a href="https://github.com/equalframework/" style="font-size:2rem;display:flex; align-items:center; wrap; flex-wrap: wrap; flex-shrink:0; text-decoration:none; color:inherit">Made with eQual framework <img itemprop="image" style="margin-left:10px; border-radius:10px" src="https://avatars.githubusercontent.com/u/111111764?s=200&amp;v=4" width="46px" height="46px" alt="@equalframework"/>
</a>

![qursus](./assets/images/qursus.png)

## Why Qursus :

We needed a way to present people about how to use eQual so we created this app. Ultimately, you will be able to learn how to use Qursus, how to use eQual framework by following our module Qursus.

## How we propose to solve the problem by using Qursus :

Qursus uses eQual framework in back-end to connect to modules of lessons. There are two views. A user can view a lesson or if the person has the rights there is an edition mode. Every module can be created and edited in the browser, in command lines or through the api, by a user with the correct credentials.

## How Qursus works :

Qursus works with eQual on the back-end. The eQqual serves the data, auth and controls the views. The Packs, Users and different modules and it chapters can be created in Wordpress by using the Qursus Plugin. The student can follow the lesson using the web app that is deployed in /public when the package qursus is initiated.

## What is Qursus :

Qursus is a learning management system powered by eQual Framework.

**UML Diagram of the application**

![uml](./assets/images/qursus-uml.drawio.png)

**Schema of the application**

A Qursus application contains one or several packs which can contain one or several modules which are themselves divided into chapters and pages. Each page has sections and "leaves". Leaves are divided into groups of 8 spaces per leaf. Those spaces can contain widgets. A widget can be a picture, a text, a title, some code excerpt... A pack can also have a bundle which is basically a zipped piece of attachments (video, pictures, pdf files) the student can download to follow the course.

![qursus-page-schema](./assets/images/qursus-page-schema.png)


## I / Install Qursus

Prerequisite : To install Qursus, eQual should be installed. Go to the [eQual documentation](https://doc.equal.run/getting-started/installation/) installation page.

Then navigate to your eQual in your docker or server instance. Then you need to add qursus to the packages. It is currently located in the [symbiose repository on github](https://github.com/yesbabylon/symbiose) under the branch dev-2.0. Fetch the repo in /packages then init the package qursus.

```bash
cd /var/www/html/
git clone https://github.com/yesbabylon/symbiose.git packages
cd /var/www/html/packages
git checkout dev-2.0
git pull
cd /var/www/html/
./equal.run --do=init_package --package=qursus
```

Now in your qursus package you should see :

```
/packages
    /qursus
        /actions
             import.php --> Create Modules, Chapters, Leaves, Pages, Groups and Widgets based on ATModule.json file.
             next.php --> Handle action from user when performing a click to see next page of a given module.
             survey.php --> Send an invite to satisfaction survey.
        /apps
            /qursus --> front-end in TypeScript and jQuery web.app that will be unpacked in /public/qursus on init
                export.sh -->
                web.app --> zip of the front-end build
                manifest.json
        /classes
            Bundle.class.php
            BundleAttachment.class.php
            Chapter.class.php
            Group.class.php
            Lang.class.php
            Leaf.class.php
            Module.class.php
            Pack.class.php
            Page.class.php
            Quiz.class.php
            Section.class.php
            UserAccess.class.php
            UserStatus.class.php
            Widget.class.php
        /data
            /module
                render.php  --> Returns a fully loaded JSON formatted single module
            /pack
                access.php --> Checks if current user has a license for a given program.
                certificate.php --> Returns a html page or a signed pdf certificate.
                complete.php --> Checks if a pack has been fully completed by current user.
                grant.php --> Checks if current user has a license for a given program.
            bundle.php   --> Sends either a single attachment or a zip archive containing all attachments.
            module.php  --> Returns a fully loaded JSON formatted single module.
            modules.php  --> "Returns a list of all modules for a given pack, enriched with current user status.
        /init
            /data
        /views
            Bundle.list.default.json
            Bundle.form.default.json
            ...
            Widget.form.create.default.json
            Widget.form.default.json
            Widget.list.default.json
        config.inc.php --> specify the DEFAULT_PACKAGE constant used for routing
        manifest.json --> usual eQual package manifest
```


## II / Configuration :

In the context of using equal with Wordpress we need to configure the environnement file and the `.htacces` file.

The configuration file will indicate what is the back-end api and what is the front-end api.
### a) config.json

**/public/assets/env/config.json**
```json
{
    "production": true,
    "parent_domain": "equal.local",
    "backend_url": "http://wpeq.local/equal.php",
    "rest_api_url": "http://wpeq.local/",
    "lang": "en",
    "locale": "en",
    "company_name": "eQual Framework",
    "company_url": "https://yesbabylon.com",
    "app_name": "eQual.run",
    "app_logo_url": "/assets/img/logo.svg",
    "app_settings_root_package": "core",
    "version": "1.0",
    "license": "AGPL",
    "license_url": "https://www.gnu.org/licenses/agpl-3.0.en.html"
}
```

### b) .htaccess

**/public/.htacces**
```ruby
Options -Indexes
DirectoryIndex index.php equal.php index.html

# BEGIN WordPress
# The directives (lines) between "BEGIN WordPress" and "END WordPress" are
# dynamically generated, and should only be modified via WordPress filters.
# Any changes to the directives between these markers will be overwritten.
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteRule ^userinfo$ equal.php [L,QSA]
RewriteRule ^appinfo$ equal.php [L,QSA]
RewriteRule ^envinfo$ equal.php [L,QSA]
RewriteRule ^workbench$ equal.php [L,QSA]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>

# END WordPress
```

### c) config.inc.php
**/packages/qursus/config.inc.php**
```php
<?
namespace config;
define('WEBSITE_URL', 'http://wpeq.local');
define('WEBSITE_TITLE', 'Learning with Qursus');
```

### d) Tips and help

In your terminal you can use the flag --announce to get information about the controller. Since eQual controllers are self documented, you will know how it works.

```bash
./equal.run --get=model_collect --announce
./equal.run --do=model_update --announce

```

## III / Classes and Controllers

Fundamentally the qursus application can be schematized this way :

```bash
├─Pack
    ├─Lang
    ├─Bundle
        ├─BundleAttachment
    ├─Module
        ├─Chapter
            ├─Page
                ├─Section
                    ├─Page
                ├─Leaves
                    ├─Group
                        ├─Widgets

```

![qursus-page](./assets/images/qursus-page.png)

### Pack

A pack is at the basis of a qursus. It has a title, a subtitle and languages it is available into. Some (learning) modules will be attached to the pack. For example, the package **Learning eQual** could have modules called **back-end , front-end, low-code**.

To create a pack. You can go to the Wordpress part of your site http://wpeq.local/wp-admin/index.php and create the Pack with the **YB LMS** plugin installed and activated. Or if you don't have Wordpress, go to the In the dashboard menu, select Pack and click on the button create. You should get a form and enter the title, the slug of the package which should be unique. You can add a subtitle if you wish. Don't forget to click on the save button.

A pack is defined by :
 - name a unique slug for the pack : *example : slug-of-qursus-first-pack*
 - title : string: *example : Learning Qursus*, The title is `multilang` so you can set a different one according to the language the pack is available in.
 - subtitle : string: *example : by using Qursus*, This field is `multilang` so you can set a different one according to the language.
 - description : text 'Description of the content of the pack, modules, chapters, etc.'*example : This is a basic description of what is taught in this pack. I can write the numbers and names of the modules, etc.*
 - modules : alias of modules_ids, the relationship between modules and the pack.
 - modules_ids : a relation one2many, a pack can have many modules.
 - quizzes_ids : a relation one2many, a pack can have many quizzes.
 - bundles_ids : a relation one2many, a pack can have many bundles.
 - langs_ids : a relation many2many, a pack can have many languages and a language can be available in many packs.


![Create and update a Pack](assets/images/pack.png)

If the pack is deleted, its associated modules are deleted too.

To create a pack or any other entity like a Module a Chapter you can also use the usual eQual controllers and replace by the appropriate entity name and fields:
http://wpeq.local/equal.php/?do=model_create&entity=qursus\Pack&fields[state]=draft&lang=en

```bash
./equal.run --do=model_create --entity=qursus\Pack --fields[state]=draft --lang=en
```

You can the update this model.


The response should be:
```json
{
    "entity": "qursus\\Pack",
    "id": 6
}
```

You can update the Pack entries:
```bash
./equal.run --do=model_update --entity='qursus\Pack' --ids=6 --fields='{name:"slug-of-the-pack", title:"Title of the Pack", subtitle : "Subtitle of the Pack", description: "This is a basic description of what is taught in this pack. I can write the numbers and names of the modules and chapters etc." }'
```

**Response**
```json
[
    {
        "id": 6,
        "name": "slug-of-the-pack",
        "title": "Title of the Pack",
        "subtitle": " Subtitle of the Pack",
        "description": " This is a basic description of what is taught in this pack. I can write the numbers and names of the modules and chapters etc.",
        "langs_ids": " ",
        "modifier": 1,
        "state": "instance",
        "modified": "2023-12-19T10:25:58+00:00"
    }
]
```

Now you can check your newly updated pack by using eQual model_collect.

```bash
./equal.run --get=model_collect --entity='qursus\Pack' --domain=['id','=',6]

```
**Response**
```json
[
    {
        "id": 6,
        "name": "slug-of-the-pack",
        "state": "instance",
        "modified": "2023-12-19T10:28:02+00:00"
    }
]

```

### Languages

You can have a pack so a course available in one or several languages. They are defined by the Lang.class.php. A language has a
 - name : in english *example : french*
 - code : an ISO 639-1 language code by *example : fr*
 - packs_ids : a relation many2one to the packs the language is used in : *example : [1 , 2, 3]*


### Quiz

You can create one or many quizzes for a pack. When the pack is deleted so are its quizzes.
They are defined by the Quiz.class.php. A quiz has a
 - identifier : integer, the unique id of the quiz in the pack *example : 1*
 - name : in english *example : Testing php fundamentals*
 - quiz-code : an integer which is multilang language code by *example : 1 for english, 2 for french, 3 for dutch*
 - packs_ids : a relation many2one to the packs the language is used in : *example : [1 , 2, 3]*

### Bundle and Bundle attachments

You can create one or many Bundles for a pack. When the pack is deleted so are its Bundles. Basically a Bundle is a zip folder the student can download. It contains attachments files that are used to follow the course.
They are defined by the Bundle.class.php and BundleAttachment.class.php. A Bundle has a
 - name : *example : Bundle Module 1 php fundamentals*
 - description : The content of the bundle described in text: *example : book of php fundamentals, video tutorial installation of php, Pdf file of php cheat-sheet*
 - attachments-ids : a relation many2one between the bundle and its attachments*
 - packs_ids : a relation many2one to the packs the bundle is used in : *example : [1 , 2, 3]*

When a bundle is deleted the attachment is removed to. A bundle attachment is defined by :
 - name : *example : Book of php fundamentals by YesBabylon*
 - url : The destination the file is at: *example : https://wpeq.local/qursus/assets/images/qursus.webp, The url is `multilang` so you can set a different one according to the language the pack is available in.
 - bundles_ids : a relation many2one to the bundle the attachment is used in *


### Module

#### Definition of a Module :

A Module is a major part in the pack. It contains chapters. A Module is defined by :
 - identifier : integer : a unique identifier of the module
 - order : integer : The position the module is in the pack: *example : 1*
 - name : alias of the title
 - title : string : Description of the module as presented to user.
 - link : computed field : URL to visual editor of the module. *example : http://wpeq.local/qursus/?mode=edit&module=11&lang=en*
 - page_count : computed result type integer: Total amount of pages in the module.
 - chapter_count : computed result type integer : Total amount of chapters in the module.
 - description : Description of the content of module.
 - duration : integer : "Indicative duration, in minutes, for completing the module.
 - chapters : alias of chapters_ids
 - chapters_ids : relationship one2many between the module and its many chapters. When the module is deleted so are its chapters.
 - pack_id : relationship many2one between the module and its parent pack. Many modules can be in one pack. When the pack is deleted so are its modules.


#### Creating a Module :
Select the pack you just created, and click on update you can add modules to it.

![Module](assets/images/module.png)

A module is a major part in your course. It will be divided into chapters and pages.

#### The modules controllers and actions :


If you want to visualize all modules of a given pack you can use the terminal and the qursus_modules controller or for instance make a http request at http://equal.local?get=qursus_modules&pack_id=5 :

```bash
./equal.run --get=qursus_modules --pack_id=5

```

**Response**

```json
[
    {
        "identifier": 1,
        "title": "My very first module",
        "duration": 10,
        "description": "<p>This is the first module of the pack Learning Qursus.<\/p>",
        "page_count": 1,
        "name": "My very first module",
        "id": 11,  // id to inform to get the module
        "state": "instance",
        "modified": "2023-12-18T16:11:11+00:00",
        "status": "not started",
        "percent": 0
    },
    {
        "identifier": 2,
        "title": "Module 2 of the Qursus course",
        "duration": 10,
        "description": "<p>Test<\/p>",
        "page_count": 0,
        "name": "Module 2 of the Qursus course",
        "id": 12,
        "state": "instance",
        "modified": "2023-12-19T11:54:07+00:00",
        "status": "not started",
        "percent": 0
    }
]
```

If you then want to see a given module content lets call the controller qursus_module and give it the param --id=11 which is the id of the first module:

```bash
./equal.run --get=qursus_module --id=11
```

**Response**
```json
{
    "id": 11,
    "identifier": 1, // first module created in the pack
    "order": 1, // order the module will be displayed at
    "title": "My very first module", 
    "description": "<p>This is the first module of the pack Learning Qursus.<\/p>",
    "duration": 10, // duration of the module in minutes
    "pack_id": { // pack the module is part of
        "id": 5,
        "name": "learning-qursus",
        "title": "Learning Qursus",
        "subtitle": "with Qursus",
        "description": "<p>We add a new description. <\/p>",
        "langs_ids": [ // languages the module is available in
            {
                "id": 1,
                "name": "English",
                "code": "en",
                "state": "instance",
                "modified": "2023-12-18T15:43:43+00:00"
            },
            {
                "id": 2,
                "name": "French",
                "code": "fr",
                "state": "instance",
                "modified": "2023-09-22T08:14:19+00:00"
            }
        ],
        "state": "instance",
        "modified": "2023-12-18T15:43:53+00:00"
    },
    "chapters": [ // chapters in the module
        {
            "id": 42,
            "identifier": 1,
            "order": 1,
            "title": "My First Chapter Qursus",
            "pages": [
                {
                    "id": 588,
                    "identifier": 1,
                    "order": 1,
                    "next_active": "[]",
                    "leaves": [
                        {
                            "id": 1313,
                            "identifier": 1,
                            "order": 1,
                            "visible": "[]",
                            "background_image": "",
                            "background_stretch": false,
                            "background_opacity": 0.5,
                            "contrast": "light",
                            "groups": [
                                {
                                    "id": 2077,
                                    "identifier": 1,
                                    "order": 1,
                                    "direction": "vertical",
                                    "row_span": 3,
                                    "visible": "[]",
                                    "fixed": false,
                                    "widgets": [
                                        {
                                            "id": 2373,
                                            "identifier": 1,
                                            "order": 1,
                                            "content": "<p><strong>Test of a <em>widget<\/em><\/strong><\/p>",
                                            "type": "page_title",
                                            "section_id": null,
                                            "image_url": "",
                                            "video_url": "",
                                            "sound_url": "",
                                            "has_separator_left": false,
                                            "has_separator_right": false,
                                            "align": "none",
                                            "on_click": "ignore",
                                            "state": "instance",
                                            "modified": "2023-12-18T16:10:26+00:00"
                                        }
                                    ],
                                    "state": "instance",
                                    "modified": "2023-12-18T16:10:32+00:00"
                                }
                            ],
                            "state": "instance",
                            "modified": "2023-12-18T16:10:38+00:00"
                        }
                    ],
                    "sections": [],
                    "state": "instance",
                    "modified": "2023-12-18T16:10:42+00:00"
                }
            ],
            "state": "instance",
            "modified": "2023-12-18T16:11:11+00:00"
        }
    ],
    "state": "instance",
    "modified": "2023-12-18T16:11:11+00:00"
}
```


### Chapter:


#### Definition of a Chapter:

A Chapter is a major part in the pack. It contains chapters. A chapter is defined by :
 - identifier | integer | a unique identifier of the chapter
 - order : integer : The position the chapter is in the pack: *example : 1*
 - name : alias of the title
 - title : string : Description of the chapter as presented to user. It is a required field.
 - page_count : computed result type integer: Total amount of pages in the chapter.
 - chapter_count : computed result type integer : Total amount of chapters in the chapter.
 - description : Description of the content of chapter.
 - pages : alias of pages_ids
 - pages_ids : relationship one2many between the chapter and its many pages. When the chapter is deleted so are its pages.
 - module_id : relationship many2one between the chapter and its parent module. Many chapters can be in one module. When the module is deleted so are its chapters.

There are functions for updating the page_count when the pages_ids is updated and/or if module_id is updated.

![Creating a chapter](assets/images/chapter.png)

You can add a page at the same time.

## Page :

### Definition of a page:

A page is a part of a chapter. It can represent a lesson or an exercise for example. It contains leaves and sections. Sections can contain a page which itself can contain other sections and leaves. A page is defined by :
 - identifier : integer : a unique identifier of the page within the chapter.
 - order : integer : The position the page is in the chapter: *example : 5*
 - next_active : computed field result type string :a JSON formatted array of visibility domain for 'next' button.
 - title : string : Description of the page as presented to user. It is a required field.
 - next_active_rule : string : it is select with the following options always visible; page submitted, item selected, 1 or more actions. By default the field is on always visible. On update it changes next_active to null.
 - leaves : alias of leaves_ids .
 - leaves_ids : relationship one2many. A page can have many leaves. A page leaves are deleted when the page is detached.
 - sections : alias of sections_ids.
 - sections_ids : relationship one2many between the page and its many sections. If the page is detached, the sections are deleted.
 - section_id : relationship many2one between the section and its many pages. If the page is deleted, the sections are deleted.
 - chapter_id : relationship many2one between the page and its parent chapter. Many pages can be in one chapter. When the chapter is deleted so are its pages. When the chapter_id is updated, the $page['chapter_id'] is also updated to match the new value.

A page usually contains two leaves. Pages are displayed flex row with one leaf on the left and a second one on the right.

## Leaf

Since you can add actions and conditions on visibility.The second leaf's visibility often results of actions from the user. Those actions are transmitted in the **context**.

 A leaf is defined by :
 - identifier : integer : a unique identifier of the leaf within the page.
 - order : integer : The position the leaf is in the page: *example : 1*
 - visible : computed field result type string, stored: .
 - visibility_rule : string, selection : always visible, selection matched identifier, page submitted or not submitted. When it is updated it changes visible field to null which will trigger calcVisible and the new value for the field visible.
 - groups : alias of groups_ids
 - groups_ids : relation one2many, a leaf can have many groups. When detached the group is deleted.
 - background_image : string : URL of the background image.
 - background_stretch : boolean : false by default
 - background_opacity : float :Opacity of the background (from 0 to 1).
 - contrast : string : selection dark or light for the background of the leaf.
 - page_id : relation many2one between the leaf and the page. Many leaves can be in one page.

![Create Leaf](assets/images/leaf.png)

## Section

 A Section is defined by :
 - identifier : integer : a unique identifier of the section within the page.
 - order : integer : The position the section is in the page: *example : 1*
 - name : computed field result type string, stored: getDisplayName made of the section_id (in the example 65) and the section_identifier (here 1) *example 65-1*.
 - pages : alias of pages_ids
 - pages_ids : relation one2many where one section can contain many pages. If the section is detached from the pages it is deleted.
 - page_id : relation many2one where many sections can be in one page. When the parent page is deleted the sections are deleted.


## Group

A leaf can contain  one or several groups. A group will take 1 to a maximum of 8 rows in the leaf. The group will contain widgets.

A Group is defined by :
 - identifier : integer : a unique identifier of the group within the leaf. When updated, it triggers onupdateVisibility which sets the field visible to null and triggers calcVisible for the field visibility.
 - order : integer : The position the group is on the leaf: *example by default the value is : 1*
 - direction :string : direction of the group can be either vertical or horizontal. By default it is selected vertical.
 - row_span : integer : Height of the group between default 1 and max 8.
 - visible : computed result type string, it is calculated by calcVisible.
 - visibility_rule : string with selection of values. The same as page visibility_rule. It triggers onupdateVisibility which sets the field visible to null and triggers calcVisible.
 - fixed : boolean : If true the group is always visible
 - widgets : alias of widgets_ids
 - widgets_ids : relation one2many. One group can have many widgets.
 - leaf_id : relation many2one. There can be many groups in a leaf.


calcVisible Retrieves data from the Group collection. Then depending on the value set in visibility_rule it will return either [] or a condition that will be implemented as a domain in the view.

You can then see it in the GroupClass::render under visible:[]


![Create a group](assets/images/group.png)


## Widget

A widget is a what you student will see. It can be a text, an excerpt of code, a chapter title, a video, a sound, an image, a selector

![Widget](assets/images/widget.png)


**Example of a Widget**
```json
"widgets": [
    {
        "id": 2373,
        "identifier": 1,
        "order": 1,
        "content": "<p><strong>Test of a <em>widget<\/em><\/strong><\/p>",
        "type": "page_title",
        "section_id": null,
        "image_url": "",
        "video_url": "",
        "sound_url": "",
        "has_separator_left": false,
        "has_separator_right": false,
        "align": "none",
        "on_click": "ignore",
        "state": "instance",
        "modified": "2023-12-18T16:10:26+00:00"
    }
],

```

 - identifier : integer : a unique identifier of the group within the leaf. When updated, it triggers onupdateVisibility which sets the field visible to null and triggers calcVisible for the field visibility.
 - order : integer : The position the widget is in the group: *example by default the value is : 1*
 - content : text/plain :  Content of the widget in text with markdown support
 - group_id : relation many2one many widget can be in one group. When the parent group is deleted, the widgets are deleted.
 - type : string : the type of the widget can be many things in the selection : 'text','code','chapter_number','chapter_title','chapter_description','page_title','headline','subtitle','head_text','tooltip', 'sound','video','image_popup','first_capital','submit_button','selector','selector_wide','selector_yes_no','selector_choice','selector_section','selector_section_wide','selector_popup'.
 when the type is updated it triggers the function onupdateType which will assign the correct onclick function according to the type of the widget.
- section_id : integer : The widget can interact with a section depending on if the widget is of type 'selector_section' or 'selector_section_wide'.
- image_url : string :  The widget may need a url if of type : image_popup', 'selector_popup', 'selector_section', 'selector_section_wide'
- video_url : string :  The widget may need a url if of type 'video'
- sound_url : string :  The widget may need a url if of type 'sound'
- has_separator_left : boolean
- has_separator_right : boolean
- on_click : string : action on the click among the selection : 'ignore','select()','select_one()','submit(),'image_full()','play()',


## UserAccess

This class is responsible for the user access to the app. The idea is that a student user receives a code with a link to connect to Qursus webapp.

It is defined by :
- code : computed result type integer function getCode : a unique identifier used for generating verification url
- code_alpha : computed result type string function getCodeAlpha: retrieve the pack based on verification url code of 4 chars (3 letters + 1 digit).
- pack_id: relation many2one : many users can access one pack
- master_user_id : integer :in case of multi-accounts, external user id.
- user_id : integer : External user identifier that is granted access
- is_complete : computed result type boolean, function getIsComplete : The user has finished the programs modules from the UserStatus the value is_complete is set to true.

### How to generate a User Code ?




## UserStatus


It is defined by :
- pack_id : relation many2one, many UserStatus can be in one pack. It is used to determine the completeness of a pack.
- module_id : relation many2one, many UserStatus can be in one Module.
- user_id : integer : External user identifier that is granted access
- chapter_index : integer : chapter index/identifier within the module
- page_index : integer : page index/identifier within the module
- page_count : integer : number of pages reviewed so far by the user.
- is_complete : boolean : true if the user has finished the module.

It has two methods : getUnique that returns ['module_id','user_id'] and onupdate is complete which will set is_complete to true if the user has completed all modules.




## Context : 

Group : Context : 
```json

{
    "context":{"actions_counter":0},
    "mode":"view"
}
```









Check the typescript syntax (lint):

`yarn run tsc`

Use babel to transpile .ts file into .js :

`npm run build`

Generate an app.bundle.js that can be embedded to any .html file:

`npm run webpack`# Building an ERP that suits you



## Symbiose suite of apps for eQual

Symbiose is a set of Business Applications components intended for Agile organizations, made to fit the needs of any business logic in an ever-changing marketplace.
It is a collection of packages designed to operate within the eQual framework, defining business entities by assembling models, views, controllers, and the related applicative logic.

Its highly customizable back-end logic and its versatile user interface configuration allow fast development of tailor-made applications on any type of device.



### Installation

#### Prerequisite

Symbiose requires [eQual framework](https://github.com/equalframework/equal).

#### Setup

This is the dev-2.0 version.

Under the root directory of your eQual installation, run the following command:

```bash
curl https://raw.githubusercontent.com/yesbabylon/symbiose/dev-2.0/install.sh
```

Note: The source of that script is available here : [install.sh](https://github.com/yesbabylon/symbiose/blob/dev-2.0/install.sh)