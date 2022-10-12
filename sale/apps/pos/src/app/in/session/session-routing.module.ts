import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { SessionCloseComponent } from './close/close.component';
import { SessionMoveComponent } from './move/move.component';
import { SessionComponent } from './session.component';

const routes: Routes = [
    {
        path: 'moves',
        loadChildren: () => import(`./moves/moves.module`).then(m => m.AppInSessionMovesModule)
    },
    {
        path: 'orders',
        loadChildren: () => import(`./orders/orders.module`).then(m => m.AppInSessionOrdersModule)
    },
    {
        path: 'order/:order_id',
        loadChildren: () => import(`./order/order.module`).then(m => m.AppInSessionOrderModule)
    },
    {
        path: 'move',
        component: SessionMoveComponent
    },
    {
        path: 'close',
        component: SessionCloseComponent
    },
    {
        path: '',
        component: SessionComponent
    }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class SessionRoutingModule {}
