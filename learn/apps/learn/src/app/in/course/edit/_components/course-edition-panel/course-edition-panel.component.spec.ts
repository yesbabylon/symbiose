import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CourseEditionPanelComponent } from './course-edition-panel.component';

describe('CourseEditionPanelComponent', () => {
    let component: CourseEditionPanelComponent;
    let fixture: ComponentFixture<CourseEditionPanelComponent>;

    beforeEach(async () => {
        await TestBed.configureTestingModule({
            declarations: [CourseEditionPanelComponent],
        }).compileComponents();
    });

    beforeEach(() => {
        fixture = TestBed.createComponent(CourseEditionPanelComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
