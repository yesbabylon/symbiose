import { Component, AfterContentInit, OnInit, NgZone } from '@angular/core';
import { ActivatedRoute, Router, RouterEvent, NavigationEnd } from '@angular/router';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';

import { ApiService, EnvService, AuthService, ContextService } from 'sb-shared-lib';
import { MatSnackBar } from '@angular/material/snack-bar';

import { CashdeskSession } from '../_models/session.model';



@Component({
  selector: 'session-move',
  templateUrl: './move.component.html',
  styleUrls: ['./move.component.scss']
})
export class SessionMoveComponent implements OnInit, AfterContentInit {


    public ready = false;

    public session: CashdeskSession = new CashdeskSession();
    public session_id: number;

    public move_note: string = "Motif du mouvement:\n";
    public type: string = 'in';
    public amount: number = 0.0;

    constructor(
        private dialog: MatDialog,
        private api: ApiService,
        private auth: AuthService,
        private env: EnvService,
        private router: Router,
        private route: ActivatedRoute,
        private context:ContextService,
        private snack: MatSnackBar,
        private zone: NgZone) {
    }

    /**
     * Set up callbacks when component DOM is ready.
     */
    public ngAfterContentInit() {

    }

    public async ngOnInit() {
        // fetch the IDs from the route
        this.route.params.subscribe(async (params) => {
            if (params && params.hasOwnProperty('session_id')) {
                try {
                    await this.load(<number>params['session_id']);
                    this.ready = true;
                }
                catch (error) {
                    console.warn(error);
                }
            }
        });
    }


    /**
     * Load an Order object using the sale_pos_order_tree controller
     * @param session_id
     */
    async load(session_id: number) {
        if (session_id > 0) {
            try {
                // fetch order lines (ordered products that haven't been paid yet)
                const data = await this.api.read(CashdeskSession.entity, [ session_id ], Object.getOwnPropertyNames(new CashdeskSession()));
                this.session = data.pop();
            }
            catch (response) {
                console.log(response);
                throw 'unable to retrieve given order';
            }

        }
    }

    public async onclickConfirm(){
        console.log('onclick confirm');
        try {
            let amount: number = Math.abs(this.amount);
            if(this.type == 'out') {
                amount = -amount;
            }
            // request operation creation
            const order:any = await this.api.create('sale\\pos\\Operation', {
                session_id: this.session.id,
                user_id: this.session.user_id,
                amount: amount,
                type: 'move',
                description: this.move_note
            });
            // and navigate to it
            this.router.navigate(['/session/'+this.session.id+'/orders']);
        }
        catch(response) {
            console.log(response);
        }
    }
}