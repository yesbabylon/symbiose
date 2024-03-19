import { Component, OnInit } from '@angular/core';
import { Chapter } from '../../../../../_types/learn';
import { ActivatedRoute, Router } from '@angular/router';
// @ts-ignore
import { ApiService } from 'sb-shared-lib';
import { User } from '../../../../../_types/equal';
import { MatSnackBar } from '@angular/material/snack-bar';

@Component({
    selector: 'app-module-edit',
    templateUrl: './lesson-edit.component.html',
    styleUrls: ['./lesson-edit.component.scss'],
})
export class LessonEditComponent implements OnInit {
    public lesson: Chapter;
    public author: string;

    constructor(
        private router: Router,
        private route: ActivatedRoute,
        private api: ApiService,
        private matSnackBar: MatSnackBar
    ) {}

    ngOnInit(): void {
        this.fetchApiResources();
    }

    private async fetchApiResources(): Promise<void> {
        await this.getLesson();
        await this.getAuthor();
    }

    private async getLesson(): Promise<void> {
        const moduleId: number = this.route.snapshot.params?.id;
        try {
            await this.api
                .collect(
                    'learn\\Chapter',
                    [['id', '=', moduleId]],
                    ['title', 'page_count', 'pages', 'order', 'creator', 'description']
                )
                .then((response: Chapter[]): void => {
                    this.lesson = response[0];
                });
        } catch (error) {
            console.error(error);
        }
    }

    public async updateLessonField(value: string | null, field: string): Promise<void> {
        try {
            await this.api.update('learn\\Course', [this.lesson.id], {
                [field]: value,
            });

            this.matSnackBar.open(`The course ${field} has been successfully updated.`, undefined, {
                duration: 4000,
                horizontalPosition: 'left',
                verticalPosition: 'bottom',
            });
        } catch (error) {
            console.error(error);

            this.matSnackBar.open(`An error occurred while updating the lesson ${field} field.`, undefined, {
                duration: 4000,
                horizontalPosition: 'left',
                verticalPosition: 'bottom',
            });
        }
    }

    public navigateToViewMode(): void {
        const lessonId: number = this.route.snapshot.params?.id;

        this.router.navigate(['lesson', lessonId], { relativeTo: this.route.parent?.parent });
    }

    private async getAuthor(): Promise<void> {
        try {
            this.api
                .collect('core\\User', ['id', '=', this.lesson.creator], ['firstname', 'lastname'])
                .then((response: User[]): void => {
                    this.author = response[0].firstname + ' ' + response[0].lastname;
                });
        } catch (error) {
            console.error(error);
        }
    }
}
