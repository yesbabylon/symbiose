import { Component, OnInit, AfterViewInit, ElementRef, QueryList, ViewChild, ViewChildren, NgZone  } from '@angular/core';
import { AuthService, ApiService, ContextService } from 'sb-shared-lib';
import { Router } from '@angular/router';



@Component({
  selector: 'app',
  templateUrl: 'app.component.html',
  styleUrls: ['app.component.scss']
})
export class AppComponent implements OnInit, AfterViewInit  {


public ready: boolean = false;

constructor(
  private context: ContextService,
  private zone: NgZone
) {}

private getDescriptor() {
  return {
      context: {
          "entity": "documents\\Document",
          "view": "dashboard.default"
      }
  };
}

public ngOnInit() {
    this.context.ready.subscribe( (ready:boolean) => {
        this.ready = ready;
    });
}

public ngAfterViewInit() {
    console.log('AppComponent::ngAfterViewInit');

    this.context.setTarget('#sb-container-document');

    this.context.change(this.getDescriptor());
}
}
