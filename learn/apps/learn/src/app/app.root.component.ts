import { Component, ElementRef, OnDestroy, OnInit, ViewChild } from '@angular/core';

import { Router } from '@angular/router';

import {
    ContextService,
    ApiService,
    AuthService,
    EnvService,
    // @ts-ignore
} from 'sb-shared-lib';

// TODO: <AlexisVS> Added for development
import menu from '../assets/menu.json';
import { MenuItem } from './_types/menu';

// import * as $ from 'jquery';
// import { type } from 'jquery';

/*
This is the component that is bootstrapped by app.module.ts
*/

declare global {
    interface Window {
        context: any;
    }
}

@Component({
    selector: 'app-root',
    templateUrl: './app.root.component.html',
    styleUrls: ['./app.root.component.scss'],
})
export class AppRootComponent implements OnInit, OnDestroy {
    public show_side_menu: boolean = false;
    public show_side_bar: boolean = true;

    public filter: string;

    // original (full & translated) menu for left pane
    private leftMenu: any = {};

    public topMenuItems = [{ name: 'Dashboard' }, { name: 'Users' }, { name: 'Settings' }];
    public navMenuItems: any = [];

    public translationsMenuLeft: any = {};
    public translationsMenuTop: any = {};

    private app_root_package: string = '';

    @ViewChild('asideMenu', { static: true }) asideMenu: ElementRef<HTMLDivElement>;

    public timeout: any;

    constructor(
        private router: Router,
        private context: ContextService,
        private api: ApiService,
        private auth: AuthService,
        private env: EnvService,
        private elementRef: ElementRef
    ) {}

    ngOnDestroy(): void {
        clearTimeout(this.timeout);
    }

    public async ngOnInit() {
        // TODO: <AlexisVS> Disabled for development
        // try {
        //     await this.auth.authenticate();
        // }
        // catch(err) {
        //     window.location.href = '/auth';
        //     return;
        // }

        // load menus from server
        this.env.getEnv().then(async (environment: any) => {
            this.app_root_package = 'core';

            // TODO: <AlexisVS> Disabled for development
            // const data = await this.api.getMenu(this.app_root_package, 'sandbox.left');

            // store full translated menu
            // TODO: <AlexisVS> Disabled for development
            // this.leftMenu = this.translateMenu(data.items, data.translation);
            this.leftMenu = menu.layout.items as MenuItem[];

            // fill left pane with unfiltered menu
            this.navMenuItems = this.leftMenu;
            // this.translationsMenuLeft = this.leftMenu.translation;

            const top_menu: any = await this.api.getMenu(this.app_root_package, 'sandbox.top');
            this.topMenuItems = top_menu.items;
            this.translationsMenuTop = top_menu.translation;
        });
    }

    private translateMenu(menu: any, translation: any) {
        let result: any[] = [];
        for (let item of menu) {
            if (item.id && translation.hasOwnProperty(item.id)) {
                item.label = translation[item.id].label;
            }
            if (item.children && item.children.length) {
                this.translateMenu(item.children, translation);
            }
            result.push(item);
        }
        return result;
    }

    private getFilteredMenu(menu: any[], filter: string = '') {
        let result: any[] = [];

        for (let item of menu) {
            // check for a match, case and diacritic insensitive
            if (
                item.label
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .match(new RegExp(filter, 'i'))
            ) {
                result.push(item);
            } else if (item.children && item.children.length) {
                let sub_result: any[] = this.getFilteredMenu(item.children, filter);
                for (let item of sub_result) {
                    result.push(item);
                }
            }
        }
        return result;
    }

    public onchangeFilter() {
        this.navMenuItems = this.getFilteredMenu(
            this.leftMenu,
            // remove trailing space + remove diacritic marks
            this.filter
                .trim()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
        );
    }

    public onToggleItem(item: any) {
        console.log('SettingsAppRoot::onToggleItem', item);

        // check item for route and context details
        this.onSelectItem(item);

        // if item is expanded, fold siblings, if any
        if (item.expanded) {
            for (let sibling of this.navMenuItems) {
                if (item != sibling) {
                    sibling.expanded = false;
                    sibling.hidden = true;
                }
            }
        } else {
            for (let sibling of this.navMenuItems) {
                sibling.hidden = false;
                if (sibling.children) {
                    for (let subitem of sibling.children) {
                        subitem.expanded = false;
                        subitem.hidden = false;
                    }
                }
            }
        }
    }

    /**
     * Items are handled as descriptors.
     * They always have a `route` property (if not, it is added and set to '/').
     * And might have an additional `context` property.
     * @param item
     */
    public onSelectItem(item: any) {
        console.log('SettingsAppRoot::onSelectItem', item);
        let descriptor: any = {
            route: '/',
        };

        // TODO: <AlexisVS> Added for development purpose
        if (item.hasOwnProperty('url')) {
            this.router.navigateByUrl(item.url);
        }

        if (!item.hasOwnProperty('route') && !item.hasOwnProperty('context')) {
            return;
        }

        if (item.hasOwnProperty('route')) {
            descriptor.route = item.route;
        }

        if (item.hasOwnProperty('context')) {
            descriptor.context = {
                ...{
                    type: 'list',
                    name: 'default',
                    mode: 'view',
                    purpose: 'view',
                    // target:  '#sb-container',
                    reset: true,
                },
                ...item.context,
            };

            if (item.context.hasOwnProperty('view')) {
                let parts = item.context.view.split('.');
                if (parts.length) {
                    descriptor.context.type = <string>parts.shift();
                }
                if (parts.length) {
                    descriptor.context.name = <string>parts.shift();
                }
            }

            if (item.context.hasOwnProperty('purpose') && item.context.purpose == 'create') {
                // descriptor.context.type = 'form';
                descriptor.context.mode = 'edit';
            }
        }

        this.context.change(descriptor);
    }

    public toggleSideMenu() {
        this.show_side_menu = !this.show_side_menu;
    }

    public toggleSideBar() {
        this.show_side_bar = !this.show_side_bar;
    }
}
