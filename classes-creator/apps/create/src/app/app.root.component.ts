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

  public topMenuItems = [{name: 'Dashboard'}, {name: 'Users'}, {name: 'Settings'}];
  public navMenuItems: any = [];

  public translationsMenuLeft: any = {};
  public translationsMenuTop: any = {};

  constructor(
    private context:ContextService,
    private api:ApiService,
    private auth:AuthService
  ) {}


  public async ngOnInit() {
    try {
      await this.auth.authenticate();
    }
    catch(err) {
      console.log(err);
      window.location.href = '/apps';
      return;
    }
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
                // target:  '#sb-container',
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
