import { Component, OnInit, ViewChild } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { ButtonModule } from 'primeng/button';

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
})
export class TabellacontattiComponent implements OnInit {
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
  statiUnivoci: { nome: string; count: number; nomeConConteggio: string }[] =
    [];
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
      //console.log(this.ruoloUtente);
      this.userId = Ruolo.user.id;
      this.roleId = Ruolo.user.role_id;
    });
    if (this.ruoloUtente == 'Cliente') {
      this.textLead = 'Amici';
    } else {
      this.textLead = 'Leads';
    }
    //console.log(this.textLead);

    this.servizioApi.getCombinedData(this.userId).subscribe((data) => {
      //console.log(data);
      this.Leads = data.leads.body.risposta
        .filter(
          (lead: any) =>
            lead.invitato_da_user_id === this.userId || lead.user.role_id === 3
        )
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
          colore: Lead.leadstatus.colors.colore,
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
      //console.log(this.Leads);
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
    //console.log(this.leadsSelected);
    //console.log(this.statusSelected);
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
      inserito: {
        start: startDate,
        end: endDate,
      },
    };
    //console.log(filterValue);

    this.dataSource.filterPredicate = this.filterUser.bind(this);
    this.dataSource.filter = JSON.stringify(filterValue);
    //console.log("datasource filter"+this.dataSource.filter);
  }

  clearFilter() {
    this.leadsSelected = [];
    this.statusSelected = [];
    this.seuSelected = [];
    this.DTRGinseritoil = null;
    this.dataSource.filter = '';
  }

  filterUser(row: any, filter: string) {
    // console.log('dentro filterUser tabella');
    // console.log(row.inserito);
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
    if (filterObj.inserito.start && filterObj.inserito.end) {
      const startDate = this.parseDate(filterObj.inserito.start);
      const endDate = this.parseDate(filterObj.inserito.end);
      const rowDate = this.parseDate(row.inserito);
      matchDate = rowDate >= startDate && rowDate <= endDate;
    }

    const matchUtente =
      !utentiSelezionati.length ||
      utentiSelezionati.includes(row.nominativoLead);
    const matchStato =
      !statiSelezionati.length || statiSelezionati.includes(row.microstato);
    const matchSeu =
      !seuSelezionati.length || seuSelezionati.includes(row.assegnato_a);
    //console.log(utentiSelezionati.includes(row.nominativoLead));
    //console.log(row.nominativoLead, utentiSelezionati);
    //console.log(row.microstato, statiSelezionati);
    return matchUtente && matchStato && matchSeu && matchDate;
  }
}
