import { Component, Inject, OnInit, OnChanges, NgZone, Output, Input, ViewChildren, QueryList, AfterViewInit, SimpleChanges } from '@angular/core';

import { ApiService, ContextService, TreeComponent, RootTreeComponent } from 'sb-shared-lib';

import { Observable, ReplaySubject, BehaviorSubject, async } from 'rxjs';

import {MatSnackBar} from '@angular/material/snack-bar';

import { BookingServicesBookingGroupComponent } from './_components/group/group.component'
import { Booking } from './_models/booking.model';

// declaration of the interface for the map associating relational Model fields with their components
interface BookingComponentsMap {
    booking_lines_groups_ids: QueryList<BookingServicesBookingGroupComponent>
};


@Component({
  selector: 'booking-services-booking',
  templateUrl: 'booking.component.html',
  styleUrls: ['booking.component.scss']
})
export class BookingServicesBookingComponent extends TreeComponent<Booking, BookingComponentsMap> implements RootTreeComponent, OnInit, OnChanges, AfterViewInit {
    @ViewChildren(BookingServicesBookingGroupComponent) bookingServicesBookingGroups: QueryList<BookingServicesBookingGroupComponent>;
    @Input() booking_id: number;

    public ready: boolean = false;
    public loading: boolean = true;

    constructor(
        private api: ApiService,
        private context: ContextService
    ) {
        super( new Booking() );
    }

    ngOnChanges(changes: SimpleChanges) {
        if(changes.booking_id && this.booking_id > 0) {
            try {
                this.load(this.booking_id);
                this.ready = true;
            }
            catch(error) {
                console.warn(error);
            }
        }
    }

    public ngAfterViewInit() {
        // init local componentsMap
        let map:BookingComponentsMap = {
            booking_lines_groups_ids: this.bookingServicesBookingGroups
        };
        this.componentsMap = map;
    }

    public ngOnInit() {
    }

    /**
     * Load an Booking object using the sale_pos_order_tree controller
     * @param booking_id
     */
    public load(booking_id: number) {
        if(booking_id > 0) {
            // #memo - init generates multiple load which badly impacts the UX
            // this.loading = true;
            this.api.fetch('/?get=lodging_booking_tree', {id:booking_id})
            .then( (result:any) => {
                if(result) {
                    console.debug('reveived updated booking', result);
                    this.update(result);
                    this.loading = false;
                }

            })
            .catch(response => {
                console.warn(response);
            });
        }
    }

    /**
     *
     * @param values
     */
    public update(values:any) {
        super.update(values);
    }


    public async oncreateGroup() {
        try {
            let rate_class_id = 4;
            // default rate class is the rate_class of the customer of the booking
            if(this.instance.customer_id.rate_class_id) {
                rate_class_id = this.instance.customer_id.rate_class_id;
            }
            let sojourn_type_id = this.instance.center_id.sojourn_type_id;

            let values:any = {
                name: "Séjour " + this.instance.center_id.name,
                order: this.instance.booking_lines_groups_ids.length + 1,
                booking_id: this.instance.id,
                rate_class_id: rate_class_id,
                sojourn_type_id: sojourn_type_id,
                date_from: this.instance.date_from.toISOString(),
                date_to: this.instance.date_to.toISOString()
            };

            if(this.instance.status != 'quote') {
                values.name = "Suppléments";
                values.is_extra = true;
                values.date_from = new Date().toISOString();
                values.date_to =  new Date().toISOString();
            }
            const group = await this.api.create("lodging\\sale\\booking\\BookingLineGroup", values);
            // reload booking tree
            this.load(this.instance.id);
        }
        catch(response) {
            this.api.errorFeedback(response);
        }
    }

    public async ondeleteGroup(group_id:number) {
        try {
            await this.api.remove("lodging\\sale\\booking\\BookingLineGroup", [group_id], true);
            // reload booking tree
            this.load(this.instance.id);
        }
        catch(response) {
            this.api.errorFeedback(response);
        }
    }

    public onupdateGroup() {
        // reload booking tree
        this.load(this.instance.id);
    }

    public ondropGroup(event:any) {

    }
}