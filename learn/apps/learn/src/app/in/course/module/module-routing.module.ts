import { RouterModule, Routes } from '@angular/router';
import { ModuleComponent } from './module.component';
import { NgModule } from '@angular/core';
import { ModuleEditComponent } from './edit/module-edit.component';

const routes: Routes = [
    {
        path: '',
        component: ModuleComponent,
    },
    {
        path: 'edit',
        component: ModuleEditComponent,
    },
    {
        path: 'lesson/:id',
        loadChildren: () => import('./lesson/lesson.module').then(m => m.AppInCourseModuleLessonModule),
    },
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule],
})
export class ModuleRoutingModule {}
