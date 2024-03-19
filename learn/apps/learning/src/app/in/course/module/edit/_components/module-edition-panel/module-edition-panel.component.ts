import { Component, OnInit } from '@angular/core';
import { Chapter } from '../../../../../../_types/learn';
import { Router } from '@angular/router';
// @ts-ignore
import { ApiService } from 'sb-shared-lib';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';
import { MatSnackBar } from '@angular/material/snack-bar';

@Component({
    selector: 'app-module-edition-panel',
    templateUrl: './module-edition-panel.component.html',
    styleUrls: ['./module-edition-panel.component.scss'],
})
export class ModuleEditionPanelComponent implements OnInit {
    public lessons: Chapter[];

    constructor(
        private router: Router,
        private api: ApiService,
        private matSnackBar: MatSnackBar
    ) {}

    ngOnInit(): void {
        this.getLessons();
    }

    private async getLessons(): Promise<void> {
        const urlSegments: string[] = this.router.url.split('/');
        const moduleId: number = +urlSegments[urlSegments.length - 2];
        try {
            await this.api.collect(
                'learn\\Chapter',
                [['module_id', '=', moduleId]],
                ['title', 'subtitle', 'description', 'order'],
                'order'
            );
        } catch (error) {
            console.error(error);
        }
    }

    public trackLessonById(index: number, lesson: Chapter): number {
        return lesson.id;
    }

    public onDrop(event: CdkDragDrop<Chapter[]>): void {
        moveItemInArray(this.lessons, event.previousIndex, event.currentIndex);

        this.lessons.forEach((lesson: Chapter, index: number): void => {
            lesson.order = index + 1;
        });

        this.lessons.forEach((lesson: Chapter): void => {
            this.updateLessonOrder(lesson);
        });
    }

    private updateLessonOrder(lesson: Chapter): void {
        try {
            this.api.update('learn\\Chapter', [lesson.id], { order: lesson.order });

            this.matSnackBar.open(`The module has been successfully moved.`, undefined, {
                duration: 4000,
                horizontalPosition: 'left',
                verticalPosition: 'bottom',
            });
        } catch (error) {
            console.error(error);

            this.matSnackBar.open(`An error occurred while moving the module.`, undefined, {
                duration: 4000,
                horizontalPosition: 'left',
                verticalPosition: 'bottom',
            });
        }
    }
}
