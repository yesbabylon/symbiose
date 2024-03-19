import { Component, OnInit } from '@angular/core';
import { Course } from '../../../_types/learn';
import { ActivatedRoute, Router } from '@angular/router';
// @ts-ignore
import { ApiService } from 'sb-shared-lib';
import { User } from '../../../_types/equal';
import { MatSnackBar } from '@angular/material/snack-bar';

@Component({
    selector: 'app-course-edit',
    templateUrl: './course-edit.component.html',
    styleUrls: ['./course-edit.component.scss'],
})
export class CourseEditComponent implements OnInit {
    public course: Course;
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
        const courseId: number = this.route.snapshot.params?.id;

        if (courseId) {
            await this.getCourse(courseId);
            await this.getAuthor();
        }
    }

    private async getCourse(courseId: number): Promise<void> {
        try {
            await this.api
                .collect(
                    'learn\\Course',
                    [['id', '=', courseId]],
                    [
                        'title',
                        'subtitle',
                        'description',
                        'chapter_count',
                        'page_count',
                        'chapters_ids',
                        'modules',
                        'creator',
                    ]
                )
                .then((response: Course[]): void => {
                    this.course = response[0];
                });
        } catch (error) {
            console.error(error);
        }
    }

    public async updateCourseField(value: string | null, field: string): Promise<void> {
        try {
            await this.api.update('learn\\Course', [this.course.id], {
                [field]: value,
            });
            this.matSnackBar.open(`The course ${field} has been successfully updated.`, undefined, {
                duration: 4000,
                horizontalPosition: 'left',
                verticalPosition: 'bottom',
            });
        } catch (error) {
            console.error(error);
            this.matSnackBar.open(`An error occurred while moving the course title field.`, undefined, {
                duration: 4000,
                horizontalPosition: 'left',
                verticalPosition: 'bottom',
            });
        }
    }

    public navigateToViewMode(): void {
        const courseId: number = this.route.snapshot.params?.id;

        this.router.navigate(['course', courseId], { relativeTo: this.route.parent?.parent });
    }

    private async getAuthor(): Promise<void> {
        try {
            this.api
                .collect('core\\User', ['id', '=', this.course.creator], ['firstname', 'lastname'])
                .then((response: User[]): void => {
                    this.author = response[0].firstname + ' ' + response[0].lastname;
                });
        } catch (error) {
            console.error(error);
        }
    }
}
