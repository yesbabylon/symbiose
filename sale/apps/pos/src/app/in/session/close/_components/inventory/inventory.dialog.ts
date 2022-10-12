import { Component, Inject, OnInit } from '@angular/core';
import { FormControl, Validators } from '@angular/forms';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { debounceTime } from 'rxjs/operators';


@Component({
    selector: 'session-close-inventory-dialog',
    templateUrl: './inventory.dialog.html',
    styleUrls: ['./inventory.dialog.scss']
})
export class SessionCloseInventoryDialog implements OnInit {

    public deleteConfirmation = false;

    public index: number;

    public disabledKeys = [',', '+']

    constructor(
        public dialogDelete: MatDialogRef<SessionCloseInventoryDialog>,
        @Inject(MAT_DIALOG_DATA) public data: any,
        // public auth : AuthService,
        // public api : ApiService, 
        // private router: Router,
        private dialog: MatDialog
    ) { 
        console.log(data);
        if(data.inventory) {
            this.inventory = data.inventory;
        }
    }

  
    public inventory = [
        {
            value: 0.01, number: ""
        },
        {
            value: 0.02, number: ""
        },
        {
            value: 0.05, number: ""
        },
        {
            value: 0.1, number: ""
        },
        {
            value: 0.2, number: ""
        },
        {
            value: 0.5, number: ""
        },
        {
            value: 1, number: ""
        },
        {
            value: 2, number: ""
        },
        {
            value: 5, number: ""
        },
        {
            value: 10, number: ""
        },
        {
            value: 20, number: ""
        },
        {
            value: 50, number: ""
        },
        {
            value: 100, number: ""
        },
        {
            value: 200, number: ""
        },
        {
            value: 500, number: ""
        }
    ];


    ngOnInit(): void {
    }

    public onKeyboardInput(event : any){
        // check if backspace, otherwise add number
        if(event.inputType == "deleteContentBackward") {
            let test = this.inventory[this.index].number.slice(0, -1);
            this.inventory[this.index].number = test;
        }
        else{
            this.inventory[this.index].number += event.target.value[event.target.value.length - 1];
        }
    }

    public onPadInput(value: any) {
        console.log('pressed')
        if (value != 'backspace' && value != ',' && value != '+/-') {
            this.inventory[this.index].number += value;
        }

        if (value == 'backspace') {
            let test = this.inventory[this.index].number.slice(0, -1);
            this.inventory[this.index].number = test;
        }
    }

    public calcTotal() {
        let total:number = 0;
        this.inventory.forEach((item) => {
            if (item.number != "") {
                total += item.value * parseFloat(item.number);
            }
        });
        return total;
    }

    onGetFocusedInput(input: any) {
        this.index = input;
    }

    public closeDialog() {
        this.dialogDelete.close({
            inventory: this.inventory,
            total: this.calcTotal()
        });
    }

    public onIncrementPadInput(event: any) {
        if (this.inventory[this.index].number == "") {
            this.inventory[this.index].number = (parseFloat(event)).toString();
        } 
        else {
            this.inventory[this.index].number = (parseFloat(this.inventory[this.index].number) + parseFloat(event)).toString();
        }
    }    
}