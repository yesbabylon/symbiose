export class BookingPriceAdapter {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'lodging\\sale\\booking\\BookingPriceAdapter'};
    // constructor with public properties
    constructor(
      public id: number = 0,
      public name: string = '',
      public value: number = 0,      
      public type: string = 'percent',
      public is_manual_discount: boolean = true,
      public discount_id: any = {}
    ) {}
}