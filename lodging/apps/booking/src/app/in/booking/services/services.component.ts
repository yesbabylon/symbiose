import { Component, OnInit, AfterViewInit, Inject, ElementRef, QueryList, ViewChild, ViewChildren, NgZone, Renderer2  } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { BookingApiService } from 'src/app/in/booking/_services/booking.api.service';
import { AuthService, ContextService, EqualUIService } from 'sb-shared-lib';
import { MatSnackBar } from '@angular/material/snack-bar';



import { Observable, BehaviorSubject, ReplaySubject } from 'rxjs';
import { find, map, startWith, debounceTime, switchMap } from 'rxjs/operators';


class Booking {
    constructor(
        public id: number = 0,
        public name: string = '',
        public created: Date = new Date(),
        public status: string = ''
    ) {}
}


@Component({
    selector: 'booking-services',
    templateUrl: 'services.component.html',
    styleUrls: ['services.component.scss']
})
export class BookingServicesComponent implements OnInit, AfterViewInit  {

    public booking: any = new Booking();
    public booking_id: number = 0;

    public ready: boolean = false;

    @ViewChild('actionButtonContainer') actionButtonContainer: ElementRef;

    public status:any = {
        'quote': 'Devis',
        'option': 'Option',
        'confirmed': 'Confirmée',
        'validated': 'Validée',
        'checkedin': 'En cours',
        'checkedout': 'Terminée',
        'invoiced': 'Facturée',
        'debit_balance': 'Solde débiteur',
        'credit_balance': 'Solde créditeur',
        'balanced': 'Soldée'
    }

    constructor(
        private auth: AuthService,
        private api: BookingApiService,
        private router: Router,
        private route: ActivatedRoute,
        private snack: MatSnackBar,
        private zone: NgZone,
        private context:ContextService,
        private eq:EqualUIService,
        private renderer: Renderer2
    ) {}



    /**
     * Set up callbacks when component DOM is ready.
     */
    public async ngAfterViewInit() {
        await this.refreshActionButton();
        this.ready = true;
    }

    public ngOnInit() {
        console.log('BookingEditComponent init');

        // when action is performed, we need to reload booking object
        // #memo - context change triggers sidemenu panes updates
        this.context.getObservable().subscribe( async (descriptor:any) => {
            if(this.ready) {
                // reload booking
                await this.load( Object.getOwnPropertyNames(new Booking()) );
                this.refreshActionButton();
                // force reloading child component
                let booking_id = this.booking_id;
                this.booking_id = 0;
                setTimeout( () => {
                    this.booking_id = booking_id;
                }, 250);
            }
        });

        // fetch the booking ID from the route
        this.route.params.subscribe( async (params) => {
            console.log('BookingEditComponent : received routeParams change', params);
            if(params && params.hasOwnProperty('booking_id')) {
                this.booking_id = <number> params['booking_id'];

                try {
                    // load booking object
                    await this.load( Object.getOwnPropertyNames(new Booking()) );

                    // relay change to context (to display sidemenu panes according to current object)
                    this.context.change({
                        context_only: true,   // do not change the view
                        context: {
                            entity: 'lodging\\sale\\booking\\Booking',
                            type: 'form',
                            purpose: 'view',
                            domain: ['id', '=', this.booking_id]
                        }
                    });

                    console.log('booking loaded', this.booking);
                }
                catch(response) {
                    console.warn(response);
                }
            }
        });
    }

    private async refreshActionButton() {
        let $button = await this.eq.getActionButton('lodging\\sale\\booking\\Booking', 'form.default', ['id', '=', this.booking_id]);
        // remove previous button, if any
        for (let child of this.actionButtonContainer.nativeElement.children) {
            this.renderer.removeChild(this.actionButtonContainer.nativeElement, child);
        }
        if($button.length) {
            this.renderer.appendChild(this.actionButtonContainer.nativeElement, $button[0]);
        }
    }

    /**
     * Assign values based on selected booking and load sub-objects required by the view.
     *
     */
    private async load(fields:any) {
        try {
            const data:any = await this.api.read("lodging\\sale\\booking\\Booking", [this.booking_id], fields);
            if(data && data.length) {
                // update local object
                for(let field of Object.keys(data[0])) {
                    this.booking[field] = data[0][field];
                }
                // assign booking to Booking API service (for conditionning calls)
                this.api.setBooking(this.booking);
            }
        }
        catch(response) {
            console.log('unexpected error');
        }
    }
}