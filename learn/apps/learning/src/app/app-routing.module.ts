import { NgModule } from '@angular/core';
import { PreloadAllModules, RouterModule, Routes } from '@angular/router';
import { AppComponent } from './in/app.component';

const routes: Routes = [
    /* routes specific to current app */
    {
        /*
		 default route, for bootstrapping the App
		  1) display a loader and try to authentify
		  2) store user details (roles and permissions)
		  3) redirect to applicable page (/apps or /auth)
		 */
        path: '',
        component: AppComponent,
    },
    {
        path: 'account',
        loadChildren: () => import('./in/account/account.module').then(m => m.AppInAccountModule),
    },
    {
        path: 'courses',
        loadChildren: () => import('./in/courses/courses.module').then(m => m.AppInCoursesModule),
    },
    {
        path: 'course/:id',
        loadChildren: () => import('./in/course/course.module').then(m => m.AppInCourseModule),
    },
];

@NgModule({
    imports: [
        RouterModule.forRoot(routes, {
            preloadingStrategy: PreloadAllModules,
            onSameUrlNavigation: 'reload',
            useHash: true,
        }),
    ],
    exports: [RouterModule],
})
export class AppRoutingModule {}
