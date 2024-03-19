import { $ } from "./jquery-lib";

import { ApiService, ContextService, EnvService } from "./learn-services";

import { ChapterClass } from "./Chapter.class";

declare global {
    interface Window {
        eq: any;
    }
}

/**
 *
 */
export class ModuleClass {
    // allow virtual keys for dynamic assignment after API updates (we make sure to only use keys defined below)
    [key: string]: any;

    public id: number;
    public identifier: number;
    public order: number;
    public name: string;                // (ex. Module 1, Insider Threat)
    public title: string;               // (ex. Awareness training)
    public description: string;
    public duration: number;            // (duration estimate in minutes)
    public chapters: ChapterClass[];

    private $container:JQuery;

    // module-specific context
    public context: any = {
        actions_counter: 0,
        chapter_index: 0,
        page_index: 0,
        max_page_index: 0,
        next_active: false,
        mode: 'view'
    };


    constructor(
        id: number = 0,
        identifier: number = 0,
        order: number = 0,
        name: string = '',                // (ex. Module 1, Insider Threat)
        title: string = '',               // (ex. Awareness training)
        description: string = '',
        duration: number = 0,             // (duration estimate in minutes)
        chapters: ChapterClass[] = []
    ) {
        this.id = id;
        this.identifier = identifier;
        this.order = order;
        this.name = name;
        this.title = title;
        this.description = description;
        this.duration = duration;
        this.chapters = chapters;

        this.$container = $();


    }


    public setContext(context: any) {
        console.log('Module::setContext', context);

        this.context = {...this.context, ...context};

    }

    public getContext() {
        return this.context;
    }

    public getContainer() {
        return this.$container;
    }


    public init() {

        this.$container = $("<div class=\"module-container viewport-container\" />").append($("<div class=\"container-inner\" />")).appendTo('body');

        let $prev = $('<div class="page-nav-prev page-nav"><i class="arrow material-icons" style="margin-left: -4px;">chevron_left</i></div>').on('click', () =>  {

            let chapter_context = this.chapters[this.context.chapter_index].getContext();
            let chapter_page_index = chapter_context.page_index;

            console.log('page-nav-prev::click', chapter_context);
            // previous page
            if(chapter_page_index > 0) {
                $('body').find('.section-container').remove();
                $('body').find('.section-page-container.viewport-container').remove();
                $('body').find('.section-nav').hide();

                this.context.page_index = chapter_page_index-1;
                this.chapters[this.context.chapter_index].render(this.context);
            }
            else {
                $('body').find('.section-container').remove();
                $('body').find('.section-page-container.viewport-container').remove();
                $('body').find('.section-nav').hide();

                // previous chapter
                if(this.context.chapter_index > 0) {
                    --this.context.chapter_index;
                    this.context.page_index = this.chapters[this.context.chapter_index].pages.length-1;
                    this.render();
                    this.onContextChange({});
                }
            }

        });

        let $next = $('<div class="page-nav-next page-nav"><i class="arrow material-icons">chevron_right</i></div>').on('click', () =>  {

            let chapter_context:any = this.chapters[this.context.chapter_index].getContext();

            console.log('page-nav-next::click', this.context, chapter_context, this.chapters[this.context.chapter_index].pages.length);

            // next page
            if( (chapter_context.page_index+1) < this.chapters[this.context.chapter_index].pages.length) {
                $('body').find('.section-container').remove();
                $('body').find('.section-page-container.viewport-container').remove();
                $('body').find('.section-nav').hide();

                if( (chapter_context.page_index+1) < this.context.page_index || this.context.mode == 'edit') {
                    this.context.next_active = true;
                    this.$container.find('.page-nav-next').show();
                }
                else {
                    // next_active depends on domain of current page 
                    this.context.next_active = false;
                    this.$container.find('.page-nav-next').hide();

                }
                this.context.page_index = chapter_context.page_index+1;
                this.chapters[this.context.chapter_index].render(this.context);
            }
            else {
                $('body').find('.section-container').remove();
                $('body').find('.section-page-container.viewport-container').remove();
                $('body').find('.section-nav').hide();

                // next chapter
                if( (this.context.chapter_index+1) < this.chapters.length) {
                    ++this.context.chapter_index;
                    this.context.page_index = 0;
                    this.render();
                    this.onContextChange({});
                }
                else {
                    // no more pages : hide 'next' button
                    this.context.next_active = false;
                    this.$container.find('.page-nav-next').hide();
                }
            }

            // notify 'next' action
            if(this.context.mode == 'view') {                
                ApiService.fetch('/?do=learn_next', { module_id: this.id, chapter_index: this.context.chapter_index, page_index: this.context.page_index});
            }

        });


        if(this.context.mode == 'view') {
            $next.hide();
        }
        else if(this.context.mode == 'edit') {
            $('body').css("overflow", "visible").css('background', '#636b7e');

            /*
             append controls to module container
            */

            let $module_controls = $('<div class="controls module-controls"><div class="label">Module '+this.identifier+'</div></div>');
            let $module_actions = $('<div class="actions module-actions"></div>');
            let $module_edit_button = $('<div class="action-button module-edit-button" title="Edit Module"><span class="material-icons mdc-fab__icon">mode_edit</span></div>');
            let $module_add_button = $('<div class="action-button module-add-button" title="Add a Chapter"><span class="material-icons mdc-fab__icon">add</span></div>');
            $module_actions.append($module_edit_button).append($module_add_button);
            $module_controls.append($module_actions);
            $module_controls.appendTo(this.$container);

            /*
             setup action handlers
            */

            $module_edit_button.on('click', async () => {
                const environment = await EnvService.getEnv();
                window.eq.popup({entity: 'learn\\Module', type: 'form', mode: 'edit', domain: ['id', '=', this.id], lang: environment.lang, callback: (data:any) => {
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
                        // request refresh for current context
                        this.propagateContextChange({refresh: true});
                    }
                }});
            });

            $module_add_button.on('click', () => {
                let chapter_identifier = (this.chapters)?this.chapters.length+1:1;
                window.eq.popup({entity: 'learn\\Chapter', type: 'form', mode: 'edit', purpose: 'create', domain: [['module_id', '=', this.id], ['identifier', '=', chapter_identifier], ['order', '=', chapter_identifier]], callback: (data:any) => {
                    // append new chapter to module
                    if(data && data.objects) {
                        for(let item of data.objects) {
                            let chapter = new ChapterClass(
                                Number(item.id),
                                Number(item.identifier),
                                Number(item.order),
                                item.title,
                                item.pages
                            );
                            if(!this.chapters) {
                                this.chapters = new Array<ChapterClass>();
                            }
                            this.chapters.push(chapter);
                        }
                        this.propagateContextChange({refresh: true});
                    }
                }});
            });
        }

        this.$container.append($prev).append($next);


        if(this.context.mode == 'view') {
            let $btnup = $('<div class="section-nav section-nav-up"><i class="arrow material-icons">chevron_left</i></div>').hide();
            let $btndown = $('<div class="section-nav section-nav-down"><i class="arrow material-icons">chevron_right</i></div>').hide();

            $btnup.on('click', () => {
                let pages_count = $btndown.data('pages-count');
                let page_index = $btndown.data('page-index');

                if(page_index-1 <= 0) {
                    $btndown.data('page-index', 0);
                    $btnup.hide();
                }

                $($('body').find('.viewport-container').get().reverse()).each( (index:number, elem:HTMLElement) => {
                    let $elem = $(elem);
                    let vpos = $elem.data("vpos");
                    vpos = (vpos == undefined)?0:vpos;
                    $elem.css("transform", "translateY(" + (vpos+100) + "vh)").data('vpos', vpos+100);
                });

            });

            $btndown.on('click', () => {
                $btnup.show();
                let pages_count = $btndown.data('pages-count');
                let page_index = $btndown.data('page-index');

                if(page_index+1 > pages_count) {
                    $btndown.data('page-index', 0);
                    $btnup.hide();
                    $('body').find('.viewport-container').each( (index:number, elem:HTMLElement) => {
                        let $elem = $(elem);
                        $elem.css("transform", "translateY(" + (100*index) + "vh)").data('vpos', 100*index);
                    });
                    // mark section as finished
                    let chapter_context = this.chapters[this.context.chapter_index].getContext();
                    let chapter_page_index = chapter_context.page_index;
                    this.chapters[this.context.chapter_index].pages[chapter_page_index].finishSection();
                }
                else {
                    $btndown.data('page-index', page_index+1);
                    $('body').find('.viewport-container').each( (index:number, elem:HTMLElement) => {
                        let $elem = $(elem);
                        let vpos = $elem.data("vpos");
                        vpos = (vpos == undefined)?0:vpos;
                        $elem.css("transform", "translateY(" + (vpos-100) + "vh)").data('vpos', vpos-100);
                    });
                }

            });

            $('body').append($btnup).append($btndown);
        }

    }

    public propagateContextChange(contextChange:any) {
        console.log('Module::propagateContext', contextChange, this.context, this.chapters);

        for(let elem of Object.keys(contextChange)) {
            if(elem.indexOf('$module') == 0) {
                let value = contextChange[elem];
                const parts = elem.split('.');
                if(parts.length > 1) {
                    if(parts[1] == 'actions_counter') {
                        ++this.context[parts[1]];
                    }
                    else if(parts[1] == 'next_active') {
                        // set next active                        
                        let chapter_context:any = this.chapters[this.context.chapter_index].getContext();
                        if( (chapter_context.page_index+1) < this.chapters[this.context.chapter_index].pages.length || (this.context.chapter_index+1) < this.chapters.length ) {
                            this.context['next_active'] = true;
                            this.$container.find('.page-nav-next').show();
                        }
                        else {
                            this.context['next_active'] = false;
                            this.$container.find('.page-nav-next').hide();
                        }
                    }
                    else if(parts[1] == 'remove_chapter') {
                        // value is widget id
                        for(const [index, chapter] of this.chapters.entries()) {
                            if(chapter.id == value) {
                                this.chapters.splice(index, 1);
                            }
                        }
                    }
                    else {
                        this.context[parts[1]] = value;
                    }
                }
            }
        }

        // an object has been changed and requests re-rendering
        if(contextChange.refresh) {
            this.render();
        }

        // relay back context updates to children
        this.onContextChange({});

    }

    /**
     * Changes received from parent or self
     * @param context
     */
     public onContextChange(contextChange:any) {

        // update contextChange object with instance context
        for(let elem of Object.keys(this.context)) {
            contextChange['$module.'+elem] = this.context[elem];
        }

        if(typeof this.chapters[this.context.chapter_index].onContextChange === 'function') {
            this.chapters[this.context.chapter_index].onContextChange(contextChange);
        }
    }

    /**
     *  Render the module, according to current context.
     *
     */
    public render() {
        console.log("Module::render()", this.context);

        if(this.context.mode == 'edit') {
            this.$container.addClass('_edit');
        }
        else {
            this.$container.removeClass('_edit');
        }

        if(this.chapters && this.chapters.length) {
            let item:any = this.chapters[this.context.chapter_index];

            let chapter:ChapterClass = new ChapterClass(
                item.id,
                item.identifier,
                item.order,
                item.title,
                item.pages
            );

            this.chapters[this.context.chapter_index] = chapter;
            chapter.setParent(this);
            this.$container.find('.container-inner').empty().append(chapter.getContainer());
            chapter.render(this.context);
        }

    }

}

export default ModuleClass;