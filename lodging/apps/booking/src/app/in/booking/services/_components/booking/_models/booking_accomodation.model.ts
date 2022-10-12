export class BookingAccomodation {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'lodging\\sale\\booking\\SojournProductModel'};
    // constructor with public properties
    constructor(
        public id: number = 0,
        public qty: number = 0,
        public product_model_id: any = {},
        public booking_line_group_id: number = 0,
        public rental_unit_assignments_ids: any[] = []
    ) {}
}