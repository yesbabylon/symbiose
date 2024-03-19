import { Component, Input, OnInit } from '@angular/core';
import { Chapter, Course, Module, UserStatement } from '../../_types/learn';

type TotalCourseProgress = {
    current: string;
    total: string;
    currentPourcentage: string;
};

@Component({
    selector: 'app-top-bar',
    templateUrl: './top-bar.component.html',
    styleUrls: ['./top-bar.component.scss'],
})
export class TopBarComponent implements OnInit {
    @Input() public environmentInfo: Record<string, any>;
    @Input() public appInfo: Record<string, any>;
    @Input() public userStatement: UserStatement;
    @Input() public course: Course;

    public currentModule: Module;
    public currentLesson: Chapter;

    /* Used to display the current module progression */
    public currentModuleProgress: string;

    /* Used to display the current lesson progression */
    public currentLessonProgress: string;

    /* Used to display the current total course progression */
    public currentTotalCourseProgress: TotalCourseProgress = {} as TotalCourseProgress;

    ngOnInit(): void {
        if (this.course.modules && this.course.modules.length > 0) {
            this.currentModule = this.getCurrentModule();

            if (this.currentModule && this.currentModule.chapters && this.currentModule.chapters.length > 0) {
                this.currentLesson = this.getCurrentLesson();
                this.computeCurrentModuleProgress();
                this.computeCurrentLessonProgress();
                this.computeProgressTotalStats();
            }
        }
    }

    public getCurrentModule(): Module {
        let moduleIndex: number = 0;

        if (this.userStatement.userStatus.length > 0) {
            const currentModuleId: number | undefined = this.userStatement.userStatus[0].module_id;

            moduleIndex = this.course.modules.findIndex(module => module.id === currentModuleId);
        }

        return this.course.modules[moduleIndex];
    }

    public getCurrentLesson(): Chapter {
        const currentChapters = this.userStatement.userStatus.sort((a, b) => b.chapter_index - a.chapter_index);
        let lessonIndex: number = 0;

        if (currentChapters.length > 0) {
            lessonIndex = currentChapters[0].chapter_index;
        }

        return this.currentModule.chapters[lessonIndex];
    }

    public computeCurrentModuleProgress(): void {
        this.currentModuleProgress = `${this.userStatement.userStatus.length === 0 ? 1 : this.userStatement.userStatus.length} / ${this.course.modules.length} - ${this.computeDuration(this.currentModule.duration)}`;
    }

    public computeCurrentLessonProgress(): void {
        let userStatus = this.userStatement.userStatus
            .filter(userStatus => userStatus.module_id === this.currentModule.id)
            .sort((a, b) => b.chapter_index - a.chapter_index)[0];

        let currentChapterIndex: number = 0;

        if (userStatus) {
            currentChapterIndex = userStatus.chapter_index;
        }

        this.currentLessonProgress = `${currentChapterIndex} / ${this.currentModule.chapter_count} - ${this.computeDuration(this.currentLesson.duration)} - ${this.currentLesson.page_count + 'p'}`;
    }

    public computeProgressTotalStats(): void {
        // current
        const activeModuleLessonsDurations: number = this.currentModule.chapters
            .filter(chapter => chapter.order <= this.currentLesson.order)
            .reduce((acc, chapter) => acc + chapter.duration, 0);

        const previousCourseModulesDurations: number = this.course.modules
            .filter(module => module.order < this.currentModule.order)
            .reduce((acc, module) => acc + module.duration, 0);

        const currentTotalProgression: number = activeModuleLessonsDurations + previousCourseModulesDurations;

        // total
        const totalCourseDuration: number = this.course.modules.reduce((acc, module) => acc + module.duration, 0);

        // currentPercentage
        const currentPourcentage: number = (currentTotalProgression / totalCourseDuration) * 100;

        this.currentTotalCourseProgress = {
            current: this.computeDuration(currentTotalProgression),
            total: this.computeDuration(totalCourseDuration),
            currentPourcentage: `${currentPourcentage.toFixed()}`,
        };
    }

    public computeDuration(duration: number): string {
        const hours: number = Math.floor(duration / 60);
        const minutes: number = duration % 60;

        if (hours === 0) {
            return `${minutes}min`;
        } else {
            return `${hours}h ${minutes}min`;
        }
    }
}
