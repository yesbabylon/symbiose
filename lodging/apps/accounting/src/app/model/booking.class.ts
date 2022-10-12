
export class BookingDayClass {

  constructor(
    public id:number = 0,
    public booking_line_id = 0,
    public rental_unit_id = 0,
    public capacity:number = 0,
    public date: Date = new Date(),
    public date_from: Date = new Date(),
    public date_to: Date = new Date(),
    public schedule_from: string = '00:00:00',
    public schedule_to: string = '24:00:00',
    public nb_nights:number = 0,
    public booking_ref: string = '',
    public booking_status: string = '',
    public booking_customer_id: number = 0,
    public booking_customer_name: string = '',
    public booking_payment_status: string = '',
    public booking_contacts_ids: number[] = []
  ) {}

}