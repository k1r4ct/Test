import { Component, DoCheck, OnChanges, OnInit, SimpleChanges } from '@angular/core';
import { AuthService } from '../servizi/auth.service';
import { ApiService } from 'src/app/servizi/api.service';
import { ToastrService } from 'ngx-toastr';
import { ContractServiceStatus } from '../servizi/contract-status-guard.service';
import { MatSnackBar,MatSnackBarHorizontalPosition,
  MatSnackBarVerticalPosition } from '@angular/material/snack-bar';
export interface RouteInfo {
  path: string;
  title: string;
  icon: string;
  class: string;
}

export const ROUTES: RouteInfo[] = [
  { path: '/dashboard', title: 'Dashboard', icon: 'nc-chart-bar-32', class: '' },
  { path: '/user', title: 'Dashboard Personale', icon: 'nc-single-02', class: '' },
  { path: '/gestionedomande', title: 'Gestione Domande', icon: 'nc-bullet-list-67', class: '' },
  { path: '/leads', title: 'Leads', icon: 'nc-send', class: '' },
  { path: '/clienti', title: 'Clienti', icon: 'nc-vector', class: '' },
  { path: '/contratti', title: 'Contratti', icon: 'nc-paper', class: '' },
  { path: '/table', title: 'Gestione Prodotti', icon: 'nc-tile-56', class: '' },
  { path: '/utenti', title: 'Gestione Utenti', icon: 'nc-circle-10', class: '' },

];

export const ROUTES_ADMIN: RouteInfo[] = [
  { path: '/dashboard', title: 'Dashboard', icon: 'nc-chart-bar-32', class: '' },
  { path: '/gestionedomande', title: 'Gestione Domande', icon: 'nc-bullet-list-67', class: '' },
  { path: '/leads', title: 'Leads', icon: 'nc-send', class: '' },
  { path: '/clienti', title: 'Clienti', icon: 'nc-vector', class: '' },
  { path: '/contratti', title: 'Contratti', icon: 'nc-paper', class: '' },
  { path: '/table', title: 'Gestione Prodotti', icon: 'nc-tile-56', class: '' },
  { path: '/macroprodotti', title: 'Gestione MacroProdotti', icon: 'nc-tile-56', class: '' },
  { path: '/utenti', title: 'Gestione Utenti', icon: 'nc-circle-10', class: '' },
  { path: '/ticket', title: 'Gestione Ticket', icon: 'nc-circle-10', class: '' },
];

export const ROUTES_BKOFF: RouteInfo[] = [
  { path: '/dashboard', title: 'Dashboard', icon: 'nc-chart-bar-32', class: '' },
  { path: '/clienti', title: 'Clienti', icon: 'nc-vector', class: '' },
  { path: '/contratti', title: 'Contratti', icon: 'nc-paper', class: '' },
  { path: '/table', title: 'Gestione Prodotti', icon: 'nc-tile-56', class: '' },
];

export const ROUTES_ADVISOR: RouteInfo[] = [
  { path: '/dashboard', title: 'Dashboard', icon: 'nc-chart-bar-32', class: '' },
  { path: '/leads', title: 'Leads', icon: 'nc-send', class: '' },
  { path: '/clienti', title: 'Clienti', icon: 'nc-vector', class: '' },
  { path: '/contratti', title: 'Contratti', icon: 'nc-paper', class: '' },
  { path: '/table', title: 'Gestione Prodotti', icon: 'nc-tile-56', class: '' },
];

export const ROUTES_CLI: RouteInfo[] = [
  { path: '/user', title: 'Dashboard Personale', icon: 'nc-single-02', class: '' },
  { path: '/leads', title: 'Amici Invitati', icon: 'nc-send', class: '' },
  { path: '/schedapr', title: 'Scheda Personale', icon: 'nc-single-copy-04', class: '' },
];


@Component({
    moduleId: module.id,
    selector: 'app-sidebar',
    templateUrl: 'sidebar.component.html',
    standalone: false
})
export class SidebarComponent implements OnInit {
  public menuItems!: any[];
  public idRole: number = 0;
  enable=true;
  horizontalPosition: MatSnackBarHorizontalPosition = 'center';
  verticalPosition: MatSnackBarVerticalPosition = 'top';
  titleLead:string="Leads";
  constructor(
    private authService: AuthService,
    private ApiService: ApiService,
    private toastr: ToastrService,
    private contractService:ContractServiceStatus,
    private snackbar:MatSnackBar,
  ) {}

  ngOnInit() {
    this.ApiService.PrendiUtente().subscribe((Utente: any) => {
      //console.log(Utente.user.qualification.descrizione);
      //console.log(Utente.user.role.id);
      this.idRole = Utente.user.role.id;

      switch (this.idRole) {

        case 1:
          //console.log(this.idRole);
          //console.log("menu Administrator");
          this.menuItems = ROUTES_ADMIN.filter((menuItem) => menuItem);
          //console.log(this.menuItems);

        break;
        case 2:
          //console.log("menu Advisor");
          this.menuItems = ROUTES_ADVISOR.filter((menuItem) => menuItem);
        break;
        case 3:
          //console.log("menu Cliente");
          this.titleLead="Amici Invitati";
          this.menuItems = ROUTES_CLI.filter((menuItem) => menuItem);
        break;
        case 4:
          //console.log("menu Operatore web");
          this.menuItems = ROUTES_ADVISOR.filter((menuItem) => menuItem);
        break;
        case 5:
          //console.log("menu BackOffice");
          this.menuItems = ROUTES_BKOFF.filter((menuItem) => menuItem);
        break;
      }
      this.contractService.contrattoSalvato$.subscribe(salvato => {
        //console.log(salvato);

        this.enable = salvato;

      });

      //this.menuItems = ROUTES.filter((menuItem) => menuItem);

    });


  }

  opensnackbar(){
    this.snackbar.open('Concludere prima il contratto.', 'Chiudi', {
      duration: 5000,
      horizontalPosition: this.horizontalPosition,
      verticalPosition: this.verticalPosition,
    });

  }
  logout() {
    this.authService.logOut();
  }
}
