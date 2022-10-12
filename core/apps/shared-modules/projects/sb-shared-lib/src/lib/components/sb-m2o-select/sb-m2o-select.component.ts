import { Component, OnInit, OnChanges, Output, Input, EventEmitter, SimpleChanges, SimpleChange } from '@angular/core';
import { FormControl, Validators } from '@angular/forms';

import { Observable, ReplaySubject } from 'rxjs';
import { map, mergeMap, debounceTime } from 'rxjs/operators';

import { ApiService } from '../../services/api.service';
import { Domain } from '../../classes/domain.class';

@Component({
  selector: 'sb-m2o-select',
  templateUrl: './sb-m2o-select.component.html',
  styleUrls: ['./sb-m2o-select.component.scss']
})
export class SbMany2OneSelectComponent implements OnInit, OnChanges {
    // full name of the entity to load
    @Input() entity: string = '';
    // id of the object to load as preset value
    @Input() id: number = 0;
    // extra fields to load (in addition to 'id', 'name')
    @Input() fields?: string[] = [];  
    // additional domain for filtering result set
    @Input() domain?: any[] = [];
    // specific controller to use for fetching data
    @Input() controller?: string = '';
    // extra parameter specific to the chosen controller
    @Input() params?: any = {};
    // mark the field as mandatory
    @Input() required?: boolean = false;
    // specific placeholder of the widget
    @Input() placeholder?: string = '';
    // specific hint/helper for the widget
    @Input() hint?: string = '';
    // message to diisplay in case no match was found
    @Input() noResult?: string = '';
    // mark the field as readonly
    @Input() disabled?: boolean = false;
    // custom method for rendering the items
    @Input() displayWith?: (a:any) => string;

    @Output() itemSelected:EventEmitter<number> = new EventEmitter<number>();

    // currently selected item
    public item: any = null;

    public inputFormControl: FormControl;
    public resultList: Observable<any>;

    private inputQuery: ReplaySubject<any>;

    constructor(private api: ApiService) {
        this.inputFormControl = new FormControl();
        this.inputQuery = new ReplaySubject(1);
    }


    ngOnInit(): void {

        // watch changes made on input
        this.inputFormControl.valueChanges.subscribe( (value:string)  => {
            if(!this.item || this.item != value) {
                this.inputQuery.next(value);
            }
        });

        // update autocomplete result list
        this.resultList = this.inputQuery.pipe(
            debounceTime(300),
            map( (value:any) => (typeof value === 'string' ? value : ( (value == null)?'':value.name ) )),
            mergeMap( async (name:string) => await this.filterResults(name) )
        );

    }

  /**
   * Update component based on changes received from parent.
   *
   * @param changes
   */
    ngOnChanges(changes: SimpleChanges) {
        let has_changed = false;

        const currentId: SimpleChange = changes.id;
        const currentEntity: SimpleChange = changes.entity;

        if(changes.required) {
            if(this.required) {
                this.inputFormControl.setValidators([Validators.required]);
                this.inputFormControl.markAsTouched();
            }
            this.inputFormControl.updateValueAndValidity();
        }

        if(currentId && currentId.currentValue && currentId.currentValue != currentId.previousValue) {
            has_changed = true;
        }

        if(currentEntity && currentEntity.currentValue && currentEntity.currentValue != currentEntity.previousValue) {
            has_changed = true;
        }

        if(has_changed) {
            this.load();
        }
    }


  /**
   * Load initial values, based on inputs assigned by parent component.
   *
   */
    private async load() {
        if(this.id && this.id > 0 && this.entity && this.entity.length) {
            try {
                const result:Array<any> = <Array<any>> await this.api.read(this.entity, [this.id], ['id', 'name', ...this.fields]);
                if(result && result.length) {
                    this.item = result[0];
                    this.inputFormControl.setValue(this.item);
                }
            }
            catch(error:any) {
                console.warn('an unexpected error occured');
            }
        }
    }

    private async filterResults(name: string) {
        let filtered:any[] = [];
        if(this.entity.length && (!this.item || this.item.name != name) ) {
            try {
                let tmpDomain = new Domain([]);
                if(name.length) {
                    tmpDomain = new Domain(["name", "ilike", '%'+name+'%']);
                }
                let domain = (new Domain(this.domain)).merge(tmpDomain).toArray();

                let data:any[];

                if(this.controller && this.controller.length) {
                    let body:any = {
                        get: this.controller,
                        entity: this.entity,
                        fields: ["id", "name", ...this.fields],
                        domain: JSON.stringify(domain),
                        ...this.params
                    };

                    // fetch objects using controller given by View (default is core_model_collect)
                    data = await this.api.fetch('/', body);
                }
                else {
                    data = await this.api.collect(this.entity, domain, ["id", "name", ...this.fields], 'name', 'asc', 0, 25);
                }

                filtered = data;
            }
            catch(error:any) {
                console.warn(error);
            }
        }
        return filtered;
    }

    public itemDisplay = (item:any): string => {
        if(!item) return '';
        if(this.displayWith) {
            return this.displayWith(item);
        }
        return item.name;
    }

    public onChange(event:any) {
        if(event && event.option && event.option.value) {
            this.item = event.option.value;
            this.inputFormControl.setValue(this.item);
            this.itemSelected.emit(this.item);
        }
    }

    public onFocus() {
        // force triggering a list refresh
        this.inputFormControl.setValue('');
    }

    public onReset() {
        this.inputFormControl.setValue(null);
    }

    public onRestore() {
        if(this.item) {
            this.inputFormControl.setValue(this.item);
        }
        else {
            this.inputFormControl.setValue(null);
        }
    }

}