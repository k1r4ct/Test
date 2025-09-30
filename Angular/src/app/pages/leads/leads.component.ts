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
export interface Lead {
  email: string;
  telefono: string;
  id_lead: number;
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
        style({ opacity: 0, transform: 'scale(0.1)' }), // Inizia piccolo al centro
        animate(
          '500ms ease-in-out',
          style({ opacity: 1, transform: 'scale(1)' })
        ), // Espandi e rendi visibile
      ]),
      transition(':leave', [
        animate(
          '500ms ease-in-out',
          style({ opacity: 0, transform: 'scale(0.1)' })
        ), // Riduci e rendi invisibile
      ]),
    ]),
  ],
  standalone: false,
})
export class LeadsComponent implements OnInit {
  @ViewChild('paginator') paginator!: MatPaginator;
  @ViewChild('paginatorSquad') paginatorSquad!: MatPaginator;
  @ViewChild('sort') sort!: MatSort;
  @ViewChild('sortSquad') sortSquad!: MatSort;
  lead: Lead = { email: '', telefono: '', id_lead: 0 };
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

  // ===== PROPRIETÀ ESISTENTI =====

  constructor(
    private servizioApi: ApiService,
    private fb: FormBuilder,
    private dialogRef: MatDialog
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
  }
  onConsensoChange(event: any) {
    //console.log(event);

    this.checked = event.checked;
    this.leadForm.get('consenso')?.setValue(this.checked);
    this.leadForm.get('consenso')?.markAsTouched();
  }
  ngOnInit(): void {
    this.servizioApi.PrendiUtente().subscribe((Ruolo: any) => {
      // console.log(Ruolo);
      this.ruoloUtente = Ruolo.user.qualification.descrizione;
      this.userId = Ruolo.user.id;
      this.roleId = Ruolo.user.role_id;

      if (this.ruoloUtente != 'Cliente') {
        this.showCalendario = false;
        this.showDettagli = false;
      }
    });
    this.servizioApi.getLeads().subscribe((LeadsAll: any) => {
      console.clear();
      console.log(LeadsAll);

      LeadsAll.body.risposta.map((Lead: any) => {
        //console.log(Lead.user.role_id === 3);

        this.Leads = LeadsAll.body.risposta
          .filter(
            (lead: any) =>
              lead.invitato_da_user_id === this.userId ||
              lead.user.role_id === 3
          )
          .map((Lead: any) => ({
            id: Lead.id,
            nome: Lead.nome,
            cognome: Lead.cognome,
            email: Lead.email,
            telefono: Lead.telefono,
            inserito_il: Lead.data_inserimento,
            assegnato_a:
              Lead.user.name || Lead.user.cognome
                ? Lead.user.name + ' ' + Lead.user.cognome
                : Lead.user.ragione_sociale,
            id_assegnato: Lead.user.id,
            stato: Lead.lead_status_id,
            colore: Lead.leadstatus.color_id,
            microstato:
              this.ruoloUtente == 'Cliente' &&
              Lead.leadstatus.micro_stato == 'Lead inserito'
                ? 'Amico Inserito'
                : Lead.leadstatus.micro_stato,
            is_converted: Lead.is_converted,
            nominativoLead: Lead.nome + ' ' + Lead.cognome,
            data_appuntamento:
              Lead.data_appuntamento + ' ' + Lead.ora_appuntamento,
            //stato:Lead.
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

        this.LeadsSquad = LeadsAll.body.risposta
          .filter(
            (lead: any) =>
              lead.invitato_da_user_id != this.userId && lead.user.role_id != 3
          )
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
            colore: Lead.leadstatus.color_id,
            microstato: Lead.leadstatus.micro_stato,
            is_converted: Lead.is_converted,
            nominativoLead: Lead.nome + ' ' + Lead.cognome,
            data_appuntamento:
              Lead.data_appuntamento + ' ' + Lead.ora_appuntamento,
            //stato:Lead.
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

      //this.leadsSelected=this.Leads;
      //console.log(this.dataSource);
      
      // Inizializza il calendario moderno
      this.generateCalendar();
    });
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
        // Ricarica i dati dopo la creazione del lead
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
    // console.log('applica filtri con filterUser');
    // console.log(row.inserito_il);
    // console.log(filter);

    if (!filter) {
      // Controlla se filter è una stringa vuota
      return true; // Nessun filtro attivo, mostra tutte le righe
    }

    const filterObj = JSON.parse(filter);
    //console.log(filterObj);
    //console.log(this.Leads);

    const utentiSelezionati = filterObj.usLead || []; // Inizializza come array vuoto se undefined
    const statiSelezionati = filterObj.statusLead || [];
    const seuSelezionati = filterObj.seuLead || [];

    // Date range handling
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
    //console.log(seuSelezionati);

    return matchUtente && matchStato && matchSeu && matchDate;
  }

  filterUserSquad(row: any, filter: string) {
    // console.log('applica filtri da filterUserSquad');
    // console.log(filter);

    if (!filter) {
      // Controlla se filter è una stringa vuota
      return true; // Nessun filtro attivo, mostra tutte le righe
    }

    const filterObjSquad = JSON.parse(filter);
    //console.log(filterObj);
    //console.log(this.Leads);

    const utentiSelezionatiSquad = filterObjSquad.usLeadSquad || []; // Inizializza come array vuoto se undefined
    const statiSelezionatiSquad = filterObjSquad.statusLeadSquad || [];
    const seuSelezionatiSquad = filterObjSquad.seuLeadSquad || [];

    // Date range handling
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

    // console.log('Filter Object:', filterObjSquad);
    // console.log('seuSelezionati:', seuSelezionatiSquad);
    // console.log('row.assegnato_a:', row.assegnato_a);
    return (
      matchUtenteSquad && matchStatoSquad && matchSeuSquad && matchDateSquad
    );
    //
  }

  applyFilter() {
    // console.log('applyFilter');
    // console.log(this.leadsSelected);
    // console.log(this.statusSelected);
    // console.log(this.seuSelected);
    // console.log(this.DTRGinseritoil);

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
    //console.log(filterValue);

    this.dataSource.filterPredicate = this.filterUser.bind(this);
    this.dataSource.filter = JSON.stringify(filterValue);
    //console.log("datasource filter"+this.dataSource.filter);
  }

  applyFilterSquad() {
    //console.log(this.leadsSelected);
    //console.log(this.statusSelected);

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

    console.log(startDate);
    console.log(endDate);

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
    //console.log(filterValue);

    this.dataSourceSquad.filterPredicate = this.filterUserSquad.bind(this);
    this.dataSourceSquad.filter = JSON.stringify(filterValueSquad);
    //console.log("datasource filter"+this.dataSource.filter);
  }

  showLeadsForDay(array: any) {
    this.allDivCard = true;
    this.showleadsTable = false;
    this.showleadsCard = true;
    this.showCreateLead = false;
    this.showQrcode = false;
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
        microstato: Lead.leadstatus.micro_stato,
        is_converted: Lead.is_converted,
        nominativoLead: Lead.nome + ' ' + Lead.cognome,
        //stato:Lead.
      }));
      //console.log(this.Leads);
    });
  }

  showLeads() {
    this.allDivCard = true;
    this.showleadsTable = !this.showleadsTable ;
    this.showleadsCard = false;
    this.showCreateLead = false;
    this.showQrcode = false;
  }

  ShowCrealeads() {
    this.allDivCard = true;
    this.showCreateLead = !this.showCreateLead;
    this.showleadsTable = false;
    this.showleadsCard = false;
    this.showQrcode = false;
    /* this.divTable.classList.remove("show");
    this.divTable.classList.add("d-none");
    this.divCard.classList.remove("show");
    this.divCard.classList.add("d-none");
    this.divNewLead.classList.remove("d-none");
    this.divNewLead.classList.add("show"); */
  }

  ShowAppQrcode() {
    this.allDivCard = true;
    this.showCreateLead = false;
    this.showleadsTable = false;
    this.showleadsCard = false;
    this.showQrcode = !this.showQrcode;
    /* this.divTable.classList.remove("show");
    this.divTable.classList.add("d-none");
    this.divCard.classList.remove("show");
    this.divCard.classList.add("d-none");
    this.divNewLead.classList.remove("d-none");
    this.divNewLead.classList.add("show"); */
  }

  showCalendarOnly() {
    this.allDivCard = false;
    this.showCreateLead = false;
    this.showleadsTable = false;
    this.showleadsCard = false;
    this.showQrcode = false;
  }
  dettagliLead(lead: any, reparto: string) {
    this.dialogRef.open(ContrattoDetailsDialogComponent, {
      width: 'calc(50% - 50px)',
      enterAnimationDuration: '500ms',
      exitAnimationDuration: '500ms',
      /* position: { left: '20%' }, */
      /* panelClass:['centered-element-spinner' ,'large-spinner'], */
      data: { lead: lead, reparto: reparto },
    });
    //console.log(lead);
  }

  onArrayidLeadChange(newArray: number[]) {
    //console.log("Array ricevuto da CalendarComponent:", newArray);
    this.showLeadsForDay(newArray);
    // Qui puoi utilizzare newArray nel tuo LeadsComponent
  }

  converti(lead: any) {
    //console.log(lead);
    this.nuovocliente = false;
    this.lead = {
      email: lead.email,
      telefono: lead.telefono,
      id_lead: lead.id,
    };
    //console.log(this.lead);
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

    // Inizia dal lunedì della settimana
    startDate.setDate(startDate.getDate() - ((startDate.getDay() + 6) % 7));

    this.calendarDays = [];
    const currentDate = new Date(startDate);

    // Genera 6 settimane (42 giorni)
    for (let i = 0; i < 42; i++) {
      const day = this.createCalendarDay(currentDate, month);
      this.calendarDays.push(day);
      currentDate.setDate(currentDate.getDate() + 1);
    }
  }

  generateWeekView() {
    // Implementazione vista settimana
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
    // Implementazione vista giorno
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

    // Filtra solo i lead con appuntamenti validi
    const leadsWithAppointments = this.Leads.filter(lead => 
      lead.data_appuntamento && 
      lead.data_appuntamento !== null && 
      lead.data_appuntamento !== 'null null' &&
      typeof lead.data_appuntamento === 'string'
    );

    const matchingLeads = leadsWithAppointments.filter(lead => {
      try {
        let leadDate: Date;
        
        // Gestisce il formato "YYYY-MM-DD HH:mm:ss HH:mm:ss"
        if (lead.data_appuntamento.includes(' ') && lead.data_appuntamento.length > 10) {
          const datePart = lead.data_appuntamento.split(' ')[0];
          leadDate = new Date(datePart);
        }
        // Formato DD/MM/YYYY
        else if (lead.data_appuntamento.includes('/')) {
          const parts = lead.data_appuntamento.split('/');
          if (parts.length === 3) {
            leadDate = new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
          } else {
            leadDate = new Date(lead.data_appuntamento);
          }
        } 
        // Altri formati standard
        else {
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
    // Deseleziona tutti gli altri giorni
    this.calendarDays.forEach((d) => (d.isSelected = false));

    // Seleziona il giorno corrente
    day.isSelected = true;

    // Mostra i lead per quel giorno
    if (day.leadsCount > 0) {
      this.showLeadsForDay(day.leads.map((lead: any) => lead.id));
    }
  }

  calculateStats() {
    this.totalLeads = this.Leads.length;

    const today = new Date();
    this.todayLeads = this.getLeadsForDate(today).length;

    // Calcola lead della settimana
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

  // ===== UTILITY METHODS =====

  // ===== METODI ESISTENTI =====

  // ===== METODO PER AGGIORNARE CALENDARIO =====
  refreshCalendar() {
    console.log('Aggiornamento calendario - Lead totali:', this.Leads.length);
    this.generateCalendar();
  }
}
