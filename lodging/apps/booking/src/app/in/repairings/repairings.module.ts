import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';

import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';

import { RepairingsRoutingModule } from './repairings-routing.module';

import { RepairingsComponent } from './repairings.component';
import { RepairingsRepairingComponent } from './repairing/repairing.component';

@NgModule({
  imports: [
    SharedLibModule,
    RepairingsRoutingModule
  ],
  declarations: [
    RepairingsComponent, RepairingsRepairingComponent
  ],
  providers: [
    { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
  ]
})
export class AppInRepairingsModule { }