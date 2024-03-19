import { NgModule } from '@angular/core';
import { CourseModuleListItemComponent } from './course-module-list-item.component';
import { MatIconModule } from '@angular/material/icon';
import { CommonModule } from '@angular/common';
import { DragDropModule } from '@angular/cdk/drag-drop';

@NgModule({
    imports: [MatIconModule, CommonModule, DragDropModule],
    declarations: [CourseModuleListItemComponent],
    exports: [CourseModuleListItemComponent],
})
export class CourseModuleListItemModule {}
