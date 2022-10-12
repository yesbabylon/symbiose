import { CdkAriaLive } from '@angular/cdk/a11y';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ContextService, ApiService, AuthService} from 'sb-shared-lib';


/*
This is the component that is bootstrapped by app.module.ts
*/

@Component({
    selector: 'app-root',
    templateUrl: './app.root.component.html',
    styleUrls: ['./app.root.component.scss']
})
export class AppRootComponent implements OnInit {

    public show_side_menu: boolean = false;
    public show_side_bar: boolean = true;

    // original (full & translated) menu for left pane
    private leftMenu: any = {};

    public topMenuItems:any[] = [];
    public navMenuItems: any = [];

    public translationsMenuLeft: any = {};
    public translationsMenuTop: any = {};

    private timeout: any;

    // #todo - add this setting to appinfo
    // maximum idle time, in ms
    private MAX_IDLE = 30 * 60 * 1000;       // set a max idle time of 5 mins

    constructor(
        private router: Router,
        private context:ContextService,
        private api:ApiService,
        private auth:AuthService
    ) {}

    /**
     * Redirects the User to the signin page, upon reaching timeout.
     *
     */
    public async onTimeout() {
        await this.auth.signOut();
        window.location.href = '/apps';
        return;
    }

    public async ngOnInit() {

        try {
            await this.auth.authenticate();
        }
        catch(err) {
            window.location.href = '/apps';
            return;
        }


        // init idle timeout (as this is the root component, this will remain active in all children components)
        this.timeout = setTimeout(() => this.onTimeout(), this.MAX_IDLE);

        document.onmousemove = () => {
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => this.onTimeout(), this.MAX_IDLE);

        };
        document.onkeydown = () => {
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => this.onTimeout(), this.MAX_IDLE);
        };

        // load menus from server
        try {

            const data = await this.api.getMenu('sale', 'pos.left');
            // store full translated menu
            this.leftMenu = this.translateMenu(data.items, data.translation);
            // fill left pane with unfiltered menu
            this.navMenuItems = this.leftMenu;
            // this.translationsMenuLeft = this.leftMenu.translation;

            const top_menu:any = await this.api.getMenu('sale', 'pos.top');
            this.topMenuItems = top_menu.items;
            this.translationsMenuTop = top_menu.translation;
        }
        catch(response) {
            console.log('unable to load menu', response);
        }
    }

    private translateMenu(menu:any, translation: any) {
        let result: any[] = [];
        for(let item of menu) {
            if(item.id && translation.hasOwnProperty(item.id)) {
                item.label = translation[item.id].label;
            }
            if(item.children && item.children.length) {
                this.translateMenu(item.children, translation);
            }
            result.push(item);
        }
        return result;
    }

    private getFilteredMenu(menu:any[], filter: string = '') {
        let result: any[] = [];

        for(let item of menu) {
            if(item.label.match(new RegExp(filter, 'i'))) {
                result.push(item);
            }
            else if(item.children && item.children.length) {
                let sub_result: any[] = this.getFilteredMenu(item.children, filter);
                for(let item of sub_result) {
                    result.push(item);
                }
            }
        }
        return result;
    }

    public onchangeFilter(event: any) {
        console.log('onchangefilter', event.target.value);
        this.navMenuItems = this.getFilteredMenu(this.leftMenu, event.target.value);
    }

    public onToggleItem(item:any) {
        // if item is expanded, fold siblings, if any
        if(item.expanded) {
            for(let sibling of this.navMenuItems) {
                if(item!= sibling) {
                    sibling.expanded = false;
                    sibling.hidden = true;
                }
            }
        }
        else {
            for(let sibling of this.navMenuItems) {
                sibling.hidden = false;
                if(sibling.children) {
                    for(let subitem of sibling.children) {
                        subitem.expanded = false;
                        subitem.hidden = false;
                    }
                }
            }
        }
    }


    public onAction() {
        let descriptor = {
            route: '/sessions/new'
        };

        this.context.change(descriptor);
    }

    /**
     * Items are handled as descriptors.
     * They always have a `route` property (if not, it is added and set to '/').
     * And might have an additional `context` property.
     * @param item
     */
    public onSelectItem(item:any) {
        let descriptor:any = {};

        if(!item.hasOwnProperty('route') && !item.hasOwnProperty('context')) {
            return;
        }

        if(item.hasOwnProperty('route')) {
            descriptor.route = item.route;
        }
        else {
            descriptor.route = '/';
        }

        if(item.hasOwnProperty('context')) {
            descriptor.context = {
                ...{
                    type:    'list',
                    name:    'default',
                    mode:    'view',
                    purpose: 'view',
                    target:  '#sb-container',
                    reset:    true
                },
                ...item.context
            };

            if( item.context.hasOwnProperty('view') ) {
                let parts = item.context.view.split('.');
                if(parts.length) descriptor.context.type = <string>parts.shift();
                if(parts.length) descriptor.context.name = <string>parts.shift();
            }

            if( item.context.hasOwnProperty('purpose') && item.context.purpose == 'create') {
                // descriptor.context.type = 'form';
                descriptor.context.mode = 'edit';
            }

        }

        this.context.change(descriptor);
    }


    public showSideMenu() {
        this.show_side_menu = true;
    }

    public toggleSideMenu() {
        this.show_side_menu = !this.show_side_menu;
    }


    public toggleSideBar() {
        this.show_side_bar = !this.show_side_bar;
    }
}