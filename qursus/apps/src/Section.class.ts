import { $ } from "./jquery-lib";
import { PageClass } from "./Page.class";
import { ApiService } from "./qursus-services";


/**
 * Frames handle a stack of contexts. They're in charge of their header.
 *
 */
export class SectionClass {
    // allow virtual keys for dynamic assignment after API updates (we make sure to only use keys defined below)
    [key: string]: any;

    // section-specific context
    private context: any = {
        actions_counter: 0,
        selection: 0,
        finished: false,
        mode: 'view'
    };

    public id: number;
    public identifier: number;
    public order: number;
    public pages: PageClass[];

    private $container:JQuery;
    private parent: PageClass;


    constructor(
        id: number,
        identifier: number,
        order: number,
        pages: PageClass[]
    ) {
        this.id = id;
        this.identifier = identifier;
        this.order = order;
        this.pages = pages;

        this.$container = $('<div class="section-container viewport-container"></div>');
    }

    public setParent(page: PageClass) {
        this.parent = page;
    }

    public getParent() {
        return this.parent;
    }

    public getContainer(page_id:number) {
        return $('body').find('.section-page-container.viewport-container.page-id_'+page_id);
    }

    public setContext(context:any) {
        for(let key of Object.keys(this.context)) {
            if(context.hasOwnProperty(key)) {
                this.context[key] = context[key];
            }
        }
    }

    public render(context:any) {
        console.log("sectionClass::render", this);

        // remove any previously rendered section
        $('body').find('.section-container').remove();
        $('body').find('.section-page-container.viewport-container').remove();

        if(this.pages && this.pages.length) {
            for(let index in this.pages) {

                let item = this.pages[index];
    
                let page:PageClass = new PageClass(
                    item.id,
                    item.identifier,
                    item.order,
                    item.leaves,
                    item.type,
                    item.next_active,
                    item.sections
                );

                this.pages[index] = page;
                page.setParent(this);

                if(context.mode == 'edit') {
                    page.setContext({mode: 'edit'});
                }
    
                let $container = $('<div class="section-page-container viewport-container page-id_'+page.id+'"></div>');
    
                if(context.mode == 'view') {
                    $container.css("transform", "translateY(" + ((parseInt(index)+1)*100) + "vh)").data('vpos', (parseInt(index)+1)*100);
                }
                else if(context.mode == 'edit') {
                    $container.addClass('_edit');
                    $container.css("transform", "translateY(" + ((parseInt(index)+1)*700) + "px)");
                }
    
                $('body').append($container);
    
                $container.append(page.render(context));
            }
    
            // update section nav button to store the count of avalable pages
            $('body').find('.section-nav.section-nav-down').data('pages-count', this.pages.length).data('page-index', 0);
        }



        if(context.mode == 'edit') {

            let $container = $('body').find('.section-page-container.viewport-container').first();

            if($container.length == 0) {
                this.$container.addClass('_edit');
                $('body').append(this.$container);
                $container = this.$container;
            }

            /*
             append controls to section (virtual) container (i.e. first sub page container)
            */

             let $section_controls = $('<div class="controls section-controls"><div class="label">Section '+this.identifier+'</div></div>');
             let $section_actions = $('<div class="actions section-actions"></div>');
             let $edit_button = $('<div class="action-button section-edit-button"><span class="material-icons mdc-fab__icon">mode_edit</span></div>');
             let $add_button = $('<div class="action-button section-add-button"><span class="material-icons mdc-fab__icon">add</span></div>');
             let $delete_button = $('<div class="action-button widget-delete-button"><span class="material-icons mdc-fab__icon">delete</span></div>');

             $container.addClass('section-id_'+this.id);
             $section_actions.append($edit_button).append($add_button).append($delete_button);
             $section_controls.append($section_actions);
             $section_controls.appendTo($container);

             $edit_button.on('click', () => {
                window.eq.popup({entity: 'qursus\\Section', type: 'form', mode: 'edit', domain: ['id', '=', this.id], callback: (data:any) => {
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

            $add_button.on('click', () => {
                let page_identifier = (this.pages)?this.pages.length+1:1;
                window.eq.popup({entity: 'qursus\\Page', type: 'form', mode: 'edit', purpose: 'create', domain: [['section_id', '=', this.id], ['identifier', '=', page_identifier], ['order', '=', page_identifier]], callback: (data:any) => {
                    // append new page to section
                    if(data && data.objects) {
                        for(let item of data.objects) {
                            let page = new PageClass(
                                item.id,
                                item.identifier,
                                item.order,
                                item.leaves,
                                item.type,
                                item.next_active,
                                item.sections
                            );
                            if(!this.pages) {
                                this.pages = new Array<PageClass>();
                            }
                            this.pages.push(page);
                        }
                        this.propagateContextChange({refresh: true});

                        this.parent.openSection(this.identifier, context);
                    }
                }});
            });

            $delete_button.on('click', () => {
                if (window.confirm("Section is about to be removed. Do you confirm ?")) {
                    ApiService.delete('qursus\\Section', [this.id], true);
                    $('body').find('.section-page-container.section-id_'+this.id).remove();
                    this.parent.propagateContextChange({'$page.remove_section': this.id, refresh: true});
                }
            });

        }

        return this.$container;
    }

    public propagateContextChange(contextChange:any) {
        console.log('Section::propagateContext', this, contextChange);

        for(let elem of Object.keys(contextChange)) {
            if(elem.indexOf('$section') == 0) {
                let value = contextChange[elem];
                const parts = elem.split('.');
                if(parts.length > 1) {
                    if(parts[1] == 'actions_counter') {
                        ++this.context[parts[1]];
                        // relay counter to parent page
                        contextChange['$page.actions_counter'] = value;
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
     * Changes received from parent
     * @param contextChange
     */
     public onContextChange(contextChange:any) {
        console.log('Section::onContextChange', contextChange);

        for(let elem of Object.keys(contextChange)) {
            if(elem.indexOf('$page.mode') == 0) {
                this.context.mode = contextChange[elem];
            }
        }

        // update contextChange object with instance context
        for(let elem of Object.keys(this.context)) {
            contextChange['$section.'+elem] = this.context[elem];
        }

        // relay contextChange to children

        if(this.pages && this.pages.length) {
            for(let page of this.pages) {
                if(typeof page.onContextChange === 'function' ) {
                    page.onContextChange(contextChange);
                }
            }
        }
    }    
}

export default SectionClass;