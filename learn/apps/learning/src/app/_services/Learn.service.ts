import { Injectable } from '@angular/core';
import { ApiService } from 'sb-shared-lib';
import { User } from '../_types/equal';
import { Course, UserStatement, UserStatus } from '../_types/learn';
import { ActivatedRoute, Router } from '@angular/router';

@Injectable({
    providedIn: 'root',
})
export class LearnService {
    public user: User;
    public userInfo: Record<string, any>;
    public userAccess: Record<string, any>;
    public userStatus: UserStatus[];

    public courseId: string;
    public course: Course;
    private moduleIdLoaded: Set<number> = new Set<number>();

    public currentModuleProgressionIndex: number = 0;
    public currentChapterProgressionIndex: number = 0;

    constructor(
        private api: ApiService,
        private router: Router
    ) {}

    public async loadRessources(): Promise<void> {
        await this.setCourseId();

        if (this.courseId) {
            await this.getUserInfos();
            await this.loadCourse();
            this.setCurrentModuleAndChapterIndex();
        }
    }

    private async setCourseId(): Promise<void> {
        const slug: string = this.router.url.replace(/%20/g, ' ').slice(1);

        const courseId: string | null = await this.getCourseIdFromSlug(slug);

        if (courseId) {
            this.courseId = courseId;
        } else {
            throw new Error('No course slug found');
        }
    }

    private async getCourseIdFromSlug(courseTitleSlug: string): Promise<string | null> {
        courseTitleSlug = courseTitleSlug.replace(/-/g, ' ');

        try {
            return (
                await this.api.collect('learn\\Course', [['title', '=', courseTitleSlug]], ['id'])
            )[0].id.toString();
        } catch (error) {
            console.error(error);
        }

        return null;
    }

    private async getUserInfos(): Promise<void> {
        try {
            this.userInfo = await this.api.get('userinfo');

            this.user = (
                await this.api.collect(
                    'core\\User',
                    [['id', '=', this.userInfo.id]],
                    [
                        'name',
                        'organisation_id',
                        'validated',
                        'lastname',
                        'login',
                        'language',
                        'identity_id',
                        'firstname',
                        'status',
                        'username',
                    ]
                )
            )[0] as User;

            this.userAccess = (
                await this.api.collect(
                    'learn\\UserAccess',
                    [
                        ['user_id', '=', this.userInfo.id],
                        ['course_id', '=', this.courseId],
                    ],
                    ['course_id', 'module_id', 'user_id', 'chapter_index', 'page_index', 'page_count', 'is_complete'],
                    'module_id'
                )
            )[0];

            this.userStatus = await this.api.collect(
                'learn\\UserStatus',
                [
                    ['user_id', '=', this.userInfo.id],
                    ['course_id', '=', this.courseId],
                ],
                [
                    'code',
                    'code_alpha',
                    'course_id',
                    'master_user_id',
                    'user_id',
                    'is_complete',
                    'module_id',
                    'chapter_index',
                ],
                'module_id',
                'desc'
            );
        } catch (error) {
            console.error(error);
        }
    }

    private async loadCourse(): Promise<Course> {
        if (!this.courseId) throw new Error('Course ID not set');

        try {
            this.course = await this.api.get('?get=learn_course', { course_id: this.courseId });
        } catch (error) {
            console.error(error);
        }

        return this.course;
    }

    private setCurrentModuleAndChapterIndex(): void {
        let moduleIndex: number = 0;
        let chapterIndex: number = 0;

        if (this.userStatus.length > 0 && this.course.modules && this.course.modules.length > 0) {
            const currentStatus: UserStatus = this.userStatus.sort((a, b) => b.module_id - a.module_id)[0];
            const currentModuleId: number = currentStatus.module_id;
            chapterIndex = currentStatus.chapter_index;

            moduleIndex = this.course.modules.findIndex(module => module.id === currentModuleId);

            if (moduleIndex === -1) {
                moduleIndex = 0;
            }
        }

        this.currentModuleProgressionIndex = moduleIndex;
        this.currentChapterProgressionIndex = chapterIndex;
    }

    public getUserStatement(): UserStatement {
        return {
            user: this.user,
            userInfo: this.userInfo,
            userAccess: this.userAccess,
            userStatus: this.userStatus,
        } as UserStatement;
    }

    public async loadCourseModule(moduleId: number): Promise<Course> {
        if (!this.moduleIdLoaded.has(moduleId)) {
            try {
                const module = await this.api.get('?get=learn_module', { id: moduleId });

                const courseModuleIndex: number = this.course.modules.findIndex(
                    courseModule => courseModule.id === module.id
                );

                this.course.modules[courseModuleIndex] = module;

                this.moduleIdLoaded.add(moduleId);
            } catch (error) {
                console.error(error);
            }
        }

        return this.course;
    }
}
