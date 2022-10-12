import { NgModule } from '@angular/core';

import { PreloadAllModules, RouterModule, Routes } from '@angular/router';

import { AppComponent } from './in/app.component';

const routes: Routes = [
  /* routes specific to current app */
  {
    path: 'payments',
    loadChildren: () => import(`./in/payments/payments.module`).then(m => m.AppInPaymentsModule) 
  },
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
    RouterModule.forRoot(routes, { preloadingStrategy: PreloadAllModules, onSameUrlNavigation: 'reload', useHash: true  })  
  ],
  exports: [RouterModule]
})
export class AppRoutingModule { }
