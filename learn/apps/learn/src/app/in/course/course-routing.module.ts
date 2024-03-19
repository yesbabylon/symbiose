import { RouterModule, Routes } from '@angular/router';
import { CourseComponent } from './course.component';
import { NgModule } from '@angular/core';
import { CourseEditComponent } from './edit/course-edit.component';

const routes: Routes = [
    {
        path: '',
        component: CourseComponent,
    },
    {
        path: 'edit',
        component: CourseEditComponent,
    },
    {
        path: 'module/:id',
        loadChildren: () => import('./module/module.module').then(m => m.AppInCourseModuleModule),
    },
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule],
})
export class CourseRoutingModule {}
