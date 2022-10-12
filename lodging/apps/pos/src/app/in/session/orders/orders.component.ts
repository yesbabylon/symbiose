import { Component, OnInit, AfterViewInit, NgZone } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { readyException } from 'jquery';
import { ApiService, ContextService } from 'sb-shared-lib';

import { CashdeskSession, Order } from './orders.model';

@Component({
  selector: 'session-orders',
  templateUrl: 'orders.component.html',
  styleUrls: ['orders.component.scss']
})
export class SessionOrdersComponent implements OnInit, AfterViewInit {

  public ready: boolean = false;

  public session: CashdeskSession = new CashdeskSession();

  public orders: Order[] = new Array<Order>();

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
        console.log('SessionOrdersComponent init');
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
                    try {
                        const result:any = await this.api.collect(Order.entity, [
                                ['session_id', '=', id],
                                ['status', '<>', 'paid']
                            ],
                            ['customer_id.name', ...Object.getOwnPropertyNames(new Order())]
                        );
                        if(result && result.length) {
                            this.orders = result;
                        }
                    }
                    catch(response) {
                        console.warn('unable to retrieve orders');
                    }
                }
            }
            catch(response) {
                throw 'unable to retrieve given session';
            }
        }
    }

    public async onclickNewOrder() {
        // create a new order
        try {
            const order:any = await this.api.create(Order.entity, { session_id: this.session.id });
            // and navigate to it
            this.router.navigate(['/session/'+this.session.id+'/order/'+order.id+'/lines']);
        }
        catch(response) {
            console.log(response);
        }
    }

    public onclickSelectOrder(order_id:number) {
        this.router.navigate(['/session/'+this.session.id+'/order/'+order_id]) ;
    }

    public async onclickDeleteOrder(order_id: number) {
        await this.api.remove(Order.entity, [order_id], true);
        this.load(this.session.id);
    }

    public onclickCloseSession() {
        this.router.navigate(['/session/'+this.session.id+'/close']);
    }

    public onclickFullscreen() {
        const elem:any = document.documentElement;
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        }
        else if (elem.mozRequestFullScreen) {
            elem.mozRequestFullScreen();
        }
        else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        }
        else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }
    }
}