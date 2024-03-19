import { NgModule } from '@angular/core';
import { LessonEditionPanelComponent } from './lesson-edition-panel.component';
import { MatIconModule } from '@angular/material/icon';
import { MatTabsModule } from '@angular/material/tabs';
import { CommonModule } from '@angular/common';

@NgModule({
    imports: [MatIconModule, MatTabsModule, CommonModule],
    declarations: [LessonEditionPanelComponent],
    exports: [LessonEditionPanelComponent],
    providers: [],
})
export class LessonEditionPanelModule {}
