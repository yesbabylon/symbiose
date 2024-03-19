import { Component, OnInit } from '@angular/core';
import { Module } from '../../../../_types/learn';
import { ActivatedRoute, Router } from '@angular/router';
// @ts-ignore
import { ApiService } from 'sb-shared-lib';
import { User } from '../../../../_types/equal';
import { MatSnackBar } from '@angular/material/snack-bar';

@Component({
    selector: 'app-module-edit',
    templateUrl: './module-edit.component.html',
    styleUrls: ['./module-edit.component.scss'],
})
export class ModuleEditComponent implements OnInit {
    public module: Module;
    public author: string;

    constructor(
        private router: Router,
        private route: ActivatedRoute,
        private api: ApiService,
        private matSnackBar: MatSnackBar
    ) {}

    ngOnInit(): void {
        this.fetchApiResources();
    }

    private async fetchApiResources(): Promise<void> {
        await this.getModule();
        await this.getAuthor();
    }

    private async getModule(): Promise<void> {
        const moduleId: number = this.route.snapshot.params?.id;
        try {
            await this.api
                .collect(
                    'learn\\Module',
                    [['id', '=', moduleId]],
                    [
                        'title',
                        'subtitle',
                        'description',
                        'page_count',
                        'chapter_count',
                        'chapters',
                        'duration',
                        'creator',
                    ]
                )
                .then((response: Module[]): void => {
                    this.module = response[0];
                });
        } catch (error) {
            console.error(error);
        }
    }

    public async updateModuleField(value: string | null, field: string): Promise<void> {
        try {
            await this.api.update('learn\\Course', [this.module.id], {
                [field]: value,
            });

            this.matSnackBar.open(`The module ${field} has been successfully updated.`, undefined, {
                duration: 4000,
                horizontalPosition: 'left',
                verticalPosition: 'bottom',
            });
        } catch (error) {
            console.error(error);

            this.matSnackBar.open(`An error occurred while updating the module ${field} field.`, undefined, {
                duration: 4000,
                horizontalPosition: 'left',
                verticalPosition: 'bottom',
            });
        }
    }

    public navigateToViewMode(): void {
        const moduleId: number = this.route.snapshot.params?.id;

        this.router.navigate(['module', moduleId], { relativeTo: this.route.parent?.parent });
    }

    public formatDuration(duration: number): string {
        const hours: number = Math.floor(duration / 60);
        const minutes: number = duration % 60;

        if (hours === 0) {
            return minutes + 'min';
        } else if (minutes === 0) {
            return hours + 'h';
        } else {
            return hours + 'h ' + (minutes < 10 ? '0' : '') + minutes + 'min';
        }
    }

    private async getAuthor(): Promise<void> {
        try {
            this.api
                .collect('core\\User', ['id', '=', this.module.creator], ['firstname', 'lastname'])
                .then((response: User[]): void => {
                    this.author = response[0].firstname + ' ' + response[0].lastname;
                });
        } catch (error) {
            console.error(error);
        }
    }
}
