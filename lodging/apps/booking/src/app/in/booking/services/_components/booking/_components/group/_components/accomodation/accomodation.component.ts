import { Component, OnInit, AfterViewInit, Input, Output, EventEmitter, ChangeDetectorRef, ViewChildren, QueryList, Host, OnChanges, SimpleChanges } from '@angular/core';
import { FormControl, Validators } from '@angular/forms';

import { ApiService, ContextService, TreeComponent } from 'sb-shared-lib';
import { BookingLineGroup } from '../../../../_models/booking_line_group.model';
import { BookingAccomodation } from '../../../../_models/booking_accomodation.model';
import { Booking } from '../../../../_models/booking.model';
import { RentalUnitClass } from 'src/app/model/rental.unit.class';
import { Observable, ReplaySubject } from 'rxjs';
import { BookingServicesBookingGroupAccomodationAssignmentComponent } from './_components/assignment.component';

// declaration of the interface for the map associating relational Model fields with their components
interface BookingLineAccomodationComponentsMap {
    rental_unit_assignments_ids: QueryList<BookingServicesBookingGroupAccomodationAssignmentComponent>
};

@Component({
    selector: 'booking-services-booking-group-rentalunitassignment',
    templateUrl: 'accomodation.component.html',
    styleUrls: ['accomodation.component.scss']
})
export class BookingServicesBookingGroupAccomodationComponent extends TreeComponent<BookingAccomodation, BookingLineAccomodationComponentsMap> implements OnInit, OnChanges, AfterViewInit  {
    // server-model relayed by parent
    @Input() set model(values: any) { this.update(values) }
    @Input() group: BookingLineGroup;
    @Input() booking: Booking;

    @Output() updated = new EventEmitter();
    @Output() deleted = new EventEmitter();

    @ViewChildren(BookingServicesBookingGroupAccomodationAssignmentComponent) BookingServicesBookingGroupAccomodationAssignmentComponents: QueryList<BookingServicesBookingGroupAccomodationAssignmentComponent>;


    public ready: boolean = false;

    public rentalunits: RentalUnitClass[] = [];

    public selectedRentalUnits: number[] = [];

    constructor(
        private cd: ChangeDetectorRef,
        private api: ApiService,
        private context: ContextService
    ) {
        super( new BookingAccomodation() );
    }

    public ngOnChanges(changes: SimpleChanges) {
        if(changes.model) {


        }
    }

    public ngAfterViewInit() {
        // init local componentsMap
        let map:BookingLineAccomodationComponentsMap = {
            rental_unit_assignments_ids: this.BookingServicesBookingGroupAccomodationAssignmentComponents
        };
        this.componentsMap = map;
        this.refreshAvailableRentalUnits();
    }

    public async ngOnInit() {
        this.ready = true;
    }

    public async refreshAvailableRentalUnits() {
        // reset rental units listing
        this.rentalunits.splice(0);
        try {
            // retrieve rental units available for assignment
            const data = await this.api.fetch('/?get=lodging_booking_rentalunits', {
                booking_line_group_id: this.instance.booking_line_group_id,
                product_model_id: this.instance.product_model_id.id
            });
            for(let item of data) {
                this.rentalunits.push(<RentalUnitClass> item);
            }
        }
        catch(response) {
            this.api.errorFeedback(response);
        }
    }

    public async update(values:any) {
        console.log('accomodation update', values);
        super.update(values);
    }




    /**
     * Add a rental unit assignment
     */
    /*
    public async oncreateAssignment() {
        try {
            const assignment:any = await this.api.create("lodging\\sale\\booking\\SojournProductModelRentalUnitAssignement", {
                qty: 1,
                booking_id: this.booking.id,
                booking_line_group_id: this.group.id,
                sojourn_product_model_id: this.instance.id
            });
            // relay to parent
            this.updated.emit();

        }
        catch(response) {
            this.api.errorFeedback(response);
        }
    }
    */

    public async ondeleteAssignment(assignment_id: any) {
        try {
            await this.api.update(this.instance.entity, [this.instance.id], {rental_unit_assignments_ids: [-assignment_id]});
            this.instance.rental_unit_assignments_ids.splice(this.instance.rental_unit_assignments_ids.findIndex((e:any)=>e.id == assignment_id),1);
            // relay to parent
            this.updated.emit();
        }
        catch(response) {
            this.api.errorFeedback(response);
        }
    }

    public async onupdateAssignement(assignment_id:any) {
        this.updated.emit();
    }

    public leftSelectRentalUnit(checked: boolean, rental_unit_id: number) {
        let index = this.selectedRentalUnits.indexOf(rental_unit_id);
        if(index == -1) {
            this.selectedRentalUnits.push(rental_unit_id);
        }
        else if(!checked) {
            this.selectedRentalUnits.splice(index, 1);
        }
    }

    public addSelection() {
        // for each rental unit in the selection, create a new assignment
        let runningActions: Promise<any>[] = [];
        for(let rental_unit_id of this.selectedRentalUnits) {
            const rentalUnit = <RentalUnitClass> this.rentalunits.find( (item) => item.id == rental_unit_id );
            if(!rentalUnit) {
                continue;
            }
            let remaining = this.group.nb_pers - this.instance.qty;
            const promise = this.api.create("lodging\\sale\\booking\\SojournProductModelRentalUnitAssignement", {
                rental_unit_id: rentalUnit.id,
                qty: Math.min(rentalUnit.capacity, remaining),
                booking_id: this.booking.id,
                booking_line_group_id: this.group.id,
                sojourn_product_model_id: this.instance.id
            });
            runningActions.push(promise);
        }
        Promise.all(runningActions).then( () => {
            // relay refresh request to parent
            this.updated.emit();
        })
        .catch( (response) =>  {
            this.api.errorFeedback(response);
        });
        this.selectedRentalUnits.splice(0);
    }

    public addAll() {
        // unselect selected items
        this.selectedRentalUnits.splice(0);
        // select all
        for(let rentalUnit of this.rentalunits) {
            this.selectedRentalUnits.push(rentalUnit.id);
        }
        this.addSelection();
    }

}