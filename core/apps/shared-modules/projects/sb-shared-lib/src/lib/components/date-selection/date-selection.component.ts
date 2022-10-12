import { Component, Input, Output, EventEmitter, forwardRef, OnInit, ViewChild } from '@angular/core';
import {FormGroup, FormControl, Validators} from '@angular/forms';

import { MatAutocompleteTrigger } from '@angular/material/autocomplete';

import {Observable, BehaviorSubject} from 'rxjs';
import {find, map, startWith} from 'rxjs/operators';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';


@Component({
  selector: 'date-selection',
  templateUrl: './date-selection.component.html',
  styleUrls: ['./date-selection.component.scss'],
  providers: [
    { 
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => DateSelectionComponent),
      multi: true
    }
  ]
})
export class DateSelectionComponent implements ControlValueAccessor, OnInit {
  @ViewChild(MatAutocompleteTrigger) day: MatAutocompleteTrigger;
  @ViewChild(MatAutocompleteTrigger) month: MatAutocompleteTrigger;
  @ViewChild(MatAutocompleteTrigger) year: MatAutocompleteTrigger;

  private date = new Date();

  writeValue(value: Date) {
    if (value) {
      this.date.setTime(value.getTime());
      this.daysformControl.setValue(this.pad(value.getDate(), 2));
      this.monthsformControl.setValue(this.pad(value.getMonth() + 1, 2));
      this.yearsformControl.setValue(value.getFullYear());

      this.onChange(value);
    }
  }

  onChange = (_:any) => {};
  onTouched = () => {};

  registerOnChange(fn: (_: any) => void): void { this.onChange = fn; }
  registerOnTouched(fn: () => void): void { this.onTouched = fn; }
  
  
  daysformControl = new FormControl();
  monthsformControl = new FormControl();
  yearsformControl = new FormControl();


  daysfiltered = new Observable<Array<any>>();
  monthsfiltered = new Observable<Array<any>>();
  yearsfiltered = new Observable<Array<any>>();


  days:Array<string> = new Array();
  months:Array<string> = new Array();
  years:Array<string> = new Array();
  

  constructor() { 

    this.days = Array(31).fill(0).map( (x,i) => this.pad(i+1, 2));
    this.months = Array(12).fill(0).map( (x,i) => this.pad(i+1, 2));
    this.years = Array(120).fill(0).map( (x,i) => ''+(1930+i));

  }

  private pad(num: number, size: number): string{
    let s = num.toString();
    while (s.length < size) s = '0' + s;
    return s;
  }

  ngOnInit(): void {

    this.daysfiltered = this.daysformControl.valueChanges        
    .pipe(
      startWith(''),
      map( (value:any) => (typeof value === 'string' ? value : value.name) ),
      map( (clue:string) => clue ? this.filter(this.days, clue) : this.days.slice())
    );

    this.monthsfiltered = this.monthsformControl.valueChanges        
    .pipe(
      startWith(''),
      map( (value:any) => (typeof value === 'string' ? value : value.name) ),
      map( (clue:string) => clue ? this.filter(this.months, clue) : this.months.slice())
    );

    this.yearsfiltered = this.yearsformControl.valueChanges        
    .pipe(
      startWith(''),
      map( (value:any) => (typeof value === 'string' ? value : value.name) ),
      map( (clue:string) => clue ? this.filter(this.years, clue) : this.years.slice())
    );
  }



  onSelectDay(value: any) {
    let date = new Date();
    date.setTime(this.date.getTime());

    // update day part
    let day = parseInt(value, 10);
    this.day = value;
    date.setDate(day);

    this.date.setTime(date.getTime());
    this.onChange(this.date);
  }

  onSelectMonth(value: any) {
    let date = new Date();
    date.setTime(this.date.getTime());

    // update month part
    date.setMonth(parseInt(value, 10) - 1);

    this.date.setTime(date.getTime());
    this.onChange(this.date);

  }

  onSelectYear(value: any) {
    let date = new Date();
    date.setTime(this.date.getTime());

    // update year part
    date.setFullYear(parseInt(value, 10));

    this.date.setTime(date.getTime());
    this.onChange(this.date);

  }

/**
   * Generic filter method.
   * 
   * @param values 
   * @param value 
   * @returns 
   */
  private filter(values: any[], value: string): any[] {
    return values.filter(option => option.indexOf(value) == 0);
  }  

}