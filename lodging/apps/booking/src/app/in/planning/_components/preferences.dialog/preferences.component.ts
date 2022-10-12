import { Component, HostListener, Inject, OnInit } from '@angular/core';
import { FormControl, Validators } from '@angular/forms';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';


@Component({
    selector: 'planning-preferences-dialog',
    templateUrl: './preferences.component.html',
    styleUrls: ['./preferences.component.scss']
})
export class PlanningPreferencesDialogComponent {
    public rows_height: number;
    public show_parents: boolean;
    public show_children: boolean;
    public show_accomodations_only: boolean;

    @HostListener('window:keyup.Enter', ['$event'])
    onDialogClick(event: KeyboardEvent): void {
        this.onSave();
    }

    constructor(
        public dialogRef: MatDialogRef<PlanningPreferencesDialogComponent>,
    //   @Inject(MAT_DIALOG_DATA) public data: DialogData,
    ) {
        let rows_height = localStorage.getItem('planning_rows_height');
        if(rows_height) {
            this.rows_height = parseInt(rows_height, 10);
        }
        else {
            this.rows_height = 30;
        }
        this.show_parents = (localStorage.getItem('planning_show_parents') === 'true');
        this.show_children = (localStorage.getItem('planning_show_children') === 'true');
        this.show_accomodations_only = (localStorage.getItem('planning_show_accomodations_only') === 'true');
    }

    public onClose(): void {
        this.dialogRef.close();
    }

    public onSave(): void {
        this.dialogRef.close(this);
    }

}