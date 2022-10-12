export class Customer {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'sale\\customer\\Customer'};
    // constructor with public properties
    constructor(
        public id: number = 0,
        public name: string = '',
        public partner_identity_id: number = 0,
    ) {}
}