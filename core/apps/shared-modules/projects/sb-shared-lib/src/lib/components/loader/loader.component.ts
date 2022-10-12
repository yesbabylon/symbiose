import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { UserClass } from '../../../lib/classes/user.class';
import { ApiService } from '../../../lib/services/api.service';
import { AuthService } from '../../../lib/services/auth.service';


@Component({
  selector: 'loader',
  templateUrl: './loader.component.html',
  styleUrls: ['./loader.component.scss']
})
export class LoaderComponent implements OnInit {

  constructor(
    private auth: AuthService,
    private api: ApiService,
    private router: Router) {

  }

  public async ngOnInit() {

    this.auth.getObservable().subscribe( (user: UserClass) => {
      this.router.navigate(['/apps']);
    });

    try {
      await this.auth.authenticate();
    }
    catch(error:any) {
      console.debug('LoaderComponent : auth error', error);
      this.router.navigate(['/auth']);
    }

  }

}