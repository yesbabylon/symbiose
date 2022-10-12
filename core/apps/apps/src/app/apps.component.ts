import { Component, OnInit } from '@angular/core';

import { AuthService } from 'sb-shared-lib';


@Component({
    selector: 'apps',
    templateUrl: 'apps.component.html',
    styleUrls: ['apps.component.scss']
})
export class AppsComponent implements OnInit {

    public user: any = {};
    public user_apps: string[];

    private colors = ['#3F52B5', '#653BB5', '#29A1A1', '#FF9741', '#F9BD15', '#83C33F', '#E95367'];

    public apps: any = {
        booking: {
            url: "/booking",
            name: "APPS_APP_BOOKING",
            icon: 'airline_seat_individual_suite',
            color: this.colors[0],
            groups: ['booking.default.user', 'booking.default.administrator']
        },
        pos: {
            url: "/pos",
            name: "APPS_APP_POS",
            icon: 'point_of_sale',
            color: this.colors[6],
            groups: ['pos.default.user', 'pos.default.administrator']
        },
        sales: {
            url: "/sale",
            name: "APPS_APP_SALES",
            icon: 'import_contacts',
            color: this.colors[4],
            groups: ['sale.default.user', 'sale.default.administrator']
        },
        accounting: {
            url: "/accounting",
            name: "APPS_APP_ACCOUNTING",
            icon: 'monetization_on',
            color: this.colors[2],
            groups: ['finance.default.user', 'finance.default.administrator']
        },
        setting: {
            url: "/settings",
            name: "APPS_APP_SETTINGS",
            icon: 'settings',
            color: this.colors[3],
            groups: ['setting.default.user', 'setting.default.administrator']
        },
        documents: {
            url: "/documents",
            name: "APPS_APP_DOCUMENTS",
            icon: 'insert_drive_file',
            color: this.colors[5],
            groups: ['documents.default.user', 'documents.default.administrator']
        },
        stats: {
            url: "/stats",
            name: "APPS_APP_STATS",
            icon: 'filter_alt',
            color: this.colors[1],
            groups: ['stats.default.user', 'stats.default.administrator']
        }
    };

    constructor(
        private auth: AuthService) {
    }

    public async ngOnInit() {
        this.auth.getObservable().subscribe( (user:any) => {
            this.user_apps = [];
            this.user = user;
        });
    }

    public getApps() {
        return Object.keys(this.apps);
    }

    public isGranted(app_name:string) {
        let app = this.apps[app_name];
        if(app.groups) {
            for(let group of app.groups) {
                if(this.auth.hasGroup(group)) {
                    return true;
                }
            }
        }
        return false;
    }

    public onSelect(app: any) {
        window.location.href = this.apps[app].url;
    }

    public async onclickDisconnect() {
        try {
            await this.auth.signOut();
            setTimeout( () => {
                window.location.href = '/auth';
            }, 500);
        }
        catch(err) {
            console.warn('unable to request signout');
        }
    }

}