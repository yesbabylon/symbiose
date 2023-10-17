import { $ } from "./jquery-lib";
import { EnvService } from "./qursus-services";

/**
 * This service acts as an interface between client and server and caches view objects to lower the traffic
 * Contents that can be cached are :
 * - Views
 * - Menus
 * - Translations
 * - Schemas
 */
export class _ApiService {

    private last_count: number;


    constructor() {
        $.ajaxSetup({
            cache: false,                // prevent caching
            beforeSend: (xhr:any) => {
                /*
                // #removed for XSS protection (we use httpOnly cookie instead)
                let access_token = this.getCookie('access_token');
                if(access_token) {
                    xhr.setRequestHeader('Authorization', "Basic " + access_token);
                }
                else {
                    console.log('_ApiService: no access token found')
                }
                */
            },
            xhrFields: { withCredentials: true }
        });


        this.last_count = 0;
    }



    public getLastCount() {
        return this.last_count;
    }

    public async getUser() {
        let result: any;
        try {
            const environment = await EnvService.getEnv();
            const response = await $.get({
                url: environment.rest_api_url+'/userinfo'
            });
            result = response;
        }
        catch(response:any) {
            throw response.responseJSON;
        }
        return result;
    }

    public async fetch(route:string, body:any = {}) {
        let result: any;
        try {
            const environment = await EnvService.getEnv();
            const response = await $.get({
                url: environment.backend_url+route,
                dataType: 'json',
                data: body,
                contentType: 'application/x-www-form-urlencoded; charset=utf-8'
            });
            result = response;
        }
        catch(response:any) {
            throw response.responseJSON;
        }
        return result;
    }

    public async create(entity:string, fields:any = {}) {
        let result: any;
        try {
            const environment = await EnvService.getEnv();
            let params = {
                entity: entity,
                fields: fields,
                lang: environment.lang
            };
            const response = await $.get({
                url: environment.backend_url+'?do=model_create',
                dataType: 'json',
                data: params,
                contentType: 'application/x-www-form-urlencoded; charset=utf-8'
            });
            result = response;
        }
        catch(response:any) {
            throw response.responseJSON;
        }
        return result;
    }

    public async read(entity:string, ids:any[], fields:[]) {
        let result: any;
        try {
            const environment = await EnvService.getEnv();
            let params = {
                entity: entity,
                ids: ids,
                fields: fields,
                lang: environment.lang
            };
            const response = await $.get({
                url: environment.backend_url+'?get=model_read',
                dataType: 'json',
                data: params,
                contentType: 'application/x-www-form-urlencoded; charset=utf-8'
            });
            result = response;
        }
        catch(response:any) {
            throw response.responseJSON;
        }
        return result;
    }

    public async delete(entity:string, ids:any[], permanent:boolean=false) {
        let result: any;
        try {
            const environment = await EnvService.getEnv();
            let params = {
                entity: entity,
                ids: ids,
                permanent: permanent
            };
            const response = await $.get({
                url: environment.backend_url+'?do=model_delete',
                dataType: 'json',
                data: params,
                contentType: 'application/x-www-form-urlencoded; charset=utf-8'
            });
            result = response;
        }
        catch(response:any) {
            throw response.responseJSON;
        }
        return result;
    }

    public async archive(entity:string, ids:any[]) {
        let result: any;
        try {
            const environment = await EnvService.getEnv();
            let params = {
                entity: entity,
                ids: ids
            };
            const response = await $.get({
                url: environment.backend_url+'?do=model_archive',
                dataType: 'json',
                data: params,
                contentType: 'application/x-www-form-urlencoded; charset=utf-8'
            });
            result = response;
        }
        catch(response:any) {
            throw response.responseJSON;
        }
        return result;
    }

    /**
     *
     * In practice, only one object is updated at a time (through form or list inline editing)
     *
     * @param entity
     * @param ids
     * @param fields
     */
    public async update(entity:string, ids:any[], fields:any, force: boolean=false) {
        console.log('ApiService::update', entity, ids, fields);
        let result: any = true;
        try {
            const environment = await EnvService.getEnv();
            let params = {
                entity: entity,
                ids: ids,
                fields: fields,
                lang: environment.lang,
                force: force
            };
            const response = await $.post({
                url: environment.backend_url+'?do=model_update',
                dataType: 'json',
                data: params,
                contentType: 'application/x-www-form-urlencoded; charset=utf-8'
            });
            result = response;
        }
        catch(response:any) {
            throw response.responseJSON;
        }
        return result;
    }

    public async clone(entity:string, ids:any[]) {
        let result: any;
        try {
            const environment = await EnvService.getEnv();
            let params = {
                entity: entity,
                ids: ids,
                lang: environment.lang
            };
            const response = await $.get({
                url: environment.backend_url+'?do=model_clone',
                dataType: 'json',
                data: params,
                contentType: 'application/x-www-form-urlencoded; charset=utf-8'
            });
            result = response;
        }
        catch(response:any) {
            throw response.responseJSON;
        }
        return result;
    }

    /**
     * Search for objects matching the given domain and return a list of objects holding requested fields and their values.
     *
     * @param entity
     * @param domain
     * @param fields
     * @param order
     * @param sort
     * @param start
     * @param limit
     * @param lang
     * @returns     Promise     Upon success, the promise is resolved into an Array holding matching objects (collection).
     */
    public async collect(entity:string, domain:any[], fields:any[], order:string, sort:string, start:number, limit:number, lang:string) {
        console.log('ApiService::collect', entity, domain, fields, order, sort, start, limit, lang);
        var result = [];
        try {
            let params = {
                entity: entity,
                domain: domain,
                fields: fields,
                lang: lang,
                order: order,
                sort: sort,
                start: start,
                limit: limit
            };
            const environment = await EnvService.getEnv();
            const response = await $.get({
                url: environment.backend_url+'?get=model_collect',
                dataType: 'json',
                data: params,
                contentType: 'application/x-www-form-urlencoded; charset=utf-8'
            }).done((event: any, textStatus: string, jqXHR: any) => {
                this.last_count = parseInt( <any>jqXHR.getResponseHeader('X-Total-Count') );
            } );
            result = response;
        }
        catch(response:any) {
            throw response.responseJSON;
        }
        return result;
    }

    /**
     * Search for objects matching the given domain and return a list of identifiers.
     *
     * @param entity
     * @param domain
     * @param order
     * @param sort
     * @param start
     * @param limit
     * @returns
     */
    public async search(entity:string, domain:any[], order:string, sort:string, start:number, limit:number) {
        var result = [];
        try {
            let params = {
                entity: entity,
                domain: domain,
                order: order,
                sort: sort,
                start: start,
                limit: limit
            };
            const environment = await EnvService.getEnv();
            const response = await $.get({
                url: environment.backend_url+'?get=model_search',
                dataType: 'json',
                data: params,
                contentType: 'application/x-www-form-urlencoded; charset=utf-8'
            });
            // reponse should be an array of ids
            result = response;
        }
        catch(response:any) {
            throw response.responseJSON;
        }
        return result;
    }

}



export default _ApiService;