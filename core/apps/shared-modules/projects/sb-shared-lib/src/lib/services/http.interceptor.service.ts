import { Injectable } from '@angular/core';
import { Observable, from } from "rxjs";
import { HttpEvent, HttpInterceptor, HttpHandler, HttpRequest } from '@angular/common/http';


@Injectable({
  providedIn: 'root'
})
export class AuthInterceptorService implements HttpInterceptor {

    private storage: any;

    constructor() { 
        this.storage = localStorage;
    }

  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    return from(this.handleAccess(request, next));
  }

  private getAccessToken() {
    return this.storage.getItem('access_token');
  }

  private handleAccess(req: HttpRequest<any>, next: HttpHandler): Promise<HttpEvent<any>> {
      // const token = this.getAccessToken();

      req = req.clone({
        // required when using httpOnly cookie for Auth
        withCredentials: true
/*
        // For Mobile: we're using HTTP from webview, origin is set to http://localhost (android) or capacitor://localhost (iOS)
        // add a custom X-App-ID header to emit CORS response allowing any request having this header set to an authorized value
        setHeaders: {
          Authorization: `Bearer ${token}`
        }
*/        
      });
  
      return next.handle(req).toPromise();
  }
}