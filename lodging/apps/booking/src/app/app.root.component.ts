import { Component, OnInit } from '@angular/core';

import { Router } from '@angular/router';

import { ContextService, ApiService, AuthService} from 'sb-shared-lib';

import * as $ from 'jquery';
import { type } from 'jquery';


/*
This is the component bootstrapped by app.module.ts
*/

@Component({
  selector: 'app-root',
  templateUrl: './app.root.component.html',
  styleUrls: ['./app.root.component.scss']
})
export class AppRootComponent implements OnInit {

    public show_side_menu: boolean = false;
    public show_side_bar: boolean = true;

    public topMenuItems:any[] = [];
    public navMenuItems: any = [];

    public translationsMenuLeft: any = {};
    public translationsMenuTop: any = {};

    constructor(
        private router: Router,
        private context:ContextService,
        private api:ApiService,
        private auth:AuthService
    ) {}


    public lower_screen_resolution: boolean = false;

    public async ngOnInit() {

        this.lower_screen_resolution = window.innerWidth < 1152;

        window.onresize = () => {
            this.lower_screen_resolution = window.innerWidth < 1152;
            // if lower than 1280 : hide right pane
            // if lower than 1152 : hide both panes
            // if lower than 1024 : show too low resolution notice
        }


        try {
            await this.auth.authenticate();
        }
        catch(response) {
            window.location.href = '/apps';
            return;
        }

        // load menus from server
        const left_menu:any = await this.api.getMenu('sale', 'booking.left');
        this.navMenuItems = left_menu.items;
        this.translationsMenuLeft = left_menu.translation;

        const top_menu:any = await this.api.getMenu('sale', 'booking.top');
        this.topMenuItems = top_menu.items;
        this.translationsMenuTop = top_menu.translation;

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
            route: '/bookings',
            context: {
                entity:     'lodging\\sale\\booking\\Booking',
                type:       'form',
                name:       'create',
                mode:       'edit',
                purpose:    'create',
                target:     '#sb-container',
                callback:   (data:any) => {
                    if(data && data.objects && data.objects.length) {
                        console.log('received value from create booking', data);
                        // new_id =  data.objects[0].id
                        let descriptor = {
                            context: {
                            entity:     'lodging\\sale\\booking\\Booking',
                            type:       'list',
                            name:       'default',
                            mode:       'view',
                            purpose:    'view',
                            target:     '#sb-container'
                            }
                        };
                        setTimeout( () => {
                            this.context.change(descriptor);
                        });
                    }
                }
            }
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
//                    target:  '#sb-container',
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

    public onUpdateSideMenu(show: boolean) {
        this.show_side_menu = show;
    }

    public toggleSideMenu() {
        this.show_side_menu = !this.show_side_menu;
    }

    public toggleSideBar() {
        this.show_side_bar = !this.show_side_bar;
    }
}