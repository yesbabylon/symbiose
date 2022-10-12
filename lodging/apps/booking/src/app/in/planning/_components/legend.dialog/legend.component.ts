import { Component, Inject, OnInit } from '@angular/core';
import { FormControl, Validators } from '@angular/forms';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';


@Component({
    selector: 'planning-legend-dialog',
    templateUrl: './legend.component.html',
    styleUrls: ['./legend.component.scss']
})
export class PlanningLegendDialogComponent {
    constructor(
        public dialogRef: MatDialogRef<PlanningLegendDialogComponent>,
    //   @Inject(MAT_DIALOG_DATA) public data: DialogData,
    ) {}

    onClose(): void {
        this.dialogRef.close();
    }
}