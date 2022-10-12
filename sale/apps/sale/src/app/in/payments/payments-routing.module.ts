import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { PaymentsComponent } from '../payments/payments.component';

import { PaymentsImportComponent } from './import/payments.import.component';


const routes: Routes = [
    {
        path: '',
        component: PaymentsComponent
    },    
    {
        path: 'import',        
        component: PaymentsImportComponent
    }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class PaymentsRoutingModule {}
