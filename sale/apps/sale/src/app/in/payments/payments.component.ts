import { Component, OnInit, AfterViewInit  } from '@angular/core';
import { AuthService, ApiService } from 'sb-shared-lib';


@Component({
  selector: 'payments',
  templateUrl: 'payments.component.html',
  styleUrls: ['payments.component.scss']
})
export class PaymentsComponent implements OnInit  {


  constructor(
    private auth: AuthService
  ) {}


  public ngOnInit() {
    console.log('PaymentsComponent init');
  }

}