import { Component, HostBinding, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
// @ts-ignore
import { ApiService } from 'sb-shared-lib';
import { Course } from '../../_types/learn';

@Component({
    selector: 'app-courses',
    templateUrl: './courses.component.html',
    styleUrls: ['./courses.component.scss'],
})
export class CoursesComponent implements OnInit {
    @HostBinding('class') public readonly classes = 'scrollbar-style';
    public courses: Course[];

    constructor(
        private router: Router,
        private route: ActivatedRoute,
        private api: ApiService
    ) {}

    ngOnInit(): void {
        this.getCourses();
    }

    public navigateToCourse(courseId: string | number): void {
        this.router.navigate([`course/${courseId}`], {
            replaceUrl: true,
        });
    }

    public async getCourses(): Promise<void> {
        try {
            await this.api
                .collect('learn\\Course', [], ['title', 'subtitle', 'description'])
                .then((response: Course[]): void => {
                    this.courses = response;
                });
        } catch (error) {
            console.error(error);
        }
    }

    public trackCourseById(index: number, course: Course): number {
        return course.id;
    }
}
