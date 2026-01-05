import { Component, OnInit, Input, Output, EventEmitter, HostListener } from '@angular/core';
import { Location } from '@angular/common';
import { Router } from '@angular/router';
import { ROUTES } from '../../sidebar/sidebar.component';
import { AuthService } from 'src/app/servizi/auth.service';
import { ThemeService } from 'src/app/servizi/theme.service';

@Component({
  moduleId: module.id,
  selector: 'app-navbar',
  templateUrl: 'navbar.component.html',
  styleUrls: ['./navbar.component.scss'],
  standalone: false
})
export class NavbarComponent implements OnInit {
  
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
  
  location: Location;

  constructor(
    location: Location,
    private router: Router,
    private authService: AuthService,
    private themeService: ThemeService
  ) {
    this.location = location;
  }

  ngOnInit(): void {
    this.listTitles = ROUTES.filter((listTitle: any) => listTitle);
    
    // Subscribe to theme changes from ThemeService
    this.themeService.isDarkMode$.subscribe(isDark => {
      this.isDarkMode = isDark;
    });
    
    // Load notifications
    this.loadNotifications();
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

  /**
   * Open notifications panel
   */
  openNotifications(): void {
    console.log('Open notifications');
    // TODO: Implement notifications panel
  }

  /**
   * Toggle user menu dropdown
   */
  toggleUserMenu(event?: Event): void {
    if (event) {
      event.stopPropagation();
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
   * Open profile settings modal - emits event to parent
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
    this.authService.logOut();
    this.router.navigate(['/login']);
  }

  /**
   * Handle click outside to close dropdown
   */
  @HostListener('document:click', ['$event'])
  onDocumentClick(event: Event): void {
    const target = event.target as HTMLElement;
    const userMenuContainer = target.closest('.user-menu-container');
    const isBackdrop = target.classList.contains('dropdown-backdrop');
    
    if ((!userMenuContainer || isBackdrop) && this.userMenuOpen) {
      this.closeUserMenu();
    }
  }

  /**
   * Handle Escape key to close dropdown
   */
  @HostListener('document:keydown.escape', ['$event'])
  onEscapeKey(event: KeyboardEvent): void {
    if (this.userMenuOpen) {
      this.closeUserMenu();
    }
  }

  /**
   * Load notification count
   */
  private loadNotifications(): void {
    this.notificationCount = 0;
  }
}