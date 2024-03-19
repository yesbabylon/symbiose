import { Route, RouterModule, Routes } from '@angular/router';
import { CoursesComponent } from './courses.component';
import { NgModule } from '@angular/core';

const routes: Routes = [
    {
        path: '',
        component: CoursesComponent,
    },
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule],
})
export class CoursesRoutingModule {}
