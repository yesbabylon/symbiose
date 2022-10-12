import { Component, AfterContentInit, OnInit, NgZone, Inject, ViewChild, ElementRef } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';

import { ApiService, ContextService } from 'sb-shared-lib';
import { MatSnackBar } from '@angular/material/snack-bar';


interface SalePaymentsImportDialogConfirmData {

}

class BankStatement {
  constructor(
    public id: number = 0,
    public name: string = '',
    public date: Date = new Date(),
    public old_balance: number = 0.0,
    public new_balance: number = 0.0,
    public bank_account_number: string = '',
    public bank_account_bic: string = ''
  ) {}
}


@Component({
  selector: 'payments-import',
  templateUrl: './payments.import.component.html',
  styleUrls: ['./payments.import.component.scss']
})
export class PaymentsImportComponent implements OnInit, AfterContentInit {

  @ViewChild('fileUpload') file_upload: ElementRef;

  public loading = true;
  public has_result = false;

  public invalid_files:String[] = [];
  public duplicate_files:String[] = [];
  public bank_statements:BankStatement[] = [];

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
    this.loading = false;
    this.has_result = false;
    this.reset();
  }

  ngOnInit() {

  }

  private reset() {
    this.invalid_files = [];
    this.duplicate_files = [];
    this.bank_statements = [];
  }

  public onGenerate() {
    const dialogRef = this.dialog.open(PaymentsImportDialogConfirm, {
      width: '50vw',
      data: {}
    });

    dialogRef.afterClosed().subscribe( async (result) => {
      if(result) {
      }
      else {
        console.log('answer is no');
      }
    });
  }


  public async onFilesSelected(event:any) {

    // accept multiple files
    const files = event.target.files;

    this.reset();
    this.loading = true;

    for(let item of files) {
      const file:File = <File> item;
      if(file) {

        const data:any = await this.readFile(file);

        try {

          const response:any = await this.api.call('?do=lodging_payments_import', {
              name: file.name,
              type: file.type,
              data: data
          });

          for(let statement of response) {
            this.bank_statements.push(<BankStatement>statement);
          }
        }
        catch (response:any) {
          console.log(response);
          if(response.hasOwnProperty('error')) {
            let error = response.error;
            if(error.hasOwnProperty('errors')) {
              if(error.errors.hasOwnProperty('CONFLICT_OBJECT')) {
                this.duplicate_files.push(file.name);
              }
              else if(error.errors.hasOwnProperty('INVALID_PARAM')) {
                this.invalid_files.push(file.name);
              }
            }
          }
          this.api.errorFeedback(response);
        }
      }
    }
    this.loading = false;
    this.has_result = true;
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
    <p>Cet assistant générera une composition sur base de la réservation <b> </b>.</p>
    <p>Les détails de la composition existante seront remplacés et les éventuels changements effectués seront perdus.</p>
    <p><b>Confirmez-vous la (re)génération ?</b></p>
  </div>

  <div mat-dialog-actions>
    <button mat-button [mat-dialog-close]="false">Annuler</button>
    <button mat-button [mat-dialog-close]="true" cdkFocusInitial>Créer</button>
  </div>
  `
})
export class PaymentsImportDialogConfirm {
  constructor(
    public dialogRef: MatDialogRef<PaymentsImportDialogConfirm>,
    @Inject(MAT_DIALOG_DATA) public data: SalePaymentsImportDialogConfirmData
  ) {}
}
