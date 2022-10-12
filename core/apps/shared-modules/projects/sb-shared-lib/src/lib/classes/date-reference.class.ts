/**
 * Class for Date descriptors parsing
 * 
 * 
 
    ## date parser

    * soit une chaine ISO
    * soit une description au format relatif à NOW

    Result of the parsing is always a date.


    Syntaxe:
    date.[this|prev|next].[day|week|month|quarter|semester|year].[first|last]


    * today = date.this.day

    * first day of current year = date.this.year.first

    * last day of last week = date.prev.week.last

 *
 */
export class DateReference {

    private date: Date;

    constructor(descriptor:string) {
        this.date = new Date();
        this.parse(descriptor);
    }


    /**
     *
     * descriptor syntax: date.[this|prev|next].[day|week|month|quarter|semester|year].[first|last]
     * @param descriptor
     */
    public parse(descriptor:string) {
        let date = new Date(descriptor);
        if(!descriptor || !isNaN(date.getMonth())) {
            this.date = date;
        }
        else {
            // init at today
            date = new Date();
            descriptor = descriptor.toLowerCase();
            if(descriptor.indexOf('date.') == 0) {
                let parts = descriptor.split('.');
                let len = parts.length;
                if(len > 2) {
                    let offset = (parts[1] == 'prev')? -1 : ((parts[1] == 'next')? 1 : 0);
                    let day = (len >= 4 && parts[3] == 'last')?'last':'first';

                    switch(parts[2]) {
                        case 'day':
                            this.date = new Date(date);
                            this.date.setDate(date.getDate() + offset);
                            break;
                        case 'week':
                            this.date = new Date(date);
                            let dow = date.getDay(), diff = -dow + (dow == 0 ? -6:1);
                            this.date.setDate(date.getDate() + diff + offset * 7);
                            if(day == 'last') {
                                this.date.setDate(this.date.getDate() + 6);
                            }
                            break;
                        case 'month':
                            this.date = new Date(date.getFullYear(), date.getMonth() + offset, 1);
                            if(day == 'last') {
                                this.date = new Date(date.getFullYear(), date.getMonth() + offset + 1, 0);
                            }
                            break;
                        case 'quarter':
                            break;
                        case 'semester':
                            break;
                        case 'year':
                            this.date = new Date(date.getFullYear() + offset, 0, 1);
                            if(day == 'last') {
		                        this.date = new Date(date.getFullYear() + offset, 11, 31);
                            }
                            break;
                    }
                }
            }
        }
    }

    public getDate()  {
        return this.date;
    }


}

export default DateReference;