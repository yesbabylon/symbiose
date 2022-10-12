import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { SessionOrdersComponent } from './orders.component';
import { SessionOrdersNewComponent } from './new/new.component';


const routes: Routes = [
    {
        path: 'new',
        component: SessionOrdersNewComponent
    },
    {
        path: '',
        component: SessionOrdersComponent
    }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class SessionOrdersRoutingModule {}
