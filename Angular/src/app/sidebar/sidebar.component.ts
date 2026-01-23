import { Component, OnInit, Output, EventEmitter, Input } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../servizi/auth.service';
import { ApiService } from 'src/app/servizi/api.service';
import { ToastrService } from 'ngx-toastr';
import { ContractServiceStatus } from '../servizi/contract-status-guard.service';
import { 
  MatSnackBar,
  MatSnackBarHorizontalPosition,
  MatSnackBarVerticalPosition 
} from '@angular/material/snack-bar';

export interface RouteInfo {
  path: string;
  title: string;
  icon: string;           // Legacy nc-icon (kept for compatibility)
  materialIcon: string;   // Material Icons Round
  class: string;
}

// Legacy export - used by navbar.component.ts
export const ROUTES: RouteInfo[] = [
  { path: '/dashboard', title: 'Dashboard', icon: 'nc-chart-bar-32', materialIcon: 'dashboard', class: '' },
  { path: '/user', title: 'Dashboard Personale', icon: 'nc-single-02', materialIcon: 'person', class: '' },
  { path: '/gestionedomande', title: 'Gestione Domande', icon: 'nc-bullet-list-67', materialIcon: 'quiz', class: '' },
  { path: '/leads', title: 'Leads', icon: 'nc-send', materialIcon: 'trending_up', class: '' },
  { path: '/clienti', title: 'Clienti', icon: 'nc-vector', materialIcon: 'groups', class: '' },
  { path: '/contratti', title: 'Contratti', icon: 'nc-paper', materialIcon: 'description', class: '' },
  { path: '/table', title: 'Gestione Prodotti', icon: 'nc-tile-56', materialIcon: 'inventory_2', class: '' },
  { path: '/utenti', title: 'Gestione Utenti', icon: 'nc-circle-10', materialIcon: 'manage_accounts', class: '' },
];

export const ROUTES_ADMIN: RouteInfo[] = [
  { path: '/dashboard', title: 'Dashboard', icon: 'nc-chart-bar-32', materialIcon: 'dashboard', class: '' },
  { path: '/gestionedomande', title: 'Gestione Domande', icon: 'nc-bullet-list-67', materialIcon: 'quiz', class: '' },
  { path: '/leads', title: 'Leads', icon: 'nc-send', materialIcon: 'trending_up', class: '' },
  { path: '/clienti', title: 'Clienti', icon: 'nc-vector', materialIcon: 'groups', class: '' },
  { path: '/contratti', title: 'Contratti', icon: 'nc-paper', materialIcon: 'description', class: '' },
  { path: '/table', title: 'Gestione Prodotti', icon: 'nc-tile-56', materialIcon: 'inventory_2', class: '' },
  { path: '/macroprodotti', title: 'Gestione MacroProdotti', icon: 'nc-tile-56', materialIcon: 'category', class: '' },
  { path: '/utenti', title: 'Gestione Utenti', icon: 'nc-circle-10', materialIcon: 'manage_accounts', class: '' },
  { path: '/ecommerce', title: 'E-commerce', icon: 'nc-shop', materialIcon: 'store', class: '' },
  { path: '/ticket', title: 'Gestione Ticket', icon: 'nc-send', materialIcon: 'support_agent', class: '' },
  { path: '/logs', title: 'Gestione Log', icon: 'nc-paper', materialIcon: 'article', class: '' },
];

export const ROUTES_BKOFF: RouteInfo[] = [
  { path: '/dashboard', title: 'Dashboard', icon: 'nc-chart-bar-32', materialIcon: 'dashboard', class: '' },
  { path: '/clienti', title: 'Clienti', icon: 'nc-vector', materialIcon: 'groups', class: '' },
  { path: '/contratti', title: 'Contratti', icon: 'nc-paper', materialIcon: 'description', class: '' },
  { path: '/table', title: 'Gestione Prodotti', icon: 'nc-tile-56', materialIcon: 'inventory_2', class: '' },
  { path: '/ticket', title: 'Gestione Ticket', icon: 'nc-send', materialIcon: 'support_agent', class: '' },
];

export const ROUTES_ADVISOR: RouteInfo[] = [
  { path: '/dashboard', title: 'Dashboard', icon: 'nc-chart-bar-32', materialIcon: 'dashboard', class: '' },
  { path: '/leads', title: 'Leads', icon: 'nc-send', materialIcon: 'trending_up', class: '' },
  { path: '/clienti', title: 'Clienti', icon: 'nc-vector', materialIcon: 'groups', class: '' },
  { path: '/contratti', title: 'Contratti', icon: 'nc-paper', materialIcon: 'description', class: '' },
  { path: '/table', title: 'Gestione Prodotti', icon: 'nc-tile-56', materialIcon: 'inventory_2', class: '' },
];

export const ROUTES_CLI: RouteInfo[] = [
  { path: '/user', title: 'Dashboard Personale', icon: 'nc-single-02', materialIcon: 'person', class: '' },
  { path: '/leads', title: 'Amici Invitati', icon: 'nc-send', materialIcon: 'group_add', class: '' },
  { path: '/ecommerce', title: 'E-commerce', icon: 'nc-shop', materialIcon: 'store', class: '' },
  { path: '/schedapr', title: 'Scheda Personale', icon: 'nc-single-copy-04', materialIcon: 'badge', class: '' },
];

@Component({
  moduleId: module.id,
  selector: 'app-sidebar',
  templateUrl: 'sidebar.component.html',
  styleUrls: ['sidebar.component.scss'],
  standalone: false
})
export class SidebarComponent implements OnInit {
  
  // Input to know if sidebar is expanded (for tooltip disable)
  @Input() sidebarExpanded: boolean = false;
  
  // Event emitter to close mobile menu when navigating
  @Output() closeMobile = new EventEmitter<void>();
  
  public menuItems: RouteInfo[] = [];
  public idRole: number = 0;
  public enable: boolean = true;
  
  horizontalPosition: MatSnackBarHorizontalPosition = 'center';
  verticalPosition: MatSnackBarVerticalPosition = 'top';

  constructor(
    private authService: AuthService,
    private apiService: ApiService,
    private toastr: ToastrService,
    private contractService: ContractServiceStatus,
    private snackbar: MatSnackBar,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadUserMenu();
    this.subscribeToContractStatus();
  }

  /**
   * Load menu items based on user role
   */
  private loadUserMenu(): void {
    this.apiService.PrendiUtente().subscribe({
      next: (response: any) => {
        this.idRole = response.user.role.id;
        this.menuItems = this.getMenuByRole(this.idRole);
      },
      error: (err) => {
        console.error('Error loading user menu:', err);
      }
    });
  }

  /**
   * Get menu items based on role ID
   */
  private getMenuByRole(roleId: number): RouteInfo[] {
    const menuMap: { [key: number]: RouteInfo[] } = {
      1: ROUTES_ADMIN,    // Administrator
      2: ROUTES_ADVISOR,  // Advisor
      3: ROUTES_CLI,      // Cliente
      4: ROUTES_ADVISOR,  // Operatore web
      5: ROUTES_BKOFF,    // BackOffice
    };
    
    return menuMap[roleId] || ROUTES_CLI;
  }

  /**
   * Subscribe to contract status for enabling/disabling menu
   */
  private subscribeToContractStatus(): void {
    this.contractService.contrattoSalvato$.subscribe(salvato => {
      this.enable = salvato;
    });
  }

  /**
   * Handle navigation click - emit event to close mobile menu
   */
  onNavItemClick(): void {
    this.closeMobile.emit();
  }

  /**
   * Show snackbar when menu is disabled
   */
  opensnackbar(): void {
    this.snackbar.open('Concludere prima il contratto.', 'Chiudi', {
      duration: 5000,
      horizontalPosition: this.horizontalPosition,
      verticalPosition: this.verticalPosition,
    });
  }

  /**
   * Logout user and navigate to login page
   */
  logout(): void {
    this.authService.logOut();
    this.router.navigate(['/login']);
    this.closeMobile.emit();
  }
}