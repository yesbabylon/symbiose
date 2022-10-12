export class BookingLine {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'lodging\\sale\\booking\\BookingLine'};
    // constructor with public properties
    constructor(
        public id: number = 0,
        public name: string = '',
        public description: string = '',
        public created: Date = new Date(),
        public total: number = 0,
        public price: number = 0,
        public vat_rate: number = 0,
        public booking_id: number = 0,
        public booking_line_group_id: number = 0,
        public price_id: number = 0,
        public order: number = 0,
        public qty: number = 0,
        public free_qty: number = 0,
        public discount: number = 0,
        public unit_price: number = 0,
        public fare_benefit: number = 0,
        public is_rental_unit: boolean = false,
        public is_accomodation: boolean = false,
        public is_meal: boolean = false,
        public qty_accounting_method: string = '',
        public qty_vars: string = '',
        public product_id: any = {},
        public auto_discounts_ids: any[] = [],
        public manual_discounts_ids: any[] = []
    ) {}
}