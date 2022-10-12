import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { SharedLibModule } from 'sb-shared-lib';


import { AppKeypadLinesComponent, PosOpeningDialog } from './in/_components/keypad-lines/keypad-lines.component';
import { AppKeypadPaymentComponent } from './in/_components/keypad-payment/keypad-payment.component';
import { AppPadGenericComponent } from './in/_components/pad/generic/generic.component';
import { AppPadTypeToggleComponent } from './in/_components/pad/type-toggle/type-toggle.component';
import { AppPadValueIncrementsComponent } from './in/_components/pad/value-increments/value-increments.component';



@NgModule({
    declarations: [
        AppKeypadLinesComponent,
        AppKeypadPaymentComponent,
        AppPadGenericComponent,
        AppPadTypeToggleComponent,
        AppPadValueIncrementsComponent,
        PosOpeningDialog
    ],
    imports: [
        CommonModule,
        SharedLibModule
    ],
    exports: [CommonModule, SharedLibModule, AppPadGenericComponent, AppPadValueIncrementsComponent, AppKeypadLinesComponent, AppKeypadPaymentComponent, PosOpeningDialog],
    providers: [
    ]
})
export class AppSharedModule { }