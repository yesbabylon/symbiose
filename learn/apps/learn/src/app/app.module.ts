import { NgModule, LOCALE_ID } from '@angular/core';

import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';

import { DateAdapter, MatNativeDateModule, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform, PlatformModule } from '@angular/cdk/platform';

import {
    SharedLibModule,
    AuthInterceptorService,
    CustomDateAdapter,
    // @ts-ignore
} from 'sb-shared-lib';
import { NgxMaterialTimepickerModule } from 'ngx-material-timepicker';

import { AppRoutingModule } from './app-routing.module';
import { AppRootComponent } from './app.root.component';
import { AppComponent } from './in/app.component';

import { MatTableModule } from '@angular/material/table';
/* HTTP requests interception dependencies */
import { HTTP_INTERCEPTORS } from '@angular/common/http';

import { registerLocaleData } from '@angular/common';
import { MAT_SNACK_BAR_DEFAULT_OPTIONS } from '@angular/material/snack-bar';

// specific locale setting
import localeFr from '@angular/common/locales/fr';
import { MatButtonModule } from '@angular/material/button';
import { MatInputModule } from '@angular/material/input';
import { MatListModule } from '@angular/material/list';
import { MatIconModule } from '@angular/material/icon';
import { FormsModule } from '@angular/forms';
import { RightmenuComponent } from './_components/rightmenu/rightmenu.component';

registerLocaleData(localeFr);

@NgModule({
    declarations: [AppRootComponent, AppComponent, RightmenuComponent],
    imports: [
        AppRoutingModule,
        BrowserModule,
        BrowserAnimationsModule,
        SharedLibModule,
        MatNativeDateModule,
        PlatformModule,
        NgxMaterialTimepickerModule.setLocale('fr-BE'),
        MatTableModule,
        MatButtonModule,
        MatInputModule,
        MatListModule,
        MatIconModule,
        FormsModule,
    ],
    providers: [
        // add HTTP interceptor to inject AUTH header to any outgoing request
        // { provide: HTTP_INTERCEPTORS, useClass: AuthInterceptorService, multi: true },
        {
            provide: MAT_SNACK_BAR_DEFAULT_OPTIONS,
            useValue: { duration: 4000, horizontalPosition: 'start' },
        },
        { provide: MAT_DATE_LOCALE, useValue: 'fr-BE' },
        { provide: LOCALE_ID, useValue: 'fr-BE' },
        {
            provide: DateAdapter,
            useClass: CustomDateAdapter,
            deps: [MAT_DATE_LOCALE, Platform],
        },
    ],
    exports: [],
    bootstrap: [AppRootComponent],
})
export class AppModule {}
