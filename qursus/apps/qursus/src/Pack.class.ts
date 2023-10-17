import { $ } from "./jquery-lib";

import { ApiService } from "./qursus-services";


/**
 * 
 */
export class PackClass {
    public id: number;
    public name: string;               // (ex. PDT, AT)
    public subtitle: string;           // (ex. Duration: 15 minutes)
    public title: string;              // (ex. Program Development Training, Awareness Training)
    public description: string;

    constructor(
        id: number,
        name: string,               // (ex. PDT, AT)
        subtitle: string,           // (ex. Duration: 15 minutes)
        title: string,              // (ex. Program Development Training, Awareness Training)
        description: string
    ) {
        this.id = id;
        this.name = name;
        this.subtitle = subtitle;
        this.title = title;
        this.description = description;
    }

    
}

export default PackClass;