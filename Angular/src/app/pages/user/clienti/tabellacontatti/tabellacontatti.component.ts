import { Component, OnInit, ViewChild, AfterViewInit } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { trigger, transition, style, animate } from '@angular/animations';

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

  constructor(private servizioApi: ApiService) {}

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

      this.loadLeadsData();
    });
  }

  private loadLeadsData(): void {
    this.servizioApi.getCombinedData(this.userId).subscribe((data) => {
      const isCliente = this.ruoloUtente === 'Cliente';

      // Dashboard Cliente: ONLY converted leads (amici inseriti a sistema)
      // Other roles: all leads as before
      this.Leads = data.leads.body.risposta
        .filter((lead: any) => {
          const isMyLead = lead.invitato_da_user_id === this.userId || lead.user.role_id === 3;
          if (isCliente) {
            return isMyLead && lead.is_converted;
          }
          return isMyLead;
        })
        .map((Lead: any) => ({
          id: Lead.id,
          nome: Lead.nome,
          cognome: Lead.cognome,
          email: Lead.email,
          telefono: Lead.telefono,
          inserito: Lead.data_inserimento,
          assegnato_a: Lead.user.name + ' ' + Lead.user.cognome,
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
        endDate = this.DTRGinseritoil![1].toLocaleDateString('it-IT', {
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
      inserito: {
        start: startDate,
        end: endDate,
      },
    };

    this.dataSource.filterPredicate = this.filterUser.bind(this);
    this.dataSource.filter = JSON.stringify(filterValue);
  }

  clearFilter() {
    this.leadsSelected = [];
    this.statusSelected = [];
    this.seuSelected = [];
    this.DTRGinseritoil = null;
    this.dataSource.filter = '';
  }

  filterUser(row: any, filter: string) {
    if (!filter) {
      return true;
    }

    const filterValue = JSON.parse(filter);
    let pass = true;

    if (filterValue.usLead && filterValue.usLead.length > 0) {
      pass = pass && filterValue.usLead.includes(row.nominativoLead);
    }

    if (filterValue.statusLead && filterValue.statusLead.length > 0) {
      pass = pass && filterValue.statusLead.includes(row.microstato);
    }

    if (filterValue.seuLead && filterValue.seuLead.length > 0) {
      pass = pass && filterValue.seuLead.includes(row.assegnato_a);
    }

    if (filterValue.inserito?.start && filterValue.inserito?.end) {
      const rowDate = this.parseDate(row.inserito);
      const startDate = this.parseDate(filterValue.inserito.start);
      const endDate = this.parseDate(filterValue.inserito.end);
      pass = pass && rowDate >= startDate && rowDate <= endDate;
    }

    return pass;
  }
}