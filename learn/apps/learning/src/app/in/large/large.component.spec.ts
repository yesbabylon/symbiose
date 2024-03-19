import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LargeComponent } from './large.component';

describe('LargeComponent', () => {
    let component: LargeComponent;
    let fixture: ComponentFixture<LargeComponent>;

    beforeEach(async () => {
        await TestBed.configureTestingModule({
            declarations: [LargeComponent],
        }).compileComponents();
    });

    beforeEach(() => {
        fixture = TestBed.createComponent(LargeComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
