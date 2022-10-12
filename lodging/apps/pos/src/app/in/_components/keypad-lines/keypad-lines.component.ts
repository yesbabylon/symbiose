import { Component, EventEmitter, Inject, Input, OnChanges, OnInit, Output, SimpleChanges, ViewChild } from '@angular/core';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { th } from 'date-fns/locale';
import { data } from 'jquery';
import { AppPadTypeToggleComponent } from '../pad/type-toggle/type-toggle.component';


@Component({
    selector: 'app-keypad-lines',
    templateUrl: './keypad-lines.component.html',
    styleUrls: ['./keypad-lines.component.scss']
})
export class AppKeypadLinesComponent implements OnInit {

    @ViewChild('togglePad') togglePad: AppPadTypeToggleComponent;
    
    @Input() customer : any;    
    @Input() hasInvoice : boolean;    

    @Output() requestInvoiceChange = new EventEmitter();
    @Output() nextClick = new EventEmitter();
    @Output() keyPress = new EventEmitter();
    @Output() onTypeMode = new EventEmitter();
    @Output() customerChange : any = new EventEmitter();

    
    public actionType: any = "qty";
    public select_customer = false;
    public posLineDisplay: string = "main";
    public operator: string = '+';


    constructor(private dialog: MatDialog) { }

    ngOnInit(): void {
        this.reset();
    }

    public reset() {
        this.select_customer = false;
        this.togglePad?.reset();        
    }

    public onclickInvoice() {
        this.hasInvoice = !this.hasInvoice;
        this.requestInvoiceChange.emit(this.hasInvoice);
    }

    public onclickNext(value: string) {
        this.nextClick.emit(value);
    }


    public checkActionType(event: any) {
        this.onTypeMode.emit(event);
    }

    public onselectCustomer(customer: any) {
        this.customer = customer;
        this.select_customer = false;
        this.customerChange.emit(customer);
    }

    public onclickKey(event: any) {
        this.keyPress.emit(event);
    }
}


@Component({
selector: 'pos-opening',
template: `
<h2 mat-dialog-title style="text-align:center">Contrôle des espèces à l'ouverture</h2>
<div mat-dialog-content  style="width: 40rem; background-color:lightgray; padding: 1rem">
<div>
    <div style="display: flex; justify-content:space-between; padding: 0.5rem">
    <h4 style="font-weight:bold">Espèces à l'ouverture</h4>
    <div class="tablet" style="width:25%; display:flex; justify-content:space-evenly;align-items: center; border-bottom: 1px solid black; float:right;padding: 0.4rem">
        <p style="font-size: 1.5rem; margin:0; height:max-content">{{data.price}}</p>
        <button style="padding: 0.75rem;" (click)="onDisplayCoins()" *ngIf ="!displayTablet"><mat-icon>tablet_android</mat-icon></button>
    </div>
    </div>
    <div>
    <!-- <app-pad style="width: 50%;" *ngIf = "displayTablet"></app-pad> -->
    <textarea style="background-color: white; border: 2px solid lightgreen; margin: 0.2rem; padding: 0.2rem; width:100%;" name="" id="" cols="30" rows="10" placeholder="Espèces">
    <ul> <li></li> </ul></textarea>
    </div>
</div>
<div mat-dialog-actions style="display: flex; justify-content: flex-end; background-color:lightgray; width: 100%">
<button mat-raised-button color="primary" style="display:block;" mat-raised-button (click)="closeDialog()" >Fermer</button>
</div>
`
})

export class PosOpeningDialog {

    public deleteConfirmation = false;
    public displayTablet = false;
    public coins: any = [{ value: "" }, { value: "" }];

    constructor(
        public dialogDelete: MatDialogRef<PosOpeningDialog>,
        @Inject(MAT_DIALOG_DATA) public data: any,
        private dialog: MatDialog
    ) { }

    ngOnInit(): void {
        console.log(this.data);
    }

    onDisplayCoins() {
    }

    public closeDialog() {
        this.dialogDelete.close({});
    }
}







