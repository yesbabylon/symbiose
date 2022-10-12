import { Inject, Injectable } from '@angular/core';

import { ReplaySubject } from 'rxjs';
import * as $ from 'jquery';
import { DOCUMENT } from '@angular/common';

import { NavigationEnd, Router } from '@angular/router';
import { EqualUIService } from './eq.service';

declare global {
  interface Window { context: any; }
}

@Injectable({
  providedIn: 'root'
})

/**
 * This service offers a getObservable() method allowing to access an Observable that any component can subscribe to.
 * Subscribers will allways receive the latest emitted value as a Context object.
 *
 */
export class ContextService {

    private observable: ReplaySubject<any>;
    public ready: ReplaySubject<any>;

    private route: string = '';
    private context: any = {};

    private timeout: any;

    private target: string = '#sb-container';


    /**
     * Provide observable for subsribing on contexts updates.
     * #memo - New subscribers will receive latest value set (history depth of 1).
     */
    public getObservable() {
        return this.observable;
    }

    /**
     * Provide current descriptor
     */
    public getDescriptor() {
        return {route: this.route, context: this.context};
    }

    public setTarget(target: string) {
        this.target = target;
    }

    constructor(
        private router: Router,
        @Inject(DOCUMENT) private document: Document,
        private eq:EqualUIService
    ) {

        this.ready = new ReplaySubject<any>(1);
        this.observable = new ReplaySubject<any>(1);

        /*
            listen to context changes from eQ: notify components that need sync (e.g. sidemenu)
        */

        this.eq.addSubscriber(['open', 'close'], (context:any) => {
            console.debug('ContextService : eQ context open/close', context);
            this.change({context: {...context}, context_only: true});
        });

        this.eq.addSubscriber(['updated'], () => {
            console.debug('ContextService : eQ context updated');
            this.observable.next({context: {...this.context}});
        });

        this.eq.addSubscriber(['navigate'], (descriptor:any) => {
            console.debug('ContextService : eQ navigate');
            this.change({...descriptor, context_only: true});
        });

        // listen to route changes and keep current route
        this.router.events.subscribe( (event: any) => {
            if (event instanceof NavigationEnd) {
                console.debug('ContextService : route change', event);
                this.route = event.url;
                // this.context = {};
                // this.observable.next(this.getDescriptor());
                this.observable.next({route: this.route, context: {}});
                // if no controller requests a change within 500ms, change to current context
                this.timeout = setTimeout( () => {
                    this.timeout = undefined;
                    this.change({context: this.context});
                }, 500);
            }
        });

  }

   /**
    * Request a change by providing a descriptor that holds a route and/or a context.
    * Changing route and context are mutually exclusive operations.
    * If both are requested, local descriptor is updated and route is changed. A second call is made 500ms later for requesting a eQ context change.
    *
    * @param descriptor  Descriptor might contain both route and context objects.
    */
    public async change(descriptor:any) {

        // if a call is pending, abort it
        if(this.timeout) {
            clearTimeout(this.timeout);
        }

        // notify subscribers that we're loading something
        this.ready.next(false);

        /*
            pass-1 update the context part of the local descriptor (to allow subscribers to route change to get the current value)
        */
        if(descriptor.hasOwnProperty('context')) {
            console.debug("ContextService: received context change request", descriptor, this);
            this.context = {...descriptor.context};
        }

        // navigate to route, if requested (a route is present)
        if(descriptor.hasOwnProperty('route') && descriptor.route != this.route) {
            console.debug("ContextService: received route change request", descriptor, this);
            // make sure no eQ context is left open
            await this.eq.closeAll();
            // changing route resets the context
            if(!descriptor.hasOwnProperty('context')) {
                this.context = {};
            }
            // change route (this will notify Router and ActivatedRoute subscribers)
            this.router.navigate([descriptor.route]);
        }
        /*
            pass-2 switch context, if requested
            context might depend on route change (controllers can request a change of target after being instanciated)
        */
        // else if(descriptor.hasOwnProperty('context') && Object.keys(descriptor.context).length) {
        else if(descriptor.hasOwnProperty('context')) {
            console.debug("ContextService: processing received context", descriptor);
            // ignore route, if present
            let context:any = {...descriptor.context};
            // inject current target (might have been updated by distinct controllers)
            context.target = this.target;
            let context_silent = false;
            if(descriptor.hasOwnProperty('context_silent')) {
                context_silent = descriptor.context_silent;
            }
            // request eQ to open context
            if(!descriptor.hasOwnProperty('context_only')) {
                if(context.hasOwnProperty('display_mode') && context.display_mode == 'popup') {
                    let dom_container = 'body';
                    if(context.hasOwnProperty('dom_container')) {
                        dom_container = context.dom_container;
                    }
                    await this.eq.popup(context, dom_container);
                }
                else {
                    console.debug("requesting context opening", context);
                    await this.eq.open(context);
                }
            }
            // notify subscribers
            this.ready.next(true);
            this.observable.next({route: this.route, context: context, context_silent: context_silent});
        }
        else {
            // nothing to do: notify subscribers
            this.ready.next(true);
        }
    }

}