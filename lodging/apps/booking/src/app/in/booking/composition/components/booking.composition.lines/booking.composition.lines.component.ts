import { transferArrayItem } from '@angular/cdk/drag-drop';
import { Component, ChangeDetectorRef, OnInit, NgZone, Input, SimpleChanges, OnChanges } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';

import { ApiService, ContextService } from 'sb-shared-lib';

class Composition {
  constructor(
    public id: number = 0,
    public booking_id: number = 0
  ){}
}

class CompositionItem {
  constructor(
    public id: number = 0,
    public firstname: string = '',
    public lastname: string = '',
    public gender: string = '',
    public date_of_birth: string = '',
    public place_of_birth: string = '',
    public email: string = '',
    public phone: string = '',
    public address: string = '',
    public country: string = '',
    public rental_unit_id: string = ''
  ) {}
}

class RentalUnit {
  constructor(
    public id: number = 0,
    public name: string = '',
    public code: string = '',
    public capacity: number = 0
  ) {}
}

@Component({
    selector: 'booking-composition-lines',
    templateUrl: './booking.composition.lines.component.html',
    styleUrls: ['./booking.composition.lines.component.scss']
})
export class BookingCompositionLinesComponent implements OnInit, OnChanges {

    @Input() composition_id: number;

    private composition: Composition = new Composition();

    // map of rental_unit_id mapping related composition items
    public composition_items: any = {};

    public rental_units: Array<RentalUnit> = [];

    public selection:any = [];
    public dragging: boolean = false;

    public vUnit:any = {items: []};

    constructor(
        private api: ApiService,
        private cd: ChangeDetectorRef,
        private context:ContextService,
        private zone: NgZone,
        private snack:MatSnackBar
    ) {}

    ngOnInit() {
    }

    async ngOnChanges(changes: SimpleChanges) {
    if(changes.composition_id) {

        try {

        // reset view
        this.composition_items = {};
        this.rental_units = [];

        const compositions = <Array<any>> await this.api.read("sale\\booking\\Composition",
            [this.composition_id],
            Object.getOwnPropertyNames( new Composition() )
        );

        if(compositions.length) {
            this.composition = compositions[0];

            {
            const data = await this.load( Object.getOwnPropertyNames( new CompositionItem() ) );

            for(let item of data) {
                if(!this.composition_items.hasOwnProperty(item['rental_unit_id'])) {
                this.composition_items[item['rental_unit_id']] = [];
                }
                this.composition_items[item['rental_unit_id']].push(item);
            }
            // add support for buffer zone
            this.composition_items[0] = [];
            }

            {
            const data = <Array<any>> await this.api.read("lodging\\realestate\\RentalUnit",
                Object.keys(this.composition_items),
                Object.getOwnPropertyNames( new RentalUnit() )
            );

            this.rental_units = data;

            // append virtual unit as moving zone
            this.rental_units.push(
                {
                    "id": 0,
                    "name": "buffer",
                    "code": "buff",
                    "capacity": 1000
                }
            );
            }

        }
        }
        catch(error) {
            console.warn(error);
            this.snack.open('Erreur inconnue', 'Erreur');
        }

    }
    }

    public onToggleItem(event: any, item: any) {
        console.log('selecting', event, item);
        if(!item.hasOwnProperty('selected')) {
            item.selected = false;
        }

        let rental_unit_id = item.rental_unit_id;

        if(item.selected) {
            item.selected = false;
        }
        else {
            // unselect items from other containers
            for(let r_id of Object.keys(this.composition_items)) {
                if(r_id != rental_unit_id) {
                    for(let i in this.composition_items[r_id]) {
                    this.composition_items[r_id][i].selected = false;
                    }
                }
            }
            
            // if shift key is pressed : multiple selection
            if(event.shiftKey) {
                // select all items between previously selected item and current item
                let in_selection: boolean = false;
                for(let index in this.composition_items[rental_unit_id]) {
                    let composition_item = this.composition_items[rental_unit_id][index];
                    if(composition_item.selected) {
                        in_selection = true;
                    }
                    if(in_selection) {
                       composition_item.selected = true; 
                    }
                    if(composition_item == item) {
                        break;
                    }
                }
            }

            item.selected = true;
        }
        // update current selection
        this.selection = this.composition_items[rental_unit_id].filter( (item:any) => item.selected );
    }

    public onDragStart(event: any) {
        this.dragging = true;
    }

    public onDrop(event: any) {
        if (event.previousContainer != event.container) {
            console.log('from', event.previousContainer.data, 'to', event.container.data);

            let current_index = event.currentIndex;
            let target_rental_unit_id = event.container.data;
            let source_rental_unit_id = event.previousContainer.data;

            console.log("nb items", this.selection.length);

            let target_index = this.rental_units.findIndex((a) => a.id == target_rental_unit_id);
            let source_index = this.rental_units.findIndex((a) => a.id == source_rental_unit_id);

            let target_rental_unit = this.rental_units[target_index];
            let source_rental_unit = this.rental_units[source_index];

            if(target_rental_unit.capacity < this.composition_items[target_rental_unit_id].length + this.selection.length) {
                for(let item of this.selection) {
                    item.selected = false;
                }
                // reset selection
                this.selection = [];
                this.snack.open('Dépassement de la capacité de destination', 'Erreur');
                return;
            }

            for(let item of this.selection) {
                console.log(item);
                let previous_index = this.composition_items[source_rental_unit_id].findIndex( (a:any) => a.id == item.id );
                // move item
                transferArrayItem(
                    this.composition_items[source_rental_unit_id],
                    this.composition_items[target_rental_unit_id],
                    previous_index,
                    current_index
                );
                ++current_index;
                // update item
                item.rental_unit_id = target_rental_unit_id;
                item.selected = false;
            }

            // update compositionItems
            if(target_rental_unit_id > 0 && target_rental_unit_id != source_rental_unit_id) {
                const composition_items_ids = this.selection.map( (a:any) => a.id );
                this.api.update('sale\\booking\\CompositionItem', composition_items_ids, {rental_unit_id: target_rental_unit_id});
            }
            // reset selection
            this.selection = [];
        }
        this.dragging = false;

    }

  public onOpenRentalUnit(rental_unit_id: number) {

    let descriptor = {
        context: {
            entity:     'lodging\\realestate\\RentalUnit',
            type:       'form',
            name:       'default',
            domain:     ['id', '=', rental_unit_id],
            mode:       'view',
            purpose:    'view',
            display_mode: 'popup',
            callback:   (data:any) => {
                if(data && data.objects && data.objects.length) {
                // received data
                }
            }
        }
    };

    // will trigger #sb-composition-container.on('_open')
    this.context.change(descriptor);
  }

    public onOpenCompositionItem(item_id: number) {

        let descriptor = {
            context: {
                entity:     'sale\\booking\\CompositionItem',
                type:       'form',
                name:       'default',
                domain:     ['id', '=', item_id],
                mode:       'view',
                purpose:    'view',
                display_mode: 'popup',
                callback:   (data:any) => {
                    if(data && data.objects && data.objects.length) {
                    // received data
                    }
                }
            }
        };

        // will trigger #sb-composition-container.on('_open')
        this.context.change(descriptor);
    }

    private async load(fields: any[]) {
        const result = await this.api.collect("sale\\booking\\CompositionItem", [
                'composition_id', '=', this.composition.id
            ],
            fields,
            'id','asc',
            0, 500
        );
        return result;
    }

    public onOpenAll() {
        for(let i = 0, n = this.rental_units.length; i < n; ++i) {
            this.vUnit.items[i] = true;
        }

    }

    public onCloseAll() {
        for(let i = 0, n = this.rental_units.length; i < n; ++i) {
            this.vUnit.items[i] = false;
        }

    }

}