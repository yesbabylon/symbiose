import { NgModule } from '@angular/core';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';


import { SharedLibModule, AuthInterceptorService, CustomDateAdapter } from 'sb-shared-lib';

import { BookingRoutingModule } from './consumptions-routing.module';

import { ConsumptionsComponent } from './consumptions.component';


@NgModule({
    imports: [
        SharedLibModule,
        BookingRoutingModule
    ],
    declarations: [
        ConsumptionsComponent
    ],
    providers: [
        { provide: DateAdapter, useClass: CustomDateAdapter, deps: [MAT_DATE_LOCALE, Platform] }
    ]
})
export class AppInConsumptionsModule { }
