import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, AuthInterceptorService, CustomDateAdapter } from 'sb-shared-lib';

import { SessionsRoutingModule } from './sessions-routing.module';

import { SessionsComponent } from './sessions.component';
import { SessionsNewComponent } from './new/new.component';
import { SessionsNewInventoryDialog } from './new/_components/inventory/inventory.dialog';

import { AppSharedModule } from '../../shared.module';


@NgModule({
  imports: [
    SharedLibModule,
    AppSharedModule,
    SessionsRoutingModule
  ],
  declarations: [
    SessionsComponent,
    SessionsNewComponent,
    SessionsNewInventoryDialog
  ],
  providers: [
    { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
  ]
})
export class AppInSessionsModule { }
