import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { Chapter, Module } from '../../../../_types/learn';
// @ts-ignore
import { ApiService } from 'sb-shared-lib';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';
import { MatSnackBar } from '@angular/material/snack-bar';

@Component({
    selector: 'app-course-module-list-item',
    templateUrl: './course-module-list-item.component.html',
    styleUrls: ['./course-module-list-item.component.scss'],
})
export class CourseModuleListItemComponent implements OnInit {
    public modules: Module[];

    constructor(
        private router: Router,
        private route: ActivatedRoute,
        private api: ApiService,
        private matSnackBar: MatSnackBar
    ) {}

    ngOnInit(): void {
        this.getModules();
    }

    public navigateToModule(moduleId: string | number): void {
        if (this.router.url.includes('edit')) {
            this.router.navigate([`module/${moduleId}/edit`], {
                relativeTo: this.route.parent,
            });
        } else {
            this.router.navigate([`module/${moduleId}`], {
                relativeTo: this.route,
            });
        }
    }

    private async getModules(): Promise<void> {
        const courseId: number = this.route.snapshot.params?.id;
        try {
            await this.api.collect(
                'learn\\Module',
                ['course_id', '=', courseId],
                ['id', 'title', 'page_count', 'description', 'duration', 'order', 'chapter_count', 'course_id'],
                'order'
            );
        } catch (error) {
            console.error(error);
        }
    }

    public trackModuleById(index: number, module: Module): number {
        return module.id;
    }

    public onDrop(event: CdkDragDrop<Module[]>): void {
        moveItemInArray(this.modules, event.previousIndex, event.currentIndex);

        this.modules.forEach((module: Module, index: number): void => {
            module.order = index + 1;
        });

        this.modules.forEach((module: Module): void => {
            this.updateModuleOrder(module);
        });
    }

    private updateModuleOrder(module: Module): void {
        try {
            this.api.update('learn\\Module', [module.id], { order: module.order });

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
