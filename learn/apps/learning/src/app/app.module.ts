import { LOCALE_ID, NgModule } from '@angular/core';
import { AppRootComponent } from './app.root.component';
import { AppComponent } from './in/app.component';
import { AppRoutingModule } from './app-routing.module';
import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { SharedLibModule, CustomDateAdapter } from 'sb-shared-lib';
import { MAT_SNACK_BAR_DEFAULT_OPTIONS } from '@angular/material/snack-bar';
import { DateAdapter, MAT_DATE_LOCALE } from '@angular/material/core';
import { Platform } from '@angular/cdk/platform';
import { TopBarComponent } from './_components/top-bar/top-bar.component';
import { LargeComponent } from './in/large/large.component';
import { MatTabsModule } from '@angular/material/tabs';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatListModule } from '@angular/material/list';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { SmallComponent } from './in/small/small.component';
import { ContentComponent } from './in/large/tabs/content/content.component';
import { PresentationComponent } from './in/large/tabs/presentation/presentation.component';
import { QAndAComponent } from './in/large/tabs/q-and-a/q-and-a.component';
import { ReviewsComponent } from './in/large/tabs/reviews/reviews.component';

@NgModule({
    declarations: [
        AppRootComponent,
        AppComponent,
        TopBarComponent,
        LargeComponent,
        SmallComponent,
        ContentComponent,
        PresentationComponent,
        QAndAComponent,
        ReviewsComponent,
    ],
    imports: [
        AppRoutingModule,
        BrowserModule,
        BrowserAnimationsModule,
        SharedLibModule,
        MatTabsModule,
        MatIconModule,
        MatInputModule,
        MatButtonModule,
        MatExpansionModule,
        MatListModule,
        MatProgressSpinnerModule,
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
    bootstrap: [AppRootComponent],
})
export class AppModule {
}
