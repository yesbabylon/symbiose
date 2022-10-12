import { Component, OnInit, AfterViewInit, ChangeDetectorRef, ViewChild, ElementRef, HostListener, OnDestroy } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Subject } from 'rxjs';
import { delay, takeUntil } from 'rxjs/operators';
import { ContextService } from 'sb-shared-lib';


@Component({
  selector: 'repairings',
  templateUrl: 'repairings.component.html',
  styleUrls: ['repairings.component.scss']
})
export class RepairingsComponent implements OnInit, AfterViewInit, OnDestroy {
    // @ViewChild('sbContainer') sbContainer: ElementRef;

    // rx subject for unsubscribing subscriptions on destroy
    private ngUnsubscribe = new Subject<void>();

    public ready: boolean = false;

    // flag telling if the route to which the component is associated with is currently active (amongst routes defined in first parent routing module)
    private active = false;

    private default_descriptor: any = {
        // route: '/booking/object.id',
        context: {
            entity: 'lodging\\sale\\booking\\Repairing',
            view:   'list.default'
        }
    };

    constructor(
        private route: ActivatedRoute,
        private context: ContextService
    ) {}

    public ngOnDestroy() {
        console.log('RepairingsComponent::ngOnDestroy');
        this.ngUnsubscribe.next();
        this.ngUnsubscribe.complete();
    }

    public ngAfterViewInit() {
        console.log('RepairingsComponent::ngAfterViewInit');

        this.context.setTarget('#sb-container-repairings');

        const descriptor = this.context.getDescriptor();
        if(!Object.keys(descriptor.context).length) {
            this.context.change(this.default_descriptor);
        }

        this.active = true;
    }

    public ngOnInit() {
        console.log('RepairingsComponent::ngOnInit');

        this.context.ready.pipe(takeUntil(this.ngUnsubscribe)).subscribe( (ready:boolean) => {
            this.ready = ready;
        });


        // if no context or all contexts have been closed, re-open default context (wait for route init)
        this.context.getObservable().pipe(takeUntil(this.ngUnsubscribe)).subscribe( () => {
            console.log('BookingsComponent:: received context change');
            const descriptor = this.context.getDescriptor();
            if(descriptor.hasOwnProperty('context') && !Object.keys(descriptor.context).length && this.active) {
                this.ready = false;
                this.context.change(this.default_descriptor);
            }
        });

    }

}