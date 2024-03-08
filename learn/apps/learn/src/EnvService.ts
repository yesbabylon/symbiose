/**
 * This service centralizes environment vars
 */
export class _EnvService {

    private environment:any = null;
    private promise:any = null;

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
                    this.assignEnv({...this.default, ...env});
                    resolve(this.environment);
                }
                catch(response) {
                    // config.json not found, fallback to default.json
                    try {
                        const response:Response = await fetch('/assets/env/default.json');
                        const env = await response.json();
                        this.assignEnv({...this.default, ...env});
                        resolve(this.environment);
                    }
                    catch(response) {
                        // default.json not found, fallback to default values
                        this.assignEnv({...this.default});
                        resolve(this.environment);
                    }
                }
            });
        }
        return this.promise;
    }

    /**
     * Assign and adapter to support older version of the URL syntax
     */
    private assignEnv(environment: any) {
        if(environment.hasOwnProperty('backend_url')) {
            if(environment.backend_url.replace('://','').indexOf('/') == -1) {
                environment.backend_url += '/';
            }
        }
        this.environment = {...environment};
    }

    public setEnv(property: string, value: any) {
        if(this.environment) {
            this.environment[property] = value;
        }
    }

    public formatNumber(value:number, scale:number = 0, thousand_sep:string=',', decimal_sep:string='.') {
        if(this.environment) {
            if(this.environment.hasOwnProperty('core.locale.numbers.decimal_precision')) {
                scale = this.environment['core.locale.numbers.decimal_precision'];
            }
            if(this.environment.hasOwnProperty('core.locale.numbers.decimal_separator')) {
                decimal_sep = this.environment['core.locale.numbers.decimal_separator'];
            }
            if(this.environment.hasOwnProperty('core.locale.numbers.thousands_separator')) {
                thousand_sep = this.environment['core.locale.numbers.thousands_separator'];
            }
            let parts:any = value.toFixed(scale).split(".");
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousand_sep);
            if(scale > 0 && parts.length == 1) {
                parts[1] = ''.padStart(scale, '0');
            }
            return parts.join(decimal_sep);
        }
        return value.toLocaleString();
    }

    public formatCurrency(value:number, scale:number = 2, thousand_sep:string=',', decimal_sep:string='.') {
        if(this.environment && this.environment.hasOwnProperty('core.locale.currency.decimal_precision')) {
            scale = this.environment['core.locale.currency.decimal_precision'];
        }
        let result = this.formatNumber(value, scale, thousand_sep, decimal_sep);
        if(this.environment.hasOwnProperty('core.units.currency')) {
            if(this.environment.hasOwnProperty('core.locale.currency.symbol_position') && this.environment['core.locale.currency.symbol_position'] == 'before') {
                result = this.environment['core.units.currency'] + ' ' + result;
            }
            else {
                result = result + ' ' + this.environment['core.units.currency'];
            }
        }
        else {
            result = '$ ' + value;
        }
        return result;
    }

}


export default _EnvService;