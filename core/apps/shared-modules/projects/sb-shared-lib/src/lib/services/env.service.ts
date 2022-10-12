import { Injectable } from '@angular/core';


@Injectable({
  providedIn: 'root'
})
export class EnvService {

  private environment: any = null;
  private promise: any = null;

  private default: any = {
    production:     false,
    parent_domain:  'equal.local',
    backend_url:    'http://equal.local',
    rest_api_url:   'http://equal.local/v1',
    lang:           'en',
    locale:         'en'
  };

  constructor() {}

  /**
   * 
   * @returns Promise
   */
  public getEnv() {
    if(!this.promise) {
      this.promise = new Promise( async (resolve, reject) => {        
        try {
          const response:Response = await fetch('/assets/env/config.json');
          const env = await response.json();
          this.environment = {...this.default, ...env};
          resolve(this.environment);
        }
        catch(response) {
          this.environment = {...this.default};
          resolve(this.environment);
        }  
      });
    }
    return this.promise;
  }

  public setEnv(property: string, value: any) {
    if(this.environment) {
      this.environment[property] = value;
    }
  }

}