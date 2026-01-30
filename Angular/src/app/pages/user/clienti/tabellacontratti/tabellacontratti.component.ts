import {
  AfterViewInit,
  Component,
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
import { MatTableDataSource } from "@angular/material/table";
import { MatPaginator } from "@angular/material/paginator";
import { MatSort } from "@angular/material/sort";
import { ApiService } from "src/app/servizi/api.service";

// Extended interface with new fields from specific_data
export interface LeadContract {
  id: number;
  nomeRag_Sociale: string;
  pIva_CodFisc: string;
  indirizzo_fornitura: string;
  data_Ins: string;
  Prodotto: string;
  seu: string;
  Stato: string;
  consumo_kwh: string | null;
  consumo_gm3: string | null;
  cte: string | null;
  pod: string | null;
  data_attivazione: string | null;
}

@Component({
  selector: 'app-tabellacontratti',
  templateUrl: './tabellacontratti.component.html',
  styleUrl: './tabellacontratti.component.scss',
  animations: [
    trigger("pageTransition", [
      transition(":enter", [
        style({ opacity: 0, transform: "scale(0.95)" }),
        animate("400ms ease-out", style({ opacity: 1, transform: "scale(1)" }))
      ]),
      transition(":leave", [
        style({ opacity: 1, transform: "scale(1)" }),
        animate("300ms ease-in", style({ opacity: 0, transform: "scale(0.95)" }))
      ])
    ]),
    // Animation for expandable rows
    trigger("detailExpand", [
      state("collapsed, void", style({ height: "0px", minHeight: "0", opacity: 0 })),
      state("expanded", style({ height: "*", opacity: 1 })),
      transition("expanded <=> collapsed", animate("300ms cubic-bezier(0.4, 0.0, 0.2, 1)")),
      transition("expanded <=> void", animate("300ms cubic-bezier(0.4, 0.0, 0.2, 1)"))
    ])
  ],
  standalone: false
})
export class TabellacontrattiComponent implements OnInit, AfterViewInit {
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;
  
  // Updated displayed columns
  displayedColumns: string[] = [
    "id",
    "nominativo/Rag.Sociale",
    "p.iva/cod.fisc",
    "indirizzo_fornitura",
    "Data Inserimento",
    "Prodotto",
    "Seu",
    "Stato",
  ];
  
  // Additional columns for expanded view
  additionalColumns: string[] = [
    "consumo_kwh",
    "consumo_gm3",
    "cte",
    "pod",
    "data_attivazione"
  ];
  
  selectedData: any;
  state = 'in';
  hidden = true;
  hidden2 = true;
  plusminus = "Apri ";
  codicefiscale = true;
  partitaiva = true;
  currentUrl: any;
  matspinner = true;
  User: any;
  LeadsContract: LeadContract[] = [];
  contractSelected: LeadContract[] = [];
  statusContractSelected: { nome: string }[] = [];
  pivaCodFisc: { valore: string }[] = [];
  indirizzoSelected: { valore: string }[] = [];
  dataSource = new MatTableDataSource<LeadContract>();
  UserRole: string = "";
  textLead: string = "";
  
  // Unique filter options
  statiUnivoci: { nome: string }[] = [];
  pivaCodFiscUnivoci: { valore: string }[] = [];
  indirizziUnivoci: { valore: string }[] = [];
  
  // Toggle for showing additional columns
  showAdditionalInfo = false;
  
  // Expanded rows tracking - allows multiple rows to be expanded
  expandedElements = new Set<number>();

  constructor(private ApiService: ApiService) {}

  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
    this.dataSource.sort = this.sort;
  }

  ngOnInit(): void {
    this.currentUrl = window.location.href;
    this.ApiService.PrendiUtente().subscribe((oggetto: any) => {
      this.User = oggetto.user;
      this.ApiService.getContratti(this.User.id).subscribe((contratti: any) => {
        this.LeadsContract = contratti.body.risposta.data.map((contratto: any) => ({
          id: contratto.id,
          nomeRag_Sociale: contratto.customer_data.ragione_sociale 
            ? contratto.customer_data.ragione_sociale 
            : contratto.customer_data.nome + " " + contratto.customer_data.cognome,
          pIva_CodFisc: contratto.customer_data.partita_iva 
            ? contratto.customer_data.partita_iva 
            : contratto.customer_data.codice_fiscale,
          indirizzo_fornitura: this.buildIndirizzo(contratto.customer_data),
          data_Ins: contratto.data_inserimento,
          Prodotto: contratto.product.macro_product.descrizione,
          seu: contratto.user_seu.name + " " + contratto.user_seu.cognome,
          Stato: contratto.status_contract.micro_stato,
          // Extract new fields from specific_data using EXACT question names from DB
          consumo_kwh: this.getSpecificDataByExactName(contratto.specific_data, 'Consumo Kwh annuo'),
          consumo_gm3: this.getSpecificDataByExactName(contratto.specific_data, 'Consumo gm3 annuo'),
          cte: this.getSpecificDataByExactName(contratto.specific_data, 'CTE'),
          pod: this.getSpecificDataByExactName(contratto.specific_data, 'POD/PDR'),
          data_attivazione: contratto.status_contract_id === 14 ? contratto.updated_at : null,
        }));
        
        // Populate unique filter options
        this.statiUnivoci = [...new Set(this.LeadsContract.map(c => c.Stato))]
          .filter(stato => stato)
          .map(stato => ({ nome: stato }));
        
        this.pivaCodFiscUnivoci = [...new Set(this.LeadsContract.map(c => c.pIva_CodFisc))]
          .filter(piva => piva)
          .map(piva => ({ valore: piva }));
        
        this.indirizziUnivoci = [...new Set(this.LeadsContract.map(c => c.indirizzo_fornitura))]
          .filter(indirizzo => indirizzo && indirizzo !== '-')
          .sort((a, b) => a.localeCompare(b))
          .map(indirizzo => ({ valore: indirizzo }));
        
        this.dataSource.data = this.LeadsContract;
        
        // Re-assign paginator after data loads
        if (this.paginator) {
          this.dataSource.paginator = this.paginator;
        }
        if (this.sort) {
          this.dataSource.sort = this.sort;
        }
        
        this.matspinner = true;
      });
    });
  }

  /**
   * Build complete address from customer_data
   * Combines indirizzo, citta, and provincia
   */
  buildIndirizzo(customerData: any): string {
    if (!customerData) return '-';
    
    const parts: string[] = [];
    
    if (customerData.indirizzo && customerData.indirizzo.trim()) {
      parts.push(customerData.indirizzo.trim());
    }
    
    if (customerData.citta && customerData.citta.trim()) {
      parts.push(customerData.citta.trim());
    }
    
    if (customerData.provincia && customerData.provincia.trim()) {
      parts.push(`(${customerData.provincia.trim().toUpperCase()})`);
    }
    
    return parts.length > 0 ? parts.join(', ') : '-';
  }

  /**
   * Extract value from specific_data array by EXACT question name
   * This is the preferred method as it matches the exact "domanda" field
   */
  getSpecificDataByExactName(specificData: any[], exactName: string): string | null {
    if (!specificData || !Array.isArray(specificData)) {
      return null;
    }

    // Find exact match (case-insensitive)
    const matchingData = specificData.find((item: any) => {
      const domanda = (item.domanda || '').toLowerCase().trim();
      return domanda === exactName.toLowerCase().trim();
    });

    if (!matchingData) {
      return null;
    }

    // Return the appropriate response type
    if (matchingData.risposta_tipo_numero !== null && matchingData.risposta_tipo_numero !== undefined) {
      return matchingData.risposta_tipo_numero.toString();
    }
    if (matchingData.risposta_tipo_stringa !== null && matchingData.risposta_tipo_stringa !== '') {
      return matchingData.risposta_tipo_stringa;
    }
    if (matchingData.risposta_tipo_bool !== null) {
      return matchingData.risposta_tipo_bool ? 'Sì' : 'No';
    }

    return null;
  }

  /**
   * Extract value from specific_data array based on question keywords
   * Searches for questions containing any of the provided keywords
   * Used as fallback when exact name doesn't match
   */
  getSpecificDataValue(specificData: any[], ...keywords: string[]): string | null {
    if (!specificData || !Array.isArray(specificData)) {
      return null;
    }

    // Find the first matching question
    const matchingData = specificData.find((item: any) => {
      const domanda = (item.domanda || '').toLowerCase();
      return keywords.some(keyword => domanda.includes(keyword.toLowerCase()));
    });

    if (!matchingData) {
      return null;
    }

    // Return the appropriate response type
    if (matchingData.risposta_tipo_stringa !== null && matchingData.risposta_tipo_stringa !== '') {
      return matchingData.risposta_tipo_stringa;
    }
    if (matchingData.risposta_tipo_numero !== null) {
      return matchingData.risposta_tipo_numero.toString();
    }
    if (matchingData.risposta_tipo_bool !== null) {
      return matchingData.risposta_tipo_bool ? 'Sì' : 'No';
    }

    return null;
  }

  /**
   * Toggle row expansion - allows multiple rows to stay expanded
   */
  toggleRow(element: LeadContract) {
    if (this.expandedElements.has(element.id)) {
      this.expandedElements.delete(element.id);
    } else {
      this.expandedElements.add(element.id);
    }
  }

  /**
   * Check if a row is expanded
   */
  isExpanded(element: LeadContract): boolean {
    return this.expandedElements.has(element.id);
  }

  /**
   * Toggle additional columns visibility
   */
  toggleAdditionalColumns() {
    this.showAdditionalInfo = !this.showAdditionalInfo;
    
    if (this.showAdditionalInfo) {
      this.displayedColumns = [
        "id",
        "nominativo/Rag.Sociale",
        "p.iva/cod.fisc",
        "indirizzo_fornitura",
        "Data Inserimento",
        "Prodotto",
        "consumo_kwh",
        "consumo_gm3",
        "cte",
        "pod",
        "data_attivazione",
        "Seu",
        "Stato",
      ];
    } else {
      this.displayedColumns = [
        "id",
        "nominativo/Rag.Sociale",
        "p.iva/cod.fisc",
        "indirizzo_fornitura",
        "Data Inserimento",
        "Prodotto",
        "Seu",
        "Stato",
      ];
    }
  }

  prenditipocontratto(event: MatSelectChange) {
    const tipocontratto = event.value;
    this.showmenucontratto(tipocontratto);
  }

  showmenucontratto(tipocontr: any) {
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

  leavePage() {
    this.state = 'out';
  }

  animationDone(event: any) {
    if (event.toState === 'out' && event.phaseName === 'done') {
      // Navigation logic here
    }
  }

  ngOnDestroy() {}

  /**
   * Apply filters including the new address filter
   */
  applyFilter() {
    const filterValue = {
      idSelected: this.contractSelected.map(idSelected => idSelected.id),
      statusSelected: this.statusContractSelected.map((statusSelected: any) => statusSelected.nome),
      PivaCodFisc: this.pivaCodFisc.map((pivaCodFisc: any) => pivaCodFisc.valore),
      indirizzoSelected: this.indirizzoSelected.map((indirizzo: any) => indirizzo.valore),  // NEW
    };

    this.dataSource.filterPredicate = this.filterContract.bind(this);
    this.dataSource.filter = JSON.stringify(filterValue);
  }

  /**
   * Filter predicate including address filter
   */
  filterContract(row: any, filter: string) {
    if (!filter) {
      return true;
    }

    const filterObj = JSON.parse(filter);
    const utentiSelezionati = filterObj.idSelected || [];
    const statiSelezionati = filterObj.statusSelected || [];
    const pivaCodFisc = filterObj.PivaCodFisc || [];
    const indirizziSelezionati = filterObj.indirizzoSelected || [];  // NEW
    
    const matchContratto = !utentiSelezionati.length || utentiSelezionati.includes(row.id);
    const matchStato = !statiSelezionati.length || statiSelezionati.includes(row.Stato);
    const matchPivaCodFisc = !pivaCodFisc.length || pivaCodFisc.includes(row.pIva_CodFisc);
    const matchIndirizzo = !indirizziSelezionati.length || indirizziSelezionati.includes(row.indirizzo_fornitura);  // NEW
    
    return matchContratto && matchStato && matchPivaCodFisc && matchIndirizzo;
  }

  /**
   * Format date for display
   */
  formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('it-IT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    } catch {
      return dateString;
    }
  }

  /**
   * Format number with units
   */
  formatConsumption(value: string | null, unit: string): string {
    if (!value) return '-';
    const numValue = parseFloat(value);
    if (isNaN(numValue)) return value;
    return `${numValue.toLocaleString('it-IT')} ${unit}`;
  }
}