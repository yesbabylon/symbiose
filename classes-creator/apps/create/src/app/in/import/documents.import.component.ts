// import { Component, OnInit, AfterViewInit  } from '@angular/core';
// import { AuthService, ApiService } from 'sb-shared-lib';
import { Component, AfterContentInit, OnInit, NgZone, Inject, ViewChild, ElementRef, ChangeDetectorRef } from '@angular/core';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import {FormControl} from '@angular/forms';
import { ApiService, ContextService, AuthService } from 'sb-shared-lib';
import { MatSnackBar } from '@angular/material/snack-bar';
import {Observable} from 'rxjs';
import {map, startWith} from 'rxjs/operators';

@Component({
  selector: 'documents-import',
  templateUrl: './documents.import.component.html',
  styleUrls: ['./documents.import.component.scss']
})
export class DocumentsImportComponent implements OnInit, AfterContentInit {



  myControl = new FormControl('');
  myControlSubPackage = new FormControl('');
  myControlClasses = new FormControl('');
  filteredOptions: Observable<string[]>;
  public packager= "";
  filteredSubPackages: Observable<string[]>;
  filteredClasses: Observable<string[]>;
  Object = Object;
  public options: any =[];
  public subpackageoptions: any =[];
  public classesoptions: any =[];
  public showSubPackage = false;
  public infos: any;
  public schema: any;
  public manies = ["many2many", "many2one", "one2many"];

  public compteur = [1];
  public classe: any = {
    "essentials": {
      "name": "",
      "field_name": [],
      "description": [],
      "types": [],
      "multilang": [false],
      "unique": [false],
      "usage": [],
      "foreign_object": [],
      "foreign_field": [],
      "rel_table": [],
      "rel_foreign_key": [],
      "rel_local_key": [],
      "package": "",
      "subpackage": ""
    },
    "many2many": ["foreign_object", "foreign_field", "rel_table", "rel_foreign_key", "rel_local_key"],
    "many2one": ["foreign_object"],
    "one2many": ["foreign_object", "foreign_field"]
  }


  constructor(
    private dialog: MatDialog,
    private api: ApiService,
    private zone: NgZone,
    public auth: AuthService,
    public snack: MatSnackBar,
    private changeDetection: ChangeDetectorRef
  ) {
  }

  // private data: DataService

  /**
   * Set up callbacks when component DOM is ready.
   */

  async ngOnInit() {
    this.infos = await this.api.fetch("?get=classes-creator_get-classes");
    console.log(this.infos);
    this.options = Object.keys(this.infos.packages);
    this.classe.essentials.types = this.infos.types;
    this.filteredOptions = this.myControl.valueChanges.pipe(
      startWith(''),
      map((value:any) => this._filter(value || '', "package")),

    );

    this.filteredSubPackages = this.myControlSubPackage.valueChanges.pipe(
      startWith(''),
      map((value:any) => this._filter(value || '', 'subpackage')),
    );

    this.filteredClasses = this.myControlClasses.valueChanges.pipe(
      startWith(''),
      map((value:any) => this._filter(value || '', 'classes')),
    );
  }
  private _filter(value: string, info : any): any[] {
    const filterValue = value.toLowerCase();

    if(info == "package"){
      this.packager = this.myControl.value;
        this.subpackageoptions = this.infos.packages[this.packager];
        if(this.subpackageoptions != null){
          if(typeof this.subpackageoptions[0] == 'string'){
            this.subpackageoptions =this.subpackageoptions;
          }else{
            this.subpackageoptions =Object.keys(this.subpackageoptions);
          }
        }
      if(this.subpackageoptions?.length <= 0 ){
        this.classesoptions = this.infos.packages[this.packager];
        console.log(this.classesoptions);
      }
      return this.options.filter((option:any) => option.toLowerCase().includes(filterValue));
    }else if(info == "subpackage"){
      this.classesoptions = this.infos.packages[this.myControl.value][this.myControlSubPackage.value];
      return this.subpackageoptions.filter((option:any) => option.toLowerCase().includes(filterValue));
    }else if (info == "classes"){
      console.log('random');
      return this.classesoptions.filter((option:any) => option.toLowerCase().includes(filterValue));
    }else{
      return ["ok"];

    }

  }

  async createClass(){
    console.log(this.classe);
    this.classe.essentials.package = this.myControl.value;
    this.classe.essentials.subpackage = this.myControlSubPackage.value;
    await this.api.fetch("?do=classes-creator_create-class", {
      name: this.classe.essentials.name,
      description: this.classe.essentials.description,
      types: this.classe.essentials.types,
      multilang: this.classe.essentials.multilang,
      unique: this.classe.essentials.unique,
      usage: this.classe.essentials.usage,
      foreign_object: this.classe.essentials.foreign_object,
      foreign_field: this.classe.essentials.foreign_field,
      rel_table: this.classe.essentials.rel_table,
      rel_foreign_key: this.classe.essentials.rel_foreign_key,
      rel_local_key: this.classe.essentials.rel_local_key,
      package: this.classe.essentials.package,
      subpackage: this.classe.essentials.subpackage
    });
  }


  async onClassSelect(){
    let className = this.myControlClasses.value.replace('.class.php', '');
    this.schema = await this.api.fetch("?get=model_schema&entity="+ this.myControl.value + "\\" + this.myControlSubPackage.value + "\\" + className);
    Object.entries(this.schema.fields).forEach((element:any, index:any) => {
      this.classe.essentials.field_name.push(element[0]);
      this.compteur.push(1);
        Object.entries(element[1]).forEach((fields:any, indexo:any)=>{
        if((this.classe.essentials[fields[0]]) !=null){
          this.classe.essentials[fields[0]].push(fields[1]);
        };
          // (this.classe.essentials[fields[0]]).push(fields[1]);
        })
    });
    // this.compteur.push(1);
    console.log(this.classe);
  }

  addField(){
      this.compteur.push(1);
      this.changeDetection.detectChanges();
  }

  ngAfterContentInit(): void {

  }
}









// Delete Dialog component






// Rename Dialog component


