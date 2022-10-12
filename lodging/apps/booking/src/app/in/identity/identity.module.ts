import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';

import { IdentityRoutingModule } from './identity-routing.module';
import { IdentityComponent } from './identity.component';


@NgModule({
  imports: [
    SharedLibModule,
    IdentityRoutingModule
  ],
  declarations: [
    IdentityComponent
  ],
  providers: [
    { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
  ]
})
export class AppInIdentityModule { }
