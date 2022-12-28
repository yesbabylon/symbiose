// import { Component, OnInit, AfterViewInit  } from '@angular/core';
// import { AuthService, ApiService } from 'sb-shared-lib';
import { Component, AfterContentInit, OnInit, NgZone, ChangeDetectorRef } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { FormControl } from '@angular/forms';
import { ApiService, AuthService } from 'sb-shared-lib';
import { MatSnackBar } from '@angular/material/snack-bar';
import { Observable } from 'rxjs';
import { map, startWith } from 'rxjs/operators';
import { Fields } from '../_models/fields.model';

@Component({
  selector: 'app',
  templateUrl: 'app.component.html',
  styleUrls: ['app.component.scss']
})
export class AppComponent implements OnInit, AfterContentInit {

  public packager = "";
  public show = false;

  // needed to filter content
  myControl = new FormControl('');
  myControlSubPackage = new FormControl('');
  myControlClasses = new FormControl('');


  // Filter content (autocomplete)
  filteredOptions: Observable<any[]>;
  filteredSubPackages: Observable<any[]>;
  filteredClasses: Observable<any[]>;


  // options of filtered content
  public options: any = [];
  public subpackageoptions: any = [];
  public classesoptions: any = [];


  // use the model to get the generic fields used for objects
  public fielders = new Fields();

  // are the inherited properties visible or not
  public inherited_visibility = false;

  // is the add button visible or not
  public showAddFieldButton = true;

  // select fields that need to be shown (add those to the ones with values and the rest goes to the details part)
  public mainFields: any = ["id", "name", "type"];


  public showFieldsDetails: any = {};


  public infos: any;
  public schema: any;
  public schema_parent: any;
  public schema_fields: any;

  // public field: Fields = new Fields();

  public oldvalue: any = "";

  public compteur: any = [];
  public newFieldCompteur: any = [];

  // Use the keys to find the properties that have to be displayed
  public newClass: any = {
    "many2many": ["foreign_object", "foreign_field", "rel_table", "rel_foreign_key", "rel_local_key"],
    "many2one": ["foreign_object"],
    "one2many": ["foreign_object", "foreign_field"]
  }

  constructor(
    private api: ApiService,
    public auth: AuthService,
    public snack: MatSnackBar,
  ) {
  }


  /**
   * Set up callbacks when component DOM is ready.
   */

  async ngOnInit() {

    // for the moment, classes gets all the packages aswell, could split the controllers
    this.infos = await this.api.fetch("?get=classes-creator_classes");
    let types = await this.api.fetch("?get=classes-creator_types");

    // package options
    this.options = Object.keys(this.infos.packages);
    // this.newClass.essentials.type = types;
    this.newClass.types = types;

    // filters the packages
    this.filteredOptions = this.myControl.valueChanges.pipe(
      startWith(''),
      map((value: any) => this._filter(value || '', "package")),
    );

    this.filteredSubPackages = this.myControlSubPackage.valueChanges.pipe(
      startWith(''),
      map((value: any) => this._filter(value || '', 'subpackage')),
    );

    // filters the classes
    this.filteredClasses = this.myControlClasses.valueChanges.pipe(
      startWith(''),
      map((value: any) => this._filter(value || '', 'classes')),
    );
  }

  // filters the search
  private _filter(value: any, info: any): any[] {
    const filterValue = value.toLowerCase();
    if (info == "package") {
      this.packager = this.myControl.value;
      this.subpackageoptions = this.infos.packages[this.packager];
      if (this.subpackageoptions != null) {
        if (typeof this.subpackageoptions[0] == 'string') {
          this.classesoptions = this.subpackageoptions;
          this.subpackageoptions = '';
        } else {
          this.subpackageoptions = Object.keys(this.subpackageoptions);
        }
      }
      if (this.subpackageoptions?.length <= 0) {
        this.classesoptions = this.infos.packages[this.packager];
      }
      return this.options.filter((option: any) => option.toLowerCase().includes(filterValue));
    } else if (info == "subpackage") {
      this.classesoptions = this.infos.packages[this.myControl.value][this.myControlSubPackage.value];
      return this.subpackageoptions.filter((option: any) => option.toLowerCase().includes(filterValue));
    } else if (info == "classes") {
      return this.classesoptions.filter((option: any) => option.toLowerCase().includes(filterValue));
    } else {
      return ["ok"];
    }
  }

  async createClass() {
    await this.api.fetch("?do=core_config_save-model", {
      schema: JSON.stringify(this.newClass.essentials),
      entity: this.myControlClasses.value
    });
  }


  async onClassSelect() {
    let className = this.myControlClasses.value.replace('.class.php', '');
    this.schema = await this.api.fetch("?get=model_schema&entity=" + className);

    // checks for the parent schema fields

    // if(this.schema.parent != "equal\\orm\\Model"){
    //   this.schema_parent = await this.api.fetch("?get=model_schema&entity=" + this.schema.parent);
    //   Object.entries(this.schema_parent.fields).forEach((element:any[]) =>
    //   {
    //     this.newClass[className][element[0]]= {};
    //     Object.entries(element[1]).forEach((fields:any[])=>{
    //       if(typeof fields[1] == 'object'){
    //         let newValue = "[";
    //         fields[1].forEach((fieldValue:any) => {
    //           newValue += fieldValue + ',';
    //         });
    //         fields[1]  = newValue + ']';
    //       }
    //       this.newClass[className][element[0]][fields[0]]  = fields[1];
    //       this.schema_fields[fields[0]] = fields[1];
    //     });
    //   });
    // }



    // add already the new fields from schema
    this.newClass[className] = {
      link: this.schema.link,
      parent: this.schema.parent,
      root: this.schema.root,
      table: this.schema.table,
      unique: this.schema.unique
    }

    let newValue1: any;
    // Add the children schema fields
    for (const element in this.schema.fields) {
      this.newClass[className][element] = new Fields();
      this.showFieldsDetails[element] = false;
      for (const key in this.schema.fields[element]) {
        if (typeof this.schema.fields[element][key] == 'object') {
          let newValue = "[";
          this.schema.fields[element][key].forEach((fieldValue: any) => {
            newValue += fieldValue + ',';
          });
          this.schema.fields[element][key] = newValue + ']';
          newValue1 = this.schema.fields[element][key];
          this.newClass[className][element][key] = newValue1;
        } else {
          this.newClass[className][element][key] = this.schema.fields[element][key];
        }
      }
    }
  }

  ngAfterContentInit(): void {

  }

  // takes care of options selection and changes the input value through the form control
  onselectoption(form_control: any, option: any) {
    form_control.setValue(option);
    if (form_control == this.myControlClasses) {
      this.onClassSelect();
    }
  }

  // checks which type of html has to be displayed, if it is type bool, many2one, etc ..
  onchecktype(type: any) {

  }


  // Is the field property a boolean or something else, to know how to display it
  isBool(value: any) {
    if (typeof value == 'boolean') {
      return true;
    } else {
      return false;
    }
  }

  // Assign the new values (Field Names)
  getvalues(oldval: any, value: any, path: any) {
    delete Object.assign(path, { [value]: path[oldval] })[oldval];
    return value;
  }


  // Add the package to the array
  addPackage() {
    this.options.push(this.myControl.value);
  }

  // Add the class to the array
  addClass() {
    this.classesoptions.push(this.myControlClasses.value);
  }



  addFieldName(value: any) {
    this.newClass[this.myControlClasses.value] = { [value]: new Fields(), ...this.newClass[this.myControlClasses.value] };

    // the object is classified following the alphabet, so if we use a it would appear at the top, could use an extra counter for that
    // Could do it en deux temps, d'abord avoir un ngfor sur de nouveaux fields, liés à une array quelconque et ensuite les rajouter comme précédemment avec un outfocus
  }

  addField() {
    this.newFieldCompteur.push("new");
    this.showAddFieldButton = false;
  }

  // Add the fields for good
  createFields() {
  }

  // Checks which fields have to be displayed following the type, using the newclass variable: 
  // public newClass: any = {
  //   "many2many": ["foreign_object", "foreign_field", "rel_table", "rel_foreign_key", "rel_local_key"],
  //   "many2one": ["foreign_object"],
  //   "one2many": ["foreign_object", "foreign_field"]
  // }
  relationsChecker(value: any, key: any) {
    if (this.newClass[value.type] && this.newClass[value.type].includes(key)) {
      return true;
    } else if (this.newClass.many2many.includes(key)) {
      // if key but not the right type, doesn't show the relations
      return false;
    } else {
      return true;
    }
  }

  // Displays content
  showContent(key: any) {
    return this.showFieldsDetails[key];
  }

  changeContent(key: any) {
    this.showFieldsDetails[key] = !this.showFieldsDetails[key];
  }

}
