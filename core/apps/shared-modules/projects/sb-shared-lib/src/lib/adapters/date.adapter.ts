import { Inject, LOCALE_ID } from '@angular/core';
import { NativeDateAdapter } from '@angular/material/core';
import { parse, format as dateFnsFormat } from 'date-fns';

export class CustomDateAdapter extends NativeDateAdapter {
    

  /*
   * 
   * #todo - adapt this to run according the current user's locale
   */
  
  getDisplayFormat() : string {
    let lang = this.locale;
    var date_format = 'MM/dd/yyyy';

    // handle dash notation (ignore with country code)
    const parts = this.locale.split('-');
    if(parts.length > 1) {
      lang = parts[0];
    }      

    switch(lang) {
      case 'en':
        date_format = 'MM/dd/yyyy';
        break;
      case 'fr':
        date_format = 'dd/MM/yyyy';
        break;
    }
    return date_format;
  }        

  parse(value: string | null): Date | null {
    var DT_FORMAT = this.getDisplayFormat();

    if (value) {
        value = value.trim()
        if(!value.match(/^\d{1,2}\/\d{1,2}\/\d{4}$/)) {
            return new Date(NaN);
        }
        return parse(value, DT_FORMAT, new Date())
    }
    return null;
  }

  format(date: Date, displayFormat: Object): string {
    var DT_FORMAT = this.getDisplayFormat();
    return dateFnsFormat(date, DT_FORMAT)
  }

  getFirstDayOfWeek(): number {
    return 1; // Monday
  }
}