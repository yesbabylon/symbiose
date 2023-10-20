import { $ } from "./jquery-lib";
import { DomainClass } from "./Domain.class";
import { TextRendererClass } from "./TextRenderer.class";
import { EnvService, ApiService } from "./qursus-services";
import GroupClass from "./Group.class";


declare global {
    interface Window { hljs: any; }
}


/**
 *
 */
export class WidgetClass {
    // allow virtual keys for dynamic assignment after API updates (we make sure to only use keys defined below)
    [key: string]: any;

    public id: number;
    public identifier: number;
    public order: number;
    public type: string;
    /*
    selection
            'chapter_number'
            'chapter_title' = 'title' (font-size: 22px)
            'text' (font-size: 16px) (support UL bullets + bold)
            'headline' (font-size: 20px, font-weight: 600)
            'sound': label, sound_file (alt. 'phonetic': value)
            'selector_popup' (each opening of a popup increment $page.actions_counter) : []{ label: string, popup: image}
            'selector' (88px x 103px) (number is the amount of preceeding 'selector' items on the leaf + 1)
            'selector_yes_no'
            'selector_choice' (380px x 75px)
            'selector_section' (190px x 215px)
            'selector_section_wide' (290px x 320px)
            'image_popup' (add a notice : "click the image to view it at full size")
            'tooltip'
            'video' (requires video_url)
            'submit_button'
            'first_capital' (first letter in big for acronyms details)
            'download_file' (link with parsing support $module.chapters.selection)
    */
    public has_separator_left: boolean;                    // (2px at bottom left of widget)
    public has_separator_right: boolean;                   // (2px at bottom right of widget)
    public align: string;                                  // 'left' or 'right'
    public on_click: string;
    /*
    function
            select()				$this.selected = true; $page.selection = index (=id); $page.actions_counter += 1
            select_one()			unselect_all(), $this.selected = true; $page.selection = index; $actions_counter += 1
            select_for_chapter() 	unselect_all(), $this.selected = true; $page.selection = index; $chapter.selection = index; $page.actions_counter += 1
            submit()				$page.submitted = 1; $page.actions_counter += 1
            image_full()    		$page.actions_counter += 1; display background_image fullpage
            nav_subpage() 			(try to display a subpage of the current page, according to ID [match] with subpage_id)
    */
    public section_id: number;                                     // (id of section within parent page sections array)

    public content: string;                                        // text (with MD support)
    public sound_url: string;
    public video_url: string;
    public image_url: string;

    public selected: boolean;

    private parent: GroupClass = null;

    private $container;
    private previously_selected = false;

    private context: any = {
        mode: 'view'
    };

    constructor(
        id: number,
        identifier: number,
        order: number,
        type: string,
        has_separator_left: boolean,
        has_separator_right: boolean,
        align: string,
        on_click: string,
        section_id: number,
        content: string,
        sound_url: string,
        video_url: string,
        image_url: string
    ) {
        this.id = id;
        this.identifier = identifier;
        this.order = order;
        this.type = type;
        this.has_separator_left = has_separator_left;
        this.has_separator_right = has_separator_right;
        this.align = align;
        this.on_click = on_click;
        this.section_id = section_id;
        this.content = content;
        this.sound_url = sound_url;
        this.video_url = video_url;
        this.image_url = image_url;

        this.$container = $('<div />');
    }

    public setParent(group: GroupClass) {
        this.parent = group;
    }

    public setContext(context:any) {
        for(let key of Object.keys(this.context)) {
            if(context.hasOwnProperty(key)) {
                this.context[key] = context[key];
            }
        }
    }

    public render(context:any) {
        console.log("WidgetClass::render", this);

        // this.setContext(context);

        let content:any = this.content;

        let widget_classes = 'widget';

        widget_classes += ' type-'+this.type;

        if(this.has_separator_right) {
            widget_classes += ' separator_right';
        }
        else if(this.has_separator_left) {
            widget_classes += ' separator_left';
        }

        let align = 'none';
        if(this.align) {
            align = this.align;
        }

        widget_classes += ' align-'+align;

        switch(this.type) {
            case 'code':
                // normalize code : remove html layout
                content = $(content.replace(/<br>/g, "").replace(/<\/p><p>/g, "\n")).text();
                // identify target lang
                let target_lang = 'javascript';
                let clues:any = {
                    'php': ['```php'],
                    'javascript': ['```javascript']
                };
                for(let lang in clues) {
                    let lang_clues = clues[lang];
                    let found = false;
                    for(let clue of lang_clues) {
                        if(content.indexOf(clue) !== -1) {
                            // remove line containing clue
                            content = content.split('\n').filter((line:string) => (line.indexOf(clue) == -1)).join('\n');
                            target_lang = lang;
                            found = true;
                            break ;
                        }
                        if(found) {
                            break;
                        }
                    }
                }
                content = '<pre style="background: #282c34; text-align: left; padding: 0 5px; border-radius: 5px;" data-lang="'+target_lang+'">' + window.hljs.highlight(content, { language: target_lang }).value.replace(/\n/g, "<br />") + '</pre>';
                break;
            case 'page_title':
            case 'chapter_title':
            case 'chapter_description':
            case 'headline':
            case 'subtitle':
            case 'text':
            case 'head_text':
            case 'selector_yes_no':
                content = TextRendererClass.render(content);
                break;
            case 'chapter_number':

                break;
            case 'submit_button':
                content = TextRendererClass.render(content) + '<div class="arrow"><i class="arrow material-icons">chevron_right</i></div>';
                break;
            case 'selector':
            case 'selector_wide':
                content = TextRendererClass.render(content);
                content = "<span class=\"counter\">"+this.identifier+"</span><span class=\"text\">"+content+"</span><span class=\"marker\"></span>";
                break;
            case 'selector_choice':
                content = "<span class=\"marker\"></span>"+content;
                break;
            case 'selector_section':
                widget_classes += ' section-'+this.section_id;
                // 1 px transparent
                let background_img = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
                if(this.image_url) {
                    background_img = this.image_url;
                }
                content = "<div class=\"selector-section-container\" style=\"background-image: url("+background_img+")\"><div class=\"overlay\"></div><div class=\"text\">"+content+"</div></div> ";
                break;
            case 'selector_section_wide':
                break;
            case 'selector_popup':
                content = "<div class=\"popup-container\"><span class=\"text\">"+content+"</span><i class=\"arrow material-icons\">chevron_right</i></div>";
                break;
            case 'tooltip':
                content = TextRendererClass.render(content);
                content = "<div class=\"tooltip\">Tip!<span class=\"text\">"+content+"</span><div class=\"image\"></div></div>";
                break;
            case 'sound':
                content = "<div class=\"sound-container\"><span>"+ content +"</span><div class=\"play\"></div></div>";
                if(this.sound_url) {
                    this.on_click = "play()";
                }
                break;
            case 'video':
                content = "<div class=\"video-container\"><div class=\"play\"></div></div>";
                if(this.video_url) {
                    this.on_click = "play()";
                }
                break;
            case 'image_popup':
                let image_url = (this.image_url)?this.image_url:'';
                content = "<div class=\"image-container\"><img width=\"100%\" height=\"100%\" src=" + image_url + "></div> ";
                break;
            case 'first_capital':
                let first = content.replace('<p>', '').substr(0, 1);
                content = TextRendererClass.render(content);
                content = "<div class=\"letter\">"+first+"</div><div class=\"text\">" + content + "</div>";
                break;
        }



        this.$container.addClass(widget_classes).append(content);

        if(this.on_click) {
            switch(this.on_click) {
                case 'play()':
                    if(this.type == 'video' && this.video_url && this.video_url.length) {
                        this.$container.on('click', () => {
                            // use leaf container to append a full page video player
                            let $leafContainer = this.parent.getParent().getContainer();
                            let $videoContainer = $('<div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: black; z-index: 1;"></div>');
                            let $video = $('<video style="height: 100%; width: 100%;" controls controlsList="nodownload"><source src="'+this.video_url+'" type="video/mp4"></video>').appendTo($videoContainer);
                            $video.on('ended', () => {
                                $videoContainer.remove();
                                this.parent.propagateContextChange({
                                    "$page.actions_counter": +1
                                });
                            });
                            $leafContainer.append($videoContainer);
                            $video.trigger('play');
                        });
                    }
                    if(this.type == 'sound' && this.sound_url && this.sound_url.length) {
                        this.$container.on('click', () => {
                            console.log('playing sound');
                            const sound = new Audio(this.sound_url);
                            sound.play();
                        });
                    }
                    break;
                case 'image_full()':
                    if(['image_popup', 'selector_popup'].indexOf(this.type) > -1 && this.image_url && this.image_url.length) {
                        this.$container.on('click', () => {
                            if(this.type == 'selector_popup') {
                                this.$container.addClass('previously_selected');
                            }
                            // use page container to append a full page image
                            let $leafContainer = this.parent.getParent().getParent().getContainer();
                            let $imageContainer = $('<div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: black; z-index: 1;"></div>');
                            let $image = $('<img style="height: 100%; width: 100%;" src="'+this.image_url+'" />').appendTo($imageContainer);
                            let $closeBtn = $('<div style="position: absolute; top: 10px; right: 10px; z-index: 2; cursor: pointer; border: solid 1px black; border-radius: 50%; width: 30px; height: 30px; background-color:white;"><i class="material-icons" style="width: 100%;text-align: center;margin-top: 2px;">close</i></div>').appendTo($imageContainer);
                            $leafContainer.append($imageContainer);
                            $closeBtn.on('click', () => {
                                $imageContainer.remove();
                                this.parent.propagateContextChange({
                                    "$page.actions_counter": +1
                                });
                            });
                        });
                    }
                    break;
                case 'select()':
                case 'select_one()':
                    console.log('widget::select()');
                    this.$container.on('click', () => {
                        let propagate:any = {
                            "$page.selection": this.identifier
                        };
                        if(!this.previously_selected) {
                            propagate["$page.actions_counter"] = +1;
                            this.previously_selected = true;
                        }
                        this.parent.propagateContextChange(propagate);
                    });
                    break;
                case 'submit()':
                    this.$container.on('click', () => {
                        console.log('widget '+ this.type + ' submit()', this);
                        this.parent.propagateContextChange({
                            "$page.submitted": true
                        });
                    });
                    break;
                default:
            }
        }

        /*
            handle section selection
        */
        switch(this.type) {
            case 'selector_section':
            case 'selector_section_wide':
                this.$container.on('click', () => {
                    // open section that matches the section_id of the widget
                    this.parent.getParent().getParent().openSection(this.section_id, context);
                });
                break;
        }



        if(context.mode == 'edit') {
            let $actions = $('<div class="actions widget-actions"></div>');

            let $edit_button = $('<div class="action-button widget-edit-button" title="Edit Widget"><span class="material-icons mdc-fab__icon">mode_edit</span></div>');
            let $delete_button = $('<div class="action-button widget-delete-button" title="Delete Widget"><span class="material-icons mdc-fab__icon">delete</span></div>');

            $actions.append($edit_button).append($delete_button);

            $edit_button.on('click', async () => {
                const environment = await EnvService.getEnv();
                window.eq.popup({entity: 'qursus\\Widget', type: 'form', mode: 'edit', domain: ['id', '=', this.id], lang: environment.lang, callback: (data:any) => {
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
                        this.parent.propagateContextChange({refresh: true});
                    }
                }});
            });


            $delete_button.on('click', () => {
                if (window.confirm("Widget is about to be removed. Do you confirm ?")) {
                    ApiService.delete('qursus\\Widget', [this.id], true);
                    this.parent.propagateContextChange({'$group.remove_widget': this.id, refresh: true});
                }
            });

            this.$container.append($actions);
        }


        return this.$container;
    }


    /**
     * Changes received from parent or self
     * @param contextChange
     */
     public onContextChange(contextChange:any) {
        console.log('Widget::onContextChange', contextChange);

        for(let elem of Object.keys(contextChange)) {
            if(elem.indexOf('$group.mode') == 0) {
                this.context.mode = contextChange[elem];
            }
        }

        let selected = this.selected;

        let domain = new DomainClass(["$page.selection", "=", this.identifier]);
        selected = domain.evaluate(contextChange) ;

        if(this.on_click && this.on_click == 'select_one') {
            this.selected = selected;
        }

        if(this.selected || selected) {
            this.$container.addClass('selected');
        }
        else {
            this.$container.removeClass('selected');
        }

    }

}

export default WidgetClass;