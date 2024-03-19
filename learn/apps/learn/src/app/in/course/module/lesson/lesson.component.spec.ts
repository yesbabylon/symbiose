import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LessonComponent } from './lesson.component';

describe('ChapterComponent', () => {
    let component: LessonComponent;
    let fixture: ComponentFixture<LessonComponent>;

    beforeEach(async () => {
        await TestBed.configureTestingModule({
            declarations: [LessonComponent],
        }).compileComponents();
    });

    beforeEach(() => {
        fixture = TestBed.createComponent(LessonComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
