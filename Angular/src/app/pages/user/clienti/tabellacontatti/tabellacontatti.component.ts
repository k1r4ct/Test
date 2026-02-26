import { Component, OnInit, ViewChild, AfterViewInit } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { MatDialog } from '@angular/material/dialog';
import { MatSnackBar } from '@angular/material/snack-bar';
import { FormBuilder, FormGroup, Validators, FormControl } from '@angular/forms';
import { trigger, transition, style, animate, state } from '@angular/animations';
import { ContrattoDetailsDialogComponent } from 'src/app/modal/modal.component';

export interface leads {
  id: number;
  nome: string;
  cognome: string;
  email: string;
  telefono: string;
  stato: string;
  inserito: string;
  assegnato_a: string;
  id_assegnato: number;
  microstato: string;
  is_converted: boolean;
  nominativoLead: string;
  data_appuntamento: string;
  colore?: string;
}

@Component({
  selector: 'app-tabellacontatti',
  templateUrl: './tabellacontatti.component.html',
  styleUrl: './tabellacontatti.component.scss',
  standalone: false,
  animations: [
    trigger('fadeIn', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateY(12px)' }),
        animate('400ms ease-out', style({ opacity: 1, transform: 'translateY(0)' })),
      ]),
    ]),
    trigger('fadeAnimation', [
      state('visible', style({ opacity: 1, display: 'block' })),
      state('hidden', style({ opacity: 0, display: 'none' })),
      transition('hidden => visible', [
        style({ opacity: 0, display: 'block' }),
        animate('300ms ease-in'),
      ]),
      transition('visible => hidden', [
        animate('300ms ease-out'),
      ]),
    ]),
  ],
})
export class TabellacontattiComponent implements OnInit, AfterViewInit {
  @ViewChild('paginator') paginator!: MatPaginator;
  @ViewChild('sort') sort!: MatSort;

  ruoloUtente: string = '';
  userId = 0;
  roleId = 0;
  Leads: leads[] = [];
  leadsSelected: leads[] = [];
  statusSelected: leads[] = [];
  seuSelected: leads[] = [];
  DTRGinseritoil: Date[] | null = null;
  dataSource = new MatTableDataSource<leads>();
  statiUnivoci: { nome: string; count: number; nomeConConteggio: string; label: string; conteggio: string }[] = [];
  seuUnivoci: { nome: string }[] = [];
  displayedColumns: string[] = [
    'nominativo',
    'email',
    'telefono',
    'inserito',
    'assegnato_a',
    'microstato',
  ];
  textLead = '';

  // ===== BACKOFFICE PROPERTIES =====
  isBackOffice = false;
  showLeadsTable = true;
  rawLeadsData: any[] = [];

  // BO inline panel toggles
  showCreateLead = false;
  showNewClient = false;
  showQrcode = false;

  // Lead creation form (copied from leads.component)
  leadForm!: FormGroup;
  checked: boolean = false;

  // New client creation form (copied from leads.component)
  newClientForm!: FormGroup;
  selectTipoCliente: string = 'consumer';
  showClientFormError: boolean = false;

  // Conversion mode
  isConvertMode = false;
  convertingLeadId: number = 0;

  // Duplicate lead warning
  showDuplicateWarning = false;
  duplicateLeadFound: any = null;

  constructor(
    private servizioApi: ApiService,
    private fb: FormBuilder,
    private dialogRef: MatDialog,
    private _snackBar: MatSnackBar
  ) {
    // Lead creation form
    this.leadForm = this.fb.group({
      nome: ['', Validators.required],
      cognome: ['', Validators.required],
      email: ['', Validators.required],
      telefono: ['', Validators.required],
      assegnato_a: [''],
      id_assegnato: [''],
      consenso: new FormControl(false, Validators.requiredTrue),
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

  ngOnInit(): void {
    this.servizioApi.PrendiUtente().subscribe((Ruolo: any) => {
      this.ruoloUtente = Ruolo.user.role.descrizione;
      this.userId = Ruolo.user.id;
      this.roleId = Ruolo.user.role_id;

      if (this.ruoloUtente == 'Cliente') {
        this.textLead = 'Amici';
      } else {
        this.textLead = 'Leads';
      }

      // BackOffice detection
      if (this.roleId === 5) {
        this.isBackOffice = true;
        this.displayedColumns = [
          'nominativo', 'email', 'telefono', 'inserito',
          'assegnato_a', 'microstato', 'azioni',
        ];
      }

      this.loadLeadsData();
    });
  }

  // ===== LEAD STATUS COLOR MAPPING (copied from leads.component) =====
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

  /**
   * Determines if a lead belongs to the current user's personal leads.
   * - Client (role 3): leads they CREATED (invitato_da_user_id)
   * - SEU/Advisor: leads ASSIGNED to them OR assigned to their direct clients
   * - BackOffice (role 5): ALL leads
   */
  private isPersonalLead(lead: any): boolean {
    if (this.roleId === 5) {
      return true; // BackOffice sees ALL leads
    }
    if (this.roleId === 3) {
      return lead.invitato_da_user_id === this.userId;
    } else {
      return lead.assegnato_a === this.userId ||
        (lead.user && lead.user.role_id === 3 && lead.user.user_id_padre === this.userId);
    }
  }

  /**
   * Returns the display name for the "Inserito da" column.
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

  private loadLeadsData(): void {
    this.servizioApi.getCombinedData(this.userId).subscribe((data) => {
      const isCliente = this.ruoloUtente === 'Cliente';

      // Store raw data for duplicate checking (BO)
      this.rawLeadsData = data.leads.body.risposta;

      this.Leads = data.leads.body.risposta
        .filter((lead: any) => {
          const myLead = this.isPersonalLead(lead);
          if (isCliente) {
            return myLead && lead.is_converted;
          }
          return myLead;
        })
        .map((Lead: any) => ({
          id: Lead.id,
          nome: Lead.nome,
          cognome: Lead.cognome,
          email: Lead.email,
          telefono: Lead.telefono,
          inserito: Lead.data_inserimento,
          assegnato_a: this.getInseritoDaDisplay(Lead),
          id_assegnato: Lead.user.id,
          stato: Lead.lead_status_id,
          colore: this.isBackOffice
            ? this.getLeadStatusColor(Lead.leadstatus.micro_stato)
            : Lead.leadstatus.color_id,
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
    });
  }

  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
    this.dataSource.sort = this.sort;
  }

  parseDate(dateString: string): Date {
    const [day, month, year] = dateString.split('/');
    return new Date(+year, +month - 1, +day);
  }

  applyFilter() {
    let startDate: any | Date | null = null;
    let endDate: any | Date | null = null;

    if (this.DTRGinseritoil != null || Array.isArray(this.DTRGinseritoil)) {
      if (this.DTRGinseritoil!.length > 1) {
        startDate = this.DTRGinseritoil![0].toLocaleDateString('it-IT', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
        });
        endDate = this.DTRGinseritoil![1]?.toLocaleDateString('it-IT', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
        });
      }
    }

    const filterValue = {
      usLead: this.leadsSelected.map((x: any) => x.nominativoLead),
      statusLead: this.statusSelected.map((x: any) => x.nome),
      seuLead: this.seuSelected.map((x: any) => x.nome),
      inseritoil: {
        start: startDate,
        end: endDate,
      },
    };

    this.dataSource.filterPredicate = (row: any, filter: string) =>
      this.filterUser(row, filter);
    this.dataSource.filter = JSON.stringify(filterValue);
  }

  filterUser(row: any, filter: string) {
    if (!filter) return true;
    const filterObj = JSON.parse(filter);
    const utentiSelezionati = filterObj.usLead || [];
    const statiSelezionati = filterObj.statusLead || [];
    const seuSelezionati = filterObj.seuLead || [];

    let matchDate = true;
    if (filterObj.inseritoil.start && filterObj.inseritoil.end) {
      const startDate = this.parseDate(filterObj.inseritoil.start);
      const endDate = this.parseDate(filterObj.inseritoil.end);
      const rowDate = this.parseDate(row.inserito);
      matchDate = rowDate >= startDate && rowDate <= endDate;
    }

    const matchUtente = !utentiSelezionati.length || utentiSelezionati.includes(row.nominativoLead);
    const matchStato = !statiSelezionati.length || statiSelezionati.includes(row.microstato);
    const matchSeu = !seuSelezionati.length || seuSelezionati.includes(row.assegnato_a);

    return matchUtente && matchStato && matchSeu && matchDate;
  }

  clearFilter() {
    this.leadsSelected = [];
    this.statusSelected = [];
    this.seuSelected = [];
    this.DTRGinseritoil = [];
    this.dataSource.filter = '';
  }

  // =====================================================
  // BACKOFFICE: HERO BUTTON TOGGLE METHODS (ALL INLINE)
  // =====================================================

  toggleLeadsTable(): void {
    this.showLeadsTable = !this.showLeadsTable;
    this.showCreateLead = false;
    this.showNewClient = false;
    this.showQrcode = false;
  }

  toggleCreateLead(): void {
    this.showCreateLead = !this.showCreateLead;
    this.showNewClient = false;
    this.showQrcode = false;
  }

  toggleNewClient(): void {
    this.showNewClient = !this.showNewClient;
    this.showCreateLead = false;
    this.showQrcode = false;
    this.isConvertMode = false;
    this.convertingLeadId = 0;
    this.showDuplicateWarning = false;
    this.duplicateLeadFound = null;
    this.showClientFormError = false;
    this.selectTipoCliente = 'consumer';
    this.newClientForm.reset();
  }

  toggleQrCode(): void {
    this.showQrcode = !this.showQrcode;
    this.showCreateLead = false;
    this.showNewClient = false;
  }

  // =====================================================
  // BACKOFFICE: LEAD CREATION (copied from leads.component)
  // =====================================================

  onConsensoChange(event: any): void {
    this.checked = event.checked;
    this.leadForm.get('consenso')?.setValue(this.checked);
    this.leadForm.get('consenso')?.markAsTouched();
  }

  creaLead(): void {
    console.log('Creating lead:', this.leadForm.value);
    this.servizioApi
      .storeNewLead(this.leadForm.value)
      .subscribe((Risposta: any) => {
        console.log('Lead created:', Risposta);
        this._snackBar.open('Lead creato con successo!', 'OK', {
          duration: 3000, horizontalPosition: 'center', verticalPosition: 'bottom',
        });
        this.showCreateLead = false;
        this.leadForm.reset();
        this.checked = false;
        this.ngOnInit();
      });
  }

  // =====================================================
  // BACKOFFICE: NEW CLIENT CREATION (copied from leads.component)
  // =====================================================

  checkDuplicateLead(): void {
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

    const matchingLead = this.rawLeadsData.find((lead: any) => {
      const leadEmail = (lead.email || '').trim().toLowerCase();
      const leadPhone = (lead.telefono || '').trim();
      const emailMatch = email && leadEmail && email === leadEmail;
      const phoneMatch = telefono && leadPhone && telefono === leadPhone;
      const isLeadOk = lead.leadstatus?.micro_stato === 'Lead OK';
      return (emailMatch || phoneMatch) && isLeadOk;
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
        inserito_da: matchingLead.invited_by
          ? (matchingLead.invited_by.name || '') + ' ' + (matchingLead.invited_by.cognome || '')
          : (matchingLead.user?.name || '') + ' ' + (matchingLead.user?.cognome || ''),
      };
    } else {
      this.showDuplicateWarning = false;
      this.duplicateLeadFound = null;
    }
  }

  dismissDuplicateWarning(): void {
    this.showDuplicateWarning = false;
  }

  proceedWithDuplicateClient(): void {
    this.showDuplicateWarning = false;
    this.createNewClient(true);
  }

  isClientFormValid(): boolean {
    const commonFields = ['email', 'telefono', 'indirizzo', 'provincia', 'citta', 'nazione', 'cap'];
    for (const field of commonFields) {
      const control = this.newClientForm.get(field);
      if (!control || !control.value || control.value.trim() === '') return false;
    }
    if (this.selectTipoCliente === 'consumer') {
      for (const field of ['nome', 'cognome', 'codice_fiscale']) {
        const control = this.newClientForm.get(field);
        if (!control || !control.value || control.value.trim() === '') return false;
      }
    } else {
      for (const field of ['ragione_sociale', 'partita_iva']) {
        const control = this.newClientForm.get(field);
        if (!control || !control.value || control.value.trim() === '') return false;
      }
    }
    return true;
  }

  submitNewClient(): void {
    if (this.showDuplicateWarning) return;
    if (!this.isClientFormValid()) {
      this.showClientFormError = true;
      return;
    }
    this.checkDuplicateLead();
    if (this.showDuplicateWarning) return;
    this.createNewClient(false);
  }

  createNewClient(skipLeadConversion: boolean = false): void {
    this.showClientFormError = true;
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

    if (this.isConvertMode && this.convertingLeadId) {
      clienteData.id_lead = this.convertingLeadId;
      this.servizioApi.nuovoClienteLead(clienteData).subscribe((risultato: any) => {
        if (risultato.response === 'ok') {
          this._snackBar.open('Lead convertito in cliente con successo!', 'OK', {
            duration: 3000, horizontalPosition: 'center', verticalPosition: 'bottom',
          });
          this.resetNewClientForm();
          this.ngOnInit();
        } else {
          this._snackBar.open('CLIENTE GIA PRESENTE!', 'Chiudi', {
            duration: 4000, horizontalPosition: 'center', verticalPosition: 'bottom',
          });
        }
      });
      return;
    }

    if (skipLeadConversion && this.duplicateLeadFound) {
      clienteData.procedure_violation = true;
      clienteData.matching_lead_id = this.duplicateLeadFound.id;
    }

    this.servizioApi.nuovoUtente(clienteData).subscribe((risultato: any) => {
      if (risultato.response === 'ok') {
        this._snackBar.open('Cliente registrato con successo!', 'OK', {
          duration: 3000, horizontalPosition: 'center', verticalPosition: 'bottom',
        });
        this.resetNewClientForm();
        this.ngOnInit();
      } else {
        this._snackBar.open('CLIENTE GIA PRESENTE!', 'Chiudi', {
          duration: 4000, horizontalPosition: 'center', verticalPosition: 'bottom',
        });
      }
    });
  }

  cancelNewClient(): void {
    this.resetNewClientForm();
  }

  private resetNewClientForm(): void {
    this.showNewClient = false;
    this.isConvertMode = false;
    this.convertingLeadId = 0;
    this.showDuplicateWarning = false;
    this.duplicateLeadFound = null;
    this.showClientFormError = false;
    this.newClientForm.reset();
  }

  // =====================================================
  // BACKOFFICE: TABLE ACTION METHODS
  // =====================================================

  dettagliLead(lead: any): void {
    this.dialogRef.open(ContrattoDetailsDialogComponent, {
      data: { reparto: 'lead', lead: lead },
      width: '700px',
    });
  }

  converti(lead: any): void {
    this.isConvertMode = true;
    this.convertingLeadId = lead.id;
    this.showNewClient = true;
    this.showCreateLead = false;
    this.showQrcode = false;
    this.showDuplicateWarning = false;
    this.duplicateLeadFound = null;
    this.showClientFormError = false;
    this.selectTipoCliente = 'consumer';
    this.newClientForm.reset();
    this.newClientForm.patchValue({
      nome: lead.nome || '',
      cognome: lead.cognome || '',
      email: lead.email || '',
      telefono: lead.telefono || '',
    });
  }

  showConvertedSnackbar(lead: any): void {
    this._snackBar.open(
      `Il lead "${lead.nominativoLead}" è già stato convertito in cliente!`,
      'OK',
      { duration: 4000, horizontalPosition: 'center', verticalPosition: 'bottom', panelClass: ['converted-snackbar'] }
    );
  }
}