import { NgModule } from '@angular/core';
import { CourseEditionPanelComponent } from './course-edition-panel.component';
import { MatIconModule } from '@angular/material/icon';
import { MatTabsModule } from '@angular/material/tabs';
import { CommonModule } from '@angular/common';
import { DragDropModule } from '@angular/cdk/drag-drop';
import { MatTreeModule } from '@angular/material/tree';
import { MatButtonModule } from '@angular/material/button';

@NgModule({
    imports: [MatIconModule, MatTabsModule, CommonModule, DragDropModule, MatTreeModule, MatButtonModule],
    declarations: [CourseEditionPanelComponent],
    exports: [CourseEditionPanelComponent],
    providers: [],
})
export class CourseEditionPanelModule {}
