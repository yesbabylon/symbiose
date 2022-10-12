import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
    selector: 'app-pad-generic',
    templateUrl: './generic.component.html',
    styleUrls: ['./generic.component.scss']
})
export class AppPadGenericComponent implements OnInit {
    @Output() newItemEvent = new EventEmitter();
    @Output() keyPress = new EventEmitter();
    @Input() disabledKeys: string[] = []; 

    constructor(private router: Router) { }
    
    public element = '';
    public numberPassed = 0;
    public mouseUp: any;
    public mouseDown: any;
    public good_route: boolean = true;
    public operator: string = '+';


    ngOnInit(): void {
        
    }

    checkActionType(event: any) {
        this.newItemEvent.emit(event);
    }

    onKeypress(value: any) {
        this.numberPassed = value;
        this.keyPress.emit(value);
    }

    onDoubleClick() {
        this.operator = (this.operator == '-')?'+':'-';
    }
}
