import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';

import { BookingFundingRoutingModule } from './funding-routing.module';

import { BookingFundingComponent } from './funding.component';
import { BookingFundingInvoiceComponent } from './invoice/invoice.component';
import { BookingFundingRemindComponent } from './remind/remind.component';

@NgModule({
  imports: [
    SharedLibModule,
    BookingFundingRoutingModule
  ],
  declarations: [
    BookingFundingComponent,
    BookingFundingInvoiceComponent,
    BookingFundingRemindComponent
  ],
  providers: [
    { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
  ]
})
export class AppInBookingFundingModule { }
