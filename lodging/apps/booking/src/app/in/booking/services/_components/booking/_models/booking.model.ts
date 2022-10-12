export class Booking {
    // index signature
    [key: string]: any;
    // model entity
    public get entity():string { return 'lodging\\sale\\booking\\Booking'};
    // constructor with public properties
    constructor(
      public id: number = 0,
      public name: string = '',
      public total: number = 0,
      public price: number = 0,
      public status: string = '',
      public date_from: Date = new Date(),
      public date_to: Date = new Date(),
      public center_id: any = {},
      public customer_id: any = {},
      public booking_lines_groups_ids: any[] = []
    ) {}
}