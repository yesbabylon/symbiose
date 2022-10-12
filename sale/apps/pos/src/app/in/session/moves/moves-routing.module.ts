import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { SessionMovesComponent } from './moves.component';
// import { BookingEditComponent } from './edit/booking.edit.component';


const routes: Routes = [
    {
        path: '',
        component: SessionMovesComponent
    }
    /*
    ,
    {
        path: 'edit/:id',
        component: BookingEditComponent
    },
    {
        path: 'edit',
        component: BookingEditComponent
    }
    */
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class SessionMovesRoutingModule {}
