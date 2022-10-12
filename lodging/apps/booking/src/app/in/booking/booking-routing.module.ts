import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { BookingComponent } from './booking.component';
import { BookingServicesComponent } from './services/services.component';
import { BookingContractComponent } from './contract/contract.component';
import { BookingCompositionComponent } from './composition/composition.component';
import { BookingQuoteComponent } from './quote/quote.component';
import { BookingInvoiceComponent } from './invoice/invoice.component';
import { BookingOptionComponent } from './option/option.component';

const routes: Routes = [
    {
        path: 'services',
        component: BookingServicesComponent
    },
    {
        path: 'contract/:contract_id',
        component: BookingContractComponent
    },
    {
        path: 'composition',
        component: BookingCompositionComponent
    },
    {
        path: 'quote',
        component: BookingQuoteComponent
    },
    {
        path: 'option',
        component: BookingOptionComponent
    },
    {
        path: 'invoice/:invoice_id',
        component: BookingInvoiceComponent
    },
    {
        path: 'funding/:funding_id',
        loadChildren: () => import(`./funding/funding.module`).then(m => m.AppInBookingFundingModule)
    },
    // wildcard route (accept root and any sub route that does not match any of the routes above)
    {
        path: '**',
        component: BookingComponent
    }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class BookingRoutingModule {}
