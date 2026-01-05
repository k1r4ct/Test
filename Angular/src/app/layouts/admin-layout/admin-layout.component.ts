import { trigger, transition, style, animate } from '@angular/animations';
import { AfterViewInit, Component, ElementRef, ViewChild, OnInit, OnDestroy, HostListener } from '@angular/core';
import { Router, NavigationStart } from '@angular/router';
import { MatDialog } from '@angular/material/dialog';
import { NuovocontrattoComponent } from 'src/app/pages/nuovocontratto/nuovocontratto.component';
import { LayoutScrollService } from 'src/app/servizi/layout-scroll.service';
import { ApiService } from 'src/app/servizi/api.service';
import { ProfileSettingsModalComponent } from 'src/app/shared/components/profile-settings-modal/profile-settings-modal.component';

@Component({
  selector: 'app-admin-layout',
  templateUrl: './admin-layout.component.html',
  styleUrls: ['./admin-layout.component.scss'],
  animations: [
    trigger("pageTransition", [
      transition(":enter", [
        style({ opacity: 0, transform: "scale(0.95)" }),
        animate("400ms ease-out", style({ opacity: 1, transform: "scale(1)" }))
      ]),
      transition(":leave", [
        animate("300ms ease-in", style({ opacity: 0, transform: "scale(0.95)" }))
      ])
    ])
  ],
  standalone: false
})
export class AdminLayoutComponent implements OnInit, AfterViewInit, OnDestroy {

  // Existing functionality
  @ViewChild(NuovocontrattoComponent) NewContratto!: NuovocontrattoComponent;
  targetElement: any;
  state: any;

  // Sidebar state
  sidebarExpanded = false;
  mobileMenuOpen = false;

  // User info for navbar
  userName: string = '';
  userRole: string = '';
  userEmail: string = '';
  userId: number = 0;

  constructor(
    private router: Router,
    private srvScroll: LayoutScrollService,
    private apiService: ApiService,
    private dialog: MatDialog
  ) {
    // Navigation listener
    this.router.events.subscribe(event => {
      if (event instanceof NavigationStart) {
        this.state = 'out';
      }
    });
  }

  ngOnInit(): void {
    // Load user info
    this.loadUserInfo();

    // Restore sidebar state from localStorage (desktop only)
    if (window.innerWidth > 991) {
      const savedState = localStorage.getItem('sidebarExpanded');
      this.sidebarExpanded = savedState === 'true';
    }
  }

  ngAfterViewInit(): void {
    // Existing scroll functionality
    this.srvScroll.scrollTrigger$.subscribe(() => {
      this.targetElement = document.getElementById('finepagina');
      if (this.targetElement) {
        this.targetElement.scrollIntoView({
          behavior: 'smooth',
          block: 'end'
        });
      }
    });
  }

  ngOnDestroy(): void {
    // Cleanup if needed
  }

  /**
   * Load user information from API
   */
  loadUserInfo(): void {
    this.apiService.PrendiUtente().subscribe({
      next: (response: any) => {
        if (response?.user) {
          this.userId = response.user.id;
          this.userName = response.user.name || '';
          this.userRole = response.user.role?.descrizione || '';
          this.userEmail = response.user.email || '';
        }
      },
      error: (err) => {
        console.error('Error loading user info:', err);
      }
    });
  }

  /**
   * Toggle sidebar expanded/collapsed state (desktop)
   */
  toggleSidebar(): void {
    this.sidebarExpanded = !this.sidebarExpanded;
    localStorage.setItem('sidebarExpanded', String(this.sidebarExpanded));
  }

  /**
   * Toggle mobile menu
   */
  toggleMobileMenu(): void {
    this.mobileMenuOpen = !this.mobileMenuOpen;

    if (this.mobileMenuOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
  }

  /**
   * Close mobile menu
   */
  closeMobileMenu(): void {
    this.mobileMenuOpen = false;
    document.body.style.overflow = '';
  }

  /**
   * Open profile settings modal
   */
  openProfileModal(): void {
    const dialogRef = this.dialog.open(ProfileSettingsModalComponent, {
      width: '700px',
      maxWidth: '95vw',
      maxHeight: '90vh',
      panelClass: 'profile-settings-dialog',
      autoFocus: false,
      disableClose: false,
      data: {
        userId: this.userId,
        userName: this.userName,
        userEmail: this.userEmail,
        userRole: this.userRole
      }
    });

    // Handle dialog close result
    dialogRef.afterClosed().subscribe(result => {
      if (result?.updated) {
        // Update local user info with new data
        if (result.userName) {
          this.userName = result.userName;
        }
        if (result.userEmail) {
          this.userEmail = result.userEmail;
        }
      }
    });
  }

  /**
   * Handle window resize
   */
  @HostListener('window:resize', ['$event'])
  onResize(event: Event): void {
    if (window.innerWidth > 991) {
      this.closeMobileMenu();
    }
  }

  /**
   * Handle escape key to close mobile menu
   */
  @HostListener('document:keydown.escape', ['$event'])
  onEscapeKey(event: KeyboardEvent): void {
    if (this.mobileMenuOpen) {
      this.closeMobileMenu();
    }
  }
}
