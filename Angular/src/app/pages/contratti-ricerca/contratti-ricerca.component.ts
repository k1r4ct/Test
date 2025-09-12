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
    "pivacf",
    "datains",
    "datastipula",
    "prodotto",
    "seu",
    "stato",
    "azioni"
  ];
  dataSource = new MatTableDataSource<Contratto>(this.LISTACONTRATTI);
  codFpIva: any;
  selectedRow: Contratto | null = null;
  isLoading = false;

  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;
  constructor(
    private contratto: ContrattoService,
    private apiService: ApiService,
    private dialog: MatDialog
  ) {}
  ngOnInit(): void {
    this.getContrattoAndResetValue();
    this.setupTableConfiguration();
    
    // Pulizia iniziale dei backdrop
    this.cleanupBackdrops();
    
    // Debug check per MatDialog
    console.log('MatDialog service nel ngOnInit:', !!this.dialog);
  }

  private setupTableConfiguration(): void {
    // Configurazione della tabella dopo l'inizializzazione
    setTimeout(() => {
      if (this.paginator) {
        this.dataSource.paginator = this.paginator;
      }
      if (this.sort) {
        this.dataSource.sort = this.sort;
      }
    });
  }
  getContrattoAndResetValue() {
    this.isLoading = true;
    this.dataSource.data = [];
    this.LISTACONTRATTI = [];
    this.selectedRow = null;
    
    const formData = new FormData();
    this.contratto
      .getContratto()
      .subscribe((contratto: any) => {
        console.log("dentro contratti ricerca, api get contratto");
        console.log(contratto);
        this.codFpIva = contratto.codFpIvaRicerca.codFPIva;
        formData.append("codFPIva", contratto.codFpIvaRicerca.codFPIva);
        formData.append("tiporicerca", contratto.codFpIvaRicerca.tiporicerca);
        this.populateTable(formData);
      });
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
    this.apiService.getContCodFPIva(formData).subscribe({
      next: (risposta: any) => {
        console.log("lista contratti trovati");
        console.log(risposta);
        
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
        
        // Debug: logga i primi contratti per verificare le date
        if (this.LISTACONTRATTI.length > 0) {
          console.log('Debug date - Primo contratto:', {
            id: this.LISTACONTRATTI[0].id,
            datains_raw: risposta.body.risposta[0]?.data_inserimento,
            datains_processed: this.LISTACONTRATTI[0].datains,
            datastipula_raw: risposta.body.risposta[0]?.data_stipula,
            datastipula_processed: this.LISTACONTRATTI[0].datastipula
          });
        }
        
        this.dataSource.data = this.LISTACONTRATTI;
        this.isLoading = false;
        
        // Configurazione tabella dopo il caricamento dei dati
        setTimeout(() => {
          if (this.dataSource.paginator) {
            this.dataSource.paginator.firstPage();
          }
        });
      },
      error: (error) => {
        console.error('Errore nel caricamento dei contratti:', error);
        this.isLoading = false;
        // Qui potresti aggiungere una notifica di errore
      }
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
    console.log('=== DEBUG MODAL DETTAGLI ===');
    console.log('Pulsante cliccato per contratto ID:', row.id);
    
    // Pulizia preventiva di eventuali backdrop residui
    this.cleanupBackdrops();
    
    // Test con configurazione più robusta per il dialog
    try {
      const dialogRef = this.dialog.open(ContrattoDetailsDialogComponent, {
        width: '80vw',
        maxWidth: '1200px',
        height: 'auto',
        maxHeight: '90vh',
        data: { row: row, reparto: reparto },
        disableClose: false,
        hasBackdrop: true,
        backdropClass: 'custom-backdrop',
        panelClass: 'custom-modal-panel',
        position: {
          top: '5vh'
        },
        autoFocus: true,
        restoreFocus: true
      });
      
      console.log('Dialog REF creato:', dialogRef);
      
      // Gestione della chiusura del dialog
      dialogRef.afterClosed().pipe(take(1)).subscribe(result => {
        console.log('Dialog chiuso:', result);
        // Pulizia backup dei backdrop dopo la chiusura
        setTimeout(() => this.cleanupBackdrops(), 100);
      });
      
      // Verifichiamo subito e poi dopo un timeout
      const checkDialog = () => {
        const dialogElement = document.querySelector('.mat-mdc-dialog-container');
        const backdrop = document.querySelector('.cdk-overlay-backdrop');
        const overlay = document.querySelector('.cdk-overlay-container');
        
        console.log('=== CONTROLLO ELEMENTI DIALOG ===');
        console.log('Dialog container:', dialogElement);
        console.log('Backdrop:', backdrop);
        console.log('Overlay container:', overlay);
        
        if (dialogElement) {
          console.log('Dialog trovato! Stili applicati:', {
            display: getComputedStyle(dialogElement).display,
            visibility: getComputedStyle(dialogElement).visibility,
            zIndex: getComputedStyle(dialogElement).zIndex,
            position: getComputedStyle(dialogElement).position
          });
        }
        
        return !!dialogElement;
      };
      
      // Check immediato
      setTimeout(() => checkDialog(), 100);
      
      // Check dopo 1 secondo
      setTimeout(() => {
        if (!checkDialog()) {
          console.error('PROBLEMA: Dialog ancora non trovato nel DOM!');
          alert('Modal non visualizzato! Controlla gli stili CSS.');
        }
      }, 1000);
      
    } catch (error) {
      console.error('Errore apertura dialog:', error);
    }
  }

  // Metodo per pulire i backdrop problematici
  private cleanupBackdrops(): void {
    // Rimuovi tutti i backdrop trasparenti che potrebbero essere rimasti
    const transparentBackdrops = document.querySelectorAll('.cdk-overlay-transparent-backdrop');
    transparentBackdrops.forEach(backdrop => {
      if (backdrop instanceof HTMLElement) {
        backdrop.style.display = 'none';
        backdrop.style.visibility = 'hidden';
        backdrop.style.pointerEvents = 'none';
      }
    });
    
    // Rimuovi backdrop che non sono della classe custom
    const allBackdrops = document.querySelectorAll('.cdk-overlay-backdrop:not(.custom-backdrop)');
    allBackdrops.forEach(backdrop => {
      if (backdrop instanceof HTMLElement && backdrop.classList.contains('cdk-overlay-transparent-backdrop')) {
        backdrop.style.display = 'none';
        backdrop.style.visibility = 'hidden';
        backdrop.style.pointerEvents = 'none';
      }
    });
  }

  // Nuovi metodi per la versione modernizzata
  selectRow(row: Contratto): void {
    this.selectedRow = this.selectedRow === row ? null : row;
  }

  getStatusColor(macrostato: string): 'primary' | 'accent' | 'warn' | undefined {
    switch (macrostato?.toLowerCase()) {
      case 'attivo':
      case 'validato':
      case 'completato':
        return 'primary';
      case 'in elaborazione':
      case 'pendente':
        return 'accent';
      case 'annullato':
      case 'rifiutato':
      case 'scaduto':
        return 'warn';
      default:
        return undefined;
    }
  }

  getStatusIcon(macrostato: string): string {
    switch (macrostato?.toLowerCase()) {
      case 'attivo':
      case 'validato':
      case 'completato':
        return 'check_circle';
      case 'in elaborazione':
      case 'pendente':
        return 'schedule';
      case 'annullato':
      case 'rifiutato':
        return 'cancel';
      case 'scaduto':
        return 'expired';
      default:
        return 'info';
    }
  }

  // Metodo per refresh dei dati
  refreshData(): void {
    this.getContrattoAndResetValue();
  }

  // Metodo pubblico per pulire i backdrop problematici
  public forceCleanupBackdrops(): void {
    this.cleanupBackdrops();
    console.log('Pulizia manuale dei backdrop eseguita');
  }

  // Metodo per esportazione (da implementare se necessario)
  exportData(): void {
    // Implementazione esportazione dati
    console.log('Esportazione dati in corso...');
  }

  // Metodo per formattare le date
  formatDate(dateValue: any): string {
    if (!dateValue) {
      return 'Non disponibile';
    }
    
    try {
      // Se è già un oggetto Date
      if (dateValue instanceof Date) {
        return this.formatDateToString(dateValue);
      }
      
      // Se è una stringa, proviamo a parsarla
      if (typeof dateValue === 'string') {
        // Gestione diversi formati di data
        let parsedDate: Date;
        
        // Formato ISO (2023-12-25T00:00:00.000Z)
        if (dateValue.includes('T') || dateValue.includes('-')) {
          // Gestione formato dd-mm-yyyy (es: "17-06-2024")
          if (dateValue.match(/^\d{2}-\d{2}-\d{4}$/)) {
            const [day, month, year] = dateValue.split('-');
            parsedDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
          } else {
            parsedDate = new Date(dateValue);
          }
        }
        // Formato italiano (25/12/2023)
        else if (dateValue.includes('/')) {
          const parts = dateValue.split('/');
          if (parts.length === 3) {
            // Assumiamo formato dd/mm/yyyy
            parsedDate = new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
          } else {
            parsedDate = new Date(dateValue);
          }
        }
        // Altri formati
        else {
          parsedDate = new Date(dateValue);
        }
        
        if (isNaN(parsedDate.getTime())) {
          console.warn('Data non valida:', dateValue);
          return dateValue.toString(); // Restituisce la stringa originale se non parsabile
        }
        
        return this.formatDateToString(parsedDate);
      }
      
      // Se è un numero (timestamp)
      if (typeof dateValue === 'number') {
        const parsedDate = new Date(dateValue);
        return this.formatDateToString(parsedDate);
      }
      
      // Fallback: restituisce il valore come stringa
      return dateValue.toString();
      
    } catch (error) {
      console.error('Errore nel parsing della data:', dateValue, error);
      return 'Formato non valido';
    }
  }

  private formatDateToString(date: Date): string {
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
  }
}
