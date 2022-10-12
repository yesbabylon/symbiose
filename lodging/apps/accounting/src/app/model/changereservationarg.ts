import { HeaderDays } from './headerdays';

export class ChangeReservationArg {

  constructor(
    public type: string, 
    public operation: string, 
    public capacity: number, 
    public days: HeaderDays,
    public date_from: Date = new Date(), 
    public date_to: Date = new Date(),
    public center_id: number = 0,
  ) { }

}
