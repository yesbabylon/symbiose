import { Component, AfterContentInit, OnInit, NgZone, Inject, ViewChild, ElementRef, HostListener, OnDestroy } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';

import { ApiService, ContextService } from 'sb-shared-lib';
import { MatSnackBar } from '@angular/material/snack-bar';


interface BookingCompositionDialogConfirmData {
  booking: Booking;
  relationship: string;
}

class Booking {
  constructor(
    public id: number = 0,
    public name: string = '',
    public composition_id: number = 0
  ) {}
}


@Component({
  selector: 'booking-composition',
  templateUrl: 'composition.component.html',
  styleUrls: ['composition.component.scss']
})
export class BookingCompositionComponent implements OnInit, AfterContentInit, OnDestroy {
    @HostListener('unloaded')
    ngOnDestroy() {
        console.log('BookingsComponent::ngOnDestroy');
        this.active = false;
    }
    @ViewChild('fileUpload') file_upload: ElementRef;

    public showSbContainer: boolean = false;
    public selectedTabIndex:number = 0;

    public ready: boolean = false;
    public loading: boolean = false;

    public booking_id: number;
    public booking: any = new Booking();


    // flag telling if the route to which the component is associated with is currently active (amongst routes defined in first parent routing module)
    private active = false;

    constructor(
        private dialog: MatDialog,
        private api: ApiService,
        private route: ActivatedRoute,
        private context:ContextService,
        private snack: MatSnackBar,
        private zone: NgZone) {
    }

  /**
   * Set up callbacks when component DOM is ready.
   */
    public ngAfterContentInit() {
        console.log('BookingCompositionComponent::ngAfterViewInit');

        this.load( Object.getOwnPropertyNames(new Booking()) );

        this.active = true;
    }

    public ngAfterViewInit() {
        console.log('BookingsComponent::ngAfterViewInit');

        this.context.setTarget('#sb-composition-container');
    }

    public ngOnInit() {
        console.log('BookingCompositionComponent::ngOnInit');

        // fetch the booking ID from the route
        this.route.params.subscribe( async (params) => {
            this.booking_id = <number> params['booking_id'];

            // relay change to context (to display sidemenu panes according to current object)
            this.context.change({
                context_only: true,   // do not change the view
                context: {
                    entity: 'lodging\\sale\\booking\\Booking',
                    type: 'form',
                    purpose: 'view',
                    domain: ['id', '=', this.booking_id]
                }
            });
        });

        this.context.ready.subscribe( (ready:boolean) => {
            this.ready = ready;
        });
    }

    private async load(fields: any[]) {
        const result = <Array<any>> await this.api.read("lodging\\sale\\booking\\Booking", [this.booking_id], fields);
        if(result && result.length) {
            const booking = <Booking> result[0];
            this.booking = new Booking(
                booking.id,
                booking.name,
                booking.composition_id
            );
        }
    }

    /**
     * Request a new eQ context for selecting a payer, and relay change to self::payerChange(), if an object was created
     * #sb-composition-container is defined in .component.html file
     */
    public viewFullList() {

        this.selectedTabIndex = 1;
        this.showSbContainer = true;

        setTimeout( () => {
            let descriptor = {
                context: {
                    entity:     'sale\\booking\\CompositionItem',
                    type:       'list',
                    name:       'default',
                    domain:     ['composition_id', '=', this.booking.composition_id],
                    close_button: true,
                    callback:   (data:any) => {
                        this.selectedTabIndex = 0;
                        this.showSbContainer = false;
                        setTimeout( () => {
                            // re-load booking context (to display sidemenu panes according to current object)
                            this.context.change({
                                context_only: true,   // do not change the view
                                context: {
                                    entity: 'lodging\\sale\\booking\\Booking',
                                    type: 'form',
                                    purpose: 'view',
                                    domain: ['id', '=', this.booking_id]
                                }
                            });

                        })
                    }
                }
            };

            this.context.change(descriptor);
        });
    }

    public onGenerate() {
        const dialogRef = this.dialog.open(BookingCompositionDialogConfirm, {
            width: '50vw',
            data: {booking: this.booking}
        });

        dialogRef.afterClosed().subscribe( async (result) => {
            if(result) {
                const data:any = await this.api.fetch('?do=lodging_composition_generate&booking_id='+this.booking_id);
                // reload
                await this.load(Object.getOwnPropertyNames(new Booking()));
                this.loading = false;
            }
            else {
                console.log('answer is no');
            }
        });
    }


    public async onFileSelected(event:any) {
        console.log('BookingCompositionComponent::onFileSelected', event);
        const file:File = event.target.files[0];

        if(file) {

            const data:any = await this.readFile(file);

            try {

                const response:any = await this.api.call('?do=lodging_composition_import', {
                    name: file.name,
                    type: file.type,
                    data: data,
                    booking_id: this.booking_id
                });

                this.loading = true;
                setTimeout( async () => {
                    // reload
                    await this.load(Object.getOwnPropertyNames(new Booking()));
                    this.loading = false;
                })

            }
            catch (err) {
                this.snack.open("Format non reconnu", "Erreur");
                console.log(err);
            }

        }

        // reset input
        this.file_upload.nativeElement.value = "";
    }


    private readFile(file: any) {
        return new Promise((resolve, reject) => {
            var reader = new FileReader();
            let blob = new Blob([file], { type: file.type });
            reader.onload = () => {
                resolve(reader.result);
            }
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    }

}


@Component({
    selector: 'dialog-booking-composition-generate-confirm-dialog',
    template: `
    <h1 mat-dialog-title>Générer la composition</h1>

    <div mat-dialog-content>
    <p>Cet assistant générera une composition sur base de la réservation <b>{{data.booking.name}}</b>.</p>
    <p>Les détails de la composition existante seront remplacés et les éventuels changements effectués seront perdus.</p>
    <p><b>Confirmez-vous la (re)génération ?</b></p>
    </div>

    <div mat-dialog-actions>
    <button mat-button [mat-dialog-close]="false">Annuler</button>
    <button mat-button [mat-dialog-close]="true" cdkFocusInitial>Créer</button>
    </div>
    `
})
export class BookingCompositionDialogConfirm {
    constructor(
        public dialogRef: MatDialogRef<BookingCompositionDialogConfirm>,
        @Inject(MAT_DIALOG_DATA) public data: BookingCompositionDialogConfirmData
    ) {}
}
