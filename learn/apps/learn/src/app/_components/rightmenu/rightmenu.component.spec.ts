import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RightmenuComponent } from './rightmenu.component';

describe('SidemenuComponent', () => {
    let component: RightmenuComponent;
    let fixture: ComponentFixture<RightmenuComponent>;

    beforeEach(async () => {
        await TestBed.configureTestingModule({
            declarations: [RightmenuComponent],
        }).compileComponents();
    });

    beforeEach(() => {
        fixture = TestBed.createComponent(RightmenuComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
