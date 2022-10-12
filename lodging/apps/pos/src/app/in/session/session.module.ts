import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';
import { SharedLibModule, AuthInterceptorService, CustomDateAdapter } from 'sb-shared-lib';
import { AppSharedModule } from '../../shared.module';
import { SessionRoutingModule } from './session-routing.module';
import { SessionComponent } from './session.component';
import { SessionCloseComponent } from './close/close.component';
import { SessionMoveComponent } from './move/move.component';
import { SessionCloseInventoryDialog } from './close/_components/inventory/inventory.dialog';

@NgModule({
  imports: [
    SharedLibModule,
    SessionRoutingModule,
    AppSharedModule
  ],
  declarations: [
    SessionComponent,
    SessionCloseComponent,
    SessionMoveComponent,
    SessionCloseInventoryDialog
  ],
  providers: [
    { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
  ]
})
export class AppInSessionModule { }
