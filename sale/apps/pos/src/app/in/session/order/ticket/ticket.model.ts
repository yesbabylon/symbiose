export class Order {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'sale\\pos\\Order'};
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
        public invoice_id: any = {},
        public session_id: any = {},
        public order_payment_parts_ids: any[] = [],
        public order_lines_ids: any[] = []
    ) {}
}

export class OrderLine {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'sale\\pos\\OrderLine'};
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

export class OrderPayment {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'sale\\pos\\OrderPayment'};
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
        public status: string = 'pending'
    ) {}
}

export class OrderPaymentPart {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'sale\\pos\\OrderPaymentPart'};
    // constructor with public properties
    constructor(
        public id: number = 0,
        public order_id: number = 0,
        public order_payment_id: number = 0,
        public amount: number = 0.0,
        public payment_method: string = '',
        public booking_id: number = 0.0,
        public voucher_ref: string = ''
    ) {}
}

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