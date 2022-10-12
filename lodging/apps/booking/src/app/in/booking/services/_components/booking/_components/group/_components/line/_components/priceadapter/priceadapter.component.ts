import { Component, Inject, OnInit, OnChanges, NgZone, Output, Input, EventEmitter, SimpleChanges, AfterViewInit } from '@angular/core';
import { AuthService, ApiService, ContextService, TreeComponent } from 'sb-shared-lib';

import { BookingPriceAdapter } from '../../../../../../_models/booking_price_adapter.model';
import { BookingLineGroup } from '../../../../../../_models/booking_line_group.model';
import { BookingLine } from '../../../../../../_models/booking_line.model';
import { Booking } from '../../../../../../_models/booking.model';


interface BookingGroupLinePriceadapterComponentsMap {
};


@Component({
  selector: 'booking-services-booking-group-line-priceadapter',
  templateUrl: 'priceadapter.component.html',
  styleUrls: ['priceadapter.component.scss']
})
export class BookingServicesBookingGroupLinePriceadapterComponent extends TreeComponent<BookingPriceAdapter, BookingGroupLinePriceadapterComponentsMap> implements OnInit, OnChanges, AfterViewInit  {
    // server-model relayed by parent
    @Input() set model(values: any) { this.update(values) }
    @Input() parent: BookingLine;
    @Input() group: BookingLineGroup;

    public ready: boolean = false;

    constructor() {
        super( new BookingPriceAdapter() );
    }


    public ngOnChanges(changes: SimpleChanges) {
        if(changes.model) {
        }
    }

    public ngAfterViewInit() {
        // init local componentsMap
        let map:BookingGroupLinePriceadapterComponentsMap = {
        };
        this.componentsMap = map;
    }

    public ngOnInit() {
        this.ready = true;
    }

    public async update(values:any) {
        super.update(values);
    }

}