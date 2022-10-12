import { NgModule } from '@angular/core';

import { PreloadAllModules, NoPreloading, RouterModule, Routes } from '@angular/router';

import { AppComponent } from './in/app.component';

/*
    target components can be loaded simultaneously
    they will be destroyed when another routing module is loaded (either above or below in the routing tree)
*/
const routes: Routes = [
    /* routes specific to current app */
    /*
    {
        path: 'bookings',
        loadChildren: () => import(`./in/bookings/bookings.module`).then(m => m.AppInBookingsModule) 
    },
    */
    {
        /*
            default route, for bootstrapping the App
            1) load necessary info
            2) ask for permissions (and store choices)
            3) redirect to applicable page (/auth/sign or /in)
            */
        path: '',
        component: AppComponent
    }
];

@NgModule({
    imports: [
        RouterModule.forRoot(routes, { preloadingStrategy: PreloadAllModules, onSameUrlNavigation: 'reload', useHash: true })
    ],
    exports: [RouterModule]
})
export class AppRoutingModule { }
