import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler, HttpEvent, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError, timer } from 'rxjs';
import { catchError, delay, switchMap } from 'rxjs/operators';
import { Router } from '@angular/router';
import { AuthService } from './auth.service';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  constructor(private authService: AuthService, private router: Router) {}

  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    return timer(200).pipe( // Ritardo di 200ms
      switchMap(() => { // Passa al flusso successivo dopo il ritardo
        const token = this.authService.getToken();

        // Escludi l'endpoint di login dall'intercettazione
        if (req.url.includes('/login')) {
          return next.handle(req);
        }

        // Aggiungi il token solo se è presente
        if (token) {
          req = req.clone({
            setHeaders: {
              Authorization: `Bearer ${token}`
            }
          });
        }

        return next.handle(req).pipe(
          catchError((error: HttpErrorResponse) => {
            if (error.status === 401) {
              // Verifica se l'errore proviene dall'endpoint /refresh
              if (req.url.includes('/refresh')) {
                this.authService.logOut();
              }
              this.router.navigate(['/login']);
            }
            return throwError(error);
          })
        );
      })
    );
  }
}