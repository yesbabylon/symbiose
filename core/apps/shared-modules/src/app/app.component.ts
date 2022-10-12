import { Component, OnInit } from '@angular/core';
import { AuthService } from '../../../projects/common/src/lib/auth.service';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent implements OnInit {
    title = 'symbiose';

    constructor(private auth: AuthService) {

    }

    public ngOnInit() {
        this.auth.getObservable().subscribe((user: any) => {
            if(user.id <= 0) {
                console.warn('user non identified');
                // redirect to /auth App
                location.href = '/auth';
            }
        });
    }


    public test() {
        console.debug(this.auth.user);
    }
}
