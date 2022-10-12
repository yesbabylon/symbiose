import { Component, Input, Output, EventEmitter, OnInit, NgZone, ChangeDetectorRef, AfterViewChecked, AfterViewInit, ViewChild, ElementRef } from '@angular/core';
import { Observable }  from 'rxjs';
import { find, map, mergeMap, startWith, debounceTime } from 'rxjs/operators';

import { CalendarParamService } from '../../../../_services/calendar.param.service';

import { HeaderDays } from 'src/app/model/headerdays';
import { ChangeReservationArg } from 'src/app/model/changereservationarg';
import { ApiService, AuthService } from 'sb-shared-lib';
import { FormControl, FormGroup } from '@angular/forms';
import { MatSelect } from '@angular/material/select';
import { MatOption } from '@angular/material/core';

@Component({
  selector: 'planning-calendar-navbar',
  templateUrl: './planning.calendar.navbar.component.html',
  styleUrls: ['./planning.calendar.navbar.component.scss']
})
export class PlanningCalendarNavbarComponent implements OnInit, AfterViewInit, AfterViewChecked {
    @Input() consumption: any;
    @Input() rental_unit: any;
    @Input() holidays: any;
    @Output() changedays = new EventEmitter<ChangeReservationArg>();
    @Output() refresh = new EventEmitter<Boolean>();
    @ViewChild('centerSelector') centerSelector: MatSelect;

    dateFrom: Date;
    dateTo: Date;
    duration: number;

    centers: any[] = [];
    selected_centers_ids: any[] = [];

    vm: any = {
        duration:   '31',
        date_range: new FormGroup({
            date_from: new FormControl(),
            date_to: new FormControl()
        })
    };

    constructor(
        private api: ApiService,
        private auth: AuthService,
        private params: CalendarParamService,
        private cd: ChangeDetectorRef,
        private zone: NgZone) {
    }


    ngAfterViewInit() {
    }


    ngAfterViewChecked() {
    }


    ngOnInit() {

        /*
            Setup events listeners
        */

        this.params.getObservable()
        .subscribe( async () => {
            console.log('received change from params');
            // update local vars according to service new values
            this.dateFrom = new Date(this.params.date_from.getTime())
            this.dateTo = new Date(this.params.date_to.getTime())

            this.duration = this.params.duration;
            this.vm.duration = this.duration.toString();
            this.vm.date_range.get("date_from").setValue(this.dateFrom);
            this.vm.date_range.get("date_to").setValue(this.dateTo);
        });


        // by default set the first center of current user
        this.auth.getObservable()
        .subscribe( async (user:any) => {
            if(user.hasOwnProperty('centers_ids') && user.centers_ids.length) {
                try {
                    const centers = await this.api.collect('lodging\\identity\\Center',
                        ['id', 'in', user.centers_ids],
                        ['id', 'name', 'code', 'sojourn_type_id'],
                        'name','asc',0,50
                    );
                    if(centers.length) {
                        this.selected_centers_ids = centers.map( (e:any) => e.id );
                        this.params.centers_ids = this.selected_centers_ids;
                        this.centers = centers;
                    }
                }
                catch(err) {
                    console.warn(err) ;
                }
            }
        });
    }


    public async onchangeDateRange() {
        let start = this.vm.date_range.get("date_from").value;
        let end = this.vm.date_range.get("date_to").value;

        if(!start || !end) return;

        if(typeof start == 'string') {
            start = new Date(start);
        }

        if(typeof end == 'string') {
            end = new Date(end);
        }

        if(start <= end) {

            // relay change to parent component
            if((start.getTime() != this.dateFrom.getTime() || end.getTime() != this.dateTo.getTime())) {
                //  update local members and relay to params service
                this.dateFrom = this.vm.date_range.get("date_from").value;
                this.dateTo = this.vm.date_range.get("date_to").value;
                this.params.date_from = this.dateFrom;
                this.params.date_to = this.dateTo;
            }
        }
    }

    public onDurationChange(event: any) {
        console.log('onDurationChange');
        // update local values
        this.duration = parseInt(event.value, 10);
        this.dateTo = new Date(this.dateFrom.getTime());
        this.dateTo.setDate(this.dateTo.getDate() + this.duration);
        this.vm.date_range.get("date_to").setValue(this.dateTo);

        this.params.date_from = this.dateFrom;
        this.params.date_to = this.dateTo;
    }

    public onToday() {
        this.dateFrom = new Date();
        this.dateTo = new Date(this.dateFrom.getTime());
        this.dateTo.setDate(this.dateTo.getDate() + this.params.duration);
        this.vm.date_range.get("date_from").setValue(this.dateFrom);
        this.vm.date_range.get("date_to").setValue(this.dateTo);

        this.params.date_from = this.dateFrom;
        this.params.date_to = this.dateTo;
    }

    public onPrev(duration: number) {
        this.dateFrom.setDate(this.dateFrom.getDate() - duration);
        this.dateTo.setDate(this.dateTo.getDate() - duration);
        this.vm.date_range.get("date_from").setValue(this.dateFrom);
        this.vm.date_range.get("date_to").setValue(this.dateTo);

        this.params.date_from = this.dateFrom;
        this.params.date_to = this.dateTo;
    }

    public onNext(duration: number) {
        this.dateFrom.setDate(this.dateFrom.getDate() + duration);
        this.dateTo.setDate(this.dateTo.getDate() + duration);
        this.vm.date_range.get("date_from").setValue(this.dateFrom);
        this.vm.date_range.get("date_to").setValue(this.dateTo);

        this.params.date_from = this.dateFrom;
        this.params.date_to = this.dateTo;
    }

    public onRefresh() {
        this.refresh.emit(true);
    }

    public onchangeSelectedCenters() {
        console.log('::onchangeSelectedCenters');
        this.params.centers_ids = this.selected_centers_ids;
    }

    public onclickUnselectAllCenters() {
        // this.centerSelector.close();
        this.centerSelector.options.forEach((item: MatOption) => item.deselect());
    }

    public onclickSelectAllCenters() {
        // this.centerSelector.close();
        this.centerSelector.options.forEach((item: MatOption) => item.select());
    }

    public onclickSelectGA() {
        this.centerSelector.options.forEach((item: MatOption) => {
            const center = this.centers.find(center => center.id == item.value);
            if(center.sojourn_type_id == 1) {
                item.select();
            }
            else {
                item.deselect();
            }
        });
    }

    public onclickSelectGG() {
        this.centerSelector.options.forEach((item: MatOption) => {
            const center = this.centers.find(center => center.id == item.value);
            if(center.sojourn_type_id == 2) {
                item.select();
            }
            else {
                item.deselect();
            }
        });
    }

    public calcHolidays() {
        return this.holidays.map( (a:any) => a.name ).join(', ');
    }

}
