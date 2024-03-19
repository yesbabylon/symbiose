import { NgModule } from '@angular/core';
import { ModuleEditionPanelComponent } from './module-edition-panel.component';
import { MatIconModule } from '@angular/material/icon';
import { MatTabsModule } from '@angular/material/tabs';
import { CommonModule } from '@angular/common';
import { DragDropModule } from '@angular/cdk/drag-drop';

@NgModule({
    imports: [MatIconModule, MatTabsModule, CommonModule, DragDropModule],
    declarations: [ModuleEditionPanelComponent],
    exports: [ModuleEditionPanelComponent],
    providers: [],
})
export class ModuleEditionPanelModule {}
