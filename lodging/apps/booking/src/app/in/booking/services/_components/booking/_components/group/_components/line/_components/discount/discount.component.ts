import { Component, Inject, OnInit, OnChanges, NgZone, Output, Input, EventEmitter, SimpleChanges, AfterViewInit } from '@angular/core';
import { AuthService, ApiService, ContextService, TreeComponent } from 'sb-shared-lib';

import { FormControl, Validators } from '@angular/forms';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';

import { BookingPriceAdapter } from '../../../../../../_models/booking_price_adapter.model';
import { BookingLineGroup } from '../../../../../../_models/booking_line_group.model';
import { BookingLine } from '../../../../../../_models/booking_line.model';
import { Booking } from '../../../../../../_models/booking.model';


import {MatSnackBar} from '@angular/material/snack-bar';

import { Observable, ReplaySubject, BehaviorSubject } from 'rxjs';

import { find, map, mergeMap, startWith, debounceTime } from 'rxjs/operators';


interface BookingGroupLineDiscountComponentsMap {
};


interface vmModel {
  value: {
    value:          number,
    formControl:    FormControl
  },
  type: {
    value:          string,
    formControl:    FormControl
  }
}

@Component({
  selector: 'booking-services-booking-group-line-discount',
  templateUrl: 'discount.component.html',
  styleUrls: ['discount.component.scss']
})
export class BookingServicesBookingGroupLineDiscountComponent extends TreeComponent<BookingPriceAdapter, BookingGroupLineDiscountComponentsMap> implements OnInit, OnChanges, AfterViewInit  {
    // server-model relayed by parent
    @Input() set model(values: any) { this.update(values) }
    @Input() parent: BookingLine;
    @Input() group: BookingLineGroup;
    @Output() updated = new EventEmitter();
    @Output() deleted = new EventEmitter();

    public ready: boolean = false;

    public vm: vmModel;

    constructor(
        private api: ApiService,
        private auth: AuthService,
        private dialog: MatDialog,
        private zone: NgZone,
        private snack: MatSnackBar
    ) {
        super( new BookingPriceAdapter() );

        this.vm = {
            value: {
                value:          0.0,
                formControl:    new FormControl('', Validators.required)
            },
            type: {
                value:          '',
                formControl:    new FormControl('', Validators.required),
            }
        };
    }


    public ngOnChanges(changes: SimpleChanges) {
        if(changes.model) {
        }
    }

    public ngAfterViewInit() {
        // init local componentsMap
        let map:BookingGroupLineDiscountComponentsMap = {
        };
        this.componentsMap = map;
    }


    public ngOnInit() {
        this.ready = true;
    }

    public async update(values:any) {
        super.update(values);
        // assign VM values
        if(this.instance.type == 'percent' && this.instance.value < 1){
            this.vm.value.formControl.setValue( parseFloat((this.instance.value * 100).toFixed(3)) );
        }
        else {
            this.vm.value.formControl.setValue( parseFloat(this.instance.value.toFixed(2)) );
        }
        this.vm.type.formControl.setValue(this.instance.type == 'amount');
    }

    public async onchangeType(event:any) {
        console.log(event);
        // true is €, false, is %
        let type = (event)?"amount":"percent";

        if(type == this.instance.type) {
            return;
        }
        let value = this.vm.value.formControl.value;
        if(type == 'percent' && value >= 1) {
            value /= 100;
        }
        try {
            await this.api.update(this.instance.entity, [this.instance.id], {type: type, value: value});
            // relay change to parent component
            this.updated.emit();
        }
        catch(response) {
            this.api.errorFeedback(response);
        }
    }

    public async onchangeValue(event:any) {
        if(this.vm.value.formControl.invalid) {
            return;
        }

        try {
            let value = this.vm.value.formControl.value;
            if(this.vm.type.formControl.value == false && value >= 1) {
                value /= 100;
            }
            if(value == this.instance.value) {
                return;
            }
            await this.api.update(this.instance.entity, [this.instance.id], {value: value});
            // relay change to parent component
            this.updated.emit();
        }
        catch(response) {
            this.api.errorFeedback(response);
        }

    }

}