import { Component, OnInit, OnDestroy, Output, EventEmitter } from '@angular/core';
import { Router } from '@angular/router';
import { Subscription } from 'rxjs';
import { trigger, transition, style, animate } from '@angular/animations';
import { NotificationService, Notification } from 'src/app/servizi/notification.service';

interface GroupedNotifications {
  label: string;
  notifications: Notification[];
}

@Component({
  selector: 'app-notifications-modal',
  templateUrl: './notifications-modal.component.html',
  styleUrls: ['./notifications-modal.component.scss'],
  standalone: false,
  animations: [
    trigger('fadeInOut', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('200ms ease-out', style({ opacity: 1 }))
      ]),
      transition(':leave', [
        animate('150ms ease-in', style({ opacity: 0 }))
      ])
    ]),
    trigger('slideIn', [
      transition(':enter', [
        style({ opacity: 0, transform: 'scale(0.95) translateY(-20px)' }),
        animate('250ms ease-out', style({ opacity: 1, transform: 'scale(1) translateY(0)' }))
      ]),
      transition(':leave', [
        animate('150ms ease-in', style({ opacity: 0, transform: 'scale(0.95) translateY(-20px)' }))
      ])
    ])
  ]
})
export class NotificationsModalComponent implements OnInit, OnDestroy {

  @Output() close = new EventEmitter<void>();

  // State
  isOpen: boolean = false;
  isLoading: boolean = false;
  notifications: Notification[] = [];
  filteredNotifications: Notification[] = [];
  groupedNotifications: GroupedNotifications[] = [];
  
  // Filter tabs
  activeFilter: 'all' | 'unread' | 'read' = 'all';
  
  // Counts
  totalCount: number = 0;
  unreadCount: number = 0;
  readCount: number = 0;
  
  // Pagination
  currentPage: number = 1;
  totalPages: number = 1;
  perPage: number = 50;
  hasMore: boolean = false;

  // BackOffice role IDs for navigation
  private readonly BACKOFFICE_ROLE_IDS = [1, 4, 5, 6, 9, 10];
  
  // Subscriptions
  private subscriptions: Subscription[] = [];

  constructor(
    private notificationService: NotificationService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadNotifications();
  }

  ngOnDestroy(): void {
    this.subscriptions.forEach(sub => sub.unsubscribe());
  }

  /**
   * Open the modal
   */
  open(): void {
    this.isOpen = true;
    this.loadNotifications();
    document.body.style.overflow = 'hidden';
  }

  /**
   * Close the modal
   */
  closeModal(): void {
    this.isOpen = false;
    document.body.style.overflow = '';
    this.close.emit();
  }

  /**
   * Close on backdrop click
   */
  onBackdropClick(event: Event): void {
    if ((event.target as HTMLElement).classList.contains('modal-backdrop')) {
      this.closeModal();
    }
  }

  /**
   * Load notifications from API
   */
  loadNotifications(): void {
    this.isLoading = true;
    
    const onlyUnread = this.activeFilter === 'unread';
    
    this.notificationService.getNotifications(this.currentPage, this.perPage, onlyUnread)
      .subscribe({
        next: (response) => {
          this.isLoading = false;
          if (response?.response === 'ok') {
            const body = response.body;
            
            // Handle paginated response
            if (body.notifications && 'data' in body.notifications) {
              this.notifications = body.notifications.data;
              this.totalPages = body.notifications.last_page;
              this.totalCount = body.notifications.total;
            } else {
              this.notifications = body.notifications as Notification[];
              this.totalCount = this.notifications.length;
            }
            
            this.unreadCount = body.unread_count || 0;
            this.readCount = this.totalCount - this.unreadCount;
            
            this.applyFilter();
          }
        },
        error: (error) => {
          this.isLoading = false;
          console.error('Error loading notifications:', error);
        }
      });
  }

  /**
   * Apply current filter and group notifications
   */
  applyFilter(): void {
    switch (this.activeFilter) {
      case 'unread':
        this.filteredNotifications = this.notifications.filter(n => !n.visualizzato);
        break;
      case 'read':
        this.filteredNotifications = this.notifications.filter(n => n.visualizzato);
        break;
      default:
        this.filteredNotifications = [...this.notifications];
    }
    
    this.groupNotificationsByDate();
  }

  /**
   * Change active filter tab
   */
  setFilter(filter: 'all' | 'unread' | 'read'): void {
    this.activeFilter = filter;
    this.currentPage = 1;
    this.loadNotifications();
  }

  /**
   * Group notifications by date
   */
  groupNotificationsByDate(): void {
    const groups: { [key: string]: Notification[] } = {
      'Oggi': [],
      'Ieri': [],
      'Questa settimana': [],
      'Questo mese': [],
      'Più vecchie': []
    };

    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    const weekAgo = new Date(today);
    weekAgo.setDate(weekAgo.getDate() - 7);
    const monthAgo = new Date(today);
    monthAgo.setMonth(monthAgo.getMonth() - 1);

    this.filteredNotifications.forEach(notification => {
      const notifDate = new Date(notification.created_at);
      const notifDay = new Date(notifDate.getFullYear(), notifDate.getMonth(), notifDate.getDate());

      if (notifDay.getTime() === today.getTime()) {
        groups['Oggi'].push(notification);
      } else if (notifDay.getTime() === yesterday.getTime()) {
        groups['Ieri'].push(notification);
      } else if (notifDay > weekAgo) {
        groups['Questa settimana'].push(notification);
      } else if (notifDay > monthAgo) {
        groups['Questo mese'].push(notification);
      } else {
        groups['Più vecchie'].push(notification);
      }
    });

    // Convert to array, filtering out empty groups
    this.groupedNotifications = Object.entries(groups)
      .filter(([_, notifications]) => notifications.length > 0)
      .map(([label, notifications]) => ({ label, notifications }));
  }

  /**
   * Handle notification click
   */
  onNotificationClick(notification: Notification): void {
    // Mark as read if unread
    if (!notification.visualizzato) {
      this.markAsRead(notification);
    }
    
    // Close modal
    this.closeModal();
    
    // Navigate based on entity type and user role
    if (notification.entity_type === 'ticket') {
      if (this.isBackOfficeUser()) {
        this.router.navigate(['/ticket']);
      } else {
        this.router.navigate(['/contratti']);
      }
    } else if (notification.entity_type === 'contract') {
      this.router.navigate(['/contratti']);
    }
  }

  /**
   * Check if current user is BackOffice
   */
  private isBackOfficeUser(): boolean {
    try {
      const roleId = localStorage.getItem('userLogin');
      if (roleId) {
        return this.BACKOFFICE_ROLE_IDS.includes(parseInt(roleId, 10));
      }
    } catch (e) {
      console.error('Error checking user role:', e);
    }
    return false;
  }

  /**
   * Mark single notification as read
   */
  markAsRead(notification: Notification, event?: Event): void {
    if (event) {
      event.stopPropagation();
    }
    
    if (!notification.visualizzato) {
      this.notificationService.markAsRead(notification.id).subscribe({
        next: () => {
          notification.visualizzato = true;
          this.unreadCount = Math.max(0, this.unreadCount - 1);
          this.readCount++;
          this.applyFilter();
        }
      });
    }
  }

  /**
   * Delete single notification
   */
  deleteNotification(notification: Notification, event: Event): void {
    event.stopPropagation();
    
    this.notificationService.deleteNotification(notification.id).subscribe({
      next: () => {
        this.notifications = this.notifications.filter(n => n.id !== notification.id);
        this.totalCount--;
        if (!notification.visualizzato) {
          this.unreadCount = Math.max(0, this.unreadCount - 1);
        } else {
          this.readCount = Math.max(0, this.readCount - 1);
        }
        this.applyFilter();
      }
    });
  }

  /**
   * Mark all notifications as read
   */
  markAllAsRead(): void {
    this.notificationService.markAllAsRead().subscribe({
      next: () => {
        this.notifications.forEach(n => n.visualizzato = true);
        this.readCount = this.totalCount;
        this.unreadCount = 0;
        this.applyFilter();
      }
    });
  }

  /**
   * Delete all read notifications
   */
  deleteAllRead(): void {
    this.notificationService.deleteAllRead().subscribe({
      next: () => {
        this.notifications = this.notifications.filter(n => !n.visualizzato);
        this.totalCount = this.unreadCount;
        this.readCount = 0;
        this.applyFilter();
      }
    });
  }

  /**
   * Load more notifications (pagination)
   */
  loadMore(): void {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
      this.isLoading = true;
      
      this.notificationService.getNotifications(this.currentPage, this.perPage, this.activeFilter === 'unread')
        .subscribe({
          next: (response) => {
            this.isLoading = false;
            if (response?.response === 'ok') {
              const body = response.body;
              if (body.notifications && 'data' in body.notifications) {
                this.notifications = [...this.notifications, ...body.notifications.data];
              }
              this.applyFilter();
            }
          },
          error: () => {
            this.isLoading = false;
          }
        });
    }
  }

  /**
   * Get icon for notification type
   */
  getNotificationIcon(notification: Notification): string {
    return notification.icon || 'notifications';
  }

  /**
   * Get icon color class based on notification type
   */
  getNotificationIconClass(notification: Notification): string {
    const type = notification.type || '';
    
    if (type.includes('new') || type.includes('message')) {
      return 'icon-info';
    } else if (type.includes('assigned') || type.includes('waiting')) {
      return 'icon-warning';
    } else if (type.includes('resolved')) {
      return 'icon-success';
    } else if (type.includes('closed')) {
      return 'icon-secondary';
    } else if (type.includes('contract')) {
      return 'icon-primary';
    }
    
    return 'icon-default';
  }

  /**
   * Format date for display
   */
  formatDate(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const notifDay = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    
    const timeStr = date.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
    
    if (notifDay.getTime() === today.getTime()) {
      return `Oggi alle ${timeStr}`;
    }
    
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    if (notifDay.getTime() === yesterday.getTime()) {
      return `Ieri alle ${timeStr}`;
    }
    
    return date.toLocaleDateString('it-IT', { 
      day: '2-digit', 
      month: '2-digit', 
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
}
