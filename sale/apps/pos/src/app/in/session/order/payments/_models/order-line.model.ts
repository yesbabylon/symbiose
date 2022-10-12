export class OrderLine {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'lodging\\sale\\pos\\OrderLine'};
    // constructor with public properties
    constructor(
        public id: number = 0,
        public order_id: number = 0,
        public order_payment_id: number = 0,
        public created: Date = new Date(),
        public name: string = '',
        public unit_price: number = 0.0,
        public vat_rate: number = 0.0,
        public discount: number = 0.0,
        public qty: number = 0,
        public free_qty: number = 0,
        public total: number = 0,
        public price: number = 0,
        public funding_id: number = 0,
        public has_funding: boolean = false
    ) {}
}