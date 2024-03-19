import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ModuleEditionPanelComponent } from './module-edition-panel.component';

describe('CourseEditionPanelComponent', () => {
    let component: ModuleEditionPanelComponent;
    let fixture: ComponentFixture<ModuleEditionPanelComponent>;

    beforeEach(async () => {
        await TestBed.configureTestingModule({
            declarations: [ModuleEditionPanelComponent],
        }).compileComponents();
    });

    beforeEach(() => {
        fixture = TestBed.createComponent(ModuleEditionPanelComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
