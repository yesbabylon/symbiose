export class OrderPayment {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'lodging\\sale\\pos\\OrderPayment'};
    // constructor with public properties
    constructor(
        public id: number = 0,
        public order_id: number = 0,
        public total_due: number = 0.0,
        public total_paid: number = 0.0,
        public order_lines_ids: any[] = [],
        public order_payment_parts_ids: any[] = [],
        public funding_id: number = 0,
        public has_funding: boolean = false,
        public status: string = ''
    ) {}
}