import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { InvoiceComponent } from './invoice.component';
import { BookingContractComponent } from './contract/contract.component';
import { BookingQuoteComponent } from './quote/quote.component';
import { BookingInvoiceComponent } from './invoice/invoice.component';

const routes: Routes = [
    {
        path: 'contract',
        component: BookingContractComponent
    },
    {
        path: 'quote',
        component: BookingQuoteComponent
    },
    {
        path: 'invoice/:invoice_id',
        component: BookingInvoiceComponent
    },
    // wildcard route (accept root and any sub route that does not match any of the routes above)
    {
        path: '**',
        component: InvoiceComponent
    }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class BookingRoutingModule {}
