import { Component, OnInit, AfterViewInit, ElementRef, QueryList, ViewChild, ViewChildren  } from '@angular/core';
import { AuthService, ApiService } from 'sb-shared-lib';
import { Router } from '@angular/router';


import { FormGroup, FormControl, Validators } from '@angular/forms';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { CdkTextareaAutosize } from '@angular/cdk/text-field';

import { Observable, BehaviorSubject } from 'rxjs';
import { find, map, startWith, debounceTime } from 'rxjs/operators';

import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';



@Component({
  selector: 'app',
  templateUrl: 'app.component.html',
  styleUrls: ['app.component.scss']
})
export class AppComponent implements OnInit, AfterViewInit  {


  constructor(
              private auth: AuthService,
              private api: ApiService,
              private router: Router,
              private dialog: MatDialog
              ) {
  }




  public ngAfterViewInit() {

  }

  public ngOnInit() {

  }


}