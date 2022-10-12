import {Component, HostBinding,  EventEmitter, Output, Input, OnInit} from '@angular/core';
import {animate, state, style, transition, trigger} from '@angular/animations';

@Component({
  selector: 'app-menu-list-item',
  templateUrl: './menu-list-item.component.html',
  styleUrls: ['./menu-list-item.component.scss'],
  animations: [
    trigger('indicatorRotate', [
      state('collapsed', style({transform: 'rotate(-90deg)'})),
      state('expanded', style({transform: 'rotate(90deg)'})),
      transition('expanded <=> collapsed',
        animate('225ms cubic-bezier(0.4,0.0,0.2,1)')
      ),
    ])
  ]
})
export class MenuListItemComponent implements OnInit {
    @Input() item: any = {};
    @Input() depth: number;
    @Input() i18n: any;
    @Output() select = new EventEmitter<any>();
    @Output() toggle = new EventEmitter<any>();

    public expanded: boolean = false;

    constructor() {
        if (this.depth === undefined) {
            this.depth = 0;
        }
    }

    ngOnInit() {
    }

    public onItemToggle(item: any) {
        console.debug('MenuListItemComponent::onItemToggle', item);
        // if item is expanded, fold siblings, if any
        if(item.expanded) {
            // make sure item is visible
            item.hidden = false;
            if(this.item.children) {
                for(let child of this.item.children) {
                    if(item != child) {
                        child.hidden = true;
                        child.expanded = false;
                        child.selected = false;
                    }
                }
            }
            // and that children are visible but not expanded
            if(item.children) {
                for(let child of item.children) {
                    child.hidden = false;
                    child.expanded = false;
                }
            }
        }
        // if item is folded, make sure all sibling are visible
        else {
            if(this.item.children) {
                for(let child of this.item.children) {
                    child.hidden = false;
                    child.selected = false;
                    if(child.children) {
                        for(let subitem of child.children) {
                            subitem.expanded = false;
                            subitem.hidden = true;
                        }
                    }
                }
            }
        }
    }

    public onItemSelected(item: any) {
        console.debug('MenuListItemComponent::onItemSelected', item);
        if (item.type == 'entry') {
            this.select.emit(item);
        }
        else {
            if (item.children && item.children.length) {
                let is_child_open = false;
                for(let child of item.children) {
                    if(child.expanded) {
                        is_child_open = true;
                    }
                }
                if(!is_child_open) {
                    item.expanded = !item.expanded;
                    this.toggle.emit(item);
                }
                else {
                    this.onItemToggle(item);
                }
            }
        }
    }

}