import { Component, OnInit, ViewChild } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { trigger, transition, style, animate } from '@angular/animations';
export interface LeadContract {
  id: number;
  nomeRag_Sociale: string;
  pIva_CodFisc: string;
  data_Ins: string;
  Prodotto: string;
  seu: string;
  Stato: string;
}
@Component({
  selector: 'app-contratti-personali',
  standalone: false,
  templateUrl: './contratti-personali.component.html',
  styleUrl: './contratti-personali.component.scss',
  animations: [
    trigger('pageTransition', [
      transition(':enter', [
        style({ opacity: 0, transform: 'scale(0.1)' }), // Inizia piccolo al centro
        animate(
          '500ms ease-in-out',
          style({ opacity: 1, transform: 'scale(1)' })
        ), // Espandi e rendi visibile
      ]),
      transition(':leave', [
        style({ opacity: 1, transform: 'scale(1)' }),
        animate(
          '500ms ease-in-out',
          style({ opacity: 0, transform: 'scale(0.1)' })
        ), // Riduci e rendi invisibile
      ]),
    ]),
  ],
})
export class ContrattiPersonaliComponent implements OnInit {
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;
  displayedColumns: string[] = [
    'id',
    'nominativo/Rag.Sociale',
    'p.iva/cod.fisc',
    'Data Inserimento',
    'Prodotto',
    'Seu',
    'Stato',
  ];
  selectedData: any;
  state = 'in'; // Stato iniziale dell'animazione
  hidden = true;
  hidden2 = true;
  plusminus = 'Apri ';
  codicefiscale = true;
  partitaiva = true;
  currentUrl: any;
  matspinner = true;
  User: any;
  LeadsContract: LeadContract[] = [];
  contractSelected: LeadContract[] = [];
  statusContractSelected: LeadContract[] = [];
  pivaCodFisc: LeadContract[] = [];
  dataSource = new MatTableDataSource<LeadContract>();
  UserRole: string = '';
  textLead: string = '';
  codf_Piva: String = '';
  id: number = 0;
  constructor(private servizioApi: ApiService) {}
  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
    this.dataSource.sort = this.sort;
  }
  ngOnInit(): void {
    this.servizioApi.PrendiUtente().subscribe((Ruolo: any) => {
      //console.log(Ruolo.user.codice_fiscale);
      this.id = Ruolo.user.id;
      if (Ruolo.user.codice_fiscale && Ruolo.user.codice_fiscale.length > 0) {
        this.codf_Piva = Ruolo.user.codice_fiscale;
      } else if (Ruolo.user.partita_iva && Ruolo.user.partita_iva.length > 0) {
        this.codf_Piva = Ruolo.user.partita_iva;
      } else if (
        Ruolo.user.codice_fiscale &&
        Ruolo.user.codice_fiscale.length > 0 &&
        Ruolo.user.partita_iva &&
        Ruolo.user.partita_iva.length > 0
      ) {
        this.codf_Piva = Ruolo.user.partita_iva;
      }
      //console.log(this.codf_Piva);
      this.cerca(this.id);
    });
  }
  animationDone(event: any) {
    // Metodo per gestire la fine dell'animazione
    if (event.toState === 'out' && event.phaseName === 'done') {
      // Qui puoi aggiungere la logica per navigare alla pagina successiva o eseguire altre azioni dopo l'animazione
    }
  }
  cerca(id: any) {
    this.servizioApi.ContrattiPersonali(id).subscribe((risultato: any) => {
      console.log('tabella contratti personali cliente');
      console.log(risultato);

      this.LeadsContract = risultato.body.risposta.map((contratto: any) => ({
        id: contratto.id,
        nomeRag_Sociale: contratto.customer_data.ragione_sociale
          ? contratto.customer_data.ragione_sociale
          : contratto.customer_data.nome +
            ' ' +
            contratto.customer_data.cognome,
        pIva_CodFisc: contratto.customer_data.partita_iva
          ? contratto.customer_data.partita_iva
          : contratto.customer_data.codice_fiscale,
        data_Ins: contratto.data_inserimento,
        Prodotto: contratto.product.macro_product.descrizione,
        seu: contratto.user_seu.name + ' ' + contratto.user_seu.cognome,
        Stato: contratto.status_contract.micro_stato,
      }));
      this.dataSource.data = this.LeadsContract;
      console.log(this.LeadsContract);
    });
  }
}
