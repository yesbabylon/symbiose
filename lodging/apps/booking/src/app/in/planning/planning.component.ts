import { Component, Renderer2, ChangeDetectorRef, OnInit, AfterViewInit, NgZone, ViewChild, ElementRef, HostListener, Inject } from '@angular/core';

import { Subscription } from 'rxjs';

import { BookingDayClass } from 'src/app/model/booking.class';
import { ChangeReservationArg } from 'src/app/model/changereservationarg';
import { ApiService, AuthService, ContextService } from 'sb-shared-lib';
import { CalendarParamService } from './_services/calendar.param.service';
import { PlanningCalendarComponent } from './_components/planning.calendar/planning.calendar.component';
import {MatDialog, MatDialogRef, MAT_DIALOG_DATA} from '@angular/material/dialog';

import * as screenfull from 'screenfull';
import { PlanningLegendDialogComponent } from './_components/legend.dialog/legend.component';
import { PlanningPreferencesDialogComponent } from './_components/preferences.dialog/preferences.component';

interface DateRange {
  from: Date,
  to: Date
}

@Component({
    selector: 'planning',
    templateUrl: './planning.component.html',
    styleUrls: ['./planning.component.scss']
})
export class PlanningComponent implements OnInit, AfterViewInit {
    @ViewChild('planningBody') planningBody: ElementRef;
    @ViewChild('planningCalendar') planningCalendar: PlanningCalendarComponent;

    public centers_ids: number[];
    public rowsHeight: number = 30;
    public date_range: DateRange = <DateRange>{};
    public fullscreen: boolean = false;

    // timeout for storing rowsHeight in local storage
    private wheelTimeout: any = null;

    constructor(
        private api: ApiService,
        private auth:AuthService,
        private context: ContextService,
        private params: CalendarParamService,
        private cd: ChangeDetectorRef,
        public dialog: MatDialog
    ) {
        this.centers_ids = [];
    }

    ngOnInit() {
        if (screenfull.isEnabled) {
            screenfull.on('change', () => {
                this.fullscreen = screenfull.isFullscreen;
            });
        }
        // #memo - we need to put this on global window to support fullscreen
        window.addEventListener('wheel', (event:any) => {
            if(event.shiftKey) {
                if(event.deltaY > 0) {
                    this.rowsHeight -= (this.rowsHeight/10) * Math.abs(event.deltaY)/100;
                }
                else if(event.deltaY < 0) {
                    this.rowsHeight += (this.rowsHeight/10) * Math.abs(event.deltaY)/100;
                }
                if(this.rowsHeight < 10) {
                    this.rowsHeight = 10;
                }
                else if(this.rowsHeight > 50) {
                    this.rowsHeight = 50;
                }

                if(this.wheelTimeout) {
                    clearTimeout(this.wheelTimeout);
                }
                this.wheelTimeout = setTimeout( () => {
                    // store new rowsHeight in local storage
                    localStorage.setItem('planning_rows_height', this.rowsHeight.toString());
                }, 1000);
            }
        }, true);

        // retrieve rowsHeigth from local storage
        let rows_height = localStorage.getItem('planning_rows_height');
        if(rows_height) {
            this.rowsHeight = parseInt(rows_height, 10);
        }
        this.retrieveSettings();
    }

    private retrieveSettings() {
        console.log('applying settings');
        let rows_height = localStorage.getItem('planning_rows_height');
        if(rows_height) {
            this.rowsHeight = parseInt(rows_height, 10);
        }
        let show_parents = localStorage.getItem('planning_show_parents');
        let show_children = localStorage.getItem('planning_show_children');
        let is_accomodation = localStorage.getItem('planning_show_accomodations_only');
        let domain: any[] = [];

        if(show_parents && show_parents === 'true') {
            domain.push([['can_rent', '=', true], ['has_parent', '=', false]]);
        }
        if(show_children && show_children === 'true') {
            domain.push([['can_rent', '=', true], ['has_parent', '=', true]]);
        }
        if(is_accomodation && is_accomodation === 'true') {
            if(!domain.length) {
                domain.push([['can_rent', '=', true], ['is_accomodation', '=', true]]);
            }
            else {
                for(let i = 0, n = domain.length; i < n; ++i) {
                    domain[i].push(['is_accomodation', '=', true]);
                }
            }
        }

        this.params.rental_units_filter = domain;
    }

    // apply updated settings from localStorage
    private applySettings() {
        this.retrieveSettings();
        this.planningCalendar.onRefresh();
    }

    /**
     * Set up callbacks when component DOM is ready.
     */
    public ngAfterViewInit() {

    }

    public async onFullScreen() {
        if (screenfull.isEnabled) {
            this.cd.detach();
            await screenfull.request(this.planningBody.nativeElement);
            this.cd.reattach();
        }
        else {
            console.log('screenfull not enabled');
        }
    }

    public onclickOpenLegendDialog(){
        const dialogRef = this.dialog.open(PlanningLegendDialogComponent, {});
    }

    public onclickOpenPrefsDialog() {
        const dialogRef = this.dialog.open(PlanningPreferencesDialogComponent, {
                width: '500px',
                height: '500px'
            });

        dialogRef.afterClosed().subscribe(result => {
            if(result) {
                localStorage.setItem('planning_rows_height', result.rows_height.toString());
                localStorage.setItem('planning_show_parents', result.show_parents.toString());
                localStorage.setItem('planning_show_children', result.show_children.toString());
                localStorage.setItem('planning_show_accomodations_only', result.show_accomodations_only.toString());
                this.applySettings();
            }
        });
    }

    public onShowBooking(consumption: any) {
        let descriptor:any

        // switch depending on object type (booking or ooo)
        if(consumption.type == 'ooo') {
            descriptor = {
                context_silent: true, // do not update sidebar
                context: {
                    entity: 'sale\\booking\\Repairing',
                    type: 'form',
                    name: 'default',
                    domain: ['id', '=', consumption.repairing_id.id],
                    mode: 'view',
                    purpose: 'view',
                    display_mode: 'popup',
                    callback: (data:any) => {
                        // restart angular lifecycles
                        this.cd.reattach();
                    }
                }
            };
        }
        // 'book' or similar
        else {
            descriptor = {
                context_silent: true, // do not update sidebar
                context: {
                    entity: 'lodging\\sale\\booking\\Booking',
                    type: 'form',
                    name: 'default',
                    domain: ['id', '=', consumption.booking_id.id],
                    mode: 'view',
                    purpose: 'view',
                    display_mode: 'popup',
                    callback: (data:any) => {
                        // restart angular lifecycles
                        this.cd.reattach();
                    }
                }
            };
        }

        if(this.fullscreen) {
            descriptor.context['dom_container'] = '.planning-body';
        }
        // prevent angular lifecycles while a context is open
        this.cd.detach();
        this.context.change(descriptor);
    }


    public onShowRentalUnit(rental_unit: any) {
        let descriptor:any = {
            context_silent: true, // do not update sidebar
            context: {
                entity: 'lodging\\realestate\\RentalUnit',
                type: 'form',
                name: 'default',
                domain: ['id', '=', rental_unit.id],
                mode: 'view',
                purpose: 'view',
                display_mode: 'popup',
                callback: (data:any) => {
                    // restart angular lifecycles
                    this.cd.reattach();
                }
            }
        };

        if(this.fullscreen) {
            descriptor.context['dom_container'] = '.planning-body';
        }
        // prevent angular lifecycles while a context is open
        this.cd.detach();
        this.context.change(descriptor);
    }
}