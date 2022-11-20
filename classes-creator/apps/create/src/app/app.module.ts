import { NgModule, LOCALE_ID } from '@angular/core';

import { MAT_DATE_LOCALE } from '@angular/material/core';

import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';

import { PlatformModule } from '@angular/cdk/platform';

import { SharedLibModule, AuthInterceptorService } from 'sb-shared-lib';
import { NgxMaterialTimepickerModule } from 'ngx-material-timepicker';

import { AppRootComponent } from './app.root.component';
import { AppComponent } from './in/app.component';
import { AppRoutingModule } from './app-routing.module';

/* HTTP requests interception dependencies */
import { HTTP_INTERCEPTORS } from '@angular/common/http';

import { registerLocaleData } from '@angular/common';
import localeFr from '@angular/common/locales/fr';

import { MAT_SNACK_BAR_DEFAULT_OPTIONS } from '@angular/material/snack-bar';
import { NgxDropzoneModule } from 'ngx-dropzone';
import { MatTableModule } from '@angular/material/table';

registerLocaleData(localeFr);

@NgModule({
  declarations: [
    AppRootComponent,
    AppComponent,

  ],
  imports: [
    AppRoutingModule,
    BrowserModule,
    BrowserAnimationsModule,
    SharedLibModule,
    PlatformModule,
    NgxMaterialTimepickerModule.setLocale('fr-BE'),
    NgxDropzoneModule,
    MatTableModule,
  ],
  providers: [
    // add HTTP interceptor to inject AUTH header to any outgoing request
    { provide: HTTP_INTERCEPTORS, useClass: AuthInterceptorService, multi: true },
    { provide: MAT_SNACK_BAR_DEFAULT_OPTIONS, useValue: { duration: 4000, horizontalPosition: 'start' } },
    { provide: MAT_DATE_LOCALE, useValue: 'fr-BE' },
    { provide: LOCALE_ID, useValue: 'fr-BE' }
    /* remember to provide CustomDateAdapter in modules with children components using dates */
  ],
  bootstrap: [AppRootComponent]
})
export class AppModule { }
