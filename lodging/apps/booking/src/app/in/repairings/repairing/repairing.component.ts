import { Component, OnInit, AfterViewInit, ChangeDetectorRef, ViewChild, ElementRef, HostListener, OnDestroy } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { Subject } from 'rxjs';
import { delay, takeUntil } from 'rxjs/operators';
import { ContextService } from 'sb-shared-lib';


@Component({
  selector: 'repairings-repairing',
  templateUrl: 'repairing.component.html',
  styleUrls: ['repairing.component.scss']
})
export class RepairingsRepairingComponent implements OnInit, AfterViewInit, OnDestroy {
    // @ViewChild('sbContainer') sbContainer: ElementRef;

    // rx subject for unsubscribing subscriptions on destroy
    private ngUnsubscribe = new Subject<void>();

    public ready: boolean = false;

    private default_descriptor: any = {
        // route: '/booking/object.id',
        context: {
            entity: 'lodging\\sale\\booking\\Repairing',
            view:   'form.default'
        }
    };


    private repairing_id: number = 0;

    constructor(
        private router: Router,
        private route: ActivatedRoute,
        private context: ContextService
    ) {}

    public ngOnDestroy() {
        console.log('RepairingsRepairingComponent::ngOnDestroy');
        this.ngUnsubscribe.next();
        this.ngUnsubscribe.complete();
    }

    public ngAfterViewInit() {
        console.log('RepairingsRepairingComponent::ngAfterViewInit');

        this.context.setTarget('#sb-container-repairing');

        const descriptor = this.context.getDescriptor();
        if(!Object.keys(descriptor.context).length) {
            this.default_descriptor.context.domain = ["id", "=", this.repairing_id];
            this.context.change(this.default_descriptor);
        }
    }

    public ngOnInit() {
        console.log('RepairingsRepairingComponent::ngOnInit');

        this.context.ready.pipe(takeUntil(this.ngUnsubscribe)).subscribe( (ready:boolean) => {
            this.ready = ready;
        });

        /*
            routing module is AppRoutingModule, siblings are /planning and /bookings
        */
        this.route.params.pipe(takeUntil(this.ngUnsubscribe)).subscribe( async (params) => {
            this.repairing_id = <number> parseInt(params['repairing_id'], 10);
        });

        // if no context or all contexts have been closed, re-open default context (wait for route init)
        this.context.getObservable().pipe(takeUntil(this.ngUnsubscribe)).subscribe( () => {
            if(this.ready) {
                console.log('RepairingsRepairingComponent:: received context change');

                const descriptor = this.context.getDescriptor();
                if(!descriptor.hasOwnProperty('context') || !Object.keys(descriptor.context).length) {
                    this.router.navigate(['/repairings']);
                }

            }
        });

    }

}