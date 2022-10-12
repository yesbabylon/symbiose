import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, AuthInterceptorService, CustomDateAdapter } from 'sb-shared-lib';

import { PaymentsRoutingModule } from './payments-routing.module';

import { PaymentsComponent } from '../payments/payments.component';




import { PaymentsImportComponent, PaymentsImportDialogConfirm } from './import/payments.import.component';

@NgModule({
  imports: [
    SharedLibModule,
    PaymentsRoutingModule
  ],
  declarations: [
    PaymentsComponent, 
    PaymentsImportComponent, PaymentsImportDialogConfirm
  ],
  providers: [
    { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
  ]
})
export class AppInPaymentsModule { }
