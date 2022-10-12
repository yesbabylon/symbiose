/*
    This file is part of Symbiose Community Edition <https://github.com/yesbabylon/symbiose>
    Some Rights Reserved, Yesbabylon SRL, 2020-2021
    Licensed under GNU AGPL 3 license <http://www.gnu.org/licenses/>
*/

/*

    # TreeComponents

    For components requiring cascade updates (bottom-up and/or top-down), we use specific components :
    * TreeComponent
    * RootTreeComponent (a RootTreeComponent is also a TreeComponent)


    The root node implements `RootTreeComponent` having a `load(id: number)` method that calls a dedicated controller from the back-end ".../tree.php" which returns a single JSON object with the full tree (root object and children).
    The tree is loaded upon the initialisation of the RootTreeComponent, afterward it is re-loaded each time the RootController receives a change notification from its children.

         *
        / \
       *   *
      / \
     *   *

    Each TreeComponent is a (partially) loaded object: either the root or a relational field.
    The fields returned by the server-model should match the view-model, defined in a TS class defined this way:
    ```
    export class Item {
        // index signature
        [key: string]: any;
        // model entity
        public get entity():string { return 'package\\path\\Item'};
        // constructor with public properties
        constructor(
            public id: number = 0,
            public created: Date = new Date(),
            public name: string = '',
            public parent_id: any = 0,
            public sub_items_ids: any[] = []
        ) {}
    }
    ```

    In case an update is made within a TreeComponent (node), it (the component) is in charge of:
        * updating the server-model by sending a request to the back-end
        * relaying the event to its parent (trough `@output emit()`) (optionally this can be skipped is no change in display is expected)
        * if the TreeComponent is the RootTreeComponent, it reloads the tree, using the load() method.

    When the RootTreeComponent receives the response from the back-end, it passes the received object to its `update()` method.
    The `update()` method is defined in the TreeComponent class (and is therefore common to all TreeComponent components), and can be overloaded by any TreeComponent.


    TreeComponents must implement a `model` @input property, defined this way : ```@Input() set model(values: any) { this.update(values) }```
    In addition TreeComponents can implement two @output properties : `updated`and `deleted`

    When a TreeComponent receives a new model (from its `update()` method), it performs the following processing:
        * simple fields are updated (which triggers the update of the bound widgets components)
        * relational fields are processed one by one:
            a) if an ID is present in the View-model but not in the Server-model, it is withdrawn from the view (deleted)
            b) if an ID is present in the Server-model but not in the View-model, it is added to the view (created)
            c) if an ID is present in both Server-model and View-model, the subtree is passed to the `update()` method of th related TreeComponent

    A TreeComponent that has sub-items, should implement a componentsMap for mapping relational fields with related sub-components.


    Example:
    ```
    interface ItemComponentsMap {
        sub_items_ids: QueryList<SubItemComponent>
    }


    export class ItemComponent extends TreeComponent<Item, ItemOrderComponentsMap> implements OnInit, AfterViewInit {

        @Input() set model(values: any) { this.update(values) }
        @Output() updated = new EventEmitter();
        @Output() deleted = new EventEmitter();

        @ViewChildren(SubItemComponent) SubItemComponents: QueryList<SubItemComponent>;

        constructor(
            private cd: ChangeDetectorRef
        ) {
            super( new Item() );
        }

        public ngAfterViewInit() {
            // init local componentsMap
            let map:ItemOrderComponentsMap = {
                sub_items_ids: this.SubItemComponents
            };
            this.componentsMap = map;
        }

        public update(values:any) {
            super.update(values);
        }

        public async ondelete() {
            await this.api.update((new ParentItem()).entity, [this.instance.parent_id], {items_ids: [-this.instance.id]});
            this.deleted.emit();
        }

        public async onchange() {
            this.updated.emit();
        }

    }
    ```

    !!! Important note
        Keep in mind that children are handled as DOM objects and are therfore only available if present in the DOM.
        This means that, when dealing with Tree components, it is preferable to hide elements rather than condition their rendering (ngIf).
*/

/*
*   TreeComponents types defitions.
*/

export interface TreeComponentInterface {
    /**
     * Update instance with raw objet.
     * A tree component is in charge of updating itself (and sub-components, if necessary).
     */
    update(values: any): void;
    /**
     * Return instance identifier.
     */
    getId(): number;
    /**
     * Model instance of the tree node.
     */
    instance: any;
}

export interface RootTreeComponent extends TreeComponentInterface {
    /**
     * RootTreeComponent must have a `load()` method.
     * Load the instance of the node, according to the Type of the TreeComponent.
     * @param id
     */
    load(id: number): void;
}

interface ComponentsMap<T> {
    // index signature
    [key: string]: any;
};

export class TreeComponent<I, T> implements TreeComponentInterface {
    // map for associating relational Model fields with their components
    protected componentsMap: ComponentsMap<T>;
    // root object of the tree Model
    public instance: any;
    // expose instance id to children components
    public getId(): number { return this.instance.id }
    // specific constructor, to be invoked as `super(new <I>())` in inherited Components
    constructor(instance: I) {
        this.instance = instance;
    }

    /**
     * Update local-model from raw object, and relay to sub-components, if any.
     */
    public update(values:any) {
        for(let field of Object.keys(this.instance)) {
            if(values.hasOwnProperty(field)) {
                // update local-model for simple fields
                if(!Array.isArray(values[field])) {
                    // handle dates
                    if(this.instance[field] instanceof Date) {
                        this.instance[field] = new Date(values[field]);
                    }
                    else {
                        // handle empty m2o fields
                        if(typeof this.instance[field] == 'object' && values[field] == null) {
                            this.instance[field] = {};
                        }
                        else {
                            this.instance[field] = values[field];
                        }
                    }
                }
                // update sub-objects of relational fields and relay to children
                else {
                    // pass-1 - remove items not present anymore
                    // check items in local-model against server-model
                    if(this.instance[field].length) {
                        // empty array
                        if(values[field].length == 0) {
                            this.instance[field] = [];
                        }
                        // existing array or object
                        else {
                            for(let i = this.instance[field].length-1; i >= 0; --i) {
                                let line = this.instance[field][i];
                                const found = values[field].find( (item:any) => item.id == line.id);
                                // line not in server-model
                                if(!found) {
                                    // remove line from local-model
                                    this.instance[field].splice(i, 1);
                                }
                            }
                        }
                    }
                    // empty array for an objet means empty object
                    else if(!values[field].length && typeof this.instance[field] == 'object' && !Array.isArray(this.instance[field])) {
                        this.instance[field] = {};
                    }
                    // pass-2 - add missing items
                    // check items in server-model against local-model (can only be an array)
                    for(let i = 0; i < values[field].length; ++i) {
                        let value = values[field][i];
                        const found = this.instance[field].find( (item:any) => item.id == value.id);
                        // item not in local-model
                        if(!found) {
                            // add item to local-model
                            this.instance[field].splice(i, 0, value);
                        }
                        // item is already in local-model: relay sub-object
                        else if(this.componentsMap.hasOwnProperty(field)) {
                            // #memo - no update of the instance's field here, to prevent refreshing the view
                            // relay to child object
                            const subitem:any = this.componentsMap[field].find( (item:any) => item.getId() == found.id);
                            if(subitem) {
                                subitem.update(value);
                            }
                            else {
                                // subitem not in DOM (did we use ngIf somewhere ?)
                            }
                        }
                    }
                }
            }
        }
    }
}