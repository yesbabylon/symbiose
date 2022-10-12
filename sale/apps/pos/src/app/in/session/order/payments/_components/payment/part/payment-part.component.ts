import { Component, OnInit, AfterViewInit, Input, Output, EventEmitter, ChangeDetectorRef, SimpleChanges } from '@angular/core';
import { FormControl } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';

import { ApiService, ContextService, TreeComponent } from 'sb-shared-lib';
import { OrderPayment } from '../../../_models/payment.model';
import { OrderPaymentPart } from '../../../_models/payment-part.model';
import { Customer } from '../../../_models/customer.model';

// declaration of the interface for the map associating relational Model fields with their components
interface OrderPaymentPartComponentsMap {
    // no sub-items
};

@Component({
    selector: 'session-order-payments-payment-part',
    templateUrl: 'payment-part.component.html',
    styleUrls: ['payment-part.component.scss']
})
export class SessionOrderPaymentsPaymentPartComponent extends TreeComponent<OrderPaymentPart, OrderPaymentPartComponentsMap> implements OnInit, AfterViewInit  {
    // servel-model relayed by parent
    @Input() set model(values: any) { this.update(values) }

    @Input() customer: Customer;
    @Input() payment: OrderPayment;

    @Output() updated = new EventEmitter();
    @Output() deleted = new EventEmitter();

    public ready: boolean = false;


    public amount:FormControl = new FormControl();
    public voucher_ref:FormControl = new FormControl();

    public get partLabel():string {
        const map: any = {
            "cash":         "espèces",
            "bank_card":    "carte",
            "booking":      "réservation",
            "voucher":      "voucher"
        };
        const value = this.instance.payment_method;
        return map.hasOwnProperty(value)?map[value]:'montant';
    }

    constructor(
        private router: Router,
        private route: ActivatedRoute,
        private cd: ChangeDetectorRef,
        private api: ApiService,
        private context: ContextService
    ) {
        super( new OrderPaymentPart() )
    }


    public ngAfterViewInit() {
        this.componentsMap = {};
    }

    public async ngOnChanges(changes: SimpleChanges) {
        if(changes.hasOwnProperty('model')) {
            // default to due_amount
            if(this.instance.amount == 0) {
                this.amount.setValue(this.payment.total_due-this.payment.total_paid);
            }
            this.ready = true;
        }
    }

    public async ngOnInit() {
        this.amount.valueChanges.subscribe( (value:number)  => this.instance.amount = value );
        this.voucher_ref.valueChanges.subscribe( (value:number)  => this.instance.voucher_ref = value );
    }

    public update(values:any) {
        super.update(values);

        // update widgets and sub-components, if necessary
        this.amount.setValue(this.instance.amount);

        this.voucher_ref.setValue(this.instance.voucher_ref);
    }

    public async onclickDelete() {
        await this.api.update((new OrderPayment()).entity, [this.instance.order_payment_id], {order_payment_parts_ids: [-this.instance.id]});
        await this.api.remove(this.instance.entity, [this.instance.id]);
        this.deleted.emit();
    }

    public async onchangeBookingId(booking: any) {
        this.instance.booking_id = booking.id;
    }

    public async onValidate() {
        try {
            await this.api.update(this.instance.entity, [this.instance.id], {
                amount: this.instance.amount,
                payment_method: this.instance.payment_method,
                booking_id: this.instance.booking_id,
                voucher_ref: this.instance.voucher_ref,
                status: 'paid'
            });
            this.updated.emit();
        }
        catch(response) {
            console.log('unexpected error', response);
        }
    }

    public displayBooking (item : any): string{
        return item.name + ' - ' + item.customer_id.name;
    }
}