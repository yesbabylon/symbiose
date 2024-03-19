import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CourseModuleLessonPageListItemComponent } from './course-module-lesson-page-list-item.component';

describe('CoursePageContentListItemComponent', () => {
    let component: CourseModuleLessonPageListItemComponent;
    let fixture: ComponentFixture<CourseModuleLessonPageListItemComponent>;

    beforeEach(async () => {
        await TestBed.configureTestingModule({
            declarations: [CourseModuleLessonPageListItemComponent],
        }).compileComponents();
    });

    beforeEach(() => {
        fixture = TestBed.createComponent(CourseModuleLessonPageListItemComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
