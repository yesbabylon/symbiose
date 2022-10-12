import { Component, Inject } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';


export interface SbDialogNotifyModel {
    title: string,
    message: string,
    ok: string
}

@Component({
    selector: 'sb-dialog-notify',
    templateUrl: './sb-dialog-notify.dialog.html',
    styleUrls: ['./sb-dialog-notify.dialog.scss']
})
export class SbDialogNotifyDialog  {

    public data: SbDialogNotifyModel;

    constructor(
        public dialogRef: MatDialogRef<SbDialogNotifyDialog>,
        @Inject(MAT_DIALOG_DATA) input: any
    ) {
        this.data = {...{
            title: '',
            message: '',
            ok: 'ok',
        }, ...input};
    }
}