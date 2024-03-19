import { NgModule } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTabsModule } from '@angular/material/tabs';
import { ModuleComponent } from './module.component';
import { CourseModuleLessonListItemModule } from './_components/course-module-lesson-list-item/course-module-lesson-list-item.module';
import { ModuleRoutingModule } from './module-routing.module';
import { ModuleEditionPanelModule } from './edit/_components/module-edition-panel/module-edition-panel.module';
import { ModuleEditComponent } from './edit/module-edit.component';
import { CommonModule } from '@angular/common';
// @ts-ignore
import { SharedLibModule } from 'sb-shared-lib';
import { AppInCoursesModule } from '../../courses/courses.module';

@NgModule({
    imports: [
        ModuleRoutingModule,
        MatTabsModule,
        MatButtonModule,
        MatIconModule,
        CourseModuleLessonListItemModule,
        ModuleEditionPanelModule,
        CommonModule,
        SharedLibModule,
        SharedLibModule,
        AppInCoursesModule,
    ],
    declarations: [ModuleComponent, ModuleEditComponent],
})
export class AppInCourseModuleModule {}
