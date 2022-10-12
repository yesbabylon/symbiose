import { Component, OnInit, AfterViewInit, ChangeDetectorRef, ViewChild, ElementRef, HostListener, OnDestroy } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Subject } from 'rxjs';
import { delay, takeUntil } from 'rxjs/operators';
import { ContextService } from 'sb-shared-lib';


@Component({
  selector: 'identity',
  templateUrl: 'identity.component.html',
  styleUrls: ['identity.component.scss']
})
export class IdentityComponent implements OnInit, AfterViewInit, OnDestroy {

    // rx subject for unsubscribing subscriptions on destroy
    private ngUnsubscribe = new Subject<void>();
    
    public ready: boolean = false;

    // flag telling if the route to which the component is associated with is currently active (amongst routes defined in first parent routing module)
    private active:boolean = false;

    private default_descriptor: any = {
        // route: '/booking/object.id',
        context: {
            entity: 'lodging\\identity\\Identity',
            view:   'form.default'
        }
    };


    private identity_id: number = 0;

    constructor(
        private route: ActivatedRoute,
        private context: ContextService
    ) {}

    public ngOnDestroy() {
        console.log('BookingComponent::ngOnDestroy');
        this.ngUnsubscribe.next();
        this.ngUnsubscribe.complete();
    }

    public ngAfterViewInit() {
        console.log('IdentityComponent::ngAfterViewInit');

        this.context.setTarget('#sb-container-identity');
        this.default_descriptor.context.domain = ["id", "=", this.identity_id];
        this.context.change(this.default_descriptor);

        this.active = true;
    }

    public ngOnInit() {
        console.log('IdentityComponent::ngOnInit');

        this.context.ready.pipe(takeUntil(this.ngUnsubscribe)).subscribe( (ready:boolean) => {
            this.ready = ready;
        });

        /*
            subscribe only once.
            routing module is AppRoutingModule, siblings are /planning and /bookings
        */
        this.route.params.pipe(takeUntil(this.ngUnsubscribe)).subscribe( async (params) => {
            this.identity_id = <number> parseInt(params['identity_id'], 10);
        });
    }

}