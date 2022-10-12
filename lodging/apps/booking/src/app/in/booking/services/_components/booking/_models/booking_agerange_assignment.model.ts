export class BookingAgeRangeAssignment {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'lodging\\sale\\booking\\BookingLineGroupAgeRangeAssignment'};
    // constructor with public properties
    constructor(
        public id: number = 0,
        public age_range_id: any = {},
        public qty: number = 0
    ) {}
}