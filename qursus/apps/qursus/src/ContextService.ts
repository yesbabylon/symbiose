import { $ } from "./jquery-lib";

export class _ContextService {

    public module_id: number = 1;           // module identifier (id field)
    public chapter_index: number = 0;
    public page_index: number = 0;

    public mode: string = 'view';

    public user_allowed: boolean = true;

    constructor() {

        let wp_user_id = this.getCookieValue('wp_lms_user');
        /*
        if(wp_user_id === undefined) {
            // prevent running app
            this.user_allowed = false;
        }
        */

        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);

        if(urlParams.has('module')) {
            this.module_id = parseInt(urlParams.get('module'));
        }

        if(urlParams.has('mode')) {
            // restrict edit mode to admin WP users (root, admin, author)
            if(urlParams.get('mode') == 'edit' && ['1', '2', '3'].includes(wp_user_id)) {
                this.mode = 'edit';
            }
        }

        if(urlParams.has('chapter')) {
            this.chapter_index = parseInt(urlParams.get('chapter'));
            if(urlParams.has('page')) {
                this.page_index = parseInt(urlParams.get('page'));
            }
        }

    }


    private getCookieValue(name:string):string|undefined {
        // document.cookie = "wp_lms_user=1;path=/";
        return document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)')?.pop() || undefined;
    }


}



export default _ContextService;