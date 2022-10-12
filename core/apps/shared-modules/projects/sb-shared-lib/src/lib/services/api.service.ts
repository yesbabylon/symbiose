import { Injectable } from '@angular/core';
import { catchError, map } from "rxjs/operators";
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { EnvService} from './env.service';
import { MatSnackBar } from '@angular/material/snack-bar';
import { TranslateService } from '@ngx-translate/core';

@Injectable({
  providedIn: 'root'
})

export class ApiService {

    private cache: any = {};
    private cache_validity: number = 1000; // cache validity in milliseconds

    constructor(
        private http: HttpClient,
        private env:EnvService,
        private translate:TranslateService,
        private snack: MatSnackBar) {
    }

    /**
     *  Sends a direct GET request to the backend without using ReST API URL
     */
    public fetch(route:string, body:any = {}) {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.get<any>(environment.backend_url+route, {params: body}).toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }

    /**
     *  Sends a direct POST request to the backend without using ReST API URL
     */
    public call(route:string, body:any = {}) {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.post<any>(environment.backend_url+route, body).toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }

    /**
     *
     * @param entity
     * @param fields
     * @returns Promise
     */
    public create(entity:string, fields:any = {}, lang: string = '') {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.put<any>(environment.backend_url+'/?do=model_create', {
                    entity: entity,
                    fields: JSON.stringify(fields),
                    lang: (lang.length)?lang:environment.lang
                }).toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }

    /**
     *
     * @param entity
     * @param ids
     * @param fields
     * @returns Promise
     */
    public read(entity:string, ids:any[], fields:any[],  order:string='id', sort:string='asc', lang:string = '') {

        let hash = btoa(entity + ids.toString() + fields.toString() + order + sort + lang);
        let now = Date.now();

        if(this.cache.hasOwnProperty(hash)) {
            let entry = this.cache[hash];
            if( (entry.timestamp + this.cache_validity) > now) {
                return entry.promise;
            }
            else {
                console.debug('cache: invalidating ' + hash);
                delete this.cache[hash];
            }
        }

        let promise = new Promise(async (resolve, reject) => {
            const environment:any = await this.env.getEnv();
            this.http.get<any>(environment.backend_url+'/?get=model_read', {params: {
                    entity: entity,
                    ids: JSON.stringify(ids),
                    fields: JSON.stringify(fields),
                    order: order,
                    sort: sort,
                    lang: (lang.length)?lang:environment.lang
                }
            }).subscribe(
                data => {
                    resolve(data);
                },
                error => reject(error)
            );
        });

        if(!this.cache.hasOwnProperty(hash)) {
            this.cache[hash] = {
                timestamp: Date.now(),
                promise: promise
            };
        }

        return promise;
    }

    /**
     *
     * @param entity
     * @param ids
     * @param values
     * @param force
     * @returns Promise
     */
    public update(entity:string, ids:number[], values:{}, force: boolean=false, lang:string = '') {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.patch<any>(environment.backend_url+'/?do=model_update', {
                    entity: entity,
                    ids: ids,
                    fields: values,
                    lang: (lang.length)?lang:environment.lang,
                    force: force
                }).toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }

    /**
     *
     * @param entity
     * @param ids
     * @param permanent
     * @returns Promise
     */
    public remove(entity:string, ids:any[], permanent:boolean=false) {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.delete<any>(environment.backend_url+'/?do=model_delete', {body: {
                        entity: entity,
                        ids: ids,
                        permanent: permanent
                    }
                }).toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
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
    public collect(entity:string, domain:any[], fields:any[], order:string='id', sort:string='asc', start:number=0, limit:number=25, lang: string = '') {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.get<any>(environment.backend_url+'/?get=model_collect', {
                    params: {
                    entity: entity,
                    domain: JSON.stringify(domain),
                    fields: JSON.stringify(fields),
                    order: order,
                    sort: sort,
                    start: start,
                    limit: limit,
                    lang: (lang.length)?lang:environment.lang
                    }
                }).toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }


  /*
    HTTP methods for API requests.

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
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.get<any>(environment.rest_api_url+route, {params: body}).toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }

    public post(route:string, body:any = {}) {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.post<any>(environment.rest_api_url+route, body).toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }

    public patch(route:string, body:any = {}) {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.patch<any>(environment.rest_api_url+route, body).toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }

    public put(route:string, body:any = {}) {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.put<any>(environment.rest_api_url+route, body).toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }

    public delete(route:string) {
        return new Promise<any>( async (resolve, reject) => {
            try {
                const environment:any = await this.env.getEnv();
                const response:any = await this.http.delete<any>(environment.rest_api_url+route).toPromise();
                resolve(response);
            }
            catch(error) {
                reject(error);
            }
        });
    }

    public async getMenu(package_name: string, menu_id: string, locale: string = '') {
        const environment:any = await this.env.getEnv();

        let result:any = {
            item: [],
            translation: {}
        };

        try {
            const menu:any = await this.fetch('/?get=model_menu&package='+package_name+'&menu_id='+menu_id+'&lang='+((locale.length)?locale:environment.locale));
            if(menu && menu.layout && menu.layout.items) {
                result.items = menu.layout.items;
            }
        }
        catch(response) {
            console.warn('error retrieving menu', response);
        }

        try {
            const menu_i18n = await this.fetch('/?get=config_i18n-menu&package='+package_name+'&menu_id='+menu_id+'&lang='+((locale.length)?locale:environment.locale));
            if(menu_i18n && menu_i18n.view) {
                result.translation = menu_i18n.view;
            }
            // #todo : do not inject but replace labels recursively
        }
        catch(response) {
            console.warn('error retrieving translation', response);
        }

        return result;
    }

    public async profileUpdate(user_id: string, firstname: string, lastname: string, language: string, avatar: string) {
        let params:any = {
            id: user_id,
            firstname: firstname,
            lastname: lastname,
            language: language
        };

        if(avatar && avatar.length) {
            params['avatar'] = avatar;
        }

        const data = await this.put('/user/'+user_id, params);

        return data;
    }

    public async passwordUpdate(user_id: string, password: string, confirm: string) {
        const environment:any = await this.env.getEnv();
        const data = await this.http.get<any>(environment.backend_url+'/?do=user_pass-update', {
            params: {
                id: user_id,
                password: password,
                confirmation: confirm
            }
        }).toPromise();
        return data;
    }

    // #todo : move this to equal-ui OR replicate equal translation svc
    public async errorFeedback(response: any) {
        if(response && response.hasOwnProperty('error') && response['error'].hasOwnProperty('errors') && Object.keys(response['error']['errors']).length) {
            const errors: string[] = ['INVALID_STATUS', 'INVALID_PARAM', 'NOT_ALLOWED', 'CONFLICT_OBJECT'];
            let response_errors:any = response['error']['errors'];

            for(let error of Object.keys(response_errors)) {
                let value = response_errors[error];

                if(typeof value == 'object') {
                    for(let field in value) {
                        let error_id:string = <string> String((Object.keys(value[field]))[0]);
                        let msg:string = <string>(Object.values(value[field]))[0];
                        let translated_error = this.translate.instant('SB_ERROR_'+error_id.toUpperCase());
                        if(translated_error.length) {
                            msg = translated_error;
                        }
                        this.snack.open(field+': '+msg, this.translate.instant('SB_ERROR_ERROR').toUpperCase());
                        return;
                    }
                }
                else {
                    // handle special errors
                    if(value == 'maximum_size_exceeded') {
                        error = 'MAXIMUM_SIZE_EXCEEDED';
                    }
                    this.snack.open(this.translate.instant('SB_ERROR_'+error), this.translate.instant('SB_ERROR_ERROR').toUpperCase());
                }
            }
        }
        else {
            this.snack.open(this.translate.instant('SB_ERROR_'+'UNKNOWN'), this.translate.instant('SB_ERROR_ERROR').toUpperCase());
        }
    }

    // #todo - remove this
    /**
     * Temporary for non translated errors
     */
    public async errorSnack(field: string, message: string) {
        this.snack.open(field+': '+message, this.translate.instant('SB_ERROR_ERROR').toUpperCase());
    }

}