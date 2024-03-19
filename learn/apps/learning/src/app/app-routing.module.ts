import { NgModule } from '@angular/core';
import { PreloadAllModules, Route, RouterModule } from '@angular/router';
import { AppComponent } from './in/app.component';
import { LargeComponent } from './in/large/large.component';
import { SmallComponent } from './in/small/small.component';

const routes: Route[] = [
    {
        path: ':slug',
        component: AppComponent,
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
