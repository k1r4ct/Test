import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { BehaviorSubject, Observable, interval, Subscription } from 'rxjs';
import { map, tap, catchError } from 'rxjs/operators';
import { environment } from 'src/environments/environment';

// Notification interface matching backend response
export interface Notification {
  id: number;
  from_user_id: number;
  from_user_name: string;
  reparto: string;
  notifica: string;
  notifica_html: string;
  visualizzato: boolean;
  type: string | null;
  type_label: string;
  icon: string;
  entity_type: string | null;
  entity_id: number | null;
  action_url: string | null;
  created_at: string;
  created_at_human: string;
  updated_at: string;
  entity?: any;
}

export interface NotificationResponse {
  response: string;
  status: string;
  body: {
    notifications: Notification[] | PaginatedNotifications;
    unread_count: number;
  };
}

export interface PaginatedNotifications {
  data: Notification[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

@Injectable({
  providedIn: 'root'
})
export class NotificationService {
  
  private apiUrl = environment.apiUrl;
  
  // BehaviorSubjects for reactive state
  private unreadCountSubject = new BehaviorSubject<number>(0);
  private notificationsSubject = new BehaviorSubject<Notification[]>([]);
  private loadingSubject = new BehaviorSubject<boolean>(false);
  
  // Public observables
  public unreadCount$ = this.unreadCountSubject.asObservable();
  public notifications$ = this.notificationsSubject.asObservable();
  public loading$ = this.loadingSubject.asObservable();
  
  // Polling subscription
  private pollingSubscription: Subscription | null = null;
  private readonly POLLING_INTERVAL = 30000; // 30 seconds

  constructor(private http: HttpClient) {}

  /**
   * Start polling for new notifications
   */
  startPolling(): void {
    // Initial fetch
    this.fetchUnreadCount();
    this.fetchRecentNotifications();
    
    // Stop any existing polling
    this.stopPolling();
    
    // Start new polling interval
    this.pollingSubscription = interval(this.POLLING_INTERVAL).subscribe(() => {
      this.fetchUnreadCount();
    });
  }

  /**
   * Stop polling
   */
  stopPolling(): void {
    if (this.pollingSubscription) {
      this.pollingSubscription.unsubscribe();
      this.pollingSubscription = null;
    }
  }

  /**
   * Fetch unread notification count (for badge)
   */
  fetchUnreadCount(): void {
    this.http.get<any>(`${this.apiUrl}notifications/unread-count`)
      .pipe(
        catchError(error => {
          console.error('Error fetching unread count:', error);
          return [];
        })
      )
      .subscribe(response => {
        if (response?.response === 'ok' && response?.body?.count !== undefined) {
          this.unreadCountSubject.next(response.body.count);
        }
      });
  }

  /**
   * Fetch recent notifications (for dropdown)
   */
  fetchRecentNotifications(limit: number = 10): void {
    this.loadingSubject.next(true);
    
    const params = new HttpParams().set('limit', limit.toString());
    
    this.http.get<NotificationResponse>(`${this.apiUrl}notifications/recent`, { params })
      .pipe(
        catchError(error => {
          console.error('Error fetching recent notifications:', error);
          this.loadingSubject.next(false);
          return [];
        })
      )
      .subscribe(response => {
        this.loadingSubject.next(false);
        if (response?.response === 'ok') {
          const notifications = response.body.notifications as Notification[];
          this.notificationsSubject.next(notifications);
          this.unreadCountSubject.next(response.body.unread_count);
        }
      });
  }

  /**
   * Get paginated notifications (for full page view)
   */
  getNotifications(page: number = 1, perPage: number = 20, onlyUnread: boolean = false): Observable<NotificationResponse> {
    let params = new HttpParams()
      .set('page', page.toString())
      .set('per_page', perPage.toString());
    
    if (onlyUnread) {
      params = params.set('only_unread', 'true');
    }
    
    return this.http.get<NotificationResponse>(`${this.apiUrl}notifications`, { params });
  }

  /**
   * Get single notification detail
   */
  getNotification(id: number): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}notifications/${id}`);
  }

  /**
   * Mark single notification as read
   */
  markAsRead(id: number): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}notifications/${id}/read`, {})
      .pipe(
        tap(response => {
          if (response?.response === 'ok') {
            // Update local state
            const current = this.notificationsSubject.value;
            const updated = current.map(n => 
              n.id === id ? { ...n, visualizzato: true } : n
            );
            this.notificationsSubject.next(updated);
            
            // Decrement unread count
            const currentCount = this.unreadCountSubject.value;
            if (currentCount > 0) {
              this.unreadCountSubject.next(currentCount - 1);
            }
          }
        })
      );
  }

  /**
   * Mark all notifications as read
   */
  markAllAsRead(): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}notifications/read-all`, {})
      .pipe(
        tap(response => {
          if (response?.response === 'ok') {
            // Update local state - mark all as read
            const current = this.notificationsSubject.value;
            const updated = current.map(n => ({ ...n, visualizzato: true }));
            this.notificationsSubject.next(updated);
            
            // Reset unread count
            this.unreadCountSubject.next(0);
          }
        })
      );
  }

  /**
   * Delete a notification
   */
  deleteNotification(id: number): Observable<any> {
    return this.http.delete<any>(`${this.apiUrl}notifications/${id}`)
      .pipe(
        tap(response => {
          if (response?.response === 'ok') {
            // Remove from local state
            const current = this.notificationsSubject.value;
            const updated = current.filter(n => n.id !== id);
            this.notificationsSubject.next(updated);
          }
        })
      );
  }

  /**
   * Delete all read notifications
   */
  deleteAllRead(): Observable<any> {
    return this.http.delete<any>(`${this.apiUrl}notifications/read/all`)
      .pipe(
        tap(response => {
          if (response?.response === 'ok') {
            // Remove read notifications from local state
            const current = this.notificationsSubject.value;
            const updated = current.filter(n => !n.visualizzato);
            this.notificationsSubject.next(updated);
          }
        })
      );
  }

  /**
   * Get current unread count value
   */
  get currentUnreadCount(): number {
    return this.unreadCountSubject.value;
  }

  /**
   * Get current notifications value
   */
  get currentNotifications(): Notification[] {
    return this.notificationsSubject.value;
  }

  /**
   * Force refresh notifications
   */
  refresh(): void {
    this.fetchUnreadCount();
    this.fetchRecentNotifications();
  }
}
