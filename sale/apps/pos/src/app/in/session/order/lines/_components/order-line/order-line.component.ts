import { Component, OnInit, OnChanges, AfterViewInit, Input, Output, EventEmitter, ChangeDetectorRef, SimpleChanges, Inject } from '@angular/core';
import { FormControl } from '@angular/forms';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { ActivatedRoute, Router } from '@angular/router';

import { ApiService, ContextService, TreeComponent } from 'sb-shared-lib';
import { Order } from '../../_models/order.model';
import { OrderLine } from '../../_models/order-line.model';


// declaration of the interface for the map associating relational Model fields with their components
interface OrderLineComponentsMap {
};

@Component({
    selector: 'session-order-lines-orderline',
    templateUrl: 'order-line.component.html',
    styleUrls: ['order-line.component.scss']
})
export class SessionOrderLinesOrderLineComponent extends TreeComponent<OrderLine, OrderLineComponentsMap> implements OnInit, OnChanges, AfterViewInit {
    // server-model relayed by parent
    @Input() set model(values: any) { this.update(values) }
    @Input() selected: any;
    @Input() product: any;
    @Output() updated = new EventEmitter();
    @Output() deleted = new EventEmitter();

    public ready: boolean = false;

    public qty: FormControl = new FormControl();
    public unit_price: FormControl = new FormControl();


    constructor(
        private router: Router,
        private route: ActivatedRoute,
        private cd: ChangeDetectorRef,
        private api: ApiService,
        private context: ContextService,
        private dialog: MatDialog
    ) {
        super(new OrderLine())
    }

    public ngOnChanges(changes: SimpleChanges): void {
    }

    public ngAfterViewInit() { }

    public ngOnInit() {
        // init componentsMap
        this.componentsMap = {
        };
        this.qty.valueChanges.subscribe((value: number) => this.instance.qty = value);
        this.unit_price.valueChanges.subscribe((value: number) => this.instance.unit_price = value);
    }

    public update(values: any) {
        console.log('SessionOrderLinesOrderLineComponent:: update', values);
        super.update(values);
        // update widgets and sub-components, if necessary
    }

    public async ondelete() {
        await this.api.update((new Order()).entity, [this.instance.order_id], { order_lines_ids: [-this.instance.id] });
        // await this.api.remove(this.instance.entity, [this.instance.id]);
        this.deleted.emit();
    }

    public async onchange() {
        await this.api.update(this.instance.entity, [this.instance.id], { 
            qty: this.instance.qty, 
            // remove trailing 3rd digit, if any
            unit_price:  parseFloat((+this.instance.unit_price).toFixed(2)),
            discount: this.instance.discount, 
            free_qty: this.instance.free_qty, 
            vat_rate: this.instance.vat_rate 
        });

        this.updated.emit();
    }

}