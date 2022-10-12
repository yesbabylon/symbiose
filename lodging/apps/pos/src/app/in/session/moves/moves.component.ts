import { Component, ChangeDetectorRef, OnInit, AfterViewInit, NgZone } from '@angular/core';

import { Subscription } from 'rxjs';

import { ApiService, AuthService } from 'sb-shared-lib';

@Component({
  selector: 'session-moves',
  templateUrl: './moves.component.html',
  styleUrls: ['./moves.component.scss']
})
export class SessionMovesComponent implements OnInit, AfterViewInit {


  public showSbContainer: boolean = false;

  constructor(
    private api: ApiService, 
    private auth:AuthService,
    private cd: ChangeDetectorRef,
    private zone: NgZone
  ) {  

  }

  ngOnInit() {
  }

  /**
   * Set up callbacks when component DOM is ready.
   */
  public ngAfterViewInit() {
    // _open and _close event are relayed by eqListener on the DOM node given as target when a context is requested
    // #sb-booking-container is defined in booking.edit.component.html
    $('#sb-planning-container').on('_close', (event, data) => {
      this.zone.run( () => {
        this.showSbContainer = false;
      });
    });
    $('#sb-planning-container').on('_open', (event, data) => {
      this.zone.run( () => {
        this.showSbContainer = true;
      });
    });
  }

}