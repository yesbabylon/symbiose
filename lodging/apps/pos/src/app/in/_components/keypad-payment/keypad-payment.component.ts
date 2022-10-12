import { Component, EventEmitter, Inject, Input, OnInit, Output, SimpleChanges, ViewChild } from '@angular/core';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { th } from 'date-fns/locale';
import { data } from 'jquery';
import { AppPadValueIncrementsComponent } from '../pad/value-increments/value-increments.component';

@Component({
  selector: 'app-keypad-payment',
  templateUrl: './keypad-payment.component.html',
  styleUrls: ['./keypad-payment.component.scss']
})
export class AppKeypadPaymentComponent implements OnInit {
    @ViewChild('incrementsPad') togglePad: AppPadValueIncrementsComponent;

    @Input() customer : any;    
    @Input() disabledKeys : any;
    @Input() hasInvoice : boolean;

    @Output() requestInvoiceChange = new EventEmitter();
    @Output() nextClick = new EventEmitter();
    @Output() keyPress = new EventEmitter();    
    @Output() customerChange : any = new EventEmitter();

    public actionType : any = "quantity";
    public select_customer = false;
    public operator : string = '+';


    constructor() { }


    ngOnInit(): void {
    }

    public onclickInvoice(){
        this.hasInvoice = !this.hasInvoice;
        this.requestInvoiceChange.emit(this.hasInvoice);
    }

    public onclickNext(){
        this.nextClick.emit();
    }
    
    public onclickKey(event: any) {
        this.keyPress.emit(event);
    }

    public onselectCustomer(customer:any){
        this.customer = customer;
        this.select_customer = false;
        this.customerChange.emit(customer);
    }

}