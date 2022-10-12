import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { BookingFundingComponent } from './funding.component';
import { BookingFundingInvoiceComponent } from './invoice/invoice.component';
import { BookingFundingRemindComponent } from './remind/remind.component';


const routes: Routes = [
    {
        path: 'invoice',
        component: BookingFundingInvoiceComponent
    },
    {
        path: 'remind',
        component: BookingFundingRemindComponent
    },
    // wildcard route (accept root and any sub route that does not match any of the routes above)
    {
        path: '**',
        component: BookingFundingComponent
    }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class BookingFundingRoutingModule {}
