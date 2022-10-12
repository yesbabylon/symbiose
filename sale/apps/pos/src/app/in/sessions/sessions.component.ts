import { Component, OnInit, AfterViewInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Subject } from 'rxjs';
import { delay, takeUntil } from 'rxjs/operators';
import { ContextService } from 'sb-shared-lib';


@Component({
  selector: 'sessions',
  templateUrl: 'sessions.component.html',
  styleUrls: ['sessions.component.scss']
})
export class SessionsComponent implements OnInit {

    // rx subject for unsubscribing subscriptions on destroy
    private ngUnsubscribe = new Subject<void>();
    
    public ready: boolean = false;

    // flag telling if the route to which the component is associated with is currently active (amongst routes defined in first parent routing module)
    private active = false;

    private default_descriptor: any = {
        // route is current ng route
        context: {
            entity: 'lodging\\sale\\pos\\CashdeskSession',
            view: "list.default",
            order: "id",
            sort: "desc"
        }
    };

    constructor(
        private route: ActivatedRoute,
        private context: ContextService
    ) {}

    public ngOnDestroy() {
        console.log('SessionsComponent::ngOnDestroy');
        this.ngUnsubscribe.next();
        this.ngUnsubscribe.complete();
    }

    public ngAfterViewInit() {
        console.log('SessionsComponent::ngAfterViewInit');

        this.context.setTarget('#sb-container-sessions');
        const descriptor = this.context.getDescriptor();
        if(!Object.keys(descriptor.context).length) {
            console.log('SessionsComponent : requesting change', this.default_descriptor);
            this.context.change(this.default_descriptor);
        }

        this.active = true;
    }

    public ngOnInit() {
        console.log('SessionsComponent::ngOnInit');

        this.context.ready.pipe(takeUntil(this.ngUnsubscribe)).subscribe( (ready:boolean) => {
            this.ready = ready;
        });

        // once view is ready, subscribe to route changes
        this.route.params.pipe(takeUntil(this.ngUnsubscribe)).subscribe( async (params:any) => {
            // no params for this route(/bookings)
        });

        // if no context or all contexts have been closed, re-open default context (wait for route init)
        this.context.getObservable().pipe(takeUntil(this.ngUnsubscribe)).subscribe( () => {
            console.log('SessionsComponent:: received context change');
            const descriptor = this.context.getDescriptor();
            if(descriptor.hasOwnProperty('context') && !Object.keys(descriptor.context).length && this.active) {
                this.ready = false;
                this.context.change(this.default_descriptor);
            }
        });
    }

}