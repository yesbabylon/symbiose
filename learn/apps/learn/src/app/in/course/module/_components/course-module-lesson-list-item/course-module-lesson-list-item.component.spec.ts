import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CourseModuleLessonListItemComponent } from './course-module-lesson-list-item.component';

describe('CoursePageContentListItemComponent', () => {
    let component: CourseModuleLessonListItemComponent;
    let fixture: ComponentFixture<CourseModuleLessonListItemComponent>;

    beforeEach(async () => {
        await TestBed.configureTestingModule({
            declarations: [CourseModuleLessonListItemComponent],
        }).compileComponents();
    });

    beforeEach(() => {
        fixture = TestBed.createComponent(CourseModuleLessonListItemComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
