import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';

import { BookingRoutingModule } from './invoice-routing.module';

import { InvoiceComponent } from './invoice.component';


import { BookingQuoteComponent } from './quote/quote.component';
import { BookingContractComponent } from './contract/contract.component';
import { BookingInvoiceComponent } from './invoice/invoice.component';


@NgModule({
  imports: [
    SharedLibModule,
    BookingRoutingModule
  ],
  declarations: [
    InvoiceComponent, 
    BookingQuoteComponent, 
    BookingContractComponent,
    BookingInvoiceComponent
  ],
  providers: [
    { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
  ]
})
export class AppInBookingModule { }
