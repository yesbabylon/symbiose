import { Component, OnInit } from '@angular/core';
import { ApiService, EnvService } from 'sb-shared-lib';
import { ActivatedRoute, Router, NavigationEnd } from '@angular/router';


@Component({
  selector: 'app-settings',
  templateUrl: './settings.component.html',
  styleUrls: ['./settings.component.scss']
})
export class SettingsComponent implements OnInit {

  constructor(
    private api: ApiService,
    private env: EnvService,
    private route: ActivatedRoute,
    private router: Router,
  ) { }

  public current_route:string
  public package: string = 'core';

  // data sorted by sections
  public sections: Array<any> = new Array();

  public sectionsMap: any = {};

  ngOnInit() {
    // Gets the right DATA for the right ROUTE PARAM
    this.route.params.subscribe(async params => {
      this.package = params.package;
      this.current_route =  'SETTINGS_LIST_' + this.package.toUpperCase();
      this.reset();
    });

    // Allow to switch the data (with initialize) when the route parameter changes
    this.router.events.subscribe( async (val) => {
      if (val instanceof NavigationEnd) {
        this.reset().then( () => '' );
      }
    });
  }

  /**
   * Initialise the payload based on current route
   * 
   */  
  public async reset() {
    try {
      const environment:any = await this.env.getEnv();
      const data: any[] = await this.api.collect(
        'core\\setting\\Setting', 
        ['package', '=', this.package], 
        ['package', 'section_id.name', 'section_id.code', 'section_id.description', 'description', 'setting_values_ids.value', 'code', 'type', 'setting_choices_ids.value', 'title', 'help', 'form_control'], 
        'id', 'asc', 0, 100,
        environment.locale
      );

      // reset the array
      this.sections = [];
      this.sectionsMap = {};
      
      // group elements by section
      data.forEach(element => {
        if(!this.sectionsMap.hasOwnProperty(element.section_id.code)) {
          this.sectionsMap[element.section_id.code] = [];
          this.sections.push(element.section_id);
        }
        this.sectionsMap[element.section_id.code].push(element);
      });
    }
    catch(error) {
      console.log('something went wrong', error);
    }
    
  }
}