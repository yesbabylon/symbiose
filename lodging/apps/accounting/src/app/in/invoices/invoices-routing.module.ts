import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { InvoicesComponent } from './invoices.component';

const routes: Routes = [
    {
        path: '',
        component: InvoicesComponent
    }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class BookingRoutingModule {}
