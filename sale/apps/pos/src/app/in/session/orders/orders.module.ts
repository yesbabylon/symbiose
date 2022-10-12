import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, AuthInterceptorService, CustomDateAdapter } from 'sb-shared-lib';

import { SessionOrdersRoutingModule } from './orders-routing.module';

import { SessionOrdersComponent } from './orders.component';
import { SessionOrdersNewComponent } from './new/new.component';
import { AppInSessionOrderModule } from '../order/order.module';

@NgModule({
  imports: [
    SharedLibModule,
    SessionOrdersRoutingModule,
    AppInSessionOrderModule
  ],
  declarations: [
    SessionOrdersComponent,
    SessionOrdersNewComponent
  ],
  providers: [
    { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
  ]
})
export class AppInSessionOrdersModule { }
