import { Component, OnInit } from '@angular/core';
import { Course } from '../../_types/learn';
// @ts-ignore
import { ApiService } from 'sb-shared-lib';
import { ActivatedRoute, Router } from '@angular/router';
import { User } from '../../_types/equal';
import { MatSnackBar } from '@angular/material/snack-bar';

@Component({
    selector: 'app-course',
    templateUrl: './course.component.html',
    styleUrls: ['./course.component.scss'],
})
export class CourseComponent implements OnInit {
    public course: Course;
    public author: string;
    public user: Record<string, any>;
    public userAccess: Record<string, any>;
    public hasCourseAccess: boolean;

    constructor(
        private router: Router,
        private route: ActivatedRoute,
        private api: ApiService,
        private matSnackBar: MatSnackBar
    ) {}

    ngOnInit(): void {
        this.fetchApiResources();
    }

    public async fetchApiResources(): Promise<void> {
        const courseId: number = this.route.snapshot.params?.id;

        if (courseId) {
            await this.getCourse(courseId);
            await this.getAuthor();
        }

        this.user = await this.api.get('userinfo');

        this.userAccess = await this.api.collect(
            'learn\\UserAccess',
            [
                ['user_id', '=', this.user.id],
                ['course_id', '=', courseId],
            ],
            ['code', 'code_alpha', 'course_id', 'master_user_id', 'user_id', 'is_complete']
        );

        this.hasCourseAccess = this.userAccess.length > 0;
    }

    public async obtainCourseAccess(): Promise<void> {
        try {
            const response = await this.api.create('learn\\UserAccess', {
                code: 0,
                code_alpha: '',
                course_id: this.course.id,
                master_user_id: this.user.id,
                user_id: this.user.id,
                is_complete: false,
            });

            if (response.hasOwnProperty('id')) {
                this.hasCourseAccess = true;
            }

            this.matSnackBar.open(
                `The course ${this.course.title} has been successfully added to your account.`,
                undefined,
                {
                    duration: 4000,
                    horizontalPosition: 'left',
                    verticalPosition: 'bottom',
                }
            );
        } catch (error) {
            console.error(error);

            this.matSnackBar.open(`An error occurred while the course has been added to your account.`, undefined, {
                duration: 4000,
                horizontalPosition: 'left',
                verticalPosition: 'bottom',
            });
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

    public navigateToEditMode(): void {
        this.router.navigate(['edit'], { relativeTo: this.route });
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
