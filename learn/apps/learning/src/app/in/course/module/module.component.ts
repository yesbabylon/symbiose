import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { Module } from '../../../_types/learn';
// @ts-ignore
import { ApiService } from 'sb-shared-lib';
import { User } from '../../../_types/equal';

@Component({
    selector: 'app-module',
    templateUrl: './module.component.html',
    styleUrls: ['./module.component.scss'],
})
export class ModuleComponent implements OnInit {
    public module: Module;
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
        await this.getModule();
        await this.getAuthor();

        this.courseTitle = (
            await this.api.collect('learn\\Course', [['id', '=', this.module.course_id]], ['title'])
        )[0].title;

        this.user = await this.api.get('userinfo');
        this.userAccess = await this.api.collect(
            'learn\\UserAccess',
            [
                ['user_id', '=', this.user.id],
                ['course_id', '=', this.module.course_id],
            ],
            ['code', 'code_alpha', 'course_id', 'master_user_id', 'user_id', 'is_complete']
        );

        this.hasCourseAccess = this.userAccess.length > 0;
    }

    private async getModule(): Promise<void> {
        const moduleId: number = this.route.snapshot.params?.id;
        try {
            await this.api
                .collect(
                    'learn\\Module',
                    [['id', '=', moduleId]],
                    [
                        'title',
                        'subtitle',
                        'description',
                        'course_id',
                        'page_count',
                        'chapter_count',
                        'chapters',
                        'duration',
                        'creator',
                    ]
                )
                .then((response: Module[]): void => {
                    this.module = response[0];
                });
        } catch (error) {
            console.error(error);
        }
    }

    public navigateToEditMode(): void {
        this.router.navigate(['edit'], { relativeTo: this.route });
    }

    public formatDuration(duration: number): string {
        const hours: number = Math.floor(duration / 60);
        const minutes: number = duration % 60;

        if (hours === 0) {
            return minutes + 'min';
        } else if (minutes === 0) {
            return hours + 'h';
        } else {
            return hours + 'h ' + (minutes < 10 ? '0' : '') + minutes + 'min';
        }
    }

    private async getAuthor(): Promise<void> {
        try {
            this.api
                .collect('core\\User', ['id', '=', this.module.creator], ['firstname', 'lastname'])
                .then((response: User[]): void => {
                    this.author = response[0].firstname + ' ' + response[0].lastname;
                });
        } catch (error) {
            console.error(error);
        }
    }
}
