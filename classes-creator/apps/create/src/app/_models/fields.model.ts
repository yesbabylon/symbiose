export class Fields {
    [key: string]: any;
    constructor(
      public readonly: boolean = false,
      public description: string = "",
      public unique: boolean = false,
      public multilang: boolean = false,
      public visible: boolean = false,
      public required: boolean = false,
      public usage: string = "",
      public type: string = "",
      public foreign_object: string = "",
      public foreign_field: string = "",
      public rel_table: string = "",
      public rel_foreign_key: string = "",
      public rel_local_key: string = "",
      public onupdate: string = "",
      public oncreate: string = "",
      public date_to: string = ""
    ) {}
}

// Model regrouping the main fields properties to be found in objects