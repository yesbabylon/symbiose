import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
// @ts-ignore
import { ApiService } from 'sb-shared-lib';
import { Chapter, Module } from '../../../../../_types/learn';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';
import { MatSnackBar } from '@angular/material/snack-bar';

@Component({
    selector: 'app-course-module-lesson-list-item',
    templateUrl: './course-module-lesson-list-item.component.html',
    styleUrls: ['./course-module-lesson-list-item.component.scss'],
})
export class CourseModuleLessonListItemComponent implements OnInit {
    public lessons: Chapter[];

    constructor(
        private router: Router,
        private route: ActivatedRoute,
        private api: ApiService,
        private matSnackBar: MatSnackBar
    ) {}

    public navigateToLesson(lessonId: string | number): void {
        if (this.router.url.includes('edit')) {
            this.router.navigate([`lesson/${lessonId}/edit`], {
                relativeTo: this.route.parent,
            });
        } else {
            this.router.navigate([`lesson/${lessonId}`], {
                relativeTo: this.route,
            });
        }
    }

    ngOnInit(): void {
        this.getLessons();
    }

    private async getLessons(): Promise<void> {
        const moduleId: number = this.route.snapshot.params?.id;
        try {
            await this.api.collect(
                'learn\\Chapter',
                [['module_id', '=', moduleId]],
                ['title', 'subtitle', 'description', 'order'],
                'order',
                'asc'
            );
        } catch (error) {
            console.error(error);
        }
    }

    public trackLessonById(index: number, chapter: Chapter): number {
        return chapter.id;
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
            this.matSnackBar.open(`The lesson has been successfully moved.`, undefined, {
                duration: 4000,
                horizontalPosition: 'left',
                verticalPosition: 'bottom',
            });
        } catch (error) {
            console.error(error);
            this.matSnackBar.open(`An error occurred while moving the lesson.`, undefined, {
                duration: 4000,
                horizontalPosition: 'left',
                verticalPosition: 'bottom',
            });
        }
    }
}
