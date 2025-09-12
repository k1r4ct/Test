import {
  AfterViewInit,
  Component,
  DoCheck,
  OnInit,
  ViewChild,
} from "@angular/core";
import {
  trigger,
  state,
  style,
  animate,
  transition,
} from "@angular/animations";
import { MatSelectChange } from "@angular/material/select";
import { MatIconModule } from '@angular/material/icon';
import { MatTableDataSource } from "@angular/material/table"; // Importa MatTableDataSource
import { MatPaginator } from "@angular/material/paginator";
import { MatSort } from "@angular/material/sort";
import { DropzoneService } from "src/app/servizi/dropzone.service";
import { ApiService } from "src/app/servizi/api.service";

export interface LeadContract {
  id:number;
  nomeRag_Sociale: string;
  pIva_CodFisc: string;
  data_Ins: string;
  Prodotto: string;
  seu: string;
  Stato: string;
}

@Component({
    selector: 'app-tabellacontratti',
    templateUrl: './tabellacontratti.component.html',
    styleUrl: './tabellacontratti.component.scss',
    animations: [
        trigger("pageTransition", [
            transition(":enter", [
                style({ opacity: 0, transform: "scale(0.1)" }), // Inizia piccolo al centro
                animate("500ms ease-in-out", style({ opacity: 1, transform: "scale(1)" })) // Espandi e rendi visibile
            ]),
            transition(":leave", [
                style({ opacity: 1, transform: "scale(1)" }),
                animate("500ms ease-in-out", style({ opacity: 0, transform: "scale(0.1)" })) // Riduci e rendi invisibile
            ])
        ])
    ],
    standalone: false
})
export class TabellacontrattiComponent implements OnInit {
  @ViewChild(MatPaginator) paginator!: MatPaginator;
    @ViewChild(MatSort) sort!: MatSort;
    displayedColumns: string[] = [
      "id",
      "nominativo/Rag.Sociale",
      "p.iva/cod.fisc",
      "Data Inserimento",
      "Prodotto",
      "Seu",
      "Stato",
    ];
  selectedData: any;
  state = 'in'; // Stato iniziale dell'animazione
  hidden = true;
  hidden2 = true;
  plusminus = "Apri ";
  codicefiscale = true;
  partitaiva = true;
  currentUrl:any;
  matspinner=true;
  User: any;
  LeadsContract: LeadContract[] = [];
  contractSelected:LeadContract[] = [];
  statusContractSelected:LeadContract[] = [];
  pivaCodFisc:LeadContract[] = [];
  dataSource = new MatTableDataSource<LeadContract>();
  UserRole:string="";
  textLead:string="";
  constructor(private dropzoneService: DropzoneService, private ApiService:ApiService) {} // Costruttore (puoi aggiungere dipendenze se necessario)
  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
    this.dataSource.sort = this.sort;
  }
  ngOnInit(): void {
      this.currentUrl=window.location.href;
      this.ApiService.PrendiUtente().subscribe((oggetto: any) => {
      this.User = oggetto.user.qualification.descrizione;
      this.ApiService.getContratti(this.User.id).subscribe((contratti: any) => {
      this.LeadsContract=contratti.body.risposta.map((contratto: any) => ({
        id: contratto.id,
        nomeRag_Sociale: contratto.customer_data.ragione_sociale?contratto.customer_data.ragione_sociale:contratto.customer_data.nome+" "+contratto.customer_data.cognome,
        pIva_CodFisc: contratto.customer_data.partita_iva?contratto.customer_data.partita_iva:contratto.customer_data.codice_fiscale,
        data_Ins: contratto.data_inserimento,
        Prodotto: contratto.product.macro_product.descrizione,
        seu: contratto.user_seu.name+" "+contratto.user_seu.cognome,
        Stato: contratto.status_contract.micro_stato,
      }));
        this.dataSource.data = this.LeadsContract;
      });
    });
  }
  prenditipocontratto(event: MatSelectChange) {
    const tipocontratto = event.value;
    //console.log(tipocontratto);
    this.showmenucontratto(tipocontratto);
  }

  showmenucontratto(tipocontr: any) {
    //console.log(tipocontr);

    if (this.hidden == true) {
      this.hidden = false;
    }
    if (tipocontr == "Partita iva") {
      this.hidden2 = false;
      this.partitaiva = false;
      this.codicefiscale = true;
    } else if (tipocontr == "Consumer") {
      this.hidden2 = false;
      this.codicefiscale = false;
      this.partitaiva = true;
    }
  }

  leavePage() { // Metodo per attivare l'animazione di uscita
    this.state = 'out';
  }

  animationDone(event: any) { // Metodo per gestire la fine dell'animazione
    if (event.toState === 'out' && event.phaseName === 'done') {
      // Qui puoi aggiungere la logica per navigare alla pagina successiva o eseguire altre azioni dopo l'animazione
    }
  }

  ngOnDestroy() {
    this.dropzoneService.destroyDropzone();
  }

  applyFilter() {
    //console.log(this.leadsSelected);
    //console.log(this.statusSelected);

    const filterValue = {
      idSelected: this.contractSelected.map(idSelected => idSelected.id),
      statusSelected: this.statusContractSelected.map(statusSelected => statusSelected.Stato),
      PivaCodFisc:this.pivaCodFisc.map(pivaCodFisc => pivaCodFisc.pIva_CodFisc),
    };
    //console.log(filterValue);

    this.dataSource.filterPredicate = this.filterContract.bind(this);
    this.dataSource.filter = JSON.stringify(filterValue);
    //console.log("datasource filter"+this.dataSource.filter);
  }

  filterContract(row: any, filter: string) {
    //console.log(filter);

    if (!filter) {
      // Controlla se filter è una stringa vuota
      return true; // Nessun filtro attivo, mostra tutte le righe
    }

    const filterObj = JSON.parse(filter);
    //console.log(filterObj);
    //console.log(this.Leads);

    const utentiSelezionati = filterObj.idSelected || []; // Inizializza come array vuoto se undefined
    const statiSelezionati = filterObj.statusSelected || [];
    const pivaCodFisc = filterObj.PivaCodFisc || [];
    const matchContratto =
      !utentiSelezionati.length ||
      utentiSelezionati.includes(row.id);
    const matchStato =
      !statiSelezionati.length || statiSelezionati.includes(row.Stato);
      const matchPivaCodFisc = !pivaCodFisc.length || pivaCodFisc.includes(row.pIva_CodFisc);
    //console.log(utentiSelezionati.includes(row.nominativoLead));
    //console.log(row.nominativoLead, utentiSelezionati);
    //console.log(row.microstato, statiSelezionati);
    return matchContratto && matchStato && matchPivaCodFisc;
  }
}


