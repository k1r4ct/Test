import { Component, OnInit, OnDestroy, Input, Output, EventEmitter, HostListener, ViewChild } from '@angular/core';
import { Location } from '@angular/common';
import { Router } from '@angular/router';
import { Subscription } from 'rxjs';
import { ROUTES } from '../../sidebar/sidebar.component';
import { AuthService } from 'src/app/servizi/auth.service';
import { ThemeService } from 'src/app/servizi/theme.service';
import { NotificationService, Notification } from 'src/app/servizi/notification.service';
import { NotificationsModalComponent } from '../notifications-modal/notifications-modal.component';

@Component({
  moduleId: module.id,
  selector: 'app-navbar',
  templateUrl: 'navbar.component.html',
  styleUrls: ['./navbar.component.scss'],
  standalone: false
})
export class NavbarComponent implements OnInit, OnDestroy {
  
  // Reference to notifications modal
  @ViewChild('notificationsModal') notificationsModal!: NotificationsModalComponent;
  
  // BackOffice role IDs for navigation logic
  private readonly BACKOFFICE_ROLE_IDS = [1, 4, 5, 6, 9, 10];
  
  // Inputs from parent (admin-layout)
  @Input() userName: string = '';
  @Input() userRole: string = '';
  @Input() userEmail: string = '';
  
  // Output events to parent
  @Output() toggleMobileMenu = new EventEmitter<void>();
  @Output() openProfileModal = new EventEmitter<void>();
  
  // Component state
  private listTitles: any[] = [];
  isDarkMode: boolean = false;
  notificationCount: number = 0;
  
  // User dropdown state
  userMenuOpen: boolean = false;
  
  // Notification dropdown state
  notificationMenuOpen: boolean = false;
  notifications: Notification[] = [];
  notificationsLoading: boolean = false;
  
  // Notifications modal state
  showNotificationsModal: boolean = false;
  
  // Subscriptions
  private subscriptions: Subscription[] = [];
  
  location: Location;

  constructor(
    location: Location,
    private router: Router,
    private authService: AuthService,
    private themeService: ThemeService,
    private notificationService: NotificationService
  ) {
    this.location = location;
  }

  ngOnInit(): void {
    this.listTitles = ROUTES.filter((listTitle: any) => listTitle);
    
    // Subscribe to theme changes from ThemeService
    this.subscriptions.push(
      this.themeService.isDarkMode$.subscribe(isDark => {
        this.isDarkMode = isDark;
      })
    );
    
    // Subscribe to notification count
    this.subscriptions.push(
      this.notificationService.unreadCount$.subscribe(count => {
        this.notificationCount = count;
      })
    );
    
    // Subscribe to notifications list
    this.subscriptions.push(
      this.notificationService.notifications$.subscribe(notifications => {
        this.notifications = notifications;
      })
    );
    
    // Subscribe to loading state
    this.subscriptions.push(
      this.notificationService.loading$.subscribe(loading => {
        this.notificationsLoading = loading;
      })
    );
    
    // Start polling for notifications
    this.notificationService.startPolling();
  }

  ngOnDestroy(): void {
    // Cleanup subscriptions
    this.subscriptions.forEach(sub => sub.unsubscribe());
    
    // Stop polling
    this.notificationService.stopPolling();
  }

  /**
   * Get user initials for avatar
   */
  get userInitials(): string {
    if (!this.userName) return 'U';
    
    const names = this.userName.trim().split(' ');
    if (names.length >= 2) {
      return (names[0][0] + names[names.length - 1][0]).toUpperCase();
    }
    return names[0].substring(0, 2).toUpperCase();
  }

  /**
   * Get current page title from route
   */
  getTitle(): string {
    let titlee = this.location.prepareExternalUrl(this.location.path());
    
    if (titlee.charAt(0) === '#') {
      titlee = titlee.slice(1);
    }
    
    for (const item of this.listTitles) {
      if (item.path === titlee) {
        return item.title;
      }
    }
    
    return 'Dashboard';
  }

  /**
   * Emit event to toggle mobile menu (handled by admin-layout)
   */
  onToggleMobileMenu(): void {
    this.toggleMobileMenu.emit();
  }

  /**
   * Toggle dark/light theme using ThemeService
   */
  toggleTheme(): void {
    this.themeService.toggleTheme();
  }

  // ==================== NOTIFICATION METHODS ====================

  /**
   * Check if current user is BackOffice
   * Reads role_id directly from 'userLogin' localStorage key
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
   * Toggle notifications dropdown
   */
  toggleNotifications(event?: Event): void {
    if (event) {
      event.stopPropagation();
    }
    
    // Close user menu if open
    if (this.userMenuOpen) {
      this.userMenuOpen = false;
    }
    
    this.notificationMenuOpen = !this.notificationMenuOpen;
    
    // Refresh notifications when opening
    if (this.notificationMenuOpen) {
      this.notificationService.fetchRecentNotifications(15);
    }
  }

  /**
   * Close notifications dropdown
   */
  closeNotifications(): void {
    this.notificationMenuOpen = false;
  }

  /**
   * Open notifications modal (all notifications)
   */
  openAllNotifications(): void {
    this.closeNotifications();
    this.showNotificationsModal = true;
    
    // Use setTimeout to ensure ViewChild is available
    setTimeout(() => {
      if (this.notificationsModal) {
        this.notificationsModal.open();
      }
    });
  }

  /**
   * Close notifications modal
   */
  onNotificationsModalClose(): void {
    this.showNotificationsModal = false;
    // Refresh the dropdown count
    this.notificationService.fetchUnreadCount();
  }

  /**
   * Handle notification click - navigate to entity
   */
  onNotificationClick(notification: Notification, event?: Event): void {
    if (event) {
      event.stopPropagation();
    }
    
    // Mark as read
    if (!notification.visualizzato) {
      this.notificationService.markAsRead(notification.id).subscribe();
    }
    
    // Close dropdown
    this.closeNotifications();
    
    // Navigate based on entity type AND user role
    if (notification.entity_type === 'ticket') {
      if (this.isBackOfficeUser()) {
        // BackOffice → ticket management page
        this.router.navigate(['/ticket']);
      } else {
        // SEU → contracts page
        this.router.navigate(['/contratti']);
      }
    } else if (notification.entity_type === 'contract') {
      // Contract notifications always go to contracts page
      this.router.navigate(['/contratti']);
    }
  }

  /**
   * Mark single notification as read
   */
  markNotificationAsRead(notification: Notification, event: Event): void {
    event.stopPropagation();
    
    if (!notification.visualizzato) {
      this.notificationService.markAsRead(notification.id).subscribe();
    }
  }

  /**
   * Mark all notifications as read
   */
  markAllAsRead(event: Event): void {
    event.stopPropagation();
    this.notificationService.markAllAsRead().subscribe();
  }

  /**
   * Delete a notification
   */
  deleteNotification(notification: Notification, event: Event): void {
    event.stopPropagation();
    this.notificationService.deleteNotification(notification.id).subscribe();
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

  // ==================== USER MENU METHODS ====================

  /**
   * Toggle user menu dropdown
   */
  toggleUserMenu(event?: Event): void {
    if (event) {
      event.stopPropagation();
    }
    
    // Close notification menu if open
    if (this.notificationMenuOpen) {
      this.notificationMenuOpen = false;
    }
    
    this.userMenuOpen = !this.userMenuOpen;
  }

  /**
   * Close user menu dropdown
   */
  closeUserMenu(): void {
    this.userMenuOpen = false;
  }

  /**
   * Open profile settings
   */
  openProfileSettings(): void {
    this.closeUserMenu();
    this.openProfileModal.emit();
  }

  /**
   * Logout user
   */
  logout(): void {
    this.closeUserMenu();
    this.notificationService.stopPolling();
    this.authService.logOut();
    this.router.navigate(['/login']);
  }

  /**
   * Close all dropdowns when clicking outside
   */
  @HostListener('document:click', ['$event'])
  onDocumentClick(event: Event): void {
    const target = event.target as HTMLElement;
    
    // Check if click is outside notification menu
    if (this.notificationMenuOpen && !target.closest('.notification-container')) {
      this.notificationMenuOpen = false;
    }
    
    // Check if click is outside user menu
    if (this.userMenuOpen && !target.closest('.user-menu-container')) {
      this.userMenuOpen = false;
    }
  }

  /**
   * Close dropdowns on escape key
   */
  @HostListener('document:keydown.escape')
  onEscapeKey(): void {
    this.notificationMenuOpen = false;
    this.userMenuOpen = false;
  }
}
