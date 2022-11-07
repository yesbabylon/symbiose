import { Injectable } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { TranslateService } from '@ngx-translate/core';
import { Subject } from 'rxjs';

const millisecondsPerDay:number = 24 * 60 * 60 * 1000;

@Injectable({
    providedIn: 'root'
})
export class CalendarParamService {

    private observable: Subject<any>;

    private _date_from: Date;
    private _date_to: Date;
    // duration in days
    private _duration: number;
    private _centers_ids: number[];
    private _rental_units_filter: any[];

    // timeout handler for debounce
    private timeout: any;
    // current state, for changes detection
    private state: string;

    constructor(private translate:TranslateService, private snack: MatSnackBar) {
        this.observable = new Subject();
    }

    /**
     * Current state according to instant values of the instance.
     */
    private getState():string {
        return this._date_from.getTime() + this._date_to.getTime() + this._centers_ids.toString() + JSON.stringify(this._rental_units_filter);
    }

    private treatAsUTC(date:Date): Date {
        let result = new Date(date.getTime());
        result.setMinutes(result.getMinutes() - result.getTimezoneOffset());
        return result;
    }

    private updateRange() {
        if(this.timeout) {
            clearTimeout(this.timeout);
        }
        // add a debounce in case range is updated several times in a row
        this.timeout = setTimeout( () => {
            console.log('update', this._date_from, this._date_to);
            this.timeout = undefined;
            const new_state = this.getState();
            if(new_state != this.state) {
                this.state = new_state;
                this._duration = Math.abs(this.treatAsUTC(this._date_to).getTime() - this.treatAsUTC(this._date_from).getTime()) / millisecondsPerDay;
                this.observable.next(this.state);
            }
        }, 150);
    }

    /**
     * Allow init request from other components
    */
    public init() {
        this._duration = 31;
        this._date_from = new Date();
        this._date_to = new Date(this._date_from.getTime());
        this._date_to.setDate(this._date_from.getDate() + this._duration);
        this._centers_ids = [];
        this._rental_units_filter = [];
        this.state = this.getState();
    }

    public getObservable(): Subject<any> {
        return this.observable;
    }

    public set centers_ids(centers_ids: number[]) {
        this._centers_ids = [...centers_ids];
        this.updateRange();
    }

    public set date_from(date: Date) {
        this._date_from = date;
        this.updateRange();
    }

    public set date_to(date: Date) {
        this._date_to = date;
        this.updateRange();
    }

    public set rental_units_filter(filter: any[]) {
        this._rental_units_filter = JSON.parse(JSON.stringify(filter));
        this.updateRange();
    }

    public get centers_ids(): number[] {
        return this._centers_ids;
    }

    public get date_from(): Date {
        return this._date_from;
    }

    public get date_to(): Date {
        return this._date_to;
    }

    public get duration(): number {
        return this._duration;
    }

    public get rental_units_filter(): any[] {
        return this._rental_units_filter;
    }

}