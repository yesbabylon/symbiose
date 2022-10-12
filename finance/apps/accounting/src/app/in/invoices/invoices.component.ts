import { Component, OnInit, AfterViewInit, OnDestroy, HostListener, ElementRef } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { delay } from 'rxjs/operators';
import { ContextService } from 'sb-shared-lib';


@Component({
  selector: 'invoices',
  templateUrl: 'invoices.component.html',
  styleUrls: ['invoices.component.scss']
})
export class InvoicesComponent implements OnInit, AfterViewInit, OnDestroy {
    @HostListener('unloaded')
    ngOnDestroy() {
        console.log('InvoicesComponent::ngOnDestroy');
        this.active = false;
    }

    public ready: boolean = false;

    // flag telling if the route to which the component is associated with is currently active (amongst routes defined in first parent routing module)
    private active = false;

    private default_descriptor: any = {
        // route is current ng route
        context: {
            entity: 'lodging\\sale\\booking\\Invoice',
            view: "list.default",
            order: "id",
            sort: "desc"
        }
    };

    constructor(
        private route: ActivatedRoute,
        private context: ContextService
    ) {}


    public ngAfterViewInit() {
        console.log('InvoicesComponent::ngAfterViewInit');

        this.context.setTarget('#sb-container');
        const descriptor = this.context.getDescriptor();
        if(!Object.keys(descriptor.context).length) {
            console.log('InvoicesComponent : requesting change', this.default_descriptor);
            this.context.change(this.default_descriptor);
        }

        this.active = true;
    }

    public ngOnInit() {
        console.log('InvoicesComponent::ngOnInit');

        this.context.ready.subscribe( (ready:boolean) => {
            console.log('InvoicesComponent:: received context ready', ready);
            this.ready = ready;
        });

        // once view is ready, subscribe to route changes
        this.route.params.subscribe( async (params:any) => {
            // no params for this route (/invoices)
        });

        // if no context or all contexts have been closed, re-open default context (wait for route init)
        this.context.getObservable().subscribe( () => {
            console.log('InvoicesComponent:: received context change');
            const descriptor = this.context.getDescriptor();
            if(descriptor.hasOwnProperty('context') && !Object.keys(descriptor.context).length && this.active) {
                this.ready = false;
                this.context.change(this.default_descriptor);
            }
        });


    }

}