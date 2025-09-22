import { Routes } from '@angular/router';
import { LoginComponent } from './componenti/login/login.component';
import { RegistrazioneComponent } from './componenti/registrazione/registrazione.component';
import { AdminLayoutComponent } from './layouts/admin-layout/admin-layout.component';
import { activateUsersFn } from './servizi/route-guard.service';
import { DropzoneDeactivateGuard } from './servizi/dropzone-deactivate-guard.service';
import { ClientiComponent } from './pages/clienti/clienti.component';
import { DashboardComponent } from './pages/dashboard/dashboard.component';
import { TableComponent } from './pages/table/table.component';
import { LeadsComponent } from './pages/leads/leads.component';
import { ListaContrattiComponent } from './pages/listacontratti/listaContratti.component';
import { ContrattiComponent } from './pages/contratti/contratti.component';
import { NuovocontrattoComponent } from './pages/nuovocontratto/nuovocontratto.component';
import { NuovoclienteComponent } from './pages/nuovocliente/nuovocliente.component';
import { ProdottiComponent } from './pages/prodotto/prodotti.component';
import { DropzoneComponent } from 'ngx-dropzone-wrapper';
import { UserComponent } from './pages/user/user.component';
import { TypographyComponent } from './pages/typography/typography.component';
import { DomandeComponent } from './pages/domande/domande.component';
import { IconsComponent } from './pages/icons/icons.component';
import { NotificationsComponent } from './pages/notifications/notifications.component';
import { UpgradeComponent } from './pages/upgrade/upgrade.component';
import { GestioneProdottiComponent } from './pages/gestione-prodotti/gestione-prodotti.component';
import { GestioneUtentiComponent } from './pages/gestione-utenti/gestione-utenti.component';
import { SchedaUtenteComponent } from './pages/scheda-utente/scheda-utente.component';
import { GestioneMacroprodottiComponent } from './pages/gestione-macroprodotti/gestione-macroprodotti.component';
import { ResetPasswordComponent } from './pages/reset-password/reset-password.component';
import { PasswordResetSuccessComponent } from './components/password-reset-success/password-reset-success.component';
import { FormGeneraleComponent } from './pages/form-generale/form-generale.component';
import { TicketManagementComponent } from './pages/ticket-management/ticket-management.component';

export const AppRoutes: Routes = [
  { path: 'login', component: LoginComponent },
  { path: 'registrazione', component: RegistrazioneComponent },
  { path: 'reset-password', component: ResetPasswordComponent },
  { path: 'password-reset-success', component: PasswordResetSuccessComponent },
  { path: 'form-generale/:userId', component: FormGeneraleComponent },
  {
    path: '',
    component: AdminLayoutComponent,
    canActivate: [activateUsersFn],
    children: [
      { path: 'dashboard', component: DashboardComponent },
      {
        path: 'user',
        component: UserComponent,
        data: { cache: false },
        runGuardsAndResolvers: 'always',
      },
      { path: 'leads', component: LeadsComponent },
      { path: 'clienti', component: ClientiComponent },
      { path: 'contratti', component: ListaContrattiComponent },
      { path: 'table', component: GestioneProdottiComponent },
      { path: 'macroprodotti', component: GestioneMacroprodottiComponent },
      { path: 'typography', component: TypographyComponent },
      { path: 'icons', component: IconsComponent },
      { path: 'notifications', component: NotificationsComponent },
      { path: 'upgrade', component: UpgradeComponent },
      { path: 'gestionedomande', component: DomandeComponent },
      { path: 'utenti', component: GestioneUtentiComponent },
      { path: 'schedapr', component: SchedaUtenteComponent },
      { path: 'ticket-management', component: TicketManagementComponent },
    ],
  },
  {
    path: '',
    component: DropzoneComponent,
    canDeactivate: [DropzoneDeactivateGuard],
  },
  { path: '**', redirectTo: 'dashboard' },
  
];
