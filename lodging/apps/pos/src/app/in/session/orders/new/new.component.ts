import { Component, OnInit, AfterViewInit, NgZone } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { readyException } from 'jquery';
import { ApiService, ContextService } from 'sb-shared-lib';

import { CashdeskSession, Order } from './../orders.model';

@Component({
  selector: 'session-orders-new',
  templateUrl: 'new.component.html',
  styleUrls: ['new.component.scss']
})
export class SessionOrdersNewComponent implements OnInit, AfterViewInit {

    public ready: boolean = false;

    public session: CashdeskSession = new CashdeskSession();
    public order: Order = new Order();

    private customer_id: number = 0;
    public deleteConfirmation = false;


    constructor(
        private router: Router,
        private route: ActivatedRoute,
        private zone: NgZone,
        private api: ApiService,
        private context: ContextService
    ) {}


    public ngAfterViewInit() {

    }

    public ngOnInit() {
        // fetch the ID from the route
        this.route.params.subscribe( async (params) => {

            if(params && params.hasOwnProperty('session_id')) {
                try {
                    await this.load(<number> params['session_id']);
                    this.ready = true;
                }
                catch(error) {
                    console.warn(error);
                }
            }
        });

    }


    private async load(id: number) {
        if(id > 0) {
            try {
                const result:any = await this.api.read(CashdeskSession.entity, [id], Object.getOwnPropertyNames(new CashdeskSession()));
                if(result && result.length) {
                    this.session = <CashdeskSession> result[0];
                }
            }
            catch(response) {
                throw 'unable to retrieve given session';
            }
        }
    }

    public onselectCustomer(customer:any) {
        console.log(customer);
        // asign local customer
        this.customer_id = customer.id;
    }

    public async onclickCreateOrder() {
        // create a new order for the current session
        try {
            const result:any = await this.api.create(Order.entity, {
                'session_id': this.session.id,
                'customer_id': this.customer_id,
                'amount': 0
            });
            // after creation, go to the order lines detail
            this.router.navigate(['/session/'+this.session.id+'/orders/']);
        }
        catch(response) {

        }
    }

}