import { $ } from "./jquery-lib";
import { ContextService } from "./qursus-services";
import { ModuleClass } from "./Module.class";
import { PackClass } from "./Pack.class";
import { EnvService } from "./qursus-services";


declare global {
    interface Window { context: any; }
}

/**
 *
 *
 */
class Qursus {

    private module: ModuleClass;
    private pack: PackClass;

    private languages: any[] = ['en'];

    constructor() {
        console.log("Qursus::constructor");

        this.onload();
    }

    private async onload() {
        const environment = await EnvService.getEnv();
        await $.getJSON("environment.json", (json: any) => {
            console.log("found environment file", json);

            for(let field in json) {
                if(environment.hasOwnProperty(field)) {
                    environment[field] = json[field];
                }
            }
            console.log(environment);
        })
        .fail( (response: any) => {
            console.log("no environment file found");
        });

        this.init();
    }

    private async init() {
        const environment = await EnvService.getEnv();
        // unknonw user
        if(!ContextService.user_allowed) {
            $('.spinner-wrapper').hide();
            $('.access-restricted').show();
        }
        // registered user
        else {
            const environment = await EnvService.getEnv();
            $.getJSON(environment.backend_url+"?get=qursus_module&id="+ContextService.module_id+'&lang='+environment.lang, (json:any) => {

                this.pack = new PackClass(json.pack_id.id, json.pack_id.name, json.pack_id.subtitle, json.pack_id.title, json.pack_id.description);
                this.module = new ModuleClass(json.id, json.identifier, json.order, json.name, json.title, json.description, json.duration, json.chapters);

                if(json.pack_id && json.pack_id.langs_ids) {
                    this.languages = json.pack_id.langs_ids;
                }

                this.module.setContext({
                    chapter_index: ContextService.chapter_index,
                    page_index: ContextService.page_index,
                    mode: ContextService.mode
                });

                this.module.init();
                $('.spinner-wrapper').hide();
                $('body').addClass(ContextService.mode);
                $('.menu-top').find('.cell-program').text(this.pack.title);
                $('.menu-top').find('.cell-module').text('Module '+this.module.identifier);
                this.module.render();

                $('.menu-top .inner .left-cell a').attr('href', '/product/'+this.pack.name);

                let $lang_select = $('<select>').on('change', (event:any) => this.onchangeLang(event) );
                for(let lang of this.languages) {
                    let $option = $('<option>').attr('value', lang.code).text(lang.name).appendTo($lang_select);
                    if(lang.code == environment.lang) {
                        $option.attr('selected', 'selected');
                        $lang_select.val(lang.code);
                    }
                }

                $('.menu-top .middle-cell').empty().append($lang_select);

            })
            .fail( (response:any) => {
                console.log('unexpected error', response);
                let error_id = 'unknown_error'
                if(response.responseJSON && response.responseJSON.errors) {
                    if(response.responseJSON.errors.NOT_ALLOWED) {
                        error_id = response.responseJSON.errors.NOT_ALLOWED;
                    }
                    else if(response.responseJSON.errors.UNKNOWN_ERROR) {
                        error_id = response.responseJSON.errors.UNKNOWN_ERROR;
                    }
                }
                switch(error_id) {
                    case 'missing_licence':
                        $('.missing-license').show();
                        break;
                    case 'unknown_user':
                        $('.access-restricted').show();
                        break;
                    default:
                        $('.unknown-error').show();
                }
                $('.spinner-wrapper').hide();
            });
        }

    }

    public setContext(context: any) {
        this.module.setContext(context);
    }

    public onchangeLang(event:any) {
        let $select = $(event.target);

        let lang = <string> $select.val();

        // update env lang
        EnvService.setEnv('lang', lang);

        // reset  everything
        $('.viewport-container').remove();
        $('.spinner-wrapper').show();

        // module
        this.init();
    }

    public render() {
        this.module.render();
    }

}

module.exports = Qursus;