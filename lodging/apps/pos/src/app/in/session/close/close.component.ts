import { Component, OnInit, AfterViewInit, NgZone } from '@angular/core';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService, AuthService, ContextService } from 'sb-shared-lib';
import { UserClass } from 'sb-shared-lib/lib/classes/user.class';

import { CashdeskSession } from 'src/app/in/session/_models/session.model';
import { SessionCloseInventoryDialog } from './_components/inventory/inventory.dialog';

@Component({
  selector: 'session-close',
  templateUrl: 'close.component.html',
  styleUrls: ['close.component.scss']
})
export class SessionCloseComponent implements OnInit, AfterViewInit {

    public ready: boolean = false;

    private user : UserClass;

    public session: CashdeskSession = new CashdeskSession();


    public total_orders: number = 0;
    public total_sales: number = 0;
    public total_moves: number = 0;
    public total_inventory: number = 0;

    public inventory: any;
    public closing_note: string = "Note de fermeture: \n";


    public checked = false;
    public submitted = false;

    constructor(
        public auth : AuthService,
        private router: Router,
        private route: ActivatedRoute,
        private dialog: MatDialog,
        private zone: NgZone,
        private api: ApiService,
        private context: ContextService
    ) {}


    public ngAfterViewInit() {

    }

    public ngOnInit() {

        this.auth.getObservable().subscribe( (user: UserClass) => {
            this.user = user;
        });

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
                const data = await this.api.fetch('/?get=sale_pos_session_tree', { id: id });

                if(data) {
                    this.session = <CashdeskSession> data;

                    this.session.orders_ids.forEach((order: any) => {
                        this.total_orders += order.price
                    });

                    this.session.operations_ids.forEach((operation: any) => {
                        if(operation.type == 'sale') {
                            this.total_sales += operation.amount;
                        }
                        else if(operation.type == 'move') {
                            this.total_moves += operation.amount;
                        }
                    });
                }
            }
            catch(response) {
                console.log(response);
                throw 'unable to retrieve given session';
            }
        }
    }


    public onInventoryClick() {
        const dialogRef = this.dialog.open(SessionCloseInventoryDialog, { data: {
                inventory: this.inventory
            }
        });

        dialogRef.afterClosed().subscribe(
            (value: any) => {
                if(value) {
                    this.total_inventory = value.total;
                    this.inventory = value.inventory;
                    // reset closing note
                    this.closing_note = "Note de fermeture: \n";
                    for(let item of this.inventory) {
                        if(item.number != '') {
                            this.closing_note +=  item.number + 'x' + item.value + "€\n";
                        }
                    }
                }
            }
        );
    }

    public calcExpected() {
        return this.total_sales + this.total_moves + this.session.amount_opening;
    }

    public calcDifference() {
        return this.total_inventory - this.calcExpected();
    }

    public async onSessionCloseClick() {
        this.submitted = true;
        let difference: number = this.calcDifference();

        if (difference == 0 || this.checked) {
            try {
                // close the session
                await this.api.update(CashdeskSession.entity, [this.session.id], {
                    status: 'closed',
                    amount_closing: this.total_inventory,
                    note: this.closing_note
                });
                this.router.navigate(['sessions'])
            }
            catch(response) {
                console.log('unexepceted error', response);
            }
        }
    }

}