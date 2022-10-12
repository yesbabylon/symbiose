export class Order {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'lodging\\sale\\pos\\Order'};
    // constructor with public properties
    constructor(
        public id: number = 0,
        public created: Date = new Date(),
        public name: string = '',
        public status: string = '',
        public total: number = 0,
        public price: number = 0,
        public total_paid: number = 0,
        public customer_id: any = {},
        public has_invoice: boolean = false,
        public session_id: any = {},
        public order_payments_ids: any[] = [],
        public order_lines_ids: any[] = []
    ) {}
}