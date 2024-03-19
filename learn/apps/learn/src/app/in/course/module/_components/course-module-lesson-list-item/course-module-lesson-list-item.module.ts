import { NgModule } from '@angular/core';
import { CourseModuleLessonListItemComponent } from './course-module-lesson-list-item.component';
import { MatIconModule } from '@angular/material/icon';
import { CommonModule } from '@angular/common';
import { DragDropModule } from '@angular/cdk/drag-drop';

@NgModule({
    imports: [MatIconModule, CommonModule, DragDropModule],
    declarations: [CourseModuleLessonListItemComponent],
    exports: [CourseModuleLessonListItemComponent],
})
export class CourseModuleLessonListItemModule {}
