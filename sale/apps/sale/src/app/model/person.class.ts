
export class PersonClass {
  id: number;
  rental_unit_id: number;
  name: string;
  capacity: number;
  date_from: Date;
  date_to: Date;
  nb_nights: number;
  room_number: string;

  constructor() {
    this.id = 0;
    this.rental_unit_id = 0;
    this.name = '';
    this.capacity = 0;
    this.date_from = new Date();
    this.date_to = new Date();
    this.nb_nights = 0;
    this.name = '';
  }

}
