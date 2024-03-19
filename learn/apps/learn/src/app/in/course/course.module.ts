import { NgModule } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatTabsModule } from '@angular/material/tabs';
import { CourseComponent } from './course.component';
import { CourseRoutingModule } from './course-routing.module';
import { CourseModuleListItemModule } from './_components/course-module-list-item/course-module-list-item.module';
import { CourseEditComponent } from './edit/course-edit.component';
import { CourseEditionPanelModule } from './edit/_components/course-edition-panel/course-edition-panel.module';
import { CommonModule } from '@angular/common';
// @ts-ignore
import { SharedLibModule } from 'sb-shared-lib';
import { AppInCoursesModule } from '../courses/courses.module';

@NgModule({
    imports: [
        CourseRoutingModule,
        MatButtonModule,
        MatIconModule,
        CourseModuleListItemModule,
        CourseEditionPanelModule,
        MatTabsModule,
        MatInputModule,
        CommonModule,
        SharedLibModule,
        AppInCoursesModule,
    ],
    declarations: [CourseComponent, CourseEditComponent],
    exports: [],
})
export class AppInCourseModule {}
