import { Component, OnInit, AfterViewInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService, AuthService, ContextService, TreeComponent, RootTreeComponent } from 'sb-shared-lib';
import { CashdeskSession } from '../../_models/session.model';
import { Order, OrderLine, OrderPayment, OrderPaymentPart } from './ticket.model';
import { UserClass } from 'sb-shared-lib/lib/classes/user.class';
import { SessionOrderLinesComponent } from '../lines/lines.component';
import { BookingLineClass } from 'src/app/model';

import { MatTableDataSource } from '@angular/material/table';
import {DataSource, SelectionModel} from '@angular/cdk/collections';



// declaration of the interface for the map associating relational Model fields with their components
interface OrderComponentsMap {

};


@Component({
    selector: 'session-order-ticket',
    templateUrl: 'ticket.component.html',
    styleUrls: ['ticket.component.scss']
})
export class SessionOrderTicketComponent extends TreeComponent<Order, OrderComponentsMap> implements RootTreeComponent, OnInit, AfterViewInit {
    // @ViewChildren(SessionOrderPaymentsOrderPaymentComponent) SessionOrderPaymentsOrderPaymentComponents: QueryList<SessionOrderPaymentsOrderPaymentComponent>;
    // @ViewChildren(SessionOrderLinesComponent) SessionOrderLinesComponents: QueryList<SessionOrderLinesComponent>;


    public ready: boolean = false;
    public focus: string;
    public user: UserClass = null;

    constructor(
        private router: Router,
        private route: ActivatedRoute,
        private api: ApiService,
        private auth: AuthService,
        private context: ContextService,
    ) {
        super(new Order());
    }

    public ngAfterViewInit() {
        // init local componentsMap
        let map: OrderComponentsMap = {
        };
        this.componentsMap = map;
    }

    public ngOnInit() {
        // fetch the IDs from the route
        this.route.params.subscribe(async (params) => {
            if (params && params.hasOwnProperty('order_id')) {
                try {
                    await this.load(<number>params['order_id']);
                    this.ready = true;
                }
                catch (error) {
                    console.warn(error);
                }
            }
        });

        this.auth.getObservable().subscribe( async (user: UserClass) => {
            this.user = user;
        });

    }

    /**
     * Load an Order object using the sale_pos_order_tree controller
     * @param order_id
     */
    async load(order_id: number) {
        if (order_id > 0) {
            try {
                const data = await this.api.fetch('/?get=lodging_sale_pos_order_tree', { id: order_id, variant: 'ticket' });
                if (data) {
                    this.update(data);
                }
            }
            catch (response) {
               throw 'unable to retrieve given order';
            }
        }
    }


    public getPaymentModesMap() : {[key: string]: number} {
        let payments_map: any = {};
        for(let part of this.instance.order_payment_parts_ids) {
            let mode = part.payment_method;
            if(!payments_map.hasOwnProperty(mode)) {
                payments_map[mode] = 0.0;
            }
            payments_map[mode] += part.amount;
        }
        return payments_map;
    }

    public getVatMap() : {[key: number]: number} {
        let vat_map: any = {};
        for(let line of this.instance.order_lines_ids) {
            let vat = parseFloat( (line.total * line.vat_rate).toFixed(2) );
            if(vat <= 0) {
                continue;
            }
            let vat_rate = line.vat_rate * 100;
            if(!vat_map.hasOwnProperty(vat_rate)) {
                vat_map[vat_rate] = 0.0;
            }
            vat_map[vat_rate] += vat;
        }
        return vat_map;
    }

    public onclickCloseSession() {
        this.router.navigate(['/session/'+this.instance.session_id.id+'/close']);
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

    public async onPrint() {
        window.print();
    }

}