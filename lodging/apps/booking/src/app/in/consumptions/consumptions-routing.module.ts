import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { ConsumptionsComponent } from './consumptions.component';

const routes: Routes = [
    {
        path: '',
        component: ConsumptionsComponent
    }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class BookingRoutingModule {}
