import { Component, ChangeDetectionStrategy, ChangeDetectorRef, Output, EventEmitter, ViewChild, OnInit, OnChanges, AfterViewInit, ViewChildren, QueryList, ElementRef, AfterViewChecked, Input, SimpleChanges } from '@angular/core';

import { ChangeReservationArg } from 'src/app/model/changereservationarg';
import { HeaderDays } from 'src/app/model/headerdays';


import { ApiService, AuthService } from 'sb-shared-lib';
import { CalendarParamService } from '../../_services/calendar.param.service';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';

import { ConsumptionCreationDialog } from './_components/consumption.dialog/consumption.component';

class RentalUnit {
    constructor(
        public id: number = 0,
        public name: string = '',
        public capacity: number = 0,
        public code: string = '',
        public status: string = '',
        public action_required: string = ''
    ) {}
}

@Component({
    selector: 'planning-calendar',
    templateUrl: './planning.calendar.component.html',
    styleUrls: ['./planning.calendar.component.scss'],
    changeDetection: ChangeDetectionStrategy.OnPush
})
export class PlanningCalendarComponent implements OnInit, OnChanges, AfterViewInit, AfterViewChecked {
    @Input() rowsHeight: number;
    @Output() filters = new EventEmitter<ChangeReservationArg>();
    @Output() showBooking = new EventEmitter();
    @Output() showRentalUnit = new EventEmitter();

    // attach DOM element to compute the cells width
    @ViewChild('calTable') calTable: any;
    @ViewChild('calTableRefColumn') calTableRefColumn: any;
    @ViewChild('selector') selector: any;

    @ViewChildren("calTableHeadCells") calTableHeadCells: QueryList<ElementRef>;

    public loading: boolean = false;
    public headers: any;

    public headerdays: HeaderDays;

    public cellsWidth: number;

    public consumptions: any = [];
    public rental_units: any = [];
    public holidays: any = [];

    public hovered_consumption: any;
    public hovered_rental_unit: any;
    public hovered_holidays: any;

    public hover_row_index = -1;

    public selection =  {
        is_active: false,
        left: 0,
        top: 0,
        width: 0,
        height: 0,
        cell_from: {
            left: 0,
            width: 0,
            rental_unit: {},
            date: {}
        },
        cell_to: {
            date: new Date()
        }
    };

    private mousedownTimeout: any;

    // duration history as hint for refreshing cell width
    private previous_duration: number;

    constructor(
        private params: CalendarParamService,
        private api: ApiService,
        private dialog: MatDialog,
        private elementRef: ElementRef,
        private cd: ChangeDetectorRef) {
            this.headers = {};
            this.rental_units = [];
            this.previous_duration = 0;
    }

     ngOnChanges(changes: SimpleChanges): void {
        if(changes.rowsHeight)     {
            this.elementRef.nativeElement.style.setProperty('--rows_height', this.rowsHeight+'px');
        }
     }

    async ngOnInit() {

        this.params.getObservable().subscribe( () => {
            console.log('PlanningCalendarComponent cal params change', this.params);
            this.onRefresh();
        });

        this.elementRef.nativeElement.style.setProperty('--rows_height', this.rowsHeight+'px');
    }

    async ngAfterViewInit() {
    }

    /**
     * After refreshing the view with new content, adapt header and relay new cell_width, if changed
     */
    async ngAfterViewChecked() {

        if(this.calTableHeadCells) {
            for(let cell of this.calTableHeadCells) {
                this.cellsWidth = cell.nativeElement.offsetWidth;
                break;
            }
        }

        let ten_percent = this.cellsWidth * 0.1;
        if(ten_percent < 100) {
            // set width to 100px
            this.calTableRefColumn.nativeElement.style.width = '100px';
        }
        else {
            if(ten_percent > 200) {
                // set width to 200px
                this.calTableRefColumn.nativeElement.style.width = '200px';
            }
            else {
                // set width to 10%
                this.calTableRefColumn.nativeElement.style.width = '10%';
            }
        }

        // this.cd.detectChanges();
    }

    public onRefresh() {
        console.log('onrefresh')
        this.loading = true;
        this.cd.detectChanges();
        // refresh the view, then run onchange
        setTimeout( async () => {
            await this.onFiltersChange();
            this.cd.reattach();
            this.loading = false;
            this.cd.detectChanges();
        });
    }

    public calcDateIndex(day: Date): string {
        let timestamp = day.getTime();
        let offset = day.getTimezoneOffset()*60*1000;
        let moment = new Date(timestamp-offset);
        return moment.toISOString().substring(0, 10);
    }

    public isWeekEnd(day:Date) {
        return (day.getDay() == 0 || day.getDay() == 6);
    }

    public isToday(day:Date) {
        const today = new Date();
        return (day.getDate() == today.getDate() && day.getMonth() == today.getMonth() && day.getFullYear() == today.getFullYear());
    }

    public hasConsumption(rentalUnit:RentalUnit, day: Date):any {
        if(!this.consumptions.hasOwnProperty(rentalUnit.id) || !this.consumptions[rentalUnit.id]) {
            return false;
        }
        return this.consumptions[rentalUnit.id].hasOwnProperty(this.calcDateIndex(day));
    }

    public getConsumption(rentalUnit:RentalUnit, day: Date): any {
        if(this.consumptions.hasOwnProperty(rentalUnit.id) && this.consumptions[rentalUnit.id]) {
            let date_index:string = this.calcDateIndex(day);
            if(this.consumptions[rentalUnit.id].hasOwnProperty(date_index)) {
                return this.consumptions[rentalUnit.id][date_index];
            }
        }
        return {};
    }

    public getDescription(rentalUnit:RentalUnit, day: Date): string {
        if(rentalUnit && rentalUnit.hasOwnProperty('id') && this.consumptions.hasOwnProperty(rentalUnit.id)) {
            let date_index:string = this.calcDateIndex(day);
            if(this.consumptions[rentalUnit.id].hasOwnProperty(date_index) && this.consumptions[rentalUnit.id][date_index]) {
                if(this.consumptions[rentalUnit.id][date_index].hasOwnProperty('booking_id')
                    && this.consumptions[rentalUnit.id][date_index]['booking_id']
                    && this.consumptions[rentalUnit.id][date_index]['booking_id'].hasOwnProperty('description')) {
                    return this.consumptions[rentalUnit.id][date_index].booking_id.description;
                }
                else if(this.consumptions[rentalUnit.id][date_index].hasOwnProperty('repairing_id')
                    && this.consumptions[rentalUnit.id][date_index]['repairing_id']
                    && this.consumptions[rentalUnit.id][date_index]['repairing_id'].hasOwnProperty('description')) {
                    return this.consumptions[rentalUnit.id][date_index].repairing_id.description;
                }

            }
        }
        return '';
    }

    public getHolidayClasses(day: Date): string[] {
        let result = [];
        let date_index:string = this.calcDateIndex(day);
        if(this.holidays.hasOwnProperty(date_index) && this.holidays[date_index] && this.holidays[date_index].length) {
            result = this.holidays[date_index];
        }
        return result.map( (o:any) => o.type);
    }

    private async onFiltersChange() {
        this.createHeaderDays();

        try {
            const domain: any[] = JSON.parse(JSON.stringify(this.params.rental_units_filter));
            if(!domain.length) {
                domain.push([['can_rent', '=', true], ["center_id", "in", this.params.centers_ids]]);
            }
            else {
                for(let i = 0, n = domain.length; i < n; ++i) {
                    domain[i].push(["center_id", "in",  this.params.centers_ids]);
                }
            }
            const rental_units = await this.api.collect(
                "lodging\\realestate\\RentalUnit",
                domain,
                Object.getOwnPropertyNames(new RentalUnit()),
                'name', 'asc', 0, 500
            );
            if(rental_units) {
                this.rental_units = rental_units;
            }
        }
        catch(response) {
            console.warn('unable to fetch rental units');
        }


        if(this.params.centers_ids.length <= 0) {
            this.loading = false;
            return;
        }

        try {
            let holidays:any = await this.api.collect(
                "calendar\\Holiday",
                [
                    [ [ "date_from", ">=",  this.params.date_from], [ "date_to", "<=",  this.params.date_to ] ],
                    [ [ "date_from", ">=",  this.params.date_from], [ "date_from", "<=",  this.params.date_to ] ],
                    [ [ "date_to", ">=",  this.params.date_from], [ "date_to", "<=",  this.params.date_to ] ],
                ],
                ['name', 'date_from', 'date_to', 'type'],
                'id', 'asc', 0, 100
            );
            if(holidays) {
                for(let holiday of holidays) {
                    holiday['date_from_int']  = parseInt(holiday.date_from.substring(0, 10).replace(/-/gi, ''), 10);
                    holiday['date_to_int'] = parseInt(holiday.date_to.substring(0, 10).replace(/-/gi, ''), 10);
                }
                this.holidays = {};
                let d = new Date();
                for (let d = new Date(this.params.date_from.getTime()); d <= this.params.date_to; d.setDate(d.getDate() + 1)) {
                    let date_index:string = this.calcDateIndex(d);
                    let date_int  = parseInt(date_index.replace(/-/gi, ''), 10);
                    this.holidays[date_index] = holidays.filter( (h:any) => (date_int >= h['date_from_int'] && date_int <= h['date_to_int']) );
                }
            }
        }
        catch(response) {
            console.warn('unable to fetch holidays');
        }



        try {

            this.consumptions = await this.api.fetch('/?get=lodging_consumption_map', {
                date_from: this.params.date_from.toISOString(),
                date_to: this.params.date_to.toISOString(),
                centers_ids: JSON.stringify(this.params.centers_ids)
            });

        }
        catch(response) {
            console.warn('unable to fetch rental units');
        }

    }


    /**
     * Recompute content of the header.
     *
     * Convert to folloiwuing structure :
     *
     * headers.months:
     *    months[]
     *        {
     *            month:
     *            days:
     *        }
     *
     * headers.days: date[]
     */
    private createHeaderDays() {

        if(this.previous_duration != this.params.duration) {
            // temporarily reset cellsWidth to an arbitrary ow value
            this.cellsWidth = 12;
        }

        this.previous_duration = this.params.duration;

        // reset headers
        this.headers = {
            months: [],
            days: []
        };

        let months:any = {};
        // pass-1 assign dates
        for (let i = 0; i < this.params.duration; i++) {
            let currdate = new Date(this.params.date_from.getTime());
            currdate.setDate(currdate.getDate() + i);
            this.headers.days.push(currdate);
            let month_index = currdate.getFullYear()*100+currdate.getMonth();
            if(!months.hasOwnProperty(month_index)) {
                months[month_index] = [];
            }
            months[month_index].push(currdate);
        }

        // pass-2 assign months (in order)
        let months_array = Object.keys(months).sort( (a: any, b: any) => (a - b) );
        for(let month of months_array) {
            this.headers.months.push(
                {
                    date: months[month][0],
                    month: month,
                    days: months[month]
                }
            );
        }

    }


    public onhoverBooking(consumption:any) {
        // relay hovered consumption to navbar
        this.hovered_consumption = consumption;
    }

    public onhoverDate(day:Date) {
        let result;
        if(day) {
            let date_index:string = this.calcDateIndex(day);
            if(this.holidays.hasOwnProperty(date_index) && this.holidays[date_index].length) {
                result = this.holidays[date_index];
            }
        }
        this.hovered_holidays = result;
    }

    public onSelectedBooking(event: any) {
        clearTimeout(this.mousedownTimeout);
        this.showBooking.emit(event);
    }

    public onSelectedRentalUnit(rental_unit: any) {
        clearTimeout(this.mousedownTimeout);
        this.showRentalUnit.emit(rental_unit);
    }

    public onhoverDay(rental_unit: any, day:Date) {
        this.hovered_rental_unit = rental_unit;

        if(day) {
            let date_index:string = this.calcDateIndex(day);
            if(this.holidays.hasOwnProperty(date_index) && this.holidays[date_index].length) {
                this.hovered_holidays = this.holidays[date_index];
            }
        }
        else {
            this.hovered_holidays = undefined;
        }
    }

    public onhoverRentalUnit(rental_unit: any) {
        this.hovered_rental_unit = rental_unit;
    }

    public onmouseleaveTable() {
        clearTimeout(this.mousedownTimeout);
        this.selection.is_active = false;
        this.selection.width = 0;
    }

    public onmouseup() {
        clearTimeout(this.mousedownTimeout);

        if(this.selection.is_active) {
            console.log('is active');
            // make from and to right
            let rental_unit:any = this.selection.cell_from.rental_unit;
            let from:any = this.selection.cell_from;
            let to:any = this.selection.cell_to;
            if(this.selection.cell_to.date < this.selection.cell_from.date) {
                from = this.selection.cell_to;
                to = this.selection.cell_from;
            }
            // check selection for existing consumption
            let valid = true;
            let diff = (<Date>this.selection.cell_to.date).getTime() - (<Date>this.selection.cell_from.date).getTime();
            let days = Math.abs(Math.floor(diff / (60*60*24*1000)))+1;
            // do not check last day : overlapse is allowed if checkout is before checkin
            for (let i = 0; i < days-1; i++) {
                let currdate = new Date(from.date.getTime());
                currdate.setDate(currdate.getDate() + i);
                if(this.hasConsumption(rental_unit, currdate)) {
                    valid = false;
                    break;
                }
            }
            if(!valid || !from.rental_unit) {
                this.selection.is_active = false;
                this.selection.width = 0;
                return;
            }
            else {
                // open dialog for requesting action dd

                const dialogRef = this.dialog.open(ConsumptionCreationDialog, {
                    width: '50vw',
                    data: {
                        rental_unit: from.rental_unit.name,
                        rental_unit_id: from.rental_unit.id,
                        date_from: from.date,
                        date_to: to.date
                    }
                });

                dialogRef.afterClosed().subscribe( async (values) => {
                    if(values) {
                        if(values.type && values.type == 'book') {
                            try {
                                await this.api.call('?do=lodging_booking_plan-option', {
                                    date_from: values.date_from.toISOString(),
                                    date_to: values.date_to.toISOString(),
                                    rental_unit_id: values.rental_unit_id,
                                    customer_identity_id: values.customer_identity_id,
                                    no_expiry: values.no_expiry,
                                    free_rental_units: values.free_rental_units
                                });

                                this.onRefresh();
                            }
                            catch(response) {
                                this.api.errorFeedback(response);
                            }
                        }
                        else if(values.type && values.type == 'ooo') {
                            try {
                                await this.api.call('?do=lodging_booking_plan-repair', {
                                    date_from: values.date_from.toISOString(),
                                    date_to: values.date_to.toISOString(),
                                    rental_unit_id: values.rental_unit_id,
                                    description: (values.description.length)?values.description:'Blocage via planning'
                                });

                                this.onRefresh();
                            }
                            catch(response) {
                                this.api.errorFeedback(response);
                            }
                        }

                    }
                });

            }
        }

        this.selection.is_active = false;
        this.selection.width = 0;

    }

    public onmousedown($event: any, rental_unit: any, day: any) {
        // start seleciton with a 100ms delay to avoid confusion with booking selection
        this.mousedownTimeout = setTimeout( () => {
            let table = this.calTable.nativeElement.getBoundingClientRect();
            let cell = $event.target.getBoundingClientRect();

            this.selection.top = cell.top - table.top;
            this.selection.left = cell.left - table.left + this.calTable.nativeElement.offsetLeft;

            this.selection.width = cell.width;
            this.selection.height = cell.height;

            this.selection.cell_from.left = this.selection.left;
            this.selection.cell_from.width = cell.width;
            this.selection.cell_from.date = day;
            this.selection.cell_from.rental_unit = rental_unit;

            this.selection.is_active = true;
        }, 100);
    }

    public onmouseover($event: any, day:any) {
        if(this.selection.is_active) {
            // selection entre le départ et la case en cours
            let table = this.calTable.nativeElement.getBoundingClientRect();
            let cell = $event.target.getBoundingClientRect();

            this.selection.cell_to.date = day;

            // diff entre les deux dates
            let diff = (<Date>this.selection.cell_to.date).getTime() - (<Date>this.selection.cell_from.date).getTime();
            let days = Math.abs(Math.floor(diff / (60*60*24*1000)))+1;

            this.selection.width = this.selection.cell_from.width * days

            if(this.selection.cell_from.date > this.selection.cell_to.date) {
                this.selection.left = cell.left - table.left + this.calTable.nativeElement.offsetLeft;
            }
            else {
                this.selection.left = this.selection.cell_from.left;
            }
        }
    }

    public preventDrag($event:any) {
        $event.preventDefault();
    }
}