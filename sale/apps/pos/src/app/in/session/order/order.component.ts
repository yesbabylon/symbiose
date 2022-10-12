import { Component, AfterContentInit, OnInit, NgZone } from '@angular/core';
import { ActivatedRoute, Router, RouterEvent, NavigationEnd } from '@angular/router';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';

import { ApiService, EnvService, AuthService, ContextService } from 'sb-shared-lib';
import { MatSnackBar } from '@angular/material/snack-bar';
import { FormControl, Validators } from '@angular/forms';
import { EditorChangeContent, EditorChangeSelection } from 'ngx-quill';
import { UserClass } from 'sb-shared-lib/lib/classes/user.class';

import { filter } from 'rxjs/operators';
import { HttpErrorResponse } from '@angular/common/http';


class Order {
  constructor(
    public id: number = 0,
    public name: string = '',
    public email: string = '',
    public phone: string = ''
  ) {}
}


@Component({
  selector: 'session-order',
  templateUrl: './order.component.html',
  styleUrls: ['./order.component.scss']
})
export class SessionOrderComponent implements OnInit, AfterContentInit {

  public showSbContainer: boolean = false;

  public loading = true;
  public is_sent = false;

  public user: UserClass = null;
  public order_id: number;

  public order: any[] = [];


  public languages: any[] = [];
  public lang: string = '';

  private lang_id: number = 0;



  constructor(
    private dialog: MatDialog,
    private api: ApiService,
    private auth: AuthService,
    private env: EnvService,
    private router: Router,
    private route: ActivatedRoute,
    private context:ContextService,
    private snack: MatSnackBar,
    private zone: NgZone) {

  }

  /**
   * Set up callbacks when component DOM is ready.
   */
  public ngAfterContentInit() {
    this.loading = false;
  }

  ngOnInit() {



    // fetch the booking ID from the route
    this.route.params.subscribe( async (params) => {
      if(params) {
        try {

          if(params.hasOwnProperty('id')){
            /*
            this.order_id = <number> parseInt(params['id']);
            await this.loadOrder();
            */
          }

        }
        catch(error) {
          console.warn(error);
        }
      }
    });


  }

}