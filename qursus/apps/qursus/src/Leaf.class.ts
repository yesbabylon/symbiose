import { $ } from "./jquery-lib";
import { GroupClass } from "./Group.class";
import { DomainClass } from "./Domain.class";
import { ApiService } from "./qursus-services";

import PageClass from "./Page.class";
import SectionClass from "./Section.class";


/**
 *
 */
export class LeafClass {
    // allow virtual keys for dynamic assignment after API updates (we make sure to only use keys defined below)
    [key: string]: any;

    public id: number;
    public identifier: number;
    public order: number;
    public groups: GroupClass[];
    public visible: DomainClass[];          // (ex. [ '$submitted', ''=',' true ] ; [ '$selected', ''=', 2])
    public background_image: string;        // image URL
    public background_opacity: number;      // float
    public background_stretch: boolean;
    public contrast: string;                 //'light', 'dark' (text color)

    private $container: JQuery;
    private parent: PageClass;

    // leaf-specific context
    private context: any = {
        actions_counter: 0,
        mode: 'view'
    };

    constructor(
        id: number = 0,
        identifier: number = 1,
        order: number = 1,
        groups: GroupClass[] = [],
        visible: DomainClass[],          // (ex. [ '$submitted', ''=',' true ] ; [ '$selected', ''=', 2])
        background_image: string,        // image URL
        background_opacity: number,      // float
        background_stretch: boolean,
        contrast: string                 //'light', 'dark' (text color)
    ) {

        this.id = id;
        this.identifier = identifier;
        this.groups = groups;
        this.visible = visible;
        this.background_image = background_image;
        this.background_opacity = background_opacity;
        this.background_stretch = background_stretch;
        this.contrast = contrast;

        if(this.visible && !Array.isArray(this.visible)) {
            this.visible = JSON.parse((<string>this.visible).replace(/'/g, '"'));
        }

        this.$container = $('<div class="leaf-container" />');
    }

    public setParent(page: PageClass) {
        this.parent = page;
    }

    public getParent() {
        return this.parent;
    }

    public getContainer() {
        return this.$container;
    }

    public setContext(context:any) {
        for(let key of Object.keys(this.context)) {
            if(context.hasOwnProperty(key)) {
                this.context[key] = context[key];
            }
        }
    }

    public render(is_single:boolean = false, context:any) {
        console.log("LeafClass::render", this, context);

//        this.setContext(context);

        if(is_single) {
            this.$container.addClass('full');
        }

        let leaf_classes = 'leaf widget';

        let background_classes = 'bg-image';
        let background_opacity = 0.4;
        let background_img_url = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";


        if(this.background_image) {
            background_img_url = this.background_image;
        }
        if(this.background_opacity) {
            background_opacity = this.background_opacity;
        }
        if(this.background_stretch) {
            background_classes += ' stretch';
        }

        let contrast = (this.contrast == 'light')?'light':'dark';
        leaf_classes += ' contrast_'+contrast;

        let $leaf = $('<div />')
        .addClass(leaf_classes)
        // background
        .append($("<div style=\"background-image: url("+background_img_url+"); opacity: "+background_opacity+";\"></div>").addClass(background_classes))


        let total_span = 0;
        let total_slides = 1;

        if(this.groups && this.groups.length) {
            // first pass : create groups objects
            for(let index in this.groups) {

                let item = this.groups[index];

                let group:GroupClass = new GroupClass(
                    item.id,
                    item.identifier,
                    item.order,
                    item.direction,
                    item.row_span,
                    item.fixed,
                    item.widgets,
                    item.visible
                );

                total_span += group.row_span;

                this.groups[index] = group;
                group.setParent(this);

                if(context.mode == 'edit') {
                    group.setContext({mode: 'edit'});
                }
            }

            total_slides = Math.ceil(total_span / 8);

            // second pass : render depending on row_span
            let remaining_spans = 8;
            for(let index in this.groups) {
                let group = this.groups[index];

                let spans = group.row_span;
                let $groupContainer = group.render(context);
                if(remaining_spans - spans < 0) {
                    // hide group
                    $groupContainer.hide();
                }
                remaining_spans -= spans;
                $leaf.append($groupContainer);
            }
        }

        if(this.parent.getParent() instanceof SectionClass) {
            $('body').find('.section-nav-down').show();
        }

        // display paginator for slides
        if(total_slides > 1) {

            if(this.parent.getParent() instanceof SectionClass) {
                $('body').find('.section-nav-down').hide();
            }

            let $paginator = $('<div class="slide-paginator"></div>');
            let $slidePrev = $('<div class="slide_nav_prev"></div>');
            let $slideNext = $('<div class="slide_nav_next"></div>');
            $paginator.data('slide', 0);

            $paginator.append($slidePrev)
            for(let i = 0; i < total_slides; ++i) {
                let $bullet = $('<div class="bullet">&bull;</div>');
                $bullet.data('slide', i);
                if(i == 0) {
                    $bullet.addClass('active');
                }
                $paginator.append($bullet);
            }
            $paginator.append($slideNext);

            $slidePrev.on('click', () => {
                let slide = $paginator.data('slide');
                if(slide-1 >= 0) {
                    // new slide index
                    let target_index = slide-1;
                    // spans in new slide (building up)
                    let new_spans_count = 0;
                    // fixed spans (independant)
                    let fixed_spans_count = 0;
                    // spans parsed so far in n-th slide
                    let tmp_spans_count = 0;
                    // n-th slide
                    let current_index = 0;

                    for(let index in this.groups) {
                        let group = this.groups[index];

                        // fixed groups are always displayed (if possible)
                        if(group.fixed) {
                            if(new_spans_count+group.row_span <= 8) {
                                // display group
                                group.getContainer().show();
                                console.log('showing fixed', tmp_spans_count, fixed_spans_count, new_spans_count);
                                fixed_spans_count += group.row_span;                                
                                new_spans_count += group.row_span;
                                tmp_spans_count += group.row_span;
                                continue;
                            }
                            else {
                                // ignore group and stop (no space left)
                                group.getContainer().hide();
                                break;
                            }
                        }

                        if(tmp_spans_count + group.row_span > 8) {
                            if(current_index > target_index) {
                                break;
                            }
                            tmp_spans_count = fixed_spans_count;
                            ++current_index;
                        }

                        // check that we're on the requested slide
                        if(current_index >= target_index) {
                            if(new_spans_count+group.row_span <= 8) {
                                group.getContainer().show();
                                console.log('showing regular', tmp_spans_count, fixed_spans_count, new_spans_count);
                                new_spans_count += group.row_span;
                                tmp_spans_count += group.row_span;
                            }
                            else {
                                for(let i = parseInt(index); i < this.groups.length; ++i) {
                                    this.groups[i].getContainer().hide();
                                }
                                break;
                            }

                        }
                        else {
                            group.getContainer().hide();
                            tmp_spans_count += group.row_span;
                        }
                    }
                    $paginator.data('slide', target_index);

                    $paginator.find('.bullet').each( (index: number, elem: HTMLElement) => {
                        let $bullet = $(elem);
                        if($bullet.data('slide') == $paginator.data('slide')) {
                            $bullet.addClass('active');
                        }
                        else {
                            $bullet.removeClass('active');
                        }
                    });
                }
            });

            $slideNext.on('click', () => {
                let slide = $paginator.data('slide');
                if(slide+1 == total_slides-1) {
                    // display next down button
                    if(this.parent.getParent() instanceof SectionClass) {
                        $('body').find('.section-nav-down').show();
                    }
                    else {
                        this.propagateContextChange({'$page.actions_counter': 1});
                    }
                }

                if(slide+1 < total_slides) {
                    // new slide index
                    let target_index = slide+1;
                    // spans in new slide (building up)
                    let new_spans_count = 0;
                    // fixed spans (independant)
                    let fixed_spans_count = 0;
                    // spans parsed so far in n-th slide
                    let tmp_spans_count = 0;
                    // n-th slide
                    let current_index = 0;

                    for(let index in this.groups) {
                        let group = this.groups[index];

                        // fixed groups are always displayed (if possible)
                        if(group.fixed) {
                            if(new_spans_count+group.row_span <= 8) {
                                // display group
                                group.getContainer().show();
                                fixed_spans_count += group.row_span;                                
                                new_spans_count += group.row_span;
                                tmp_spans_count += group.row_span;
                                continue;
                            }
                            else {
                                // ignore group and stop (no space left)
                                group.getContainer().hide();
                                break;
                            }
                        }

                        if(tmp_spans_count + group.row_span > 8) {
                            if(current_index > target_index) {
                                break;
                            }
                            tmp_spans_count = fixed_spans_count;
                            ++current_index;
                        }

                        // check that we're on the requested slide
                        if(current_index >= target_index) {
                            if(new_spans_count+group.row_span <= 8) {
                                group.getContainer().show();
                                new_spans_count += group.row_span;
                                tmp_spans_count += group.row_span;
                            }
                            else {
                                for(let i = parseInt(index); i < this.groups.length; ++i) {
                                    this.groups[i].getContainer().hide();
                                }
                                break;
                            }
                        }
                        else {
                            group.getContainer().hide();
                            tmp_spans_count += group.row_span;
                        }
                    }
                    $paginator.data('slide', target_index);

                    $paginator.find('.bullet').each( (index: number, elem: HTMLElement) => {
                        let $bullet = $(elem);
                        if($bullet.data('slide') == $paginator.data('slide')) {
                            $bullet.addClass('active');
                        }
                        else {
                            $bullet.removeClass('active');
                        }
                    });
                }
            });

            $leaf.append($paginator);
        }

        if(context.mode == 'view') {
            if(this.visible && this.visible.length) {
                let visible = true;

                let domain = new DomainClass(this.visible);
                visible = domain.evaluate(context) ;

                if(!visible) {
                    $leaf.addClass('hidden');
                }

            }
        }
        else {
            let $actions = $('<div class="actions leaf-actions"></div>');

            let $edit_button = $('<div class="action-button leaf-edit-button" title="Edit Leaf"><span class="material-icons mdc-fab__icon">mode_edit</span></div>');
            $edit_button.on('click', () => {
                window.eq.popup({entity: 'qursus\\Leaf', type: 'form', mode: 'edit', domain: ['id', '=', this.id], callback: (data:any) => {
                    if(data && data.objects) {
                        for(let object of data.objects) {
                            if(object.id != this.id) continue;
                            for(let field of Object.keys(this)) {
                                if(object.hasOwnProperty(field)) {
                                    let value = (this[field])?((this[field].constructor)(object[field])):object[field];
                                    this[field] = value;
                                }
                            }
                        }
                        this.propagateContextChange({refresh: true});
                    }
                }});
            });
            let $add_button = $('<div class="action-button leaf-add-button" title="Add a Group"><span class="material-icons mdc-fab__icon">add</span></div>');
            $add_button.on('click', () => {
                let group_identifier = (this.groups)?this.groups.length+1:1;
                window.eq.popup({entity: 'qursus\\Group', type: 'form', name: 'create', mode: 'edit', purpose: 'create', domain: [['leaf_id', '=', this.id], ['identifier', '=', group_identifier], ['order', '=', group_identifier]], callback: (data:any) => {
                    // append new page to chapter
                    if(data && data.objects) {
                        for(let item of data.objects) {
                            let group = new GroupClass(
                                Number(item.id),
                                Number(item.identifier),
                                Number(item.order),
                                item.direction,
                                Number(item.row_span),
                                item.fixed,
                                item.widgets,
                                item.visible
                            );
                            if(!this.groups) {
                                this.groups = new Array<GroupClass>();
                            }
                            this.groups.push(group);
                        }
                        this.propagateContextChange({refresh: true});
                    }
                }});
            });
            let $delete_button = $('<div class="action-button leaf-delete-button" title="Delete Leaf"><span class="material-icons mdc-fab__icon">delete</span></div>');
            $delete_button.on('click', () => {
                if (window.confirm("Leaf is about to be removed. Do you confirm ?")) {
                    ApiService.delete('qursus\\Leaf', [this.id], true);
                    this.parent.propagateContextChange({'$page.remove_leaf': this.id, refresh: true});
                }
            });

            $actions.append($edit_button);
            $actions.append($add_button);
            $actions.append($delete_button);

            this.$container.append($actions);
        }


        this.$container.append($leaf);

        return this.$container;
    }

    public propagateContextChange(contextChange:any) {
        console.log('Leaf::propagateContext', contextChange);

        for(let elem of Object.keys(contextChange)) {
            if(elem.indexOf('$leaf') == 0) {
                let value = contextChange[elem];
                const parts = elem.split('.');
                if(parts.length > 1) {
                    if(parts[1] == 'actions_counter') {
                        ++this.context[parts[1]];
                        // relay counter to parent leaf
                        contextChange['$page.actions_counter'] = value;
                    }
                    else if(parts[1] == 'remove_group' && this.groups && this.groups.length) {
                        // value is widget id
                        for(const [index, group] of this.groups.entries()) {
                            if(group.id == value) {
                                this.groups.splice(index, 1);
                            }
                        }
                    }
                    else if(parts[1] == 'move_group_up' && this.groups && this.groups.length) {
                        let group_index = 0;
                        for(const [index, group] of this.groups.entries()) {
                            if(group.id == value) {
                                group_index = index;
                                break;
                            }
                        }
                        if(group_index > 0) {
                            let temp = this.groups[group_index-1];
                            this.groups[group_index-1] = this.groups[group_index];
                            this.groups[group_index] = temp;
                            ApiService.update('qursus\\Group', [this.groups[group_index-1].id], {'order': this.groups[group_index].order}, true);
                            ApiService.update('qursus\\Group', [this.groups[group_index].id], {'order': this.groups[group_index-1].order}, true);
                        }
                    }
                    else if(parts[1] == 'move_group_down' && this.groups && this.groups.length) {
                        let group_index = 0;
                        for(const [index, group] of this.groups.entries()) {
                            if(group.id == value) {
                                group_index = index;
                                break;
                            }
                        }
                        if(group_index < this.groups.length-1) {
                            let temp = this.groups[group_index+1];
                            this.groups[group_index+1] = this.groups[group_index];
                            this.groups[group_index] = temp;
                            ApiService.update('qursus\\Group', [this.groups[group_index+1].id], {'order': this.groups[group_index].order}, true);
                            ApiService.update('qursus\\Group', [this.groups[group_index].id], {'order': this.groups[group_index+1].order}, true);
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

    public onContextChange(contextChange:any) {
        console.log('Leaf::onContextChange', this, contextChange);

        for(let elem of Object.keys(contextChange)) {
            if(elem.indexOf('$page.mode') == 0) {
                this.context.mode = contextChange[elem];
            }
        }

        let visible = true;

        if(this.visible){
            let domain = new DomainClass(this.visible);
            visible = domain.evaluate(contextChange) ;
        }

        if(!visible && this.context.mode == 'view') {
            this.$container.find('.leaf').addClass('hidden');
        }
        else {
            this.$container.find('.leaf').removeClass('hidden');

            // update contextChange object with instance context
            for(let elem of Object.keys(this.context)) {
                contextChange['$leaf.'+elem] = this.context[elem];
            }

            if(this.groups && this.groups.length) {
                for(let group of this.groups) {
                    if(typeof group.onContextChange === 'function' ) {
                        group.onContextChange(contextChange);
                    }
                }
            }
        }
    }

}

export default LeafClass;