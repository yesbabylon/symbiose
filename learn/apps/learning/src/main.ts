import { enableProdMode } from '@angular/core';
import { platformBrowserDynamic } from '@angular/platform-browser-dynamic';

import { AppModule } from './app/app.module';
// @ts-ignore
import { EnvService } from 'sb-shared-lib';

const env: EnvService = new EnvService();

env.getEnv()
    .then((environment: Record<string, any>): void => {
        if (environment.production) {
            enableProdMode();
        }
        platformBrowserDynamic()
            .bootstrapModule(AppModule)
            .catch((err: any) => console.error(err));
    })
    .catch((err: any) => console.error(err));
