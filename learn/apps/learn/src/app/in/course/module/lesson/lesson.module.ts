import { NgModule } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTabsModule } from '@angular/material/tabs';
import { LessonRoutingModule } from './lesson-routing.module';
import { LessonComponent } from './lesson.component';
import { CourseModuleLessonPageListItemModule } from './_components/course-module-lesson-page-list-item/course-module-lesson-page-list-item.module';
import { LessonEditionPanelModule } from './edit/_components/lesson-edition-panel/lesson-edition-panel.module';
import { LessonEditComponent } from './edit/lesson-edit.component';
import { CommonModule } from '@angular/common';
import { SharedLibModule } from 'sb-shared-lib';
import { AppInCoursesModule } from '../../../courses/courses.module';

@NgModule({
    imports: [
        LessonRoutingModule,
        MatButtonModule,
        MatIconModule,
        MatTabsModule,
        CourseModuleLessonPageListItemModule,
        LessonEditionPanelModule,
        CommonModule,
        SharedLibModule,
        AppInCoursesModule,
    ],
    declarations: [LessonComponent, LessonEditComponent],
})
export class AppInCourseModuleLessonModule {}
