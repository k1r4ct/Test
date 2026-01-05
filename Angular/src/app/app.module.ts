import { NgModule, CUSTOM_ELEMENTS_SCHEMA } from "@angular/core";
import { BrowserModule } from "@angular/platform-browser";
import {
  provideHttpClient,
  withInterceptorsFromDi,
} from "@angular/common/http";
import { AppComponent } from "./app.component";
import { BrowserAnimationsModule } from "@angular/platform-browser/animations";
import { AdminLayoutComponent } from "./layouts/admin-layout/admin-layout.component";
import { LoginComponent } from "./componenti/login/login.component";
import { RegistrazioneComponent } from "./componenti/registrazione/registrazione.component";
import { SidebarComponent } from "./sidebar/sidebar.component";
import { ClientiComponent } from "./pages/clienti/clienti.component";
import { NavbarComponent } from "./shared/navbar/navbar.component";
import { SidebarModule } from "./sidebar/sidebar.module";
import { RouterModule } from "@angular/router";
import { AppRoutes } from "./app.routing";
import { NgbModule } from "@ng-bootstrap/ng-bootstrap";
import { ToastrModule } from "ngx-toastr";
import { provideAnimations } from "@angular/platform-browser/animations";
import { LeadsComponent } from "./pages/leads/leads.component";
import { DomandeComponent } from "./pages/domande/domande.component";
import { ContrattiComponent } from "./pages/contratti/contratti.component";
import { FormsModule, ReactiveFormsModule } from "@angular/forms";
import { ListaContrattiComponent } from "./pages/listacontratti/listaContratti.component";
import { MaterialComponentsModule } from "./material-components/material-components/material-components.component";
import { NuovocontrattoComponent } from "./pages/nuovocontratto/nuovocontratto.component";
import { RicercaclientiComponent } from "./pages/ricercaclienti/ricercaclienti.component";
import { NuovocontraenteComponent } from "./pages/nuovocontraente/nuovocontraente.component";
import { ProdottiComponent } from "./pages/prodotto/prodotti.component";
import { NuovoclienteComponent } from "./pages/nuovocliente/nuovocliente.component";
import { TimerComponent } from "./timer/timer.component";
import { FilepondUploaderComponent } from './filepond-uploader/filepond-uploader.component';
import { DettagliContrattoProdottoComponent } from "./pages/dettagli-contratto-prodotto/dettagli-contratto-prodotto.component";
import { MatFormFieldModule } from "@angular/material/form-field";
import { MatChipsModule } from "@angular/material/chips";
import { FilePondModule } from 'ngx-filepond';
import { MatGridListModule } from "@angular/material/grid-list";
import { HTTP_INTERCEPTORS } from "@angular/common/http";
import { AuthInterceptor } from "./servizi/interceptor.service";
import { MatSnackBarModule } from "@angular/material/snack-bar";
import { MatButtonModule } from "@angular/material/button";
import { MatProgressSpinnerModule } from "@angular/material/progress-spinner";
import { UserComponent } from "./pages/user/user.component";
import { DashboardComponent } from "./pages/dashboard/dashboard.component";
import { MatTabsModule } from "@angular/material/tabs";
import { MatInputModule } from "@angular/material/input";
import { MatTooltipModule } from "@angular/material/tooltip";
import { MatDatepickerModule } from "@angular/material/datepicker";
import { PagamentoComponent } from "./pages/pagamento/pagamento.component";
import { MatNativeDateModule } from "@angular/material/core";
import { MAT_DATE_LOCALE } from "@angular/material/core";
import { ContrattiRicercaComponent } from "./pages/contratti-ricerca/contratti-ricerca.component";
import { ContrattoDetailsDialogComponent } from "src/app/modal/modal.component";
import { ConfirmDialogComponent } from 'src/app/confirm-dialog/confirm-dialog.component';
import { GestioneProdottiComponent } from "./pages/gestione-prodotti/gestione-prodotti.component";
import { GestioneMacroprodottiComponent } from "./pages/gestione-macroprodotti/gestione-macroprodotti.component";
import { NuovoprodottoComponent } from "./pages/nuovoprodotto/nuovoprodotto.component";
import { MatCheckboxModule } from '@angular/material/checkbox';
import {MatSlideToggleModule} from '@angular/material/slide-toggle';
import {
  MatDialogModule,
  MatDialog,
  MatDialogActions,
  MatDialogClose,
  MatDialogContent,
  MatDialogRef,
  MatDialogTitle,
  MAT_DIALOG_DATA,
} from "@angular/material/dialog";
import { MatIconModule } from '@angular/material/icon';
import { registerLocaleData } from '@angular/common';
import localeIt from '@angular/common/locales/it';
import { CalendarDateFormatter, CalendarModule, DateAdapter } from 'angular-calendar';
import { adapterFactory } from 'angular-calendar/date-adapters/date-fns';
import { LOCALE_ID } from '@angular/core';
import { CalendarComponent } from "./pages/calendar/calendar.component";
import { CustomDateFormatter } from './custom-date-formatter';
import { DragAndDropModule } from 'angular-draggable-droppable';
registerLocaleData(localeIt);
import { LeadRowColorDirective } from 'src/app/lead-row-color.directive';
import { MessageNotificationComponent } from "./pages/message-notification/message-notification.component";
import { SafeHtmlPipe } from './safe-html.pipe';
import { GestioneUtentiComponent } from "./pages/gestione-utenti/gestione-utenti.component";
import { ConvertiLeadComponent } from "./pages/converti-lead/converti-lead.component";
import { ToastModule } from 'primeng/toast';
import { OrganizationChartModule } from 'primeng/organizationchart';
import { ToolbarModule } from 'primeng/toolbar';
import { DockModule } from 'primeng/dock';
import { CommonModule } from '@angular/common';
import { ChatbotComponent } from "./pages/chatbot/chatbot.component";
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { ListboxModule } from 'primeng/listbox';
import { MessagesModule } from 'primeng/messages';
import { MessageService } from 'primeng/api';
import { ProgressSpinnerModule } from 'primeng/progressspinner';
import { FileUploadModule } from 'primeng/fileupload';
import { CardModule } from 'primeng/card';
import { NuovofornitoreComponent } from "./pages/nuovofornitore/nuovofornitore.component";
import { InputTextModule } from 'primeng/inputtext';
import { FloatLabelModule } from 'primeng/floatlabel';
import { OverlayPanelModule } from 'primeng/overlaypanel';
import { InputGroupModule } from 'primeng/inputgroup';
import { InputGroupAddonModule } from 'primeng/inputgroupaddon';
import { ButtonModule } from 'primeng/button';
import { ChipModule } from 'primeng/chip';
import { ChartModule } from 'primeng/chart';
import { LeadMonthsComponent } from './pages/user/chart/lead-months/lead-months.component';
import { TabellacontattiComponent } from "./pages/user/clienti/tabellacontatti/tabellacontatti.component";
import { TabellacontrattiComponent } from "./pages/user/clienti/tabellacontratti/tabellacontratti.component";
import { DragDropModule } from '@angular/cdk/drag-drop';
import { CardStatisticComponent } from "./pages/user/clienti/card/card-statistic/card-statistic.component";
import { CardStatisticComponentSeu } from "./pages/user/seu/card/card-statistic/card-statistic.component";
import { DatePickerModule } from 'primeng/datepicker';
import { provideAnimationsAsync } from '@angular/platform-browser/animations/async';
import { providePrimeNG } from 'primeng/config';
import { ToggleSwitchModule } from 'primeng/toggleswitch';
import { SchedaUtenteComponent } from "./pages/scheda-utente/scheda-utente.component";
import { ContrattiPersonaliComponent } from "./pages/user/clienti/contratti-personali/contratti-personali.component";
import { ChartsModule } from "./pages/user/chart/chart.module";
import Nora  from '@primeng/themes/nora';
import Lara  from '@primeng/themes/lara';
import Material  from '@primeng/themes/lara';
import Aura  from '@primeng/themes/aura';
import { QrcodeGeneratorComponent } from "./pages/qrcode-generator/qrcode-generator.component";
import { TicketManagementComponent } from "./pages/ticket-management/ticket-management.component";
import { WalletClienteComponent } from "./pages/user/clienti/wallet-cliente/wallet-cliente.component";
import { AttachmentPreviewModalComponent } from './attachment-preview-modal/attachment-preview-modal.component';

// ⭐ STEP 2: Profile Settings Modal Component
import { ProfileSettingsModalComponent } from "./shared/components/profile-settings-modal/profile-settings-modal.component";

@NgModule({
  declarations: [
    AppComponent,
    AdminLayoutComponent,
    LoginComponent,
    RegistrazioneComponent,
    ClientiComponent,
    NavbarComponent,
    LeadsComponent,
    ContrattiComponent,
    ListaContrattiComponent,
    NuovocontrattoComponent,
    RicercaclientiComponent,
    NuovocontraenteComponent,
    ProdottiComponent,
    NuovoclienteComponent,
    DettagliContrattoProdottoComponent,
    DomandeComponent,
    UserComponent,
    DashboardComponent,
    PagamentoComponent,
    ContrattiRicercaComponent,
    ContrattoDetailsDialogComponent,
    ConfirmDialogComponent,
    GestioneProdottiComponent,
    GestioneMacroprodottiComponent,
    NuovoprodottoComponent,
    CalendarComponent,
    LeadRowColorDirective,
    MessageNotificationComponent,
    SafeHtmlPipe,
    GestioneUtentiComponent,
    ConvertiLeadComponent,
    ChatbotComponent,
    NuovofornitoreComponent,
    LeadMonthsComponent,
    TabellacontattiComponent,
    TabellacontrattiComponent,
    CardStatisticComponent,
    CardStatisticComponentSeu,
    SchedaUtenteComponent,
    ContrattiPersonaliComponent,
    TicketManagementComponent,
    WalletClienteComponent,
    AttachmentPreviewModalComponent,
    // ⭐ STEP 2: Profile Settings Modal
    ProfileSettingsModalComponent,
  ],
  schemas: [CUSTOM_ELEMENTS_SCHEMA],
  bootstrap: [AppComponent],
  imports: [
    MatDialogModule,
    MatInputModule,
    MatTabsModule,
    BrowserModule,
    SidebarModule,
    BrowserAnimationsModule,
    RouterModule.forRoot(AppRoutes, {
        useHash: false,
    }),
    NgbModule,
    ToastrModule.forRoot(),
    BrowserAnimationsModule,
    ReactiveFormsModule,
    MaterialComponentsModule,
    FormsModule,
    /* TimerComponent, */
    MatFormFieldModule,
    MatChipsModule,
    FilePondModule,
    MatGridListModule,
    MatSnackBarModule,
    MatButtonModule,
    MatProgressSpinnerModule,
    MatTooltipModule,
    MatDatepickerModule,
    MatNativeDateModule,
    MatCheckboxModule,
    MatSlideToggleModule,
    MatIconModule,
    DragAndDropModule,
    OrganizationChartModule,
    ToolbarModule,
    DockModule,
    CommonModule,
    MultiSelectModule,
    DropdownModule,
    ListboxModule,
    ToastModule,
    MessagesModule,
    ProgressSpinnerModule,
    FileUploadModule,
    CardModule,
    InputTextModule,
    FloatLabelModule,
    OverlayPanelModule,
    InputGroupModule,
    InputGroupAddonModule,
    ButtonModule,
    ChipModule,
    ChartModule,
    ChartsModule,
    DragDropModule,
    DatePickerModule,
    ToggleSwitchModule,
    CalendarModule.forRoot({
        provide: DateAdapter,
        useFactory: adapterFactory,
    }),
    QrcodeGeneratorComponent,
    FilepondUploaderComponent
],
  providers: [
    provideAnimations(),
    {
      provide: HTTP_INTERCEPTORS,
      useClass: AuthInterceptor,
      multi: true,
    },
    provideHttpClient(withInterceptorsFromDi()),
    { provide: MAT_DATE_LOCALE, useValue: "it-IT" },
    { provide: LOCALE_ID, useValue: 'it-IT' },
    { provide: CalendarDateFormatter, useClass: CustomDateFormatter },
    MessageService,
    provideAnimationsAsync(),
    providePrimeNG({
      theme: {
        preset: Lara,
        options: {
          darkModeSelector: '.my-app-dark'
        }
      },
      ripple: true,
      translation: {
        dateFormat: 'dd/mm/yy',
        firstDayOfWeek: 1,
        dayNames: ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'],
        dayNamesShort: ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'],
        dayNamesMin: ['Do', 'Lu', 'Ma', 'Me', 'Gi', 'Ve', 'Sa'],
        monthNames: ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
        monthNamesShort: ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
        today: 'Oggi',
        clear: 'Cancella',
        weekHeader: 'Sm'
      }
  })
  ],

})
export class AppModule {}

export interface ExampleTab {
  label: string;
  content: string;
}