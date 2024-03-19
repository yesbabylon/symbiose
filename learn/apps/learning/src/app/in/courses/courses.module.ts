import { NgModule } from '@angular/core';
import { CoursesRoutingModule } from './courses-routing.module';
import { CoursesComponent } from './courses.component';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { CommonModule } from '@angular/common';
import { TopBarComponent } from '../../_components/top-bar/top-bar.component';
import { BreadcrumbComponent } from '../../_components/breadcrumb/breadcrumb.component';

@NgModule({
    imports: [CoursesRoutingModule, MatCardModule, MatButtonModule, CommonModule],
    declarations: [CoursesComponent, TopBarComponent, BreadcrumbComponent],
    exports: [TopBarComponent],
})
export class AppInCoursesModule {}
