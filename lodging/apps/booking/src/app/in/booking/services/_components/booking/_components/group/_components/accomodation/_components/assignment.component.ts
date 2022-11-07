import { Component, Inject, OnInit, OnChanges, NgZone, Output, Input, EventEmitter, SimpleChanges, AfterViewInit, ViewChild, AfterContentInit } from '@angular/core';
import { AuthService, ApiService, ContextService, TreeComponent } from 'sb-shared-lib';

import { FormControl, Validators } from '@angular/forms';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatAutocomplete, MatAutocompleteSelectedEvent } from '@angular/material/autocomplete';

import { BookingPriceAdapter } from '../../../../../_models/booking_price_adapter.model';
import { BookingLineGroup } from '../../../../../_models/booking_line_group.model';
import { BookingAccomodationAssignment } from '../../../../../_models/booking_accomodation_assignment.model';
import { BookingAccomodation } from '../../../../../_models/booking_accomodation.model';
import { Booking } from '../../../../../_models/booking.model';


import {MatSnackBar} from '@angular/material/snack-bar';


interface BookingGroupAccomodationAssignmentComponentsMap {
};

@Component({
  selector: 'booking-services-booking-group-accomodation-assignment',
  templateUrl: 'assignment.component.html',
  styleUrls: ['assignment.component.scss']
})
export class BookingServicesBookingGroupAccomodationAssignmentComponent extends TreeComponent<BookingAccomodationAssignment, BookingGroupAccomodationAssignmentComponentsMap> implements OnInit, OnChanges, AfterContentInit, AfterViewInit  {
    // server-model relayed by parent
    @Input() set model(values: any) { this.update(values) }
    @Input() accomodation: BookingAccomodation;
    @Input() group: BookingLineGroup;
    @Input() booking: Booking;
    @Output() updated = new EventEmitter();
    @Output() deleted = new EventEmitter();

    public ready: boolean = false;

    public params:any = {};

    public qtyFormControl: FormControl;
    public assignmentQtyOpen: boolean = false;


    constructor(
        private api: ApiService,
        private auth: AuthService,
        private dialog: MatDialog,
        private zone: NgZone,
        private snack: MatSnackBar
    ) {
        super( new BookingAccomodationAssignment() );
        this.qtyFormControl = new FormControl('', [Validators.required, this.validateQty.bind(this)]);
    }

    private validateQty(c: FormControl) {
        // qty cannot be bigger than the rental unit capacity
        // qty cannot be bigger than the number of persons
        return (
                this.instance &&
                this.group &&
                c.value <= this.instance.rental_unit_id.capacity &&
                c.value <= this.group.nb_pers
            ) ? null : {
                validateQty: {
                    valid: false
                }
            };
    }

    public ngOnChanges(changes: SimpleChanges) {
        if(changes.model) {
        }
    }

    public ngAfterContentInit() {
    }

    public ngAfterViewInit() {
        // init local componentsMap
        let map:BookingGroupAccomodationAssignmentComponentsMap = {};
        this.componentsMap = map;

        this.params = {
            booking_line_group_id: this.instance.booking_line_group_id,
            product_model_id: this.accomodation.product_model_id.id
        }
    }


    public ngOnInit() {
        this.ready = true;
    }

    public async update(values:any) {
        console.log('assignment update', values);
        super.update(values);
        // assign VM values
        this.qtyFormControl.setValue(this.instance.qty);
    }

    public ondelete() {
        this.deleted.emit();
    }

    public async onchangeQty(event:any) {
        if(this.qtyFormControl.invalid) {
            this.qtyFormControl.markAsTouched();
            this.snack.open("Quantité supérieure à la capacité de l'unité ou à la taille du groupe.");
            return;
        }
        let qty = event.srcElement.value;
        this.qtyFormControl.setValue(qty);
        this.assignmentQtyOpen = false;
        let prev_qty = this.instance.qty;
        this.instance.qty = this.qtyFormControl.value;
        // notify back-end about the change
        try {
            await this.api.update(this.instance.entity, [this.instance.id], {qty: this.qtyFormControl.value});
            // relay change to parent component
            this.updated.emit();
        }
        catch(response) {
            this.instance.qty = prev_qty;
            this.qtyFormControl.setValue(prev_qty);
            this.api.errorFeedback(response);
        }
    }

    public onclickAssignmentQty() {
        this.assignmentQtyOpen = true;
    }


}