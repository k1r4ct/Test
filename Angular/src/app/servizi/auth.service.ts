import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, catchError, tap, throwError } from 'rxjs';
import { Router } from '@angular/router';
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class AuthService {

  private isLoggedInSubject: BehaviorSubject<boolean>;
  public isLoggedIn$: Observable<boolean>;
  private global = { API_URL : ''};

  constructor(private http: HttpClient, private router: Router) {
    this.isLoggedInSubject = new BehaviorSubject<boolean>(this.hasToken());
    this.isLoggedIn$ = this.isLoggedInSubject.asObservable();
    this.global.API_URL = environment.apiUrl;
  }

  signIn(form: any) {
    return this.http.post(this.global.API_URL + 'login', form).pipe(
      tap((data: any) => {
        localStorage.setItem('jwt', data.token.original.access_token);
        localStorage.setItem('session_expired', data.token.original.expires_in);
        localStorage.setItem('userLogin', data.user.id);
        this.isLoggedInSubject.next(true);
        setTimeout(() => {
          this.router.navigate(['/dashboard']);
        }, 100);
      }),
      catchError((error: any) => {
        localStorage.removeItem('jwt');
        this.isLoggedInSubject.next(false);
        this.router.navigate(['/login']);
        return throwError(error);
      })
    );
  }

  logOut() {
      // Make the HTTP call FIRST, then clear session
      this.http.post(this.global.API_URL + 'logout', {}).subscribe({
        next: () => {
          this.clearSession();
        },
        error: () => {
          this.clearSession();
        }
      });
  }

  private clearSession() {
      localStorage.removeItem('jwt');
      localStorage.removeItem('session_expired');
      localStorage.removeItem('userLogin');
      this.isLoggedInSubject.next(false);
  }

  isUserLogin(): boolean {
    return this.hasToken();
  }

  public hasToken(): boolean {
    return !!localStorage.getItem('jwt');
  }

  public getToken() {
    return localStorage.getItem('jwt');
  }

  refreshToken() {
    const token = {
      token: this.getToken()
    };
    return this.http.post(this.global.API_URL + 'refresh', token).pipe(
      catchError((error: any) => {
        this.logOut();
        this.router.navigate(['/login']);
        return throwError(error);
      })
    );
  }
}