import { Component, OnInit, AfterViewInit, ChangeDetectorRef, ViewChild, ElementRef, HostListener, OnDestroy } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { delay } from 'rxjs/operators';
import { ContextService } from 'sb-shared-lib';


@Component({
  selector: 'invoice',
  templateUrl: 'invoice.component.html',
  styleUrls: ['invoice.component.scss']
})
export class InvoiceComponent implements OnInit, AfterViewInit, OnDestroy {
    // @ViewChild('sbContainer') sbContainer: ElementRef;
    @HostListener('unloaded')
    ngOnDestroy() {
        console.log('BookingComponent::ngOnDestroy');
        this.active = false;
    }

    public ready: boolean = false;

    // flag telling if the route to which the component is associated with is currently active (amongst routes defined in first parent routing module)
    private active:boolean = false;

    private default_descriptor: any = {
        // route: '/booking/object.id',
        context: {
            entity: 'lodging\\sale\\booking\\Booking',
            view:   'form.default'
        }
    };


    private booking_id: number = 0;

    constructor(
        private route: ActivatedRoute,
        private context: ContextService
    ) {}


    public ngAfterViewInit() {
        console.log('BookingComponent::ngAfterViewInit');

        this.context.setTarget('#sb-container-booking');

        const descriptor = this.context.getDescriptor();
        if(!Object.keys(descriptor.context).length) {
            this.default_descriptor.context.domain = ["id", "=", this.booking_id];
            this.context.change(this.default_descriptor);
        }

        this.active = true;
    }

    public ngOnInit() {
        console.log('BookingComponent::ngOnInit');

        this.context.ready.subscribe( (ready:boolean) => {
            this.ready = ready;
        });

        /*
            subscribe only once.
            routing module is AppRoutingModule, siblings are /planning and /bookings
        */
        this.route.params.subscribe( async (params) => {            
            this.booking_id = <number> parseInt(params['booking_id'], 10);            
        });
    }

}