import { RouterModule, Routes } from '@angular/router';
import { AccountComponent } from './account.component';
import { NgModule } from '@angular/core';
import { MyProfileComponent } from './my-profile/my-profile.component';
import { MyCoursesComponent } from './my-courses/my-courses.component';
import { SettingsComponent } from './settings/settings.component';

const routes: Routes = [
    {
        path: '',
        component: AccountComponent,
        children: [
            { path: '', redirectTo: 'my-profile', pathMatch: 'full' },
            { path: 'my-profile', component: MyProfileComponent },
            { path: 'my-courses', component: MyCoursesComponent },
            { path: 'settings', component: SettingsComponent },
        ],
    },
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule],
})
export class AccountRoutingModule {}
