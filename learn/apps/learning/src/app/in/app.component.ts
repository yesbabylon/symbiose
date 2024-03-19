import { Component, OnInit } from '@angular/core';
// @ts-ignore
import { ContextService } from 'sb-shared-lib';

@Component({
    // eslint-disable-next-line @angular-eslint/component-selector
    selector: 'app',
    templateUrl: 'app.component.html',
    styleUrls: ['app.component.scss'],
})
export class AppComponent implements OnInit {
    public ready: boolean = false;

    constructor(private context: ContextService) {}

    public ngOnInit() {
        this.context.ready.subscribe((ready: boolean) => {
            this.ready = ready;
        });
    }
}
