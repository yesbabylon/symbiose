import { NgModule } from '@angular/core';
import { CourseModuleLessonPageListItemComponent } from './course-module-lesson-page-list-item.component';
import { MatIconModule } from '@angular/material/icon';

@NgModule({
    imports: [MatIconModule],
    declarations: [CourseModuleLessonPageListItemComponent],
    exports: [CourseModuleLessonPageListItemComponent],
})
export class CourseModuleLessonPageListItemModule {}
