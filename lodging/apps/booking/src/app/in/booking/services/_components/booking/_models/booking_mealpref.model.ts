export class BookingMealPref {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'sale\\booking\\MealPreference'};
    // constructor with public properties
    constructor(
        public id: number = 0,
        public type: string = '',
        public pref: string = '',
        public qty: number = 0
    ) {}
}