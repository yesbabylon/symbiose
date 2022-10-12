import { Component, EventEmitter, OnInit, Output } from '@angular/core';

@Component({
  selector: 'app-pad-type-toggle',
  templateUrl: './type-toggle.component.html',
  styleUrls: ['./type-toggle.component.scss']
})
export class AppPadTypeToggleComponent implements OnInit {

    @Output() selectedModeChange = new EventEmitter();
    @Output() keyPress = new EventEmitter();

    public mode = 'qty'; // 'qty' or 'unit_price'

    constructor() { }

    onchangeMode(mode: string) {
        this.selectedModeChange.emit(mode);
    }

    onpressKey(value: string) {
        this.keyPress.emit(value);
    }

    ngOnInit(): void {
    }

    public reset() {
        this.mode = 'qty';
        this.selectedModeChange.emit(this.mode);
    }

}