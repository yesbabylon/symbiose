import { $ } from "./jquery-lib";
import { WidgetClass } from "./Widget.class";
import { DomainClass } from "./Domain.class";
import { ApiService } from "./qursus-services";

import LeafClass from "./Leaf.class";


/**
 *
 */
export class GroupClass {
    // allow virtual keys for dynamic assignment after API updates (we make sure to only use keys defined below)
    [key: string]: any;

    public id: number;
    public identifier: number;
    public order: number;
    public direction: string;                  // 'horizontal' (widgets are displayed inline), 'vertical' (widgets stacked one on the other)
    public row_span: number;                   // (default = 1, max = 6)
    public fixed: boolean;                     // (if true, must remain always visible)
    public widgets: WidgetClass[];
    public visible: [];

    private $container;

    private parent: LeafClass = null;

    // group-specific context
    private context: any = {
        actions_counter: 0,
        mode: 'view'
    };

    constructor(
        id: number,
        identifier: number,
        order: number,
        direction: string,                  // 'horizontal' (widgets are displayed inline), 'vertical' (widgets stacked one on the other)
        row_span: number,                   // (default = 1, max = 6)
        fixed: boolean,                     // (if true, must remain always visible)
        widgets: WidgetClass[],
        visible: []                         // domain (ex. [ '$page.selection', '=', 2 ])
    ) {
        this.id = id;
        this.identifier = identifier;
        this.order = order;
        this.direction = direction;
        this.row_span = row_span;
        this.fixed = fixed;
        this.widgets = widgets;
        this.visible = visible;

        if(this.visible && !Array.isArray(this.visible)) {
            this.visible = JSON.parse((<string>this.visible).replace(/'/g, '"'));
        }

        this.$container = $('<div />');
    }

    public getContext() {
        return this.context;
    }

    public getContainer() {
        return this.$container;
    }

    public setParent(leaf: LeafClass) {
        this.parent = leaf;
    }

    public getParent() {
        return this.parent;
    }

    public setContext(context:any) {
        for(let key of Object.keys(this.context)) {
            if(context.hasOwnProperty(key)) {
                this.context[key] = context[key];
            }
        }
    }

    public render(context:any) {
        console.log("GroupClass::render", this);

        // this.setContext(context);

        let group_classes = 'group ';
        let row_span = 1;
        let direction = 'vertical';
        if(this.row_span) {
            row_span = this.row_span;
        }
        if(this.direction) {
            direction = this.direction;
        }

        group_classes += ' row_span'+row_span;
        group_classes += ' '+direction;

        // handle horizontal widget layouts (depending on the amount of widgets)
        if(direction == 'horizontal' && this.widgets) {
            if(this.widgets.length >= 3) {
                if(this.widgets.length <= 4) {
                    group_classes += " justify";
                }
                // if >= 5
                else {
                    group_classes += " line-break";
                }
            }
        }

        this.$container.addClass(group_classes);

        for(let index in this.widgets) {

            let item = this.widgets[index];

            let widget:WidgetClass = new WidgetClass(
                item.id,
                item.identifier,
                item.order,
                item.type,
                item.has_separator_left,
                item.has_separator_right,
                item.align,
                item.on_click,
                item.section_id,
                item.content,
                item.sound_url,
                item.video_url,
                item.image_url
            );

            this.widgets[index] = widget;
            widget.setParent(this);

            this.$container.append(widget.render(context));
        }

        if(context.mode == 'view') {
            if(this.visible && this.visible.length) {
                this.$container.addClass('hidden');
            }
        }
        else {
            let $actions = $('<div class="actions group-actions"></div>');

            let $edit_button = $('<div class="action-button group-edit-button" title="Edit Group"><span class="material-icons mdc-fab__icon">mode_edit</span></div>');
            let $add_button = $('<div class="action-button group-add-button" title="Add a Widget"><span class="material-icons mdc-fab__icon">add</span></div>');
            let $move_up_button = $('<div class="action-button group-add-button" title="Move Group up"><span class="material-icons mdc-fab__icon">keyboard_arrow_up</span></div>');
            let $move_down_button = $('<div class="action-button group-add-button" title="Move Group down"><span class="material-icons mdc-fab__icon">keyboard_arrow_down</span></div>');
            let $delete_button = $('<div class="action-button group-delete-button" title="Delete Group"><span class="material-icons mdc-fab__icon">delete</span></div>');

            $actions.append($edit_button).append($add_button).append($move_up_button).append($move_down_button).append($delete_button);

            $edit_button.on('click', () => {
                window.eq.popup({entity: 'qursus\\Group', type: 'form', mode: 'edit', domain: ['id', '=', this.id], callback: (data:any) => {
                    if(data && data.objects) {
                        for(let object of data.objects) {
                            if(object.id != this.id) continue;
                            for(let field of Object.keys(this)) {
                                if(object.hasOwnProperty(field)) {
                                    this[field] = (this[field].constructor)(object[field]);
                                }
                            }
                        }
                        this.propagateContextChange({refresh: true});
                    }
                }});
            });

            $add_button.on('click', () => {
                let widget_identifier = (this.widgets)?this.widgets.length+1:1;
                window.eq.popup({entity: 'qursus\\Widget', type: 'form', name: 'create', mode: 'edit', purpose: 'create', domain: [['group_id', '=', this.id], ['identifier', '=', widget_identifier], ['order', '=', widget_identifier]], callback: (data:any) => {
                    // append new widget to group
                    if(data && data.objects) {
                        for(let item of data.objects) {
                            let widget = new WidgetClass(
                                Number(item.id),
                                Number(item.identifier),
                                Number(item.order),
                                item.type,
                                item.has_separator_left,
                                item.has_separator_right,
                                item.align,
                                item.on_click,
                                item.section_id,
                                item.content,
                                item.sound_url,
                                item.video_url,
                                item.image_url
                            );
                            if(!this.widgets) {
                                this.widgets = new Array<WidgetClass>();
                            }
                            this.widgets.push(widget);
                        }
                        this.propagateContextChange({refresh: true});
                    }
                }});
            });

            $move_up_button.on('click', () => {
                this.parent.propagateContextChange({'$leaf.move_group_up': this.id, refresh: true});
            });

            $move_down_button.on('click', () => {
                this.parent.propagateContextChange({'$leaf.move_group_down': this.id, refresh: true});
            });

            $delete_button.on('click', () => {
                if (window.confirm("Group is about to be removed. Do you confirm ?")) {
                    ApiService.delete('qursus\\Group', [this.id], true);
                    this.parent.propagateContextChange({'$leaf.remove_group': this.id, refresh: true});
                }
            });

            this.$container.append($actions);
        }


        return this.$container;
    }


    public propagateContextChange(contextChange:any) {
        console.log('Group::propagateContext', this, contextChange);

        for(let elem of Object.keys(contextChange)) {
            if(elem.indexOf('$group') == 0) {
                let value = contextChange[elem];
                const parts = elem.split('.');
                if(parts.length > 1) {
                    if(parts[1] == 'actions_counter') {
                        ++this.context[parts[1]];
                        // relay counter to parent leaf
                        contextChange['$leaf.actions_counter'] = value;
                    }
                    else if(parts[1] == 'remove_widget') {
                        // value is widget id
                        for(const [index, widget] of this.widgets.entries()) {
                            if(widget.id == value) {
                                this.widgets.splice(index, 1);
                            }
                        }
                    }
                    else {
                        this.context[parts[1]] = value;
                    }
                }
                delete contextChange[elem];
            }
        }

        this.parent.propagateContextChange(contextChange);
    }

    /**
     * Changes received from parent or self
     * @param contextChange
     */
    public onContextChange(contextChange:any) {
        console.log('Group::onContextChange', contextChange);

        for(let elem of Object.keys(contextChange)) {
            if(elem.indexOf('$leaf.mode') == 0) {
                this.context.mode = contextChange[elem];
            }
        }

        let visible = true;

        if(this.visible && Array.isArray(this.visible)) {
            let domain = new DomainClass(this.visible);
            visible = domain.evaluate(contextChange) ;
        }

        if(!visible && this.context.mode == 'view') {
            this.$container.addClass('hidden');
        }
        else {
            this.$container.removeClass('hidden');

            // update contextChange object with instance context
            for(let elem of Object.keys(this.context)) {
                contextChange['$group.'+elem] = this.context[elem];
            }
            if(this.widgets) {
                for(let widget of this.widgets) {
                    if(typeof widget.onContextChange === 'function') {
                        widget.onContextChange(contextChange);
                    }
                }
            }

        }
    }
}

export default GroupClass;