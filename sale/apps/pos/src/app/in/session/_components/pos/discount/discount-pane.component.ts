import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';

@Component({
  selector: 'session-order-lines-discount-pane',
  templateUrl: './discount-pane.component.html',
  styleUrls: ['./discount-pane.component.scss']
})
export class SessionOrderLinesDiscountPaneComponent implements OnInit {
    @Output() closePane = new EventEmitter();
    @Output() selectField = new EventEmitter();
    @Input() set model(values: any) { this.update(values) }

    public index : number;
    public liners : any = [];

    constructor() { }

    ngOnInit(): void {
    }

    onSelectField(selected : any){
        this.index = selected[0].value;
        this.selectField.emit(this.liners[this.index].field);
    }

    update(values: any) {
        console.log('update discount pane', values);
        if(values) {
            this.liners = [{
                name: "Gratuités",
                unit : "p.",
                value : values.free_qty,
                field : 'free_qty',
                color : "",
                disabled : false
            },
            {
                name: "Réduction",
                unit : "%",
                value : (values.discount*100).toFixed(0),
                field : 'discount',
                color : "",
                disabled : false
            },
            {
                name: "Quantité",
                unit : "p.",
                value : values.qty,
                field: "qty",
                color : "#3f51b5",
                disabled : false
            },
            {
                name: "Prix",
                unit : "€",
                value : values.unit_price,
                field: "unit_price",
                color : "#3f51b5",
                disabled : false
            },
            {
                name: "TVA",
                unit : "%",
                value : (values.vat_rate*100).toFixed(0),
                field: "vat_rate",
                color : "#3f51b5",
                disabled : false
            }];
        }
    }

    onClose(){
        this.closePane.emit();
    }

}