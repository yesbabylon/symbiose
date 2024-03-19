import { Component } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { ActivatedRouteSnapshot, NavigationEnd, Router } from '@angular/router';
import { filter } from 'rxjs/operators';

interface PageSegment {
    path: string;
    label: string;
}

interface EntitySegment extends PageSegment {
    entity: string;
    id: string;
}

type RouteSegment = PageSegment | EntitySegment;

@Component({
    selector: 'app-breadcrumb',
    templateUrl: './breadcrumb.component.html',
    styleUrls: ['./breadcrumb.component.scss'],
})
export class BreadcrumbComponent {
    // Subject emitting the breadcrumb hierarchyF
    private readonly _routeSegments$: BehaviorSubject<RouteSegment[]> = new BehaviorSubject<RouteSegment[]>([]);

    // Observable exposing the breadcrumb hierarchy
    readonly routeSegments$: Observable<RouteSegment[]> = this._routeSegments$.asObservable();

    constructor(private router: Router) {
        this.router.events.pipe(filter(event => event instanceof NavigationEnd)).subscribe((): void => {
            const root = this.router.routerState.snapshot.root;
            const routeSegments: RouteSegment[] = [];
            this.addBreadcrumb(root, [], routeSegments);
            this._routeSegments$.next(routeSegments);
        });
    }

    private addBreadcrumb(route: ActivatedRouteSnapshot, parentUrl: string[], routeSegments: RouteSegment[]) {
        if (route) {
            const activatedRouteSegments: string[] = route.url.map(url => url.path);
            const fullPathSegments: string[] = parentUrl.concat(route.url.map(url => url.path));
            const fullPathSegmentsJoined: string = fullPathSegments.join('/');

            // Add an element for the current route part
            for (let i: number = 0; i < activatedRouteSegments.length; i++) {
                if (
                    activatedRouteSegments[i] &&
                    activatedRouteSegments[i + 1] &&
                    !isNaN(+activatedRouteSegments[i + 1])
                ) {
                    const routeSegment: EntitySegment = {
                        entity: activatedRouteSegments[i],
                        id: activatedRouteSegments[i + 1],
                        path: fullPathSegmentsJoined,
                        label: `${activatedRouteSegments[i]}[${activatedRouteSegments[i + 1]}]`,
                    };
                    routeSegments.push(routeSegment);
                    i = i + 1;
                } else {
                    const routeSegment: PageSegment = {
                        path: fullPathSegmentsJoined,
                        label: activatedRouteSegments[i],
                    };
                    routeSegments.push(routeSegment);
                }
            }

            if (route.firstChild) {
                this.addBreadcrumb(route.firstChild, fullPathSegments, routeSegments);
            }
        }
    }
}
