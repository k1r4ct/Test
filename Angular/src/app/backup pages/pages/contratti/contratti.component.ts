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
  Stato: string;
}

@Component({
    selector: "app-contratti",
    templateUrl: "./contratti.component.html",
    styleUrls: ["./contratti.component.scss"],
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
export class ContrattiComponent implements OnInit {
  @ViewChild(MatPaginator) paginator!: MatPaginator;
    @ViewChild(MatSort) sort!: MatSort;
    displayedColumns: string[] = [
      "id",
      "nominativo/Rag.Sociale",
      "p.iva/cod.fisc",
      "Data Inserimento",
      "Prodotto",
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
  dataSource = new MatTableDataSource<LeadContract>();
  constructor(private dropzoneService: DropzoneService, private ApiService:ApiService) {} // Costruttore (puoi aggiungere dipendenze se necessario)
  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
    this.dataSource.sort = this.sort;
  }
  ngOnInit(): void {
      this.currentUrl=window.location.href;
      this.ApiService.PrendiUtente().subscribe((oggetto: any) => {
        this.User = oggetto.user;
      this.ApiService.getContratti(this.User.id).subscribe((contratti: any) => {
        this.LeadsContract=contratti.body.risposta.map((contratto: any) => ({
          id: contratto.id,
          nomeRag_Sociale: contratto.customer_data.ragione_sociale?contratto.customer_data.ragione_sociale:contratto.customer_data.nome+" "+contratto.customer_data.cognome,
          pIva_CodFisc: contratto.customer_data.partita_iva?contratto.customer_data.partita_iva:contratto.customer_data.codice_fiscale,
          data_Ins: contratto.data_inserimento,
          Prodotto: contratto.product.macro_product.descrizione,
          Stato: contratto.status_contract.micro_stato,
        }));
        //console.log(contratti);
        
        //console.log(this.LeadsContract);
        this.dataSource.data = this.LeadsContract;
      });
      //console.log(this.dataSource.data);
      
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
}


