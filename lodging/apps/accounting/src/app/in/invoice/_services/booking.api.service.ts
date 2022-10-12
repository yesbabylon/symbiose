import { Injectable } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { TranslateService } from '@ngx-translate/core';
import { ApiService } from 'sb-shared-lib';

@Injectable({
  providedIn: 'root'
})

export class BookingApiService {

  // booking object for conditionning API calls  
  private booking: any;

  constructor(private api: ApiService, private translate:TranslateService, private snack: MatSnackBar) {}

  public setBooking(booking:any) {
    this.booking = booking;
  }

  /**
   *  Sends a direct GET request to the backend without using API URL
   */
  public fetch(route:string, body:any = {}) {
    return this.api.fetch(route, body);
  }

  /**
   *  Sends a direct POST request to the backend without using API URL
   */
  public call(route:string, body:any = {}) {
    console.log('BookingApiService::call', this.booking);
    return this.api.call(route, body);
  }

  /**
   * 
   * @param entity 
   * @param fields 
   * @returns Promise
   */
  public create(entity:string, fields:any = {}) {
    console.log('BookingApiService::create', this.booking);

    if(this.booking.status != 'quote') {
      return new Promise( (resolve, reject) => reject({error: {errors: { INVALID_STATUS: true}}}) );
    }
    else {
      return this.api.create(entity, fields);
    }    
  }

  /**
   * 
   * @param entity 
   * @param ids 
   * @param fields 
   * @returns Promise
   */
  public read(entity:string, ids:any[], fields:any[],  order:string='id', sort:string='asc') {
    console.log('BookingApiService::read', this.booking);
    return this.api.read(entity, ids, fields, order, sort);
  }

  /**
   * 
   * @param entity 
   * @param ids 
   * @param values 
   * @param force 
   * @returns Promise
   */
  public update(entity:string, ids:number[], values:{}, force: boolean=false) {
    console.log('BookingApiService::update', this.booking);
  
    if(this.booking.status != 'quote') {
      return new Promise( (resolve, reject) => reject({error: {errors: { INVALID_STATUS: true}}}) );
    }
    else {
      return this.api.update(entity, ids, values, force);
    }
    
  }

  /**
   * 
   * @param entity 
   * @param ids 
   * @param permanent 
   * @returns Promise
   */
  public remove(entity:string, ids:any[], permanent:boolean=false) {
    console.log('BookingApiService::remove', this.booking);

    if(this.booking.status != 'quote') {
      return new Promise( (resolve, reject) => reject({error: {errors: { INVALID_STATUS: true}}}) );
    }
    else {
      return this.api.remove(entity, ids, permanent)
    }

  }

  /**
   * 
   * @param entity 
   * @param domain 
   * @param fields 
   * @param order 
   * @param sort 
   * @param start 
   * @param limit 
   * @returns Promise
   */
  public collect(entity:string, domain:any[], fields:any[], order:string='id', sort:string='asc', start:number=0, limit:number=25) {
    return this.api.collect(entity, domain, fields, order, sort, start, limit);
    
  }

  /*
    All methods using API return a Promise object.
    They can ben invoked either by chaing .then() and .catch() methods, or with await prefix (assuming parent function is declared as async).
  */

  /**
   * Send a GET request to the API.
   *
   * @param route
   * @param body
   * @returns Promise
   */
  public get(route:string, body:any = {}) {
    return this.api.get(route, body);
  }

  public post(route:string, body:any = {}) {
    return this.api.post(route, body);
  }

  public patch(route:string, body:any = {}) {
    return this.api.patch(route, body);
  }

  public put(route:string, body:any = {}) {
    return this.api.put(route, body);
  }

  public delete(route:string) {
    return this.api.delete(route);
  }


  public errorFeedback(response: any) {
    
    let error:string = 'UNKNOWN';

    if(response && response.hasOwnProperty('error') && response['error'].hasOwnProperty('errors')) {
      let errors = response['error']['errors'];

      if(errors.hasOwnProperty('INVALID_STATUS')) {
        error = 'BOOKING_INVALID_STATUS';
      }
      else if(errors.hasOwnProperty('INVALID_PARAM')) {
        error = 'INVALID_PARAM';
        if(errors['INVALID_PARAM'] == 'maximum_size_exceeded') {
          error = 'MAXIMUM_SIZE_EXCEEDED';
        }        
      }
      else if(errors.hasOwnProperty('NOT_ALLOWED')) {
        error = 'NOT_ALLOWED';
      }
      else if(errors.hasOwnProperty('CONFLICT_OBJECT')) {
        error = 'BOOKING_CONFLICT_OBJECT';
      }
    }

    this.snack.open(this.translate.instant('SB_ERROR_'+error), this.translate.instant('SB_ERROR_ERROR'));
  }

}