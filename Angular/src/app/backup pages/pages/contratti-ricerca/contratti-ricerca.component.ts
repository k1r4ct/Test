import { Component, OnInit, ViewChild, inject } from "@angular/core";
import { ContrattoService } from "src/app/servizi/contratto.service";
import { ApiService } from "src/app/servizi/api.service";
import { MatTableDataSource } from "@angular/material/table";
import { MatPaginator } from "@angular/material/paginator";
import { MatSort } from "@angular/material/sort";
import {
  MatDialog,
  MatDialogActions,
  MatDialogClose,
  MatDialogContent,
  MatDialogRef,
  MatDialogTitle,
} from "@angular/material/dialog";
import { ContrattoDetailsDialogComponent } from "src/app/modal/modal.component";
import { trigger, transition, style, animate } from "@angular/animations";
import { take } from "rxjs";
export interface Contratto {
  id: number;
  cliente: string;
  contraente: string;
  pivacf: string;
  datains: string;
  datastipula: string;
  prodotto: string;
  seu: string;
  macroprodotto: string;
  macrostato: string;
  stato: string;
  tipo_contratto: string;
  dettagli_contraente: {
    indirizzo: string;
    citta: string;
    cap: string;
    email: string;
    telefono: string;
  }[];
  user: {
    id: string;
    email: string;
    Partita_iva_CodF: string;
    nomeCognome: string;
    ragSociale: string;
  }[];
  specific_data:RispostaSpecificData[];
}
interface RispostaSpecificData {
  domanda: string;
  risposta: string | number | boolean | null; // Può essere uno di questi tipi o null
  tipo: 'text' | 'number' | 'boolean' | 'unknown'; // Tipo della risposta
}
@Component({
    selector: "app-contratti-ricerca",
    animations: [
        trigger("pageTransition", [
            transition(":enter", [
                style({ opacity: 0, transform: "scale(0.1)" }),
                animate("500ms ease-in-out", style({ opacity: 1, transform: "scale(1)" })),
            ]),
            transition(":leave", [
                animate("500ms ease-in-out", style({ opacity: 0, transform: "scale(0.1)" })),
            ]),
        ]),
    ],
    templateUrl: "./contratti-ricerca.component.html",
    styleUrl: "./contratti-ricerca.component.scss",
    standalone: false
})
export class ContrattiRicercaComponent implements OnInit {
  LISTACONTRATTI: Contratto[] = [];
  displayedColumns: string[] = [
    "id",
    "tipo_contratto",
    "cliente/Rag.Sociale",
    "contraente",
    "telefono",
    "pivacf",
    "datains",
    "datastipula",
    "prodotto",
    "seu",
    "macroprodotto",
    "macrostato",
    "stato",
  ];
  dataSource = new MatTableDataSource<Contratto>(this.LISTACONTRATTI);
  codFpIva: any;
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  constructor(
    private contratto: ContrattoService,
    private apiService: ApiService,
    private dialog: MatDialog
  ) {}
  ngOnInit(): void {
    this.getContrattoAndResetValue();
  }
  getContrattoAndResetValue() {
    this.dataSource.data = [];
    this.LISTACONTRATTI = [];
    const formData = new FormData();
    this.contratto
      .getContratto()
      
      .subscribe((contratto: any) => {
        //console.log("dentro contratti ricerca, api get contratto");
        
        //console.log(contratto);
        this.codFpIva = contratto.codFpIvaRicerca.codFPIva;
        formData.append("codFPIva", contratto.codFpIvaRicerca.codFPIva);
        formData.append("tiporicerca", contratto.codFpIvaRicerca.tiporicerca);
      });
    this.populateTable(formData);
  }

  getRisposta(dato: any): string | number | boolean | null {
    if (dato.risposta_tipo_numero !== null) {
      return dato.risposta_tipo_numero;
    } else if (dato.risposta_tipo_stringa !== null) {
      return dato.risposta_tipo_stringa;
    } else if (dato.risposta_tipo_bool !== null) {
      return dato.risposta_tipo_bool;
    } else {
      return null;
    }
  }

  getTipoRisposta(dato: any): 'text' | 'number' | 'boolean' | 'unknown' {
    if (dato.risposta_tipo_numero !== null) {
      return 'number';
    } else if (dato.risposta_tipo_stringa !== null) {
      return 'text';
    } else if (dato.risposta_tipo_bool !== null) {
      return 'boolean';
    } else {
      return 'unknown';
    }
  }
  populateTable(formData: any) {
    this.apiService.getContCodFPIva(formData).subscribe((risposta: any) => {
      //console.log("lista contratti trovati");
      //console.log(risposta);
      this.LISTACONTRATTI = risposta.body.risposta.map((contratto: any) => ({
        id: contratto.id,
        cliente:
          contratto.user.name && contratto.user.cognome
            ? contratto.user.name + " " + contratto.user.cognome
            : contratto.user.ragione_sociale,
        contraente:
          contratto.customer_data.cognome && contratto.customer_data.nome
            ? contratto.customer_data.nome +
              " " +
              contratto.customer_data.cognome
            : contratto.customer_data.ragione_sociale,
        pivacf: contratto.customer_data.codice_fiscale
          ? contratto.customer_data.codice_fiscale
          : contratto.customer_data.partita_iva,
        datains: contratto.data_inserimento,
        datastipula: contratto.data_stipula,
        prodotto: contratto.product.descrizione,
        seu: contratto.user_seu.cognome + " " + contratto.user_seu.name,
        macroprodotto: contratto.product.macro_product.descrizione,
        macrostato:
          contratto.status_contract.option_status_contract[0].macro_stato,
        stato: contratto.status_contract.micro_stato,

        tipo_contratto: contratto.customer_data.ragione_sociale
          ? "Contratto business"
          : "Contratto Consumer",
        dettagli_contraente: {
          indirizzo: contratto.customer_data.indirizzo,
          citta: contratto.customer_data.citta,
          cap: contratto.customer_data.cap,
          email: contratto.customer_data.email,
          telefono: contratto.customer_data.telefono,
        },
        user: {
          id: contratto.user.id,
          email: contratto.user.email,
          Partita_iva_CodF: contratto.user.partita_iva
            ? contratto.user.partita_iva
            : contratto.user.codice_fiscale,
          nomeCognome:
            contratto.user.name && contratto.user.cognome
              ? contratto.user.name + " " + contratto.user.cognome
              : "cliente BUSINESS",
          ragSociale: contratto.user.ragione_sociale
            ? contratto.user.ragione_sociale
            : "cliente CONSUMER",
        },
        specific_data:contratto.specific_data.map((dato: any) => ({
          domanda: dato.domanda,
          risposta: this.getRisposta(dato),
          tipo: this.getTipoRisposta(dato)
        })),
      }));
      this.dataSource.data = this.LISTACONTRATTI; // Aggiorna la tabella
      if (this.dataSource.paginator) {
        this.dataSource.paginator.firstPage();
      }
      //console.log(this.LISTACONTRATTI);
    });
  }
  applyFilter(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    this.dataSource.filter = filterValue.trim().toLowerCase();

    if (this.dataSource.paginator) {
      this.dataSource.paginator.firstPage();
    }
  }

  visualizzaDettagli(row: Contratto, reparto: string) {
    //console.log(row);
    //console.log(this.LISTACONTRATTI);
    this.dialog.open(ContrattoDetailsDialogComponent, {
      width: "calc(50% - 50px)",
      enterAnimationDuration: "500ms",
      exitAnimationDuration: "500ms",
      /* position: { left: '20%' }, */
      /* panelClass:['centered-element-spinner' ,'large-spinner'], */
      data: { row: row, reparto: reparto },
    });
  }
}
