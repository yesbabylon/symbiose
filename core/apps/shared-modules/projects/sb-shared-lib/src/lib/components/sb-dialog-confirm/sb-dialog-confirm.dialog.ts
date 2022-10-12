import { Component, Inject } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';


export interface SbDialogConfirmModel {
    title: string,
    message: string,
    no: string,
    yes: string
}

@Component({
    selector: 'sb-dialog-confirm',
    templateUrl: './sb-dialog-confirm.dialog.html',
    styleUrls: ['./sb-dialog-confirm.dialog.scss']
})
export class SbDialogConfirmDialog  {

    public data: SbDialogConfirmModel;

    constructor(
        public dialogRef: MatDialogRef<SbDialogConfirmDialog>,
        @Inject(MAT_DIALOG_DATA) input: any
    ) {
        this.data = {...{
            title: '',
            message: '',
            no: 'no',
            yes: 'yes',
        }, ...input};
    }
}