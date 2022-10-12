import { Component, Inject, OnInit, OnChanges, NgZone, Output, Input, EventEmitter, SimpleChanges, AfterViewInit, ViewChild } from '@angular/core';
import { AuthService, ApiService, ContextService, TreeComponent } from 'sb-shared-lib';

import { FormControl, Validators } from '@angular/forms';
import { MatAutocomplete, MatAutocompleteSelectedEvent } from '@angular/material/autocomplete';

import { BookingLineGroup } from '../../../../_models/booking_line_group.model';
import { BookingMealPref } from '../../../../_models/booking_mealpref.model';
import { Booking } from '../../../../_models/booking.model';


import {MatSnackBar} from '@angular/material/snack-bar';

import { Observable, ReplaySubject, BehaviorSubject } from 'rxjs';

import { find, map, mergeMap, startWith, debounceTime, debounce } from 'rxjs/operators';


interface BookingGroupMealPrefComponentsMap {
};

interface vmModel {
    type: {
        formControl: FormControl
    },
    pref: {
        formControl: FormControl
    },
    qty: {
        formControl: FormControl
    }
};

@Component({
  selector: 'booking-services-booking-group-mealpref',
  templateUrl: 'mealpref.component.html',
  styleUrls: ['mealpref.component.scss']
})
export class BookingServicesBookingGroupMealPrefComponent extends TreeComponent<BookingMealPref, BookingGroupMealPrefComponentsMap> implements OnInit, OnChanges, AfterViewInit  {
    // server-model relayed by parent
    @Input() set model(values: any) { this.update(values) }
    @Input() preference: BookingMealPref;
    @Input() group: BookingLineGroup;
    @Input() booking: Booking;
    @Output() updated = new EventEmitter();
    @Output() deleted = new EventEmitter();

    public ready: boolean = false;

    public vm: vmModel;

    constructor(
        private api: ApiService,
        private auth: AuthService,
        private zone: NgZone,
        private snack: MatSnackBar
    ) {
        super( new BookingMealPref() );

        this.vm = {
            type: {
                formControl:    new FormControl(),
            },
            pref: {
                formControl:    new FormControl(),
            },
            qty: {
                formControl:    new FormControl('', [Validators.required, this.validateQty.bind(this)]),
            }
        };
    }

    private validateQty(c: FormControl) {
        // qty cannot be zero
        // qty cannot be bigger than the number of persons
        return (this.instance && this.group &&
            c.value > 0 && c.value <= this.group.nb_pers ) ? null : {
            validateQty: {
                valid: false
            }
        };
    }

    public ngOnChanges(changes: SimpleChanges) {
        if(changes.model) {
        }
    }

    public ngAfterViewInit() {
        // init local componentsMap
        let map:BookingGroupMealPrefComponentsMap = {
        };
        this.componentsMap = map;
    }


    public ngOnInit() {

        this.vm.qty.formControl.valueChanges.pipe(debounceTime(500)).subscribe( () => {
            if(this.vm.qty.formControl.invalid) {
                this.vm.qty.formControl.markAsTouched();
                return;
            }

            this.onchange();
        });

        this.vm.type.formControl.valueChanges.pipe(debounceTime(500)).subscribe( () => this.onchange() );

        this.vm.pref.formControl.valueChanges.pipe(debounceTime(500)).subscribe( () => this.onchange() );

        this.ready = true;
    }

    public async update(values:any) {
        super.update(values);
        // assign VM values
        this.vm.qty.formControl.setValue(this.instance.qty);
        this.vm.type.formControl.setValue(this.instance.type);
        this.vm.pref.formControl.setValue(this.instance.pref);
    }

    private async onchange() {
        // notify back-end about the change
        if(this.instance.qty != this.vm.qty.formControl.value || this.instance.type != this.vm.type.formControl.value || this.instance.pref != this.vm.pref.formControl.value) {
            try {
                await this.api.update(this.instance.entity, [this.instance.id], {
                    type: this.vm.type.formControl.value,
                    pref: this.vm.pref.formControl.value,
                    qty: this.vm.qty.formControl.value
                });
                // do not relay change to parent component
            }
            catch(response) {
                this.api.errorFeedback(response);
            }
        }
    }


}