import { Component, Inject, OnInit, SimpleChanges } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService, ApiService } from 'sb-shared-lib';
import { UserClass } from 'sb-shared-lib/lib/classes/user.class';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { CashdeskSession } from 'src/app/in/sessions/sessions.model';
import { SessionsNewInventoryDialog } from './_components/inventory/inventory.dialog';

@Component({
    selector: 'sessions-new',
    templateUrl: './new.component.html',
    styleUrls: ['./new.component.scss']
})
export class SessionsNewComponent implements OnInit {

    public deleteConfirmation = false;
    public displayTablet = false;
    public newSession : any;

    public index: number;
    public total: number = 0;
    public actionType: any = "";
    public coin : any = "";

    public coins : any;

    public cashdesk_id : number;
    public user : UserClass;


    constructor( 
        public auth : AuthService,
        public api : ApiService, 
        private router: Router,
        private dialog: MatDialog
    ) { }


    ngOnInit(): void {
        this.auth.getObservable().subscribe( (user: UserClass) => {
            this.user = user;
        });
    }

    ngOnChanges(changes: SimpleChanges): void {

    }

    passedNumber(value:any){
    }

    public onDisplayCoins() {
        this.displayTablet = true;
        const dialogRef = this.dialog.open(SessionsNewInventoryDialog, {});
    
        dialogRef.afterClosed().subscribe(
            data => {
                console.log(data)
                this.coins = data.data.filter((element:any) => {
                    element.number != "";
                });
            }
        );
    }

    public async onclickOpenSession() {
        // if a session already exists, resume it
        const result = await this.api.collect(CashdeskSession.entity, [['status', '=', 'pending'], ['cashdesk_id', '=', this.cashdesk_id]], []);

        // show pending orders for the targeted session
        if(result.length > 0) {
            this.router.navigate(['/session/' + result[0].id + '/orders']);
            return;
        }

        // otherwise open a new session
        if(this.user && this.cashdesk_id){
            const dialogRef = this.dialog.open(SessionsNewInventoryDialog, {
                data: {user : this.user, cashdesk_id : this.cashdesk_id}
            });
            dialogRef.afterClosed().subscribe( async (cash_inventory: any) => {
                if(cash_inventory) {
                    // #todo - add inventory as note
                    try {
                        const session = await this.api.create(CashdeskSession.entity, {amount_opening: cash_inventory.total, cashdesk_id: this.cashdesk_id, user_id: this.user.id, });        
                        this.router.navigate(['/session/' + session.id + '/orders']);
                    }
                    catch(response) {
                        console.log(response);
                    }
                }
            });            
        }
    }

    public onselectCashdesk(cashdesk:any) {
        this.cashdesk_id = cashdesk.id;        
    }

}