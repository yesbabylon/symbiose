import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { RepairingsComponent } from './repairings.component';
import { RepairingsRepairingComponent } from './repairing/repairing.component';

const routes: Routes = [
    {
        path: 'repairing/:repairing_id',
        component: RepairingsRepairingComponent
    },
    // wildcard route (accept root and any sub route that does not match any of the routes above)
    {
        path: '**',
        component: RepairingsComponent
    }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class RepairingsRoutingModule {}
