import { Component, HostBinding, OnInit, OnDestroy } from '@angular/core';
import { trigger, state, style, animate, transition } from '@angular/animations';
import { Router, NavigationEnd } from '@angular/router';
import { Subject } from 'rxjs';
import { takeUntil, filter } from 'rxjs/operators';
import { InactivityService } from './servizi/inactivity.service';
import { ApiService } from './servizi/api.service';
import { AuthService } from './servizi/auth.service';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss'],
  animations: [
    trigger("pageTransition", [
      transition(":enter,:leave", [
        style({ opacity: 0, transform: "scale(0.1)" }),
        animate("500ms ease-in-out", style({ opacity: 1, transform: "scale(1)" }))
      ]),
      transition(":leave", [
        animate("500ms ease-in-out", style({ opacity: 0, transform: "scale(0.1)" }))
      ])
    ])
  ],
  standalone: false
})
export class AppComponent implements OnInit, OnDestroy {

  private destroy$ = new Subject<void>();
  state = 'pagina1';

  constructor(
    private router: Router,
    private inactivityService: InactivityService,
    private apiService: ApiService,
    private authService: AuthService
  ) {}

  ngOnInit(): void {
    // Check auth status and initialize inactivity tracking
    this.checkAndInitializeInactivity();

    // Re-check on route changes (e.g., after login)
    this.router.events
      .pipe(
        filter(event => event instanceof NavigationEnd),
        takeUntil(this.destroy$)
      )
      .subscribe(() => {
        this.checkAndInitializeInactivity();
      });

    // Subscribe to auth changes
    this.authService.isLoggedIn$
      .pipe(takeUntil(this.destroy$))
      .subscribe(isLoggedIn => {
        if (!isLoggedIn) {
          // User logged out - stop inactivity service
          this.inactivityService.stop();
        }
      });
  }

  /**
   * Check if user is logged in and initialize inactivity service
   */
  private checkAndInitializeInactivity(): void {
    const token = localStorage.getItem('jwt');

    if (token && !this.inactivityService.isRunning()) {
      // User is logged in but service not running - initialize it
      this.initializeInactivityService();
    }
  }

  /**
   * Initialize the inactivity service with user's role
   */
  private initializeInactivityService(): void {
    this.apiService.PrendiUtente()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response: any) => {
          const roleId = response.user?.role?.id || response.user?.role_id || 3;
          this.inactivityService.initialize(roleId);
        },
        error: (err) => {
          console.error('[App] Error fetching user for inactivity service:', err);
          // Fallback: initialize with default role (Cliente)
          this.inactivityService.initialize(3);
        }
      });
  }

  getRouteAnimationData() {
    return this.router.url;
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }
}