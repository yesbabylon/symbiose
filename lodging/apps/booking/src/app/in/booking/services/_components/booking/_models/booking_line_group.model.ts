export class BookingLineGroup {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'lodging\\sale\\booking\\BookingLineGroup'};
    // constructor with public properties
    constructor(
        public id: number = 0,
        public name: string = '',
        public created: Date = new Date(),
        public total: number = 0,
        public price: number = 0,
        public fare_benefit: number = 0,
        public booking_id: number = 0,
        public order: number = 0,
        public has_pack: boolean = false,
        public is_locked: boolean = false,
        public is_autosale: boolean = false,
        public is_extra: boolean = false,
        public has_schedulable_services: boolean = false,
        public date_from: Date = new Date(),
        public date_to: Date = new Date(),
        public time_from: string = '',
        public time_to: string = '',
        public nb_pers: number = 0,
        public nb_nights: number = 0,
        public is_sojourn: boolean = false,
        public is_event: boolean = false,
        public has_locked_rental_units: boolean = false,
        public pack_id: any = {},
        public sojourn_type_id: number = 0,
        public rate_class_id: any = {},
        public booking_lines_ids: any[] = [],
        public meal_preferences_ids: any[] = [],
        public age_range_assignments_ids: any[] = [],
        public sojourn_product_models_ids: any[] = []
    ) {}
}