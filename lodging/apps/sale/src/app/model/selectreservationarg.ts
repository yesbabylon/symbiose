
export class SelectReservationArg {

  constructor(
    public rental_unit_id: number, 
    public date_from: Date, 
    public date_to: Date
  ) { }

}
