import { Component, EventEmitter, OnInit, Output } from '@angular/core';

@Component({
  selector: 'app-pad-value-increments',
  templateUrl: './value-increments.component.html',
  styleUrls: ['./value-increments.component.scss']
})
export class AppPadValueIncrementsComponent implements OnInit {

    @Output() incrementClick = new EventEmitter();
    @Output() keyPress = new EventEmitter();

    constructor() { }

    public onclickIncrement(value: number) {
        this.incrementClick.emit(value);
    }

    public onclickKey(){
        this.keyPress.emit("backspace");
    }

    ngOnInit(): void {
    }

}