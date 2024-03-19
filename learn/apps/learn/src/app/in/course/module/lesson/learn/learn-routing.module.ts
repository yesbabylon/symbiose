import { RouterModule, Routes } from '@angular/router';
import { NgModule } from '@angular/core';
import { LearnComponent } from './learn.component';

const routes: Routes = [
    {
        path: '',
        component: LearnComponent,
    },
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule],
})
export class LearnRoutingModule {}
