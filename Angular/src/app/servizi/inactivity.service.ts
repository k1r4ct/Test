import { Injectable, NgZone, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { Subject, fromEvent, merge } from 'rxjs';
import { takeUntil, throttleTime } from 'rxjs/operators';
import { environment } from 'src/environments/environment';

/**
 * Silent Inactivity Service
 * 
 * Handles automatic logout after period of inactivity.
 * NO UI - runs completely in background.
 * 
 * Rules:
 * - Timer PAUSED when CRM tab is active (user is viewing)
 * - Timer COUNTING when CRM is in background (other tab/app)
 * - User activity (click/keypress/scroll) resets timer
 * - Timeout values fetched from backend based on role
 */

// Role ID to setting key mapping
const ROLE_TIMEOUT_KEYS: { [roleId: number]: string } = {
  1: 'session_timeout_admin',      // Administrator: 60 min default
  2: 'session_timeout_advisor',    // Advisor (SEU): 20 min default
  3: 'session_timeout_cliente',    // Cliente: 20 min default
  4: 'session_timeout_operatore',  // Operatore Web: 20 min default
  5: 'session_timeout_backoffice', // BackOffice: 60 min default
};

// Default timeouts in seconds (fallback if API fails)
const DEFAULT_TIMEOUTS: { [roleId: number]: number } = {
  1: 3600,  // 60 min
  2: 1200,  // 20 min
  3: 1200,  // 20 min
  4: 1200,  // 20 min
  5: 3600,  // 60 min
};

@Injectable({
  providedIn: 'root'
})
export class InactivityService implements OnDestroy {

  private destroy$ = new Subject<void>();
  private apiUrl = environment.apiUrl;

  // Timer state
  private countdownInterval: any = null;
  private remainingSeconds: number = 0;
  private timeoutSeconds: number = 1200; // Default 20 min
  private isPaused: boolean = true;
  private isInitialized: boolean = false;

  constructor(
    private ngZone: NgZone,
    private http: HttpClient,
    private router: Router
  ) {}

  /**
   * Initialize the service - call this after user login
   * @param roleId - The user's role ID
   */
  initialize(roleId: number): void {
    if (this.isInitialized) {
      return;
    }


    // Set default timeout for role
    this.timeoutSeconds = DEFAULT_TIMEOUTS[roleId] || 1200;
    this.remainingSeconds = this.timeoutSeconds;

    // Fetch actual timeout from backend
    this.fetchTimeoutFromBackend(roleId);

    // Setup event listeners
    this.setupVisibilityTracking();
    this.setupActivityTracking();

    // Start countdown (will be paused if page is visible)
    this.isPaused = document.visibilityState === 'visible';
    this.startCountdown();

    this.isInitialized = true;
  }

  /**
   * Stop the service - call this on logout
   */
  stop(): void {
    this.stopCountdown();
    this.isInitialized = false;
  }

  /**
   * Check if service is running
   */
  isRunning(): boolean {
    return this.isInitialized;
  }

  /**
   * Fetch timeout setting from backend API
   */
  private fetchTimeoutFromBackend(roleId: number): void {
    const settingKey = ROLE_TIMEOUT_KEYS[roleId];

    if (!settingKey) {
      return;
    }

    this.http.get<any>(`${this.apiUrl}log-settings/${settingKey}`)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          if (response.success && response.data?.value) {
            const newTimeout = parseInt(response.data.value, 10);
            if (newTimeout > 0) {
              this.timeoutSeconds = newTimeout;
              this.remainingSeconds = newTimeout;
            }
          }
        },
        error: (err) => {
          console.warn('[Inactivity] Failed to fetch timeout from backend, using default', err);
        }
      });
  }

  /**
   * Setup Page Visibility API tracking
   */
  private setupVisibilityTracking(): void {
    fromEvent(document, 'visibilitychange')
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        const isVisible = document.visibilityState === 'visible';

        if (isVisible) {
          // Page active - pause countdown
          this.isPaused = true;
        } else {
          // Page in background - resume countdown
          this.isPaused = false;
        }
      });

    // Backup: window focus/blur
    fromEvent(window, 'focus')
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        this.isPaused = true;
      });

    fromEvent(window, 'blur')
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        this.isPaused = false;
      });
  }

  /**
   * Setup user activity tracking (click, keypress, scroll, touch)
   */
  private setupActivityTracking(): void {
    const activityEvents$ = merge(
      fromEvent(document, 'click'),
      fromEvent(document, 'mousedown'),
      fromEvent(document, 'keypress'),
      fromEvent(document, 'touchstart'),
      fromEvent(document, 'scroll', { passive: true })
    ).pipe(
      throttleTime(1000), // Max 1 event per second
      takeUntil(this.destroy$)
    );

    activityEvents$.subscribe(() => {
      this.resetTimer();
    });
  }

  /**
   * Reset the inactivity timer (called on user activity)
   */
  private resetTimer(): void {
    this.remainingSeconds = this.timeoutSeconds;
  }

  /**
   * Start the countdown interval
   */
  private startCountdown(): void {
    this.stopCountdown();

    // Run outside Angular zone for performance
    this.ngZone.runOutsideAngular(() => {
      this.countdownInterval = setInterval(() => {
        // Only decrement if NOT paused
        if (!this.isPaused) {
          this.remainingSeconds--;

          if (this.remainingSeconds <= 0) {
            // Time's up - logout
            this.ngZone.run(() => {
              this.handleTimeout();
            });
          }
        }
      }, 1000);
    });
  }

  /**
   * Stop the countdown interval
   */
  private stopCountdown(): void {
    if (this.countdownInterval) {
      clearInterval(this.countdownInterval);
      this.countdownInterval = null;
    }
  }

  /**
   * Handle session timeout - silent logout
   */
  private handleTimeout(): void {
    console.log('[Inactivity] Session expired - logging out');
    this.stop();

    // Clear session data
    localStorage.removeItem('jwt');
    localStorage.removeItem('session_expired');
    localStorage.removeItem('userLogin');

    // Navigate to login
    this.router.navigate(['/login']);
  }

  ngOnDestroy(): void {
    this.stop();
    this.destroy$.next();
    this.destroy$.complete();
  }
}