import { Component, Inject, OnInit } from '@angular/core';
import { FormControl, Validators } from '@angular/forms';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { debounceTime } from 'rxjs/operators';

interface vmModel {
    type: {
        formControl: FormControl
    },
    no_expiry: {
        formControl: FormControl
    },
    free_rental_units: {
        formControl: FormControl
    },
    description: {
        formControl: FormControl
    }
};


@Component({
    selector: 'planning-calendar-consumption-creation-dialog',
    templateUrl: './consumption.component.html',
    styleUrls: ['./consumption.component.scss']
})
export class ConsumptionCreationDialog implements OnInit {

    public vm: vmModel;

    private type: string = '';
    private customer_identity_id: number = 0;
    private free_rental_units: boolean = false;
    private no_expiry: boolean = false;
    private description: string = '';

    constructor(
        public dialogRef: MatDialogRef<ConsumptionCreationDialog>,
        @Inject(MAT_DIALOG_DATA) public data: any
    ) {

        this.vm = {
            type: {
                formControl: new FormControl('', Validators.required)
            },
            no_expiry: {
                formControl: new FormControl()
            },
            free_rental_units: {
                formControl: new FormControl()
            },
            description: {
                formControl: new FormControl()
            }
        }
    }

    public ngOnInit() {
        this.vm.type.formControl.valueChanges.subscribe( (value:string) => {
            this.type = value;
        });
        this.vm.no_expiry.formControl.valueChanges.subscribe( (value:boolean) => {
            this.no_expiry = value;
        });
        this.vm.free_rental_units.formControl.valueChanges.subscribe( (value:boolean) => {
            this.free_rental_units = value;
        });
        this.vm.description.formControl.valueChanges.pipe( debounceTime(300) ).subscribe( (value:string) => {
            this.description = value;
        });

    }

    public selectIdentity(identity:any) {
        console.log('identity selected', identity, );
        this.customer_identity_id = identity.id;
    }

    public onsubmit() {
        if(this.type == '') {
            this.vm.type.formControl.markAsTouched();
            return;
        }

        if(this.type == 'book' && !this.customer_identity_id) {
            return;
        }
        this.dialogRef.close({
            date_from: this.data.date_from,
            date_to: this.data.date_to,
            rental_unit_id: this.data.rental_unit_id,
            customer_identity_id: this.customer_identity_id,
            type: this.type,
            no_expiry: this.no_expiry,
            free_rental_units: this.free_rental_units,
            description: this.description
        });
    }
}