import { Component, OnInit } from '@angular/core';
import { Chapter } from '../../../../_types/learn';
import { ActivatedRoute, Router } from '@angular/router';
// @ts-ignore
import { ApiService } from 'sb-shared-lib';
import { User } from '../../../../_types/equal';

@Component({
    selector: 'app-lesson',
    templateUrl: './lesson.component.html',
    styleUrls: ['./lesson.component.scss'],
})
export class LessonComponent implements OnInit {
    public lesson: Chapter;
    public author: string;

    public user: Record<string, any>;
    public userAccess: Record<string, any>;
    public courseTitle: string;
    public hasCourseAccess: boolean;

    constructor(
        private router: Router,
        private route: ActivatedRoute,
        private api: ApiService
    ) {}

    ngOnInit(): void {
        this.fetchApiResources();
    }

    private async fetchApiResources(): Promise<void> {
        await this.getLesson();
        await this.getAuthor();

        const courseId: string | null | undefined = this.route.snapshot.parent?.parent?.parent?.paramMap.get('id');

        if (typeof courseId === 'string') {
            this.courseTitle = (await this.api.collect('learn\\Course', [['id', '=', courseId]], ['title']))[0].title;

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
            console.log(this.lesson);
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
                .collect('core\\User', ['id', '=', this.lesson.creator], ['firstname', 'lastname'])
                .then((response: User[]): void => {
                    this.author = response[0].firstname + ' ' + response[0].lastname;
                });
        } catch (error) {
            console.error(error);
        }
    }
}
