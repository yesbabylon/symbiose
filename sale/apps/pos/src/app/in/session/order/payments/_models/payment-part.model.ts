export class OrderPaymentPart {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'lodging\\sale\\pos\\OrderPaymentPart'};
    // constructor with public properties
    constructor(
        public id: number = 0,
        public order_id: number = 0,
        public order_payment_id: number = 0,
        public amount: number = 0.0,
        public payment_method: string = '',
        public booking_id: number = 0.0,
        public voucher_ref: string = '',
        public status: string = ''
    ) {}
}