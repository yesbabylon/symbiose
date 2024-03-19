import { Component, OnInit, ViewChild, ViewContainerRef } from '@angular/core';
import { Event, NavigationEnd, Router, RouterEvent } from '@angular/router';
import { filter } from 'rxjs/operators';
import { CourseEditionPanelComponent } from '../../in/course/edit/_components/course-edition-panel/course-edition-panel.component';
import { ModuleEditionPanelComponent } from '../../in/course/module/edit/_components/module-edition-panel/module-edition-panel.component';
import { LessonEditionPanelComponent } from '../../in/course/module/lesson/edit/_components/lesson-edition-panel/lesson-edition-panel.component';

@Component({
    selector: 'app-rightmenu',
    template: ` <ng-template #dynamicComponent></ng-template>`,
})
export class RightmenuComponent implements OnInit {
    @ViewChild('dynamicComponent', { read: ViewContainerRef }) dynamicComponent: ViewContainerRef;

    public componentsGlossary: Record<string, Record<string, any>> = {
        course: {
            editPanel: CourseEditionPanelComponent,
        },
        module: {
            editPanel: ModuleEditionPanelComponent,
        },
        lesson: {
            editPanel: LessonEditionPanelComponent,
        },
    };

    constructor(private router: Router) {}

    ngOnInit(): void {
        this.router.events
            .pipe(filter((e: Event): e is RouterEvent => e instanceof RouterEvent))
            .subscribe((routerEvent: RouterEvent): void => {
                if (routerEvent instanceof NavigationEnd) {
                    this.dynamicComponent.clear();

                    if (routerEvent.urlAfterRedirects.includes('edit')) {
                        const splitUrl: string[] = routerEvent.urlAfterRedirects.split('/');

                        const editIndex: number = splitUrl.findIndex((e: string): boolean => e === 'edit');

                        if (editIndex !== -1) {
                            const entity: string = splitUrl[editIndex - 2];

                            this.dynamicComponent.createComponent<any>(this.componentsGlossary[entity].editPanel);
                        }
                    }
                }
            });
    }
}
