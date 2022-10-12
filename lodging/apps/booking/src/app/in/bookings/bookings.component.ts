import { Component, OnInit, AfterViewInit, OnDestroy, HostListener, ElementRef } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { ContextService } from 'sb-shared-lib';


@Component({
  selector: 'bookings',
  templateUrl: 'bookings.component.html',
  styleUrls: ['bookings.component.scss']
})
export class BookingsComponent implements OnInit, AfterViewInit, OnDestroy {

    // rx subject for unsubscribing subscriptions on destroy
    private ngUnsubscribe = new Subject<void>();

    public ready: boolean = false;

    // flag telling if the route to which the component is associated with is currently active (amongst routes defined in first parent routing module)
    private active = false;

    private default_descriptor: any = {
        // route is current ng route
        context: {
            entity: 'lodging\\sale\\booking\\Booking',
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
        console.log('BookingsComponent::ngOnDestroy');
        this.ngUnsubscribe.next();
        this.ngUnsubscribe.complete();
    }

    public ngAfterViewInit() {
        console.log('BookingsComponent::ngAfterViewInit');

        this.context.setTarget('#sb-container-bookings');
        const descriptor = this.context.getDescriptor();
        if(!Object.keys(descriptor.context).length) {
            console.log('BookingComponent : requesting change', this.default_descriptor);
            this.context.change(this.default_descriptor);
        }

        this.active = true;
    }

    public ngOnInit() {
        console.log('BookingsComponent::ngOnInit');

        this.context.ready.pipe(takeUntil(this.ngUnsubscribe)).subscribe( (ready:boolean) => {
            this.ready = ready;
        });

        // once view is ready, subscribe to route changes
        this.route.params.pipe(takeUntil(this.ngUnsubscribe)).subscribe( async (params:any) => {
            // no params for this route(/bookings)
        });

        // if no context or all contexts have been closed, re-open default context (wait for route init)
        this.context.getObservable().pipe(takeUntil(this.ngUnsubscribe)).subscribe( () => {
            console.log('BookingsComponent:: received context change');
            const descriptor = this.context.getDescriptor();
            if(descriptor.hasOwnProperty('context') && !Object.keys(descriptor.context).length && this.active) {
                this.ready = false;
                this.context.change(this.default_descriptor);
            }
        });
    }

}