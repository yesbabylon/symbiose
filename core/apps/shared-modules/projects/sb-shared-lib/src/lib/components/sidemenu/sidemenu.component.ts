import { Component, OnInit, Output, Input, EventEmitter, NgZone, ViewChild, ElementRef, ChangeDetectorRef } from '@angular/core';
import { Router } from '@angular/router';
import { debounceTime } from 'rxjs/operators';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';
import { ContextService } from '../../services/context.service';
import { EnvService } from '../../services/env.service';
import { EqualUIService } from '../../services/eq.service';

import * as screenfull from 'screenfull';
import { HttpErrorResponse } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';

@Component({
  selector: 'app-sidemenu',
  templateUrl: './sidemenu.component.html',
  styleUrls: ['./sidemenu.component.scss']
})
export class AppSideMenuComponent implements OnInit {

  @ViewChild('helpfullscreen') helpFullScreen: ElementRef;
  @Output() updated = new EventEmitter();
  // @Input() refresh: Observable<Boolean>;

  private environment: any = null;

  public panes: any = [{
      id: "validity-check",
      icon: "check_circle_outline"
    },
    {
      id: "view-help",
      icon: "help_outline"
    },
    {
      id: "object-history",
      icon: "history"
    },
    {
      id: "user-settings",
      icon: "settings"
    }
  ];

    public selected_tab_id = 'object-routes';
    public user: any = {};

    public view_description: string = '';
    public object_routes_items: any = [];
    public object_checks_items: any = [];
    public latest_changes: any = [];
    public object_checks_result: any = {
        title: '', // string
        content: [] // array of objects
    };

    private view_id: string = '';
    private object_class: string = '';
    private object_id: number = 0;

    private object: any = {};

    constructor(
        private cd:ChangeDetectorRef,
        private context: ContextService,
        private router: Router,
        private api: ApiService,
        private auth: AuthService,
        private zone: NgZone,
        private env: EnvService,
        private eq:EqualUIService,
        private translate: TranslateService
    ) {}

    ngOnInit(): void {

        (async () => {
            this.environment = await this.env.getEnv();
        })();

        this.auth.getObservable().subscribe((user: any) => {
            this.user = user;
        });


        this.context.getObservable()
        .pipe(
            // delay requests to prevent slowing down main screen display
            debounceTime(500)
        )
        .subscribe( async (descriptor: any) => {
            console.debug('SideMenu::received descriptor from Context', descriptor);

            // #todo - check if context is distinct

            // reset local vars
            this.object_checks_result.title = "";
            this.object_checks_result.content = [];
            // #memo - routes are updated dynamically
            // this.object_routes_items = [];
            this.object_checks_items = [];
            this.view_description = '';
            this.latest_changes = [];

            if (descriptor.hasOwnProperty('context') && !descriptor.context_silent
                && descriptor.context.entity && descriptor.context.entity.length) {

                console.debug('SideMenu::searching for context menu', descriptor.context);

                // 1) retrieve the details of the view that was requested
                let view_type = (descriptor.context.hasOwnProperty('type')) ? descriptor.context.type : 'list';
                let view_name = (descriptor.context.hasOwnProperty('name')) ? descriptor.context.name : 'default';
                let view_id = view_type + '.' + view_name;

                if (descriptor.context.hasOwnProperty('view')) {
                    view_id = descriptor.context.view;
                }

                // 2) retrieve the object id (provided in context domain or in route URL)
                let object_id: number = 0;


                if(view_type == 'form') {
                    // by convention the current object id, if present in route, is the latest numeric value (ex.: '/booking/13/contract/735')
                    if (descriptor.hasOwnProperty('route')) {
                        // route is expected to hold the ID of the object as last part
                        const parts = descriptor.route.split('/');
                        for(let i = parts.length; i > 0; --i) {
                            let candidate = parseInt(parts[i - 1], 10);
                            if (!isNaN(candidate)) {
                                object_id = candidate;
                                break;
                            }
                        }
                    }

                    // id in domain prevails over route
                    if (descriptor.context.hasOwnProperty('domain')) {
                        // domain is expected to hold a single ID condition (ex. ['id', '=', 3])
                        let domain: any[] = [];
                        if(Array.isArray(descriptor.context.domain) && descriptor.context.domain.length) {

                            // 1) linearize domain
                            for(let item of descriptor.context.domain) {
                                if(Array.isArray(item)) {
                                    for(let subitem of item) {
                                        if(Array.isArray(subitem)) {
                                            domain.push([...subitem]);
                                        }
                                        else {
                                            if(item.length == 3) {
                                                domain.push([...item]);
                                            }
                                            break;
                                        }
                                    }
                                }
                                else {
                                    if(descriptor.context.domain.length == 3) {
                                        domain.push([...descriptor.context.domain]);
                                    }
                                    break;
                                }
                            }

                            // 2) look for an object ID
                            for(let condition of domain) {
                                if(condition[0] == 'id') {
                                    let candidate = parseInt(condition[2], 10);
                                    if (!isNaN(candidate)) {
                                        object_id = candidate;
                                    }
                                }
                            }
                        }
                    }
                }

                // 3) retrive the entity that was requested
                let object_class: string = descriptor.context.entity;

                // check if we are showing a view for a specific entity but we want the actions to apply on another entity
                if (descriptor.context.hasOwnProperty('target_entity')) {
                    object_class = descriptor.context.target_entity;
                    if (descriptor.context.hasOwnProperty('target_view') || descriptor.context.hasOwnProperty('target_type') || descriptor.context.hasOwnProperty('target_name')) {
                        let view_type = (descriptor.context.hasOwnProperty('target_type')) ? descriptor.context.target_type : 'form';
                        let view_name = (descriptor.context.hasOwnProperty('target_name')) ? descriptor.context.target_name : 'default';
                        view_id = view_type + '.' + view_name;
                        if (descriptor.context.hasOwnProperty('target_view')) {
                            view_id = descriptor.context.target_view;
                        }
                    }
                }

                // remember (un)resolved args
                this.view_id = view_id;
                this.object_id = object_id;
                this.object_class = object_class;

                // if(view_id != this.view_id || this.object_class != object_class || this.object_id != object_id) {
                if(!object_id) {
                    this.object_routes_items = [];
                    // hide side menu
                    this.updated.emit(false);
                }
                else {
                    console.debug('AppSideMenuComponent: updated values', this.view_id, this.object_class, this.object_id);

                    let object_fields = ['id', 'name', 'state', 'created', 'modified', 'status', 'order'];

                    let view_routes = [];

                    // load routes and look for references to object fields (to append those to the fields to load, `object_fields`)
                    try {

                        // request the schema of the view from eQ lib

                        const apiService = this.eq.getApiService();
                        const translationService = this.eq.getTranslationService();

                        const translation:any = await apiService.getTranslation(this.object_class);

                        // fetch view, with fallback to default name of same type
                        let view_type = 'form';
                        let view_name = 'default';

                        let parts = this.view_id.split('.');
                        if(parts.length) view_type = <string>parts.shift();
                        if(parts.length) view_name = <string>parts.shift();

                        let view:any = await apiService.getView(this.object_class, view_type + '.' + view_name);

                        if(!Object.keys(view).length) {
                            // fallback to default view
                            view = await apiService.getView(this.object_class, view_type + '.default');
                            if(!Object.keys(view).length) {
                                throw 'unknown_view';
                            }
                        }

                        // load routes from view, if any
                        if (view.hasOwnProperty('routes') && view.routes.length) {
                            view_routes = view.routes;

                            for (let route of view_routes) {
                                route.label = translationService.resolve(translation, 'view', [this.view_id, 'routes'], route.id, route.label)
                                if (route.hasOwnProperty('visible')) {
                                    let domain = route.visible;

                                    if (typeof domain == 'string') {
                                        domain = JSON.parse(domain);
                                    }

                                    if (Array.isArray(domain) && domain.length) {
                                        // #todo - improve
                                        // get first part of domain as target field
                                        let object_field = domain[0];

                                        if (object_field.length && !object_fields.includes(object_field)) {
                                            object_fields.push(object_field);
                                        }

                                    }
                                }
                                if (route.hasOwnProperty('route')) {
                                    const parts = route.route.split('/');
                                    for (let part of parts) {
                                        if(part.indexOf('object.') >= 0) {
                                            let object_field = part.replace('object.', '');
                                            if (object_field.length && !object_fields.includes(object_field)) {
                                                object_fields.push(object_field);
                                            }
                                        }
                                    }
                                }
                                if (route.hasOwnProperty('context') && route.context.hasOwnProperty('domain')) {
                                    let domain = JSON.stringify(route.context.domain);
                                    let regexp = /object\.([^"]+)/g;
                                    let match = regexp.exec(domain);

                                    while (match) {
                                        if (match.length && !object_fields.includes(match[1])) {
                                            object_fields.push(match[1]);
                                        }
                                        match = regexp.exec(domain);
                                    }
                                }
                            }
                        }
                    }
                    catch (err) {
                        console.warn(err);
                    }

                    // read basic field of targeted object
                    const data: any[] = < Array < any >> await this.api.read(object_class, [object_id], object_fields);
                    this.object = data[0];

                    // 'history' : read modifications history
                    await this.updateHistory();

                    // remove routes that are not part of the current view
                    for(let i = this.object_routes_items.length-1; i >= 0; --i) {
                        let id = this.object_routes_items[i].id;
                        if(!view_routes.find( (e:any) => e.id == id )) {
                            this.object_routes_items.splice(i, 1);
                        }
                    }
                    // build routes, if any
                    for (let route of view_routes) {
                        if (route.hasOwnProperty('visible')) {
                            let domain = route.visible;

                            //  #todo - improve
                            let res = false;

                            let operand = domain[0];
                            let operator = domain[1];
                            let value = domain[2];

                            if (!this.object || !this.object.hasOwnProperty(operand)) continue;

                            operand = this.object[operand];

                            // handle special cases
                            if (operator == '=') {
                                operator = '==';
                            }
                            else if (operator == '<>') {
                                operator = '!=';
                            }

                            if (Array.isArray(value)) {
                                if (operator == 'in') {
                                    res = (value.indexOf(operand) > -1);
                                }
                                else if (operator == 'not in') {
                                    res = (value.indexOf(operand) == -1);
                                }
                            }
                            else {
                                let c_condition = "( '" + operand + "' " + operator + " '" + value + "')";
                                res = < boolean > eval(c_condition);
                            }

                            if (!res) {
                                // remove route, if present
                                let index:any = this.object_routes_items.findIndex( (e:any) => e.id == route.id );
                                if(index >= 0) {
                                    this.object_routes_items.splice(index, 1);
                                }
                                continue;
                            }
                        }
                        // add route, if not present yet
                        if(!this.object_routes_items.find( (e:any) => e.id == route.id )) {
                            this.object_routes_items.push(route);
                        }
                    }

                    // notify parent about if there are routes or not
                    this.updated.emit(!!this.object_routes_items.length);
                }


                // 'alerts' : read alert messages specific to object (checks)
                await this.updateAlerts();

                // 'help' : in all cases, request detailed documentation about the current view
                try {
                    const helper: any = await this.api.fetch('/?get=model_view-help&entity=' + object_class + '&view_id=' + this.view_id + '&lang=' + this.environment.lang);
                    // #todo : use a cache here

                    if (helper && helper.hasOwnProperty('result')) {
                        this.view_description = helper.result;
                    }
                }
                catch (err) {
                    console.warn(err);
                }
            }

            this.cd.detectChanges();

        });

    }

    private async updateHistory() {
        try {
            const collection = await this.api.collect('core\\Log', [
                ['object_id', '=', this.object_id],
                ['object_class', '=', this.object_class],
                ['user_id', '>', 0]
            ], ['action', 'user_id.name'], 'id', 'desc', 0, 10);

            this.latest_changes = collection;
        }
        catch (response) {
            console.warn(response);
        }
    }

    private async updateAlerts() {
        if(this.object_class.length == 0 || this.object_id == 0) {
            this.object_checks_items = [];
            return;
        }
        try {

            const data = await this.api.fetch('/?get=model_collect', {
                entity: "core\\alert\\Message",
                fields: JSON.stringify(["severity", "controller", "message_model_id.name", "message_model_id.label", "message_model_id.description", "links"]),
                domain: JSON.stringify([["object_class", "=", this.object_class], ["object_id", "=", this.object_id]]),
                lang: this.environment.lang
            });

            this.object_checks_items = [];
            // load checks from view, if any
            if (data && data.length) {
                for(let item of data) {
                    item.parsed_links = [];
                    if(item.links) {
                        let links = JSON.parse(item.links);
                        for(let link of links) {
                            let parts = link.replace(/\[([^\]]+)\]\(([^\)]+)\)/, '$2,$1').split(',');
                            item.parsed_links.push({link: parts[0], text: parts[1]});
                        }
                    }
                    this.object_checks_items.push(item);
                }
                // switch to alert pane
                this.selected_tab_id = 'validity-check';
                // notify parent about that pane must be visible
                this.updated.emit(true);
            }
            else {
                this.selected_tab_id = 'object-routes';
            }

        }
        catch (err) {
            console.warn(err);
        }
    }

    public onHelpFullScreen() {
        console.debug('SideMenuComponent::onHelpFullScreen');
        if (screenfull.isEnabled) {
            screenfull.toggle(this.helpFullScreen.nativeElement);
        }
        else {
            console.warn('screenfull not enabled');
        }
    }

    public async onDisconnect() {
        try {
            await this.auth.signOut();
            window.location.href = '/auth';
        }
        catch (err) {
            console.warn('unable to request signout');
        }
    }

    public onUserSettings() {
        // this.router.navigate(['']);
        let descriptor = {
            context: {
            entity: 'lodging\\identity\\User',
            type: 'form',
            name: 'default',
            domain: ['id', '=', this.user.id],
            mode: 'edit',
            purpose: 'view',
            display_mode: 'popup'
            }
        };

        // this.router.navigate(['/']);
        this.context.change(descriptor);
    }

    public async onObjectCheck(item: any) {
        if (item.hasOwnProperty('controller')) {
            try {
                const data = await this.api.fetch('/?get=' + item.controller, {
                    id: this.object_id
                });
                // no error status : nothing went wrong
                this.object_checks_result.title = "Rien à signaler";
                this.object_checks_result.content = [];
            }
            catch (response: any) {
                if (response && response.status != 404) {

                    if (response.hasOwnProperty('status')) {
                        this.object_checks_result.title = "Erreur(s) détectée(s)";
                        if (response.status == 409) {
                            this.object_checks_result.title = "Conflit(s) détecté(s)";
                        }
                    }

                    if (response.hasOwnProperty('error')) {
                        if (response.error.hasOwnProperty('errors')) {
                            for (let key of Object.keys(response.error.errors)) {
                                let msg_id = response.error.errors[key];
                                this.object_checks_result.content.push({
                                type: 'message',
                                message: this.translate.instant(msg_id)
                                });
                            }
                        }
                        else {
                            this.object_checks_result.content = response.error;
                        }
                    }
                }
            }
        }
    }


    public onObjectRoute(item: any) {
        console.debug('AppSideMenuComponent::onObjectRoute', item, this.view_id, this.object_class, this.object_id, this.object);

        let descriptor: any = {};
        let target_id:any = 0;

        if (item.hasOwnProperty('route')) {
            let route = item.route;
            for (let object_field of Object.keys(this.object)) {
                target_id = this.object[object_field];
                // handle m2o sub-ojects (assuming id is always loaded)
                if(typeof target_id == 'object') {
                    target_id = target_id.id;
                }
                route = route.replace('object.' + object_field, target_id);
            }
            descriptor.route = route;
        }

        // route targets another app
        if (item.hasOwnProperty('app')) {
            // interpolate the
            // open link in new tab
            window.open('/'+item.app+'/#'+descriptor.route, '_blank');
            return;
        }

        if (item.hasOwnProperty('context')) {
            let context:any = {...item.context};
            if (context.hasOwnProperty('domain') && Array.isArray(context.domain)) {
                let domain = JSON.stringify(context.domain);
                for (let object_field of Object.keys(this.object)) {
                    target_id = this.object[object_field];
                    // handle m2o sub-ojects (assuming id is always loaded)
                    if(typeof target_id == 'object') {
                        target_id = target_id.id;
                    }
                    domain = domain.replace('object.' + object_field, target_id);
                }
                context.domain = JSON.parse(domain);
            }
            else if(target_id) {
                context.domain = ['id', '=', target_id];
            }
            descriptor.context = context;
        }

        if (Object.keys(descriptor).length) {
            this.context.change(descriptor);
        }

    }

    public async onDismissAlert(alert:any) {
        try {
            const data:any = await this.api.fetch('?do=core_alert_dismiss&id='+alert.id);
            // reload
            this.updateAlerts();
        }
        catch(response) {
            // ignore errors
        }
    }

    public getAlertIcon(alert: any) {
        switch(alert.severity) {
            case 'notice': return 'info_outline';
            case 'warning': return 'star_outline';
            case 'important': return 'warning_amber';
            case 'urgent': return 'priority_high';
        }
        return 'info_outline';
    }

    public getAlertColor(alert:any) {
        switch(alert.severity) {
            case 'notice': return '#4e4ad6';
            case 'warning': return '#41c01b';
            case 'important': return '#eead26';
            case 'urgent': return '#ff0000';
        }
        return 'black';


    }

}
