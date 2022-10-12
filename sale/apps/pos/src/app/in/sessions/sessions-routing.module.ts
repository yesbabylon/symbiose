import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { SessionsNewComponent } from './new/new.component';
import { SessionsComponent } from './sessions.component';

const routes: Routes = [
    {
        path: 'new',
        component: SessionsNewComponent
    },
    {
        path: '**',
        component: SessionsComponent
    }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class SessionsRoutingModule {}
