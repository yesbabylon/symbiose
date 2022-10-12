import { Component, OnInit, NgZone, AfterViewInit, OnDestroy } from '@angular/core';
import { Subject } from 'rxjs';
import { AuthService, ApiService, ContextService } from 'sb-shared-lib';
import { Router } from '@angular/router';

import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';


@Component({
  selector: 'app',
  templateUrl: 'app.component.html',
  styleUrls: ['app.component.scss']
})
export class AppComponent implements OnInit, AfterViewInit  {
    // rx subject for unsubscribing subscriptions on destroy
    private ngUnsubscribe = new Subject<void>();

    public ready: boolean = false;

    // flag telling if the route to which the component is associated with is currently active (amongst routes defined in first parent routing module)
    private active = false;

    private default_descriptor: any = {
        // route is current ng route
        context: {
            "entity": "sale\\pay\\Payment",
            "view": "dashboard.default"
        }
    };

    constructor(
        private context: ContextService,
        private zone: NgZone
    ) {}

    public ngOnDestroy() {
        console.log('AppComponent::ngOnDestroy');
        this.ngUnsubscribe.next();
        this.ngUnsubscribe.complete();
    }

    public ngOnInit() {
        console.log('AppComponent::ngOnInit');
        this.context.ready.subscribe( (ready:boolean) => {
            this.ready = ready;
        });
    }

    public ngAfterViewInit() {
        console.log('AppComponent::ngAfterViewInit');

        this.context.setTarget('#sb-container-sale');
        const descriptor = this.context.getDescriptor();
        if(!Object.keys(descriptor.context).length) {
            console.log('AppComponent : requesting change', this.default_descriptor);
            this.context.change(this.default_descriptor);
        }

        this.active = true;
    }
}