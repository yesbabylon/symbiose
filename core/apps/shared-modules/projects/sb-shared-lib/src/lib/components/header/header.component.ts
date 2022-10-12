import { Component, OnInit, Output, Input, EventEmitter } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.scss']
})
export class HeaderComponent implements OnInit {
    @Output() toggleMenu = new EventEmitter();
    @Output() toggleBar = new EventEmitter();
    @Input() section: string;
    @Input() items: any[];
    @Input() action: string;
    @Input() i18n: any;
    @Output() select = new EventEmitter<any>();
    @Output() onAction = new EventEmitter<any>();

    public user: any = {};
    public date: Date = new Date();

    public ready: boolean = false;

    constructor(
        private router: Router,
        private auth: AuthService
    ) { }

    ngOnInit(): void {
        this.auth.getObservable().subscribe((user: any) => {
            this.user = user;
            this.ready = true;
        });
    }

    public calcUserBackground() {
        const colors = ['#21b5b8', '#ea4e61', '#f7ab2a', '#83bb32'];
        let i = 0;
        if(this.user && this.user.hasOwnProperty('id')) {
            i = this.user.id % 4;
        }
        return colors[i];
    }

    public calcUserInitials() {
        let res = '';
        if(this.user && this.user.hasOwnProperty('identity_id') && this.user.identity_id) {
            if(this.user.identity_id.hasOwnProperty('firstname')) {
                res = this.user.identity_id.firstname.charAt(0);
            }
            if(this.user.identity_id.hasOwnProperty('lastname')) {
                res += this.user.identity_id.lastname.charAt(0)
            }
        }
        else if(this.user.hasOwnProperty('name')) {
            let parts = this.user.name.split(' ');
            if(parts.length > 0) {
                res = parts[0].charAt(0);
                if(parts.length > 1) {
                    res += parts[1].charAt(0);
                }
            }
        }
        return res;
    }

    public toggleSideMenu() {
        this.toggleMenu.emit();
    }

    public toggleSideBar() {
        this.toggleBar.emit();
    }

    public doAction() {
        this.onAction.emit();
    }

    public onSelectItem(item:any) {
        console.debug('HeaderComponent::onclick', item);

        this.select.emit(item);
        /*
        if(item && item.route) {
            this.router.navigate([item.route]);
        }
        */
    }
}
