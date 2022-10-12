import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService, ContextService } from 'sb-shared-lib';

import { CashdeskSession } from './_models/session.model';


@Component({
  selector: 'session',
  templateUrl: 'session.component.html',
  styleUrls: ['session.component.scss']
})
export class SessionComponent implements OnInit {

    public ready: boolean = false;

    public session: CashdeskSession = new CashdeskSession();

    constructor(
        private router: Router,
        private route: ActivatedRoute,
        private api: ApiService,
        private context: ContextService
    ) {}


    public ngOnInit() {
        console.log('SessionComponent init');

        // fetch the booking ID from the route
        this.route.params.subscribe( async (params) => {
            console.log('received routes ', params);
            if(params && params.hasOwnProperty('session_id')) {
                await this.load(<number> params['session_id']);
            }
        });

        this.ready = true;
    }

    private async load(id: number) {
        if(id > 0) {
            try {
                const result:any = await this.api.read(CashdeskSession.entity, [id], Object.getOwnPropertyNames(new CashdeskSession()));
                if(result && result.length) {
                  this.session = <CashdeskSession> result[0];
                }
            }
            catch(response) {
                console.warn('unable to retrieve given session');
            }
        }
    }

    public onclickSessionsList() {
        this.router.navigate(['/sessions']);
    }
}