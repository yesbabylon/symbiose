import { NgModule } from '@angular/core';

import { PreloadAllModules, RouterModule, Routes } from '@angular/router';

import { AppComponent } from './in/app.component';

const routes: Routes = [
  /* routes specific to current app */
  {
    path: 'import',
    loadChildren: () => import(`./in/import/documents.import.module`).then(m => m.AppInDocumentsModule)
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
    RouterModule.forRoot(routes, { preloadingStrategy: PreloadAllModules })
  ],
  exports: [RouterModule]
})
export class AppRoutingModule { }
