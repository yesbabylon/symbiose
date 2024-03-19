import { Component, ElementRef, EventEmitter, Input, Output, ViewChild } from '@angular/core';
import { MatButton } from '@angular/material/button';
import { Course, UserStatement } from '../../_types/learn';

type DrawerState = 'inactive' | 'active' | 'pinned';

@Component({
    selector: 'app-large',
    templateUrl: './large.component.html',
    styleUrls: ['./large.component.scss'],
})
export class LargeComponent {
    @ViewChild('drawer', { static: true }) drawer: ElementRef<HTMLDivElement>;
    @ViewChild('sideBarMenuButton') sideBarMenuButton: MatButton;

    @Input() public userStatement: UserStatement;
    @Input() public environnementInfo: Record<string, any>;
    @Input() public appInfo: Record<string, any>;
    @Input() public course: Course;
    @Input() public hasAccessToCourse: boolean;
    @Input() public isLoading: boolean;
    @Input() public currentModuleProgressionIndex: number;
    @Input() public currentChapterProgressionIndex: number;

    @Output() public moduleToLoad: EventEmitter<number> = new EventEmitter<number>();

    public drawerState: DrawerState = 'inactive';
    public menuIcon: string = 'menu';
    public selectedModuleIndex: number = 0;

    constructor() {
        window.addEventListener('click', (event: MouseEvent): void => {
            if (
                this.drawerState === 'active' &&
                !this.sideBarMenuButton._elementRef.nativeElement.contains(event.target as Node)
            ) {
                if (!this.drawer.nativeElement.contains(event.target as Node)) {
                    this.drawerState = 'inactive';
                    this.menuIcon = 'menu';
                }
            }
        });
    }

    public onSideBarButtonClick(): void {
        switch (this.drawerState) {
            case 'inactive':
                this.drawerState = 'active';
                this.menuIcon = 'push_pin';
                break;
            case 'active':
                this.drawerState = 'pinned';
                this.menuIcon = 'close';
                break;
            case 'pinned':
                this.drawerState = 'inactive';
                this.menuIcon = 'menu';
                break;
        }
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

    public getUserStatusChapterIndex(moduleId: number): number {
        const chapterStatus = this.userStatement.userStatus.find(userStatus => userStatus.module_id === moduleId);

        if (chapterStatus) {
            return chapterStatus.chapter_index;
        } else {
            return 0;
        }
    }

    public async onClickChapter(moduleId: number): Promise<void> {
        this.moduleToLoad.emit(moduleId);
    }
}
