import {
  Component,
  OnInit,
  Renderer2,
  ViewChild,
  ElementRef,
} from '@angular/core';
import {
  trigger,
  state,
  style,
  animate,
  transition,
} from '@angular/animations';
import {
  FormBuilder,
  FormGroup,
  Validators,
  FormControl,
} from '@angular/forms';
import { ApiService } from 'src/app/servizi/api.service';
import { MatDialog } from '@angular/material/dialog';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { ContrattoDetailsDialogComponent } from 'src/app/modal/modal.component';
import { MatSort } from '@angular/material/sort';
import { ActivatedRoute } from '@angular/router';
import { MatSnackBar, MatSnackBarHorizontalPosition, MatSnackBarVerticalPosition } from '@angular/material/snack-bar';
import { DatePickerModule } from 'primeng/datepicker';
import { DateRange } from '@angular/material/datepicker';
import { ButtonModule } from 'primeng/button';

export interface leads {
  id: number;
  nome: string;
  cognome: string;
  email: string;
  telefono: string;
  stato: string;
  inserito_il: string;
  assegnato_a: string;
  id_assegnato: number;
  microstato: string;
  is_converted: boolean;
  nominativoLead: string;
  data_appuntamento: string;
}
export interface leadsSquad {
  id: number;
  nome: string;
  cognome: string;
  email: string;
  telefono: string;
  stato: string;
  inserito_il: string;
  assegnato_a: string;
  id_assegnato: number;
  microstato: string;
  is_converted: boolean;
  nominativoLead: string;
  data_appuntamento: string;
}

// Enhanced Lead interface — includes nome/cognome for pre-filling conversion form
export interface Lead {
  email: string;
  telefono: string;
  id_lead: number;
  nome: string;
  cognome: string;
}

//Interface for contract info in expandable rows
export interface LeadContractInfo {
  id: number;
  codice_contratto: string;
  intestatario: string;
  stato: string;
  punti_bonus: number;
}

@Component({
  selector: 'app-leads',
  templateUrl: './leads.component.html',
  styleUrls: ['./leads.component.scss'],
  animations: [
    trigger('fadeAnimation', [
      state(
        'visible',
        style({
          opacity: 1,
          display: 'block',
        })
      ),
      state(
        'hidden',
        style({
          opacity: 0,
          display: 'none',
        })
      ),
      transition('visible <=> hidden', [animate('400ms ease-in-out')]),
    ]),
    trigger('pageTransition', [
      transition(':enter', [
        style({ opacity: 0, transform: 'scale(0.1)' }),
        animate(
          '500ms ease-in-out',
          style({ opacity: 1, transform: 'scale(1)' })
        ),
      ]),
      transition(':leave', [
        animate(
          '500ms ease-in-out',
          style({ opacity: 0, transform: 'scale(0.1)' })
        ),
      ]),
    ]),
    // Animation for expandable contract rows
    trigger('detailExpand', [
      state('collapsed, void', style({ height: '0px', minHeight: '0', opacity: 0 })),
      state('expanded', style({ height: '*', opacity: 1 })),
      transition('expanded <=> collapsed', animate('300ms cubic-bezier(0.4, 0.0, 0.2, 1)')),
      transition('expanded <=> void', animate('300ms cubic-bezier(0.4, 0.0, 0.2, 1)'))
    ]),
  ],
  standalone: false,
})
export class LeadsComponent implements OnInit {
  @ViewChild('paginator') paginator!: MatPaginator;
  @ViewChild('paginatorSquad') paginatorSquad!: MatPaginator;
  @ViewChild('sort') sort!: MatSort;
  @ViewChild('sortSquad') sortSquad!: MatSort;

  // Enhanced lead object — now includes nome/cognome for conversion form pre-fill
  lead: Lead = { email: '', telefono: '', id_lead: 0, nome: '', cognome: '' };
  state: any;
  leadForm: FormGroup;
  leadSingleForm: FormGroup;
  Leads: leads[] = [];
  LeadsSquad: leadsSquad[] = [];
  leadsSelected: leads[] = [];
  leadsSelectedSquad: leadsSquad[] = [];
  seuSelected: leads[] = [];
  seuSelectedSquad: leadsSquad[] = [];
  statusSelected: leads[] = [];
  statusSelectedSquad: leadsSquad[] = [];
  inseritoIl: leads[] = [];
  inseritoIlSquad: leadsSquad[] = [];

  DTRGinseritoil: Date[] | null = null;
  DTRGinseritoilSquad: Date[] | null = null;

  showleadsTable = false;
  showleadsCard = false;
  showCreateLead = false;
  showQrcode = false;
  allDivCard = false;
  displayedColumns: string[] = [
    'nominativo',
    'email',
    'telefono',
    'inserito_il',
    'assegnato_a',
    'microstato',
    'azioni',
  ];

  nuovocliente = true;
  dataSource = new MatTableDataSource<leads>();
  dataSourceSquad = new MatTableDataSource<leadsSquad>();

  showCalendario = true;
  showDettagli = true;
  showCalendarTab = false;

  ruoloUtente = '';
  userId = 0;
  roleId = 0;
  statiUnivoci: { nome: string; count: number; nomeConConteggio: string }[] =
    [];
  statiUnivociSquad: {
    nome: string;
    count: number;
    nomeConConteggioSquad: string;
  }[] = [];
  seuUnivoci: { nome: string }[] = [];
  seuUnivociSquad: { nome: string }[] = [];
  checked: boolean = false;

  // ===== PROPRIETÀ CALENDARIO MODERNO =====
  calendarView: 'month' | 'week' | 'day' = 'month';
  currentDate: Date = new Date();
  currentPeriodText: string = '';
  weekDays: string[] = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
  calendarDays: any[] = [];
  isCalendarLoading: boolean = false;
  totalLeads: number = 0;
  todayLeads: number = 0;
  weekLeads: number = 0;
  useOriginalCalendar: boolean = false;

  // Expandable rows properties (Client role)
  expandedLeadIds: Set<number> = new Set();
  leadContractsMap: Map<number, LeadContractInfo[]> = new Map();
  rawLeadsData: any[] = []; // Store raw API data to access lead_converted
  readonly BONUS_COEFFICIENT = 0.5;

  // ===== NEW CLIENT CREATION PROPERTIES =====
  showNewClient = false;
  selectTipoCliente: string = 'consumer';
  newClientForm: FormGroup;
  showClientFormError: boolean = false;

  // Conversion mode: true when opening form from "Converti" button
  isConvertMode = false;
  convertingLeadId: number = 0;

  // Duplicate lead warning
  showDuplicateWarning = false;
  duplicateLeadFound: any = null;

  constructor(
    private servizioApi: ApiService,
    private fb: FormBuilder,
    private dialogRef: MatDialog,
    private route: ActivatedRoute,
    private _snackBar: MatSnackBar
  ) {
    this.leadForm = this.fb.group({
      nome: ['', Validators.required],
      cognome: ['', Validators.required],
      email: ['', Validators.required],
      telefono: ['', Validators.required],
      assegnato_a: [''],
      id_assegnato: [''],
      consenso: new FormControl(false, Validators.requiredTrue),
    });

    this.leadSingleForm = this.fb.group({
      nome: ['', Validators.required],
      cognome: ['', Validators.required],
      email: ['', Validators.required],
      telefono: ['', Validators.required],
      assegnato_a: ['', Validators.required],
      id_assegnato: ['', Validators.required],
    });

    // New client creation form (consumer/business)
    this.newClientForm = this.fb.group({
      nome: ['', Validators.required],
      cognome: ['', Validators.required],
      ragione_sociale: ['', Validators.required],
      email: ['', [Validators.required, Validators.email]],
      telefono: ['', Validators.required],
      codice_fiscale: ['', [Validators.required, Validators.minLength(16)]],
      partita_iva: ['', [Validators.required, Validators.minLength(11)]],
      indirizzo: ['', Validators.required],
      provincia: ['', [Validators.required, Validators.minLength(2)]],
      citta: ['', Validators.required],
      nazione: ['', Validators.required],
      cap: ['', Validators.required],
    });
  }

  onConsensoChange(event: any) {
    this.checked = event.checked;
    this.leadForm.get('consenso')?.setValue(this.checked);
    this.leadForm.get('consenso')?.markAsTouched();
  }

  // ===== LEAD STATUS COLOR MAPPING =====
  // Maps micro_stato to proper badge colors (database colors are for row bg, not badges)
  private getLeadStatusColor(microStato: string): string {
    const colorMap: Record<string, string> = {
      'Lead inserito':        'linear-gradient(135deg, #6366f1, #818cf8)',
      'Amico Inserito':       'linear-gradient(135deg, #6366f1, #818cf8)',
      'Lead Sospeso':         'linear-gradient(135deg, #f59e0b, #fbbf24)',
      'Lead Non Interessato': 'linear-gradient(135deg, #ef4444, #f87171)',
      'Appuntamento Preso':   'linear-gradient(135deg, #3b82f6, #60a5fa)',
      'Appuntamento KO':      'linear-gradient(135deg, #ef4444, #f87171)',
      'Lead OK':              'linear-gradient(135deg, #10b981, #34d399)',
    };
    return colorMap[microStato] || 'linear-gradient(135deg, #64748b, #94a3b8)';
  }

  // ===== PERSONAL/SQUAD LEAD FILTERING =====

  /**
   * Determines if a lead belongs to the current user's personal leads.
   * - Client (role 3): leads they CREATED (invitato_da_user_id) — survives reassignment
   * - SEU/Advisor: leads ASSIGNED to them OR assigned to their direct clients
   */
  private isPersonalLead(lead: any): boolean {
    if (this.roleId === 3) {
      return lead.invitato_da_user_id === this.userId;
    } else {
      return lead.assegnato_a === this.userId ||
        (lead.user && lead.user.role_id === 3 && lead.user.user_id_padre === this.userId);
    }
  }

  /**
   * Returns the display name for the "Inserito da" column.
   * - Client: shows the assignee (themselves, since they auto-assign)
   * - SEU/Advisor: shows who CREATED the lead (invited_by from backend)
   */
  private getInseritoDaDisplay(Lead: any): string {
    if (this.roleId === 3) {
      return Lead.user.name || Lead.user.cognome
        ? Lead.user.name + ' ' + Lead.user.cognome
        : Lead.user.ragione_sociale;
    } else {
      if (Lead.invited_by) {
        return Lead.invited_by.name || Lead.invited_by.cognome
          ? (Lead.invited_by.name || '') + ' ' + (Lead.invited_by.cognome || '')
          : Lead.invited_by.ragione_sociale || 'N/D';
      }
      return Lead.user.name || Lead.user.cognome
        ? Lead.user.name + ' ' + Lead.user.cognome
        : Lead.user.ragione_sociale;
    }
  }

  ngOnInit(): void {
    this.servizioApi.PrendiUtente().subscribe((Ruolo: any) => {
      this.ruoloUtente = Ruolo.user.qualification.descrizione;
      this.userId = Ruolo.user.id;
      this.roleId = Ruolo.user.role_id;

      if (this.ruoloUtente != 'Cliente') {
        this.showCalendario = false;
        this.showDettagli = false;
      }

      // Auto-show leads table on page load (all roles)
      this.allDivCard = true;
      this.showleadsTable = true;

      // Handle query param ?action=new (from dashboard "Invita Amico")
      this.route.queryParams.subscribe(params => {
        if (params['action'] === 'new') {
          this.ShowCrealeads();
        }
      });
    });

    this.servizioApi.getLeads().subscribe((LeadsAll: any) => {
      console.clear();
      console.log(LeadsAll);

      // Store raw data for lead_converted access and duplicate checking
      this.rawLeadsData = LeadsAll.body.risposta;

      LeadsAll.body.risposta.map((Lead: any) => {

        // Personal leads: role-based filtering
        this.Leads = LeadsAll.body.risposta
          .filter((lead: any) => this.isPersonalLead(lead))
          .map((Lead: any) => ({
            id: Lead.id,
            nome: Lead.nome,
            cognome: Lead.cognome,
            email: Lead.email,
            telefono: Lead.telefono,
            inserito_il: Lead.data_inserimento,
            assegnato_a: this.getInseritoDaDisplay(Lead),
            id_assegnato: Lead.user.id,
            stato: Lead.lead_status_id,
            colore: this.getLeadStatusColor(
              this.ruoloUtente === 'Cliente' && Lead.leadstatus.micro_stato === 'Lead inserito'
                ? 'Amico Inserito'
                : Lead.leadstatus.micro_stato
            ),
            microstato:
              this.ruoloUtente == 'Cliente' &&
              Lead.leadstatus.micro_stato == 'Lead inserito'
                ? 'Amico Inserito'
                : Lead.leadstatus.micro_stato,
            is_converted: Lead.is_converted,
            nominativoLead: Lead.nome + ' ' + Lead.cognome,
            data_appuntamento:
              Lead.data_appuntamento + ' ' + Lead.ora_appuntamento,
          }));

        const conteggiStati = this.Leads.reduce((acc: any, curr: any) => {
          acc[curr.microstato] = (acc[curr.microstato] || 0) + 1;
          return acc;
        }, {});

        this.statiUnivoci = Object.keys(conteggiStati).map((key) => ({
          nome: key,
          count: conteggiStati[key],
          nomeConConteggio: `${key}`,
          label: `${key} (x${conteggiStati[key]})`,
          conteggio: ` (x${conteggiStati[key]})`,
        }));

        this.seuUnivoci = [
          ...new Set(this.Leads.map((lead) => lead.assegnato_a)),
        ].map((value) => ({
          nome: value,
        }));

        this.dataSource.data = this.Leads;
        this.dataSource.paginator = this.paginator;

        // Squad leads: everything that is NOT personal
        this.LeadsSquad = LeadsAll.body.risposta
          .filter((lead: any) => !this.isPersonalLead(lead))
          .map((Lead: any) => ({
            id: Lead.id,
            nome: Lead.nome,
            cognome: Lead.cognome,
            email: Lead.email,
            telefono: Lead.telefono,
            inserito_il: Lead.data_inserimento,
            assegnato_a: Lead.user.name + ' ' + Lead.user.cognome,
            id_assegnato: Lead.user.id,
            stato: Lead.lead_status_id,
            colore: this.getLeadStatusColor(Lead.leadstatus.micro_stato),
            microstato: Lead.leadstatus.micro_stato,
            is_converted: Lead.is_converted,
            nominativoLead: Lead.nome + ' ' + Lead.cognome,
            data_appuntamento:
              Lead.data_appuntamento + ' ' + Lead.ora_appuntamento,
          }));

        const conteggiStatiSquad = this.LeadsSquad.reduce(
          (acc: any, curr: any) => {
            acc[curr.microstato] = (acc[curr.microstato] || 0) + 1;
            return acc;
          },
          {}
        );

        this.statiUnivociSquad = Object.keys(conteggiStatiSquad).map((key) => ({
          nome: key,
          count: conteggiStati[key],
          nomeConConteggioSquad: `${key}`,
          label: `${key} (x${conteggiStatiSquad[key]})`,
          conteggio: `(x${conteggiStatiSquad[key]})`,
        }));

        this.seuUnivociSquad = [
          ...new Set(this.LeadsSquad.map((leadSquad) => leadSquad.assegnato_a)),
        ].map((value) => ({
          nome: value,
        }));

        this.dataSourceSquad.data = this.LeadsSquad;
        this.dataSourceSquad.paginator = this.paginatorSquad;
      });

      // Inizializza il calendario moderno
      this.generateCalendar();

      // Load contracts for Client role expandable rows
      if (this.roleId === 3) {
        this.loadLeadContracts();
      }
    });
  }

  // Load contracts and build the lead -> contracts map
  private loadLeadContracts(): void {
    this.servizioApi.getCombinedData(this.userId).subscribe((combinedData) => {
      const contratti = combinedData.contratti?.body?.risposta?.data
        || combinedData.contratti?.body?.risposta
        || [];

      this.Leads.forEach((lead: any) => {
        const rawLead = this.rawLeadsData.find((l: any) => l.id === lead.id);
        if (rawLead && rawLead.lead_converted && rawLead.lead_converted.cliente_id) {
          const clienteId = rawLead.lead_converted.cliente_id;
          const clientContracts = contratti
            .filter((c: any) => c.associato_a_user_id === clienteId)
            .map((c: any) => ({
              id: c.id,
              codice_contratto: c.codice_contratto || 'N/D',
              intestatario: c.customer_data
                ? (c.customer_data.ragione_sociale
                  || ((c.customer_data.nome || '') + ' ' + (c.customer_data.cognome || '')).trim())
                : '-',
              stato: c.status_contract?.micro_stato || '-',
              punti_bonus: c.status_contract_id === 15
                ? Math.round((c.product?.macro_product?.punti_valore || 0) * this.BONUS_COEFFICIENT)
                : 0,
            }));

          this.leadContractsMap.set(lead.id, clientContracts);
        }
      });
    });
  }

  // Toggle expandable row for a lead
  toggleLeadRow(lead: any): void {
    if (this.roleId !== 3) return;
    if (this.expandedLeadIds.has(lead.id)) {
      this.expandedLeadIds.delete(lead.id);
    } else {
      this.expandedLeadIds.add(lead.id);
    }
  }

  isLeadExpanded(lead: any): boolean {
    return this.expandedLeadIds.has(lead.id);
  }

  getLeadContracts(leadId: number): LeadContractInfo[] {
    return this.leadContractsMap.get(leadId) || [];
  }

  leadHasContracts(leadId: number): boolean {
    const contracts = this.leadContractsMap.get(leadId);
    return !!contracts && contracts.length > 0;
  }

  parseDate(dateString: string): Date {
    const [day, month, year] = dateString.split('/');
    return new Date(+year, +month - 1, +day);
  }

  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
    this.dataSource.sort = this.sort;

    this.dataSourceSquad.paginator = this.paginatorSquad;
    this.dataSourceSquad.sort = this.sortSquad;
  }

  creaLead() {
    console.log('Creazione lead:', this.leadForm.value);
    this.servizioApi
      .storeNewLead(this.leadForm.value)
      .subscribe((Risposta: any) => {
        console.log('Lead creato:', Risposta);
        this.ngOnInit();
      });
    this.showLeads();
  }

  clearFilter() {
    this.leadsSelected = [];
    this.statusSelected = [];
    this.seuSelected = [];
    this.DTRGinseritoil = [];
    this.dataSource.filter = '';
    this.dataSourceSquad.filter = '';
  }

  clearFilterSquad() {
    this.leadsSelectedSquad = [];
    this.statusSelectedSquad = [];
    this.seuSelectedSquad = [];
    this.DTRGinseritoilSquad = [];
    this.dataSource.filter = '';
    this.dataSourceSquad.filter = '';
  }

  filterUser(row: any, filter: string) {
    if (!filter) {
      return true;
    }

    const filterObj = JSON.parse(filter);
    const utentiSelezionati = filterObj.usLead || [];
    const statiSelezionati = filterObj.statusLead || [];
    const seuSelezionati = filterObj.seuLead || [];

    let matchDate = true;
    if (filterObj.inseritoil.start && filterObj.inseritoil.end) {
      const startDate = this.parseDate(filterObj.inseritoil.start);
      const endDate = this.parseDate(filterObj.inseritoil.end);
      const rowDate = this.parseDate(row.inserito_il);
      matchDate = rowDate >= startDate && rowDate <= endDate;
    }

    const matchUtente =
      !utentiSelezionati.length ||
      utentiSelezionati.includes(row.nominativoLead);
    const matchStato =
      !statiSelezionati.length || statiSelezionati.includes(row.microstato);
    const matchSeu =
      !seuSelezionati.length || seuSelezionati.includes(row.assegnato_a);

    return matchUtente && matchStato && matchSeu && matchDate;
  }

  filterUserSquad(row: any, filter: string) {
    if (!filter) {
      return true;
    }

    const filterObjSquad = JSON.parse(filter);
    const utentiSelezionatiSquad = filterObjSquad.usLeadSquad || [];
    const statiSelezionatiSquad = filterObjSquad.statusLeadSquad || [];
    const seuSelezionatiSquad = filterObjSquad.seuLeadSquad || [];

    let matchDateSquad = true;
    if (filterObjSquad.inseritoil.start && filterObjSquad.inseritoil.end) {
      const startDate = this.parseDate(filterObjSquad.inseritoil.start);
      const endDate = this.parseDate(filterObjSquad.inseritoil.end);
      const rowDate = this.parseDate(row.inserito_il);
      matchDateSquad = rowDate >= startDate && rowDate <= endDate;
    }

    const matchUtenteSquad =
      !utentiSelezionatiSquad.length ||
      utentiSelezionatiSquad.includes(row.nominativoLead);
    const matchStatoSquad =
      !statiSelezionatiSquad.length ||
      statiSelezionatiSquad.includes(row.microstato);
    const matchSeuSquad =
      !seuSelezionatiSquad.length ||
      seuSelezionatiSquad.includes(row.assegnato_a);

    return (
      matchUtenteSquad && matchStatoSquad && matchSeuSquad && matchDateSquad
    );
  }

  applyFilter() {
    let startDate: any | Date | null = null;
    let endDate: any | Date | null = null;

    if (this.DTRGinseritoil != null || Array.isArray(this.DTRGinseritoil)) {
      if (this.DTRGinseritoil.length > 1) {
        startDate = this.DTRGinseritoil[0].toLocaleDateString('it-IT', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
        });
        endDate = this.DTRGinseritoil[1].toLocaleDateString('it-IT', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
        });
      }
    }

    const filterValue = {
      usLead: this.leadsSelected.map((usLead) => usLead.nominativoLead),
      statusLead: this.statusSelected.map((statusLead) => statusLead.nome),
      seuLead: this.seuSelected.map((seuLead) => seuLead.nome),
      inseritoil: {
        start: startDate,
        end: endDate,
      },
    };

    this.dataSource.filterPredicate = this.filterUser.bind(this);
    this.dataSource.filter = JSON.stringify(filterValue);
  }

  applyFilterSquad() {
    let startDate: any | Date | null = null;
    let endDate: any | Date | null = null;

    if (
      this.DTRGinseritoilSquad != null ||
      Array.isArray(this.DTRGinseritoilSquad)
    ) {
      if (this.DTRGinseritoilSquad.length > 1) {
        startDate = this.DTRGinseritoilSquad[0].toLocaleDateString('it-IT', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
        });
        endDate = this.DTRGinseritoilSquad[1].toLocaleDateString('it-IT', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
        });
      }
    }

    const filterValueSquad = {
      usLeadSquad: this.leadsSelectedSquad.map(
        (usLeadSquad) => usLeadSquad.nominativoLead
      ),
      statusLeadSquad: this.statusSelectedSquad.map(
        (statusLeadSquad) => statusLeadSquad.nome
      ),
      seuLeadSquad: this.seuSelectedSquad.map(
        (seuLeadSquad) => seuLeadSquad.nome
      ),
      inseritoil: {
        start: startDate,
        end: endDate,
      },
    };

    this.dataSourceSquad.filterPredicate = this.filterUserSquad.bind(this);
    this.dataSourceSquad.filter = JSON.stringify(filterValueSquad);
  }

  showLeadsForDay(array: any) {
    this.allDivCard = true;
    this.showleadsTable = false;
    this.showleadsCard = true;
    this.showCreateLead = false;
    this.showQrcode = false;
    this.showNewClient = false;
    this.showCalendarTab = false;
    this.servizioApi.getLeadsDayClicked(array).subscribe((Risposta: any) => {
      console.log(Risposta.body.risposta);
      this.Leads = Risposta.body.risposta.map((Lead: any) => ({
        id: Lead.id,
        nome: Lead.nome,
        cognome: Lead.cognome,
        email: Lead.email,
        telefono: Lead.telefono,
        assegnato_a: Lead.user.name + ' ' + Lead.user.cognome,
        id_assegnato: Lead.user.id,
        stato: Lead.lead_status_id,
        colore: this.getLeadStatusColor(Lead.leadstatus.micro_stato),
        microstato: Lead.leadstatus.micro_stato,
        is_converted: Lead.is_converted,
        nominativoLead: Lead.nome + ' ' + Lead.cognome,
      }));
    });
  }

  showLeads() {
    this.allDivCard = true;
    this.showleadsTable = !this.showleadsTable;
    this.showleadsCard = false;
    this.showCreateLead = false;
    this.showQrcode = false;
    this.showNewClient = false;
    this.showCalendarTab = false;
  }

  ShowCrealeads() {
    this.allDivCard = true;
    this.showCreateLead = !this.showCreateLead;
    this.showleadsTable = false;
    this.showleadsCard = false;
    this.showQrcode = false;
    this.showNewClient = false;
    this.showCalendarTab = false;
  }

  ShowAppQrcode() {
    this.allDivCard = true;
    this.showCreateLead = false;
    this.showleadsTable = false;
    this.showleadsCard = false;
    this.showQrcode = !this.showQrcode;
    this.showNewClient = false;
    this.showCalendarTab = false;
  }

  showCalendarOnly() {
    this.allDivCard = false;
    this.showCreateLead = false;
    this.showleadsTable = false;
    this.showleadsCard = false;
    this.showQrcode = false;
    this.showNewClient = false;
    this.showCalendarTab = false;
  }

  /**
   * Toggle the calendar tab inside main-content (same pattern as other tabs)
   */
  ShowCalendar() {
    this.allDivCard = true;
    this.showCalendarTab = !this.showCalendarTab;
    this.showCreateLead = false;
    this.showleadsTable = false;
    this.showleadsCard = false;
    this.showQrcode = false;
    this.showNewClient = false;

    // Refresh calendar when opening
    if (this.showCalendarTab) {
      this.generateCalendar();
    }
  }

  // ===== NEW CLIENT CREATION (Punto 3) =====

  /**
   * Toggle "Nuovo Cliente" form — standalone mode (not from lead conversion)
   */
  ShowNewClient() {
    this.allDivCard = true;
    this.showNewClient = !this.showNewClient;
    this.showCreateLead = false;
    this.showleadsTable = false;
    this.showleadsCard = false;
    this.showQrcode = false;
    this.showCalendarTab = false;

    // Reset form and flags
    this.isConvertMode = false;
    this.convertingLeadId = 0;
    this.showDuplicateWarning = false;
    this.duplicateLeadFound = null;
    this.showClientFormError = false;
    this.selectTipoCliente = 'consumer';
    this.newClientForm.reset();
  }

  /**
   * Check if email or phone matches an existing lead in the system.
   * Called on blur/change of email and telefono fields in the new client form.
   */
  checkDuplicateLead(): void {
    // Skip check in convert mode — we ARE converting the lead
    if (this.isConvertMode) {
      this.showDuplicateWarning = false;
      this.duplicateLeadFound = null;
      return;
    }

    const email = (this.newClientForm.get('email')?.value || '').trim().toLowerCase();
    const telefono = (this.newClientForm.get('telefono')?.value || '').trim();

    if (!email && !telefono) {
      this.showDuplicateWarning = false;
      this.duplicateLeadFound = null;
      return;
    }

    // Search through ALL leads (raw API data) for matching email or phone
    const matchingLead = this.rawLeadsData.find((lead: any) => {
      const leadEmail = (lead.email || '').trim().toLowerCase();
      const leadPhone = (lead.telefono || '').trim();

      const emailMatch = email && leadEmail && email === leadEmail;
      const phoneMatch = telefono && leadPhone && telefono === leadPhone;

      return emailMatch || phoneMatch;
    });

    if (matchingLead) {
      this.showDuplicateWarning = true;
      this.duplicateLeadFound = {
        id: matchingLead.id,
        nome: matchingLead.nome,
        cognome: matchingLead.cognome,
        email: matchingLead.email,
        telefono: matchingLead.telefono,
        stato: matchingLead.leadstatus?.micro_stato || 'N/D',
        is_converted: matchingLead.is_converted,
        inserito_da: matchingLead.invited_by
          ? (matchingLead.invited_by.name || '') + ' ' + (matchingLead.invited_by.cognome || '')
          : (matchingLead.user?.name || '') + ' ' + (matchingLead.user?.cognome || ''),
      };
    } else {
      this.showDuplicateWarning = false;
      this.duplicateLeadFound = null;
    }
  }

  /**
   * User dismisses the duplicate warning — go to conversion of the existing lead instead
   */
  goToConvertExistingLead(): void {
    if (!this.duplicateLeadFound) return;

    // Use the existing converti flow with the duplicate lead data
    this.converti({
      id: this.duplicateLeadFound.id,
      nome: this.duplicateLeadFound.nome,
      cognome: this.duplicateLeadFound.cognome,
      email: this.duplicateLeadFound.email,
      telefono: this.duplicateLeadFound.telefono,
      is_converted: this.duplicateLeadFound.is_converted,
    });

    this.showDuplicateWarning = false;
    this.duplicateLeadFound = null;
    this.showNewClient = false;
  }

  /**
   * User confirms creating client despite duplicate lead warning.
   * This is a PROCEDURE VIOLATION — we log it as an error.
   */
  proceedWithDuplicateClient(): void {
    this.showDuplicateWarning = false;
    // The form submission continues — the actual API call will also log the violation
    this.createNewClient(true);
  }

  /**
   * Create a new client from the form.
   * @param skipLeadConversion If true, logs a procedure violation warning
   */
  createNewClient(skipLeadConversion: boolean = false): void {
    this.showClientFormError = true;

    // Build client data based on tipo
    const clienteData: any = {
      email: this.newClientForm.value.email,
      telefono: this.newClientForm.value.telefono,
      indirizzo: this.newClientForm.value.indirizzo,
      provincia: this.newClientForm.value.provincia,
      citta: this.newClientForm.value.citta,
      nazione: this.newClientForm.value.nazione,
      cap: this.newClientForm.value.cap,
      qualifica: 9,
      ruolo: 3,
      us_padre: localStorage.getItem('userLogin'),
      tipo: this.selectTipoCliente,
      password: 'Benvenutoinsemprechiaro',
    };

    if (this.selectTipoCliente === 'consumer') {
      clienteData.nome = this.newClientForm.value.nome;
      clienteData.cognome = this.newClientForm.value.cognome;
      clienteData.codice_fiscale = this.newClientForm.value.codice_fiscale;
      clienteData.ragione_sociale = '-';
      clienteData.partita_iva = '00000000000';
    } else {
      clienteData.ragione_sociale = this.newClientForm.value.ragione_sociale;
      clienteData.partita_iva = this.newClientForm.value.partita_iva;
      clienteData.nome = '-';
      clienteData.cognome = '-';
      clienteData.codice_fiscale = '0000000000000000';
    }

    // In convert mode, use nuovoClienteLead (creates user + leadConverted record)
    if (this.isConvertMode && this.convertingLeadId) {
      clienteData.id_lead = this.convertingLeadId;
      this.servizioApi.nuovoClienteLead(clienteData).subscribe((risultato: any) => {
        if (risultato.response === 'ok') {
          console.log('Client created from lead conversion:', risultato);
          this.showNewClient = false;
          this.isConvertMode = false;
          this.convertingLeadId = 0;
          this.newClientForm.reset();
          this.showClientFormError = false;
          this.ngOnInit(); // Reload data
        } else {
          console.error('Client already exists:', risultato);
        }
      });
      return;
    }

    // Standalone mode: use nuovoUtente + log violation if duplicate was skipped
    if (skipLeadConversion && this.duplicateLeadFound) {
      clienteData.procedure_violation = true;
      clienteData.matching_lead_id = this.duplicateLeadFound.id;
      clienteData.matching_lead_nome = this.duplicateLeadFound.nome + ' ' + this.duplicateLeadFound.cognome;
    }

    this.servizioApi.nuovoUtente(clienteData).subscribe((risultato: any) => {
      if (risultato.response === 'ok') {
        console.log('Client created (standalone):', risultato);

        // Log procedure violation if a matching lead was skipped
        if (skipLeadConversion && this.duplicateLeadFound) {
          this.logProcedureViolation(this.duplicateLeadFound, clienteData);
        }

        this.showNewClient = false;
        this.newClientForm.reset();
        this.showClientFormError = false;
        this.duplicateLeadFound = null;
        this.ngOnInit();
      } else {
        console.error('Client already exists:', risultato);
      }
    });
  }

  /**
   * Log a procedure violation when a client is created without converting the matching lead.
   * Calls backend endpoint to create a system log entry.
   */
  private logProcedureViolation(duplicateLead: any, clienteData: any): void {
    const logData = {
      level: 'error',
      source: 'user_activity',
      message: `Procedure violation: Client created without converting matching lead #${duplicateLead.id}`,
      entity_type: 'lead',
      entity_id: duplicateLead.id,
      context: {
        matching_lead: {
          id: duplicateLead.id,
          nome: duplicateLead.nome,
          cognome: duplicateLead.cognome,
          email: duplicateLead.email,
          telefono: duplicateLead.telefono,
          stato: duplicateLead.stato,
        },
        new_client: {
          email: clienteData.email,
          telefono: clienteData.telefono,
          tipo: clienteData.tipo,
        },
        user_id: this.userId,
        action: 'client_created_without_lead_conversion',
      },
    };

    // Use a generic log endpoint if available, otherwise just console.error
    // TODO: Connect to backend logging endpoint (POST /api/logProcedureViolation)
    console.error('[PROCEDURE VIOLATION]', logData);
    // this.servizioApi.logProcedureViolation(logData).subscribe();
  }

  // ===== LEAD CONVERSION (Punto 4) =====

  /**
   * Open the new client form pre-filled with lead data for conversion.
   * This replaces the old converti() that only passed email/telefono/id.
   */
  converti(lead: any) {
    // Set conversion mode
    this.isConvertMode = true;
    this.convertingLeadId = lead.id;

    // Also set the old lead object for backward compatibility with converti-lead component
    this.nuovocliente = false;
    this.lead = {
      email: lead.email,
      telefono: lead.telefono,
      id_lead: lead.id,
      nome: lead.nome,
      cognome: lead.cognome,
    };

    // Open the new client form and pre-fill with lead data
    this.allDivCard = true;
    this.showNewClient = true;
    this.showCreateLead = false;
    this.showleadsTable = false;
    this.showleadsCard = false;
    this.showQrcode = false;
    this.showCalendarTab = false;

    this.showDuplicateWarning = false;
    this.duplicateLeadFound = null;
    this.showClientFormError = false;
    this.selectTipoCliente = 'consumer';

    // Pre-fill form with lead data
    this.newClientForm.reset();
    this.newClientForm.patchValue({
      nome: lead.nome || '',
      cognome: lead.cognome || '',
      email: lead.email || '',
      telefono: lead.telefono || '',
    });
  }

  /**
   * Cancel the new client form and go back to leads table
   */
  cancelNewClient(): void {
    this.showNewClient = false;
    this.isConvertMode = false;
    this.convertingLeadId = 0;
    this.showDuplicateWarning = false;
    this.duplicateLeadFound = null;
    this.showClientFormError = false;
    this.newClientForm.reset();
    this.nuovocliente = true;

    // Show leads table
    this.showleadsTable = true;
  }

  /**
   * Show a snackbar notification when user clicks the thumb-up icon
   * on a lead that has already been converted to a client.
   */
  showConvertedSnackbar(lead: any): void {
    this._snackBar.open(
      `Il lead "${lead.nominativoLead}" è già stato convertito in cliente!`,
      'OK',
      {
        duration: 4000,
        horizontalPosition: 'center',
        verticalPosition: 'bottom',
        panelClass: ['converted-snackbar'],
      }
    );
  }

  /**
   * Submit the new client form.
   * If duplicate warning is active and not yet confirmed, show warning first.
   */
  submitNewClient(): void {
    // If duplicate warning is showing, don't submit — user must choose
    if (this.showDuplicateWarning) {
      return;
    }

    // Check for required fields based on tipo
    const isValid = this.isClientFormValid();
    if (!isValid) {
      this.showClientFormError = true;
      return;
    }

    // Check for duplicates one last time before submit
    this.checkDuplicateLead();
    if (this.showDuplicateWarning) {
      return; // Show warning, don't submit yet
    }

    this.createNewClient(false);
  }

  /**
   * Validates the client form based on the selected client type.
   */
  private isClientFormValid(): boolean {
    const f = this.newClientForm.value;

    // Common required fields
    if (!f.email || !f.telefono || !f.indirizzo || !f.citta || !f.nazione || !f.cap || !f.provincia) {
      return false;
    }

    if (this.selectTipoCliente === 'consumer') {
      return !!(f.nome && f.cognome && f.codice_fiscale && f.codice_fiscale.length >= 16);
    } else {
      return !!(f.ragione_sociale && f.partita_iva && f.partita_iva.length >= 11);
    }
  }

  // ===== LEAD DETAILS DIALOG =====

  dettagliLead(lead: any, reparto: string) {
    this.dialogRef.open(ContrattoDetailsDialogComponent, {
      width: 'calc(50% - 50px)',
      enterAnimationDuration: '500ms',
      exitAnimationDuration: '500ms',
      data: { lead: lead, reparto: reparto },
    });
  }

  onArrayidLeadChange(newArray: number[]) {
    this.showLeadsForDay(newArray);
  }

  // ===== METODI CALENDARIO MODERNO =====

  changeView(view: 'month' | 'week' | 'day') {
    console.log('Cambio vista da', this.calendarView, 'a', view);
    this.calendarView = view;
    this.generateCalendar();
  }

  onViewChange(event: any) {
    console.log('Vista cambiata:', event.value);
    this.calendarView = event.value;
    this.generateCalendar();
  }

  previousPeriod() {
    if (this.calendarView === 'month') {
      this.currentDate.setMonth(this.currentDate.getMonth() - 1);
    } else if (this.calendarView === 'week') {
      this.currentDate.setDate(this.currentDate.getDate() - 7);
    } else {
      this.currentDate.setDate(this.currentDate.getDate() - 1);
    }
    this.generateCalendar();
  }

  nextPeriod() {
    if (this.calendarView === 'month') {
      this.currentDate.setMonth(this.currentDate.getMonth() + 1);
    } else if (this.calendarView === 'week') {
      this.currentDate.setDate(this.currentDate.getDate() + 7);
    } else {
      this.currentDate.setDate(this.currentDate.getDate() + 1);
    }
    this.generateCalendar();
  }

  goToToday() {
    this.currentDate = new Date();
    this.generateCalendar();
  }

  generateCalendar() {
    if (this.calendarView === 'month') {
      this.generateMonthView();
    } else if (this.calendarView === 'week') {
      this.generateWeekView();
    } else if (this.calendarView === 'day') {
      this.generateDayView();
    }

    this.calculateStats();
  }

  updatePeriodText() {
    const options: Intl.DateTimeFormatOptions = {
      year: 'numeric',
      month: 'long',
    };

    if (this.calendarView === 'month') {
      this.currentPeriodText = this.currentDate.toLocaleDateString('it-IT', options);
    } else if (this.calendarView === 'week') {
      const startOfWeek = new Date(this.currentDate);
      startOfWeek.setDate(this.currentDate.getDate() - this.currentDate.getDay() + 1);
      const endOfWeek = new Date(startOfWeek);
      endOfWeek.setDate(startOfWeek.getDate() + 6);

      this.currentPeriodText = `${startOfWeek.getDate()} - ${endOfWeek.getDate()} ${endOfWeek.toLocaleDateString('it-IT', { month: 'long', year: 'numeric' })}`;
    } else {
      this.currentPeriodText = this.currentDate.toLocaleDateString('it-IT', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      });
    }
  }

  generateMonthView() {
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);

    startDate.setDate(startDate.getDate() - ((startDate.getDay() + 6) % 7));

    this.calendarDays = [];
    const currentDate = new Date(startDate);

    for (let i = 0; i < 42; i++) {
      const day = this.createCalendarDay(currentDate, month);
      this.calendarDays.push(day);
      currentDate.setDate(currentDate.getDate() + 1);
    }
  }

  generateWeekView() {
    const startOfWeek = new Date(this.currentDate);
    startOfWeek.setDate(this.currentDate.getDate() - this.currentDate.getDay() + 1);

    this.calendarDays = [];
    const currentDate = new Date(startOfWeek);

    for (let i = 0; i < 7; i++) {
      const day = this.createCalendarDay(currentDate, currentDate.getMonth());
      this.calendarDays.push(day);
      currentDate.setDate(currentDate.getDate() + 1);
    }
  }

  generateDayView() {
    this.calendarDays = [this.createCalendarDay(this.currentDate, this.currentDate.getMonth())];
  }

  createCalendarDay(date: Date, currentMonth: number) {
    const today = new Date();
    const leadsForDay = this.getLeadsForDate(date);

    return {
      date: date.getDate(),
      fullDate: new Date(date),
      isCurrentMonth: date.getMonth() === currentMonth,
      isToday: this.isSameDate(date, today),
      isSelected: false,
      leadsCount: leadsForDay.length,
      statuses: this.getStatusesForDay(leadsForDay),
      leads: leadsForDay,
    };
  }

  getLeadsForDate(date: Date): any[] {
    if (!this.Leads || this.Leads.length === 0) {
      return [];
    }

    const leadsWithAppointments = this.Leads.filter(lead =>
      lead.data_appuntamento &&
      lead.data_appuntamento !== null &&
      lead.data_appuntamento !== 'null null' &&
      typeof lead.data_appuntamento === 'string'
    );

    const matchingLeads = leadsWithAppointments.filter(lead => {
      try {
        let leadDate: Date;

        if (lead.data_appuntamento.includes(' ') && lead.data_appuntamento.length > 10) {
          const datePart = lead.data_appuntamento.split(' ')[0];
          leadDate = new Date(datePart);
        } else if (lead.data_appuntamento.includes('/')) {
          const parts = lead.data_appuntamento.split('/');
          if (parts.length === 3) {
            leadDate = new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
          } else {
            leadDate = new Date(lead.data_appuntamento);
          }
        } else {
          leadDate = new Date(lead.data_appuntamento);
        }

        if (isNaN(leadDate.getTime())) {
          return false;
        }

        return this.isSameDate(leadDate, date);
      } catch (error) {
        return false;
      }
    });

    return matchingLeads;
  }

  getStatusesForDay(leads: any[]) {
    const statusMap = new Map();

    leads.forEach((lead) => {
      const status = lead.microstato;
      if (statusMap.has(status)) {
        statusMap.set(status, statusMap.get(status) + 1);
      } else {
        statusMap.set(status, 1);
      }
    });

    return Array.from(statusMap.entries()).map(([name, count]) => ({
      name,
      count,
      color: this.getStatusColor(name),
    }));
  }

  getStatusColor(status: string): string {
    const colorMap: { [key: string]: string } = {
      'Lead inserito': '#3182ce',
      'Amico Inserito': '#3182ce',
      'Contattato': '#38a169',
      'Appuntamento fissato': '#d69e2e',
      'Convertito': '#805ad5',
      'Non interessato': '#e53e3e',
    };

    return colorMap[status] || '#718096';
  }

  selectDay(day: any) {
    this.calendarDays.forEach((d) => (d.isSelected = false));
    day.isSelected = true;

    if (day.leadsCount > 0) {
      this.showLeadsForDay(day.leads.map((lead: any) => lead.id));
    }
  }

  calculateStats() {
    this.totalLeads = this.Leads.length;

    const today = new Date();
    this.todayLeads = this.getLeadsForDate(today).length;

    const startOfWeek = new Date(today);
    startOfWeek.setDate(today.getDate() - today.getDay() + 1);
    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);

    this.weekLeads = this.Leads.filter((lead) => {
      if (lead.data_appuntamento) {
        const leadDate = new Date(lead.data_appuntamento);
        return leadDate >= startOfWeek && leadDate <= endOfWeek;
      }
      return false;
    }).length;
  }

  isSameDate(date1: Date, date2: Date): boolean {
    return (
      date1.getFullYear() === date2.getFullYear() &&
      date1.getMonth() === date2.getMonth() &&
      date1.getDate() === date2.getDate()
    );
  }

  trackByDay(index: number, day: any): any {
    return day.fullDate.getTime();
  }

  refreshCalendar() {
    console.log('Aggiornamento calendario - Lead totali:', this.Leads.length);
    this.generateCalendar();
  }
}