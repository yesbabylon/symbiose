import { Component, Input, Output, ElementRef, EventEmitter, OnInit, OnChanges, SimpleChanges, ViewChild, AfterViewInit, ChangeDetectorRef } from '@angular/core';
import { es } from 'date-fns/locale';

const millisecondsPerDay:number = 24 * 60 * 60 * 1000;

@Component({
  selector: 'planning-calendar-booking',
  templateUrl: './planning.calendar.booking.component.html',
  styleUrls: ['./planning.calendar.booking.component.scss']
})
export class PlanningCalendarBookingComponent implements OnInit, OnChanges  {
    @Input()  day: Date;
    @Input()  consumption: any;
    @Input()  width: number;
    @Input()  height: number;
    @Output() hover = new EventEmitter<any>();
    @Output() selected = new EventEmitter<any>();

    constructor(
        private elementRef: ElementRef
    ) {}

    ngOnInit() { }

    ngOnChanges(changes: SimpleChanges) {
        if (changes.consumption || changes.width) {
            this.datasourceChanged();
        }
        if(changes.height) {
            this.elementRef.nativeElement.style.setProperty('--height', this.height+'px');
        }
    }

    /**
     * convert a string formated time to a unix timestamp
     */
    private getTime(time:string) : number {
        let parts = time.split(':');
        return (parseInt(parts[0])*3600) + (parseInt(parts[1])*60) + parseInt(parts[2]);
    }

    private calcDiff(date1: Date, date2: Date) : number {
        let diff = Math.abs(date1.getTime() - date2.getTime());
        return Math.ceil(diff / (1000 * 3600 * 24));
    }

    private calcDateInt(day: Date) {
        let timestamp = day.getTime();
        let offset = day.getTimezoneOffset()*60*1000;
        let moment = new Date(timestamp-offset);
        return parseInt(moment.toISOString().substring(0, 10).replace(/-/g, ''), 10);
    }

    private isSameDate(date1:Date, date2:Date) : boolean {
        return (this.calcDateInt(date1) == this.calcDateInt(date2));
    }

    private datasourceChanged() {

        const unit = this.width/(24*3600);

        // offset since the start of the current day
        let offset:number = 0;
        let width:string = '100%';

        // #todo - we shoud have info about last visible date
        let time_to = this.getTime(this.consumption.schedule_to);

        if(this.isSameDate(new Date(this.consumption.date_from), this.day)) {
            let time_from = this.getTime(this.consumption.schedule_from);
            offset  = unit * time_from;
            let days = this.calcDiff(new Date(this.consumption.date_to), new Date(this.consumption.date_from)) - 1;
            width = Math.abs(unit * (((24*3600)-time_from) + (24*3600*days) + (time_to))).toString() + 'px';
        }
        else {
            // let days = this.calcDateInt(new Date(this.consumption.date_to)) - this.calcDateInt(this.day);
            let days = this.calcDiff(new Date(this.consumption.date_to), this.day);
            width = Math.abs(unit * ((24*3600*days) + (time_to))).toString() + 'px';
        }

        this.elementRef.nativeElement.style.setProperty('--height', this.height+'px');
        // #memo - width can be expressed in px or %
        this.elementRef.nativeElement.style.setProperty('--width', width);
        this.elementRef.nativeElement.style.setProperty('--offset', offset+'px');
    }


    public onShowBooking(booking: any) {
       this.selected.emit(booking);
    }

    public onEnterConsumption(consumption:any) {
        this.hover.emit(consumption);
    }

    public onLeaveConsumption(consumption:any) {
        this.hover.emit();
    }

    public getColor() {
        const colors: any = {
            yellow: '#ff9633',
            turquoise: '#0fc4a7',
            green: '#0FA200',
            blue: '#0288d1',
            violet: '#9575cd',
            red: '#C80651',
            grey: '#988a7d',
        };

        if(this.consumption.type == 'ooo') {
            return colors['red'];
        }
        if(this.consumption.booking_id?.status == 'option') {
            return colors['blue'];
        }
        if(this.consumption.booking_id?.status == 'confirmed') {
            return colors['yellow'];
        }
        if(this.consumption.booking_id?.status == 'validated') {
            return colors['green'];
        }
        if(this.consumption.booking_id?.status == 'checkedin') {
            return colors['turquoise'];
        }        
        return colors['grey'];
    }

}