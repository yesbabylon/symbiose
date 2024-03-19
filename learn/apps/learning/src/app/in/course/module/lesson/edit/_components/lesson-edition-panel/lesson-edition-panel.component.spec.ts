import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LessonEditionPanelComponent } from './lesson-edition-panel.component';

describe('CourseEditionPanelComponent', () => {
    let component: LessonEditionPanelComponent;
    let fixture: ComponentFixture<LessonEditionPanelComponent>;

    beforeEach(async () => {
        await TestBed.configureTestingModule({
            declarations: [LessonEditionPanelComponent],
        }).compileComponents();
    });

    beforeEach(() => {
        fixture = TestBed.createComponent(LessonEditionPanelComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
