import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, AuthInterceptorService, CustomDateAdapter } from 'sb-shared-lib';

import { BookingsRoutingModule } from './bookings-routing.module';

import { BookingsComponent } from './bookings.component';


@NgModule({
  imports: [
    SharedLibModule,
    BookingsRoutingModule
  ],
  declarations: [
    BookingsComponent
  ],
  providers: [
    { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
  ]
})
export class AppInBookingsModule { }
