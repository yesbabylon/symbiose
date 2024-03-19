import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CourseModuleListItemComponent } from './course-module-list-item.component';

describe('CoursePageContentListItemComponent', () => {
    let component: CourseModuleListItemComponent;
    let fixture: ComponentFixture<CourseModuleListItemComponent>;

    beforeEach(async () => {
        await TestBed.configureTestingModule({
            declarations: [CourseModuleListItemComponent],
        }).compileComponents();
    });

    beforeEach(() => {
        fixture = TestBed.createComponent(CourseModuleListItemComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
