import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RessourcesComponent } from './ressources.component';

describe('RessourcesComponent', () => {
    let component: RessourcesComponent;
    let fixture: ComponentFixture<RessourcesComponent>;

    beforeEach(async () => {
        await TestBed.configureTestingModule({
            declarations: [RessourcesComponent],
        }).compileComponents();
    });

    beforeEach(() => {
        fixture = TestBed.createComponent(RessourcesComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });
});
