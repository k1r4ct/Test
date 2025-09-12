import {
  AfterViewInit,
  Component,
  OnInit,
  ViewChild,
  ChangeDetectorRef,
  DoCheck,
  AfterContentInit,
} from "@angular/core";
import { MatPaginator } from "@angular/material/paginator";
import { MatSort, Sort } from "@angular/material/sort";
import { MatTableDataSource } from "@angular/material/table";
import { ContrattoService } from "src/app/servizi/contratto.service";
import { SharedService } from "src/app/servizi/shared.service";
import { ApiService } from "src/app/servizi/api.service";
import { BehaviorSubject, isEmpty, Subject } from "rxjs"; // Import BehaviorSubject
import { trigger, transition, style, animate } from "@angular/animations";
import { MatIconRegistry } from "@angular/material/icon";
import { DomSanitizer } from "@angular/platform-browser";
import { FormControl, FormGroup } from "@angular/forms";
import { Router } from "@angular/router";
import { DropzoneComponent } from "ngx-dropzone-wrapper";
import { ActivatedRoute } from "@angular/router";
import { MatDateRangePicker } from "@angular/material/datepicker";
import { RicercaclientiService } from "src/app/servizi/ricercaclienti.service";
import { saveAs } from "file-saver";
import * as Papa from "papaparse";
import { json } from "node:stream/consumers";
export interface UserData {
  id: string;
  name: string;
  progressman: string;
  fruit: string;
}
export interface AltriProdotti {
  id: string;
  descrizione: string;
}
export interface ListContrattiData {
  id: string;
  cliente: string;
  pivacf: string;
  datains: string;
  datastipula: string;
  prodotto: string;
  seu: string;
  macroprodotto: string;
  macrostato: string;
  stato: string;
  file: string[];
}

export interface ListaClienti {
  cliente: string;
}

export interface DettagliContratto {
  inserito_da_user_id: number;
  id: string;
  cliente: string;
  partitaiva: string;
  datains: string;
  macroprodotto: string;
  macroprodotto_id: string;
  microprodotto: string;
  microprodotto_id: string;
  stato: string;
  id_stato: string;
  file: string[];
  ragione_sociale: string;
  macro_stato: string;
  note: string;
  specific_data: RispostaSpecificData[];
  dettagli_contraente: {
    citta: string;
    indirizzo: string;
    cap: string;
    email: string;
    telefono: string;
  }[];
}
export interface EmpFilter {
  name: string;
  options: string[];
  defaultValue: string;
}
export interface optionStatus {
  macro_stato: string;
  id_status: string;
  id_option: string;
  micro_stato: string;
}
export interface SEU {
  id: number;
  nominativo: string;
}
export interface ModificaMassiva {
  id: number;
  nome_ragSociale: string;
  stato_contratto: string;
  macro_prodotto: string;
}
export interface ConteggioProdotti {
  descrizione: string;
  contatore: number;
}
interface MicroStatoItem {
  idMacro: any; // Oppure il tipo specifico dell'ID del macro stato
  microStati: string[];
}
interface MacroProdotti {
  id: string;
  descrizione: string;
  prodottiCollegati: {
    id: string;
    descrizioneProdottoNew: string;
  }[];
}
interface RispostaSpecificData {
  id: number;
  domanda: string;
  risposta: string | number | boolean | null; // Può essere uno di questi tipi o null
  tipo: "text" | "number" | "boolean" | "select" | "unknown"; // Tipo della risposta
}
// ... (costanti FRUITS, NAMES e USERS)

@Component({
  selector: "app-lista-contratti",
  standalone: false,
  templateUrl: "./listaContratti.component.html",
  styleUrl: "./listaContratti.component.scss",
  animations: [
    trigger("pageTransition", [
      transition(":enter", [
        style({ opacity: 0, transform: "scale(0.1)" }),
        animate(
          "500ms ease-in-out",
          style({ opacity: 1, transform: "scale(1)" })
        ),
      ]),
      transition(":leave", [
        animate(
          "500ms ease-in-out",
          style({ opacity: 0, transform: "scale(0.1)" })
        ),
      ]),
    ]),
  ],
})
export class ListaContrattiComponent implements OnInit, DoCheck, AfterViewInit {
  paginationInfo: {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number;
    to: number;
    nextPageUrl: string | null;
    prevPageUrl: string | null;
  } = {
    currentPage: 1,
    lastPage: 1,
    perPage: 50,
    total: 0,
    from: 0,
    to: 0,
    nextPageUrl: null,
    prevPageUrl: null
  };
  DettagliContratto: DettagliContratto[] = [];
  LISTACONTRATTI: ListContrattiData[] = [];
  LISTACLIENTI: ListaClienti[] = [];
  ALTRIPRODOTTI: AltriProdotti[] = [];
  MACROPRODOTTI: MacroProdotti[] = [];
  MODIFICAMASSIVA: ModificaMassiva[] = [];
  CONTEGGIOPRODOTTI: ConteggioProdotti[] = [];
  fileSelezionati: any[] = [];
  visualizzaModificaMassiva = false;
  
  // Proprietà per gestire la ricerca server-side e la cache
  public searchMessage = ''; // Messaggio per l'utente sulla ricerca
  allContrattiCache: Map<number, any[]> = new Map(); // Map per memorizzare i contratti per pagina
  totalCachedContratti: any[] = []; // Array di tutti i contratti memorizzati nella cache
  isLoadingContratti: boolean = false; // Flag per il caricamento
  
  // Proprietà per gestire la vista filtrata
  isFilteredView: boolean = false;
  filteredCount: number = 0;
  
  formID = new FormControl("");
  formCliente = new FormControl("");
  selectAllChecked = false;
  formDataIns = new FormControl("");
  isPickerformDataInsDisabled = false;

  formDataStipula = new FormControl("");
  isPickerformDataStipulaDisabled = false;
  listaSupplier: string[] = [];
  listacfpi: string[] = [];
  listacfpi_bk: string[] = [];
  formCFPI = new FormControl("");

  listaProdotti: string[] = [];
  listaProdotti_bk: string[] = [];
  formProdotti = new FormControl("");
  seu: SEU[] = [];
  seuSelected: any;
  listaSEU: string[] = [];
  listaSEU_bk: string[] = [];
  formSEU = new FormControl("");

  contrattoSelezionato = new FormControl("");

  listaMacroPro: string[] = [];
  listaMacroPro_bk: string[] = [];
  formMacroPro = new FormControl("");

  listaMacroStato: string[] = [];
  listaMacroStato_bk: string[] = [];
  formMacroStato = new FormControl("");

  listaStato: string[] = [];
  listaStato_bk: string[] = [];
  formStato = new FormControl("");

  listaSuppliers: string[] = [];
  listaSupplier_bk: string[] = [];
  formSupplier = new FormControl("");

  // FormControl per i dettagli del contratto
  formMacroProdotto = new FormControl("");
  formMicroProdotto = new FormControl("");
  formStatoAvanzamento = new FormControl("");

  arrayDomandeDaEscludere: string[] = [];
  nuovaspecific_data: any[] = [];

  caricacontratto: boolean = false;  
  idmacroprodottocontratto: number = 0;
  matspinner = true;
  idcontrattosel: number = 0;

  listaStatiAvanzamento: any[] = [];
  listaStatiOption: any[] = [];
  OptionStatus: optionStatus[] = [];
  displayedColumns: string[] = [
    "id",
    "cliente",
    "pivacf",
    "datains",
    "datastipula",
    "prodotto",
    "seu",
    "stato",
    "file",
    "azioni",
  ];
  selected = true;
  opzioneTEXT = "";
  dataSourceFilters!: MatTableDataSource<ListContrattiData>;
  non_modificare_risposta = false;
  selectMulti = false;
  enableMultiSelect = false;
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;
  @ViewChild(DropzoneComponent) dropzoneComponent!: DropzoneComponent; // Referenza al componente figlio Dropzone

  @ViewChild("pickerDataIns") pickerDataIns!: MatDateRangePicker<Date>;
  @ViewChild("pickerDataStipula") pickerDataStipula!: MatDateRangePicker<Date>;

  idContrattoSelezionatoSubject: Subject<number> = new Subject<number>();
  state: any;
  filterSelectObj!: any[];
  //filterDictionary = new Map<string, string[]>();
  filterDictionary = new Map<string, string | string[]>();
  idcontratto: any;
  contrattoselezionato = true;

  private contrattiSubject = new BehaviorSubject<ListContrattiData[]>([]);
  contratti$ = this.contrattiSubject.asObservable();
  User: any;
  NomeTabella: any;
  public countContratti = 0;
  toppings = new FormControl("");
  toppingList: string[] = [
    "Extra cheese",
    "Mushroom",
    "Onion",
    "Pepperoni",
    "Sausage",
    "Tomato",
  ];
  row: any;
  filtroQueryString: any;
  OGGETTO_FILTRICONTRATTI: any;
  macroStati: string[] = [];
  microStatiPerMacroStato: { [macroStato: string]: MicroStatoItem } = {};
  abilitaDownload = false;
  abilitaSelezioneMultipla= false;
  constructor(
    private sharedservice: SharedService,
    private shContratto: ContrattoService,
    private ApiService: ApiService,
    private matIconRegistry: MatIconRegistry,
    private domSanitizer: DomSanitizer,
    private router: Router,
    private activatedRoute: ActivatedRoute,
    private changeDetectorRef: ChangeDetectorRef,
    private ricercaCliente: RicercaclientiService
  ) {
    this.matIconRegistry.addSvgIcon(
      "file-jpg",
      this.domSanitizer.bypassSecurityTrustResourceUrl(
        "assets/icons/file-jpg.svg"
      ) // Percorso dell'icona JPG
    );
    this.matIconRegistry.addSvgIcon(
      "file-png",
      this.domSanitizer.bypassSecurityTrustResourceUrl(
        "assets/icons/file-jpg.svg"
      ) // Percorso dell'icona JPG
    );
    this.matIconRegistry.addSvgIcon(
      "file-pdf",
      this.domSanitizer.bypassSecurityTrustResourceUrl(
        "assets/icons/file-pdf.svg"
      ) // Percorso dell'icona PDF
    );
  }

  recuperaAuth() {
    this.ApiService.PrendiUtente().subscribe((Auth: any) => {
      //console.log(Auth);
      if (Auth.user.role_id == 1 ) {
        this.non_modificare_risposta = false;
        this.abilitaDownload = true;
        this.abilitaSelezioneMultipla = true;
      }else if(Auth.user.role_id == 5){  
        this.non_modificare_risposta = false;
        this.abilitaDownload = false;
        this.abilitaSelezioneMultipla = true;        
      } else {
        this.non_modificare_risposta = true;
        this.abilitaDownload = false;
        this.abilitaSelezioneMultipla = false;
      }
    });
    //console.log("prova");
    // DataInsForm = {
    //   start: new FormControl(new Date()),
    //   end: new FormControl(new Date()),
    // };
    //DataInsForm = new FormControl(new Date())
    // const start = '20/7/2024'; // Formato YYYY-MM-DD
    // const end = '22/7/2024'; // Formato YYYY-MM-DD
    // this.startDateInputDI.nativeElement.value = start;
    // this.endDateInputDI.nativeElement.value = end;
    // //this.startDateInputDI.nativeElement.dispatchEvent(new Event('dateInput'));
    // //this.endDateInputDI.nativeElement.dispatchEvent(new Event('dateInput'));
    // this.changeDetectorRef.detectChanges();
    // setTimeout(() => {
    //   this.changeDetectorRef.detectChanges(); // O emetti gli eventi dateInput
    // });
    // const nuovaDataInizio = new Date('2024/07/20');
    // const nuovaDataFine = new Date('2024/07/27');
    // this.pickerDataIns.select(nuovaDataInizio);
    // this.pickerDataIns.select(nuovaDataFine);
  }

  vaiAClienti() {
    // Naviga alla rotta '/clienti'
    this.router.navigate(["/clienti"]);
  }

  vaiAClientiCodFPiva(codFpiva: any) {
    //console.log(codFpiva);
    this.ricercaCliente.setRicerca(codFpiva);
    this.router.navigate(["/clienti"]);
  }

  getFileIcon(filename: string): string {
    const extension = filename.split(".").pop()?.toLowerCase(); // Ottieni l'estensione del file

    switch (extension) {
      case "jpg":
      case "png":
      case "jpeg":
        return "image";
      case "pdf":
        return "picture_as_pdf";
      default:
        return "insert_drive_file"; // Icona predefinita per altri tipi di file
    }
  }

  trasformaData(dataString: string): string | null {
    // Regex più flessibile per i formati accettati
    const regex = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/;
    const match = dataString.match(regex);

    if (match) {
      let [, giorno, mese, anno] = match;

      // Aggiungi zeri iniziali se necessario
      giorno = giorno.padStart(2, "0");
      mese = mese.padStart(2, "0");

      return `${anno}/${mese}/${giorno}`;
    } else {
      console.error(
        "Formato data non valido. Utilizzare d/M/yyyy, dd/MM/yyyy, ecc."
      );
      return null;
    }
  }

  isDataCompresaTra(data: Date, inizio: Date, fine: Date): boolean {
    const timestampData = data.getTime();
    const timestampInizio = inizio.getTime();
    const timestampFine = fine.getTime();

    return timestampData >= timestampInizio && timestampData <= timestampFine;
  }

  ngOnInit(): void {
    this.recuperaAuth();
    this.ricercaCliente.resetNuovaRicerca();
    this.isLoadingContratti = false;
    this.sharedservice.hideRicercaContratto();

    this.activatedRoute.queryParams.subscribe((params) => {
      //console.log(params);

      if (params["filtro"] && typeof params["filtro"] === "string") {
        // Controlla se il parametro esiste ed è una stringa
        try {
          this.filtroQueryString = JSON.parse(params["filtro"]);
        } catch (error) {
          console.error("Errore nell'analizzare il filtro JSON:", error);
          // Gestisci l'errore, ad esempio impostando filtroQueryString a un valore predefinito
          this.filtroQueryString = null;
        }
      }
      //console.log(this.filtroQueryString);
    });

    this.ApiService.PrendiUtente().subscribe((oggetto: any) => {
      this.User = oggetto.user;
      //console.log(this.User);

      this.ApiService.getContratti(this.User.id).subscribe((contratti: any) => {
        //console.clear();
        //console.log(' LISTA CONTRATTI per ' + this.User.id);
        //console.log(contratti);
        //console.log(contratti.body.risposta);
        this.dataSourceFilters = new MatTableDataSource(this.LISTACONTRATTI);
        if (contratti.body.risposta && contratti.body.risposta.data && contratti.body.risposta.data.length > 0) {
          // Mantieni il loader attivo durante l'elaborazione dei dati iniziali

          this.LISTACLIENTI = contratti.body.risposta.data.map((contratto: any) => ({
            cliente:
              contratto.customer_data.cognome && contratto.customer_data.nome
                ? contratto.customer_data.cognome +
                  " " +
                  contratto.customer_data.nome
                : contratto.customer_data.ragione_sociale, // Fallback se ragione_sociale è nullo
          }));

          let ricavacfpi = contratti.body.risposta.data.map((contratto: any) =>
            contratto.customer_data.codice_fiscale
              ? contratto.customer_data.codice_fiscale
              : contratto.customer_data.partita_iva
          );
          // ordina in ordine alfabetico
          ricavacfpi.sort();
          // togli i duplicati
          this.listacfpi = ricavacfpi.filter((str: any, index: any) => {
            const set = new Set(ricavacfpi.slice(0, index));
            return !set.has(str);
          });
          this.listacfpi_bk = this.listacfpi;
          //console.log(this.listacfpi);

          //console.log(this.seu);

          let ricavaSEU = contratti.body.risposta.data.map(
            (contratto: any) =>
              contratto.user_seu.cognome + " " + contratto.user_seu.name
          );
          // ordina in ordine alfabetico
          ricavaSEU.sort();
          this.listaSEU = ricavaSEU.filter((str: any, index: any) => {
            const set = new Set(ricavaSEU.slice(0, index));
            return !set.has(str);
          });
          //console.log(this.listaSEU);
          this.listaSEU_bk = this.listaSEU;
          this.ApiService.ListaProdotti().subscribe((prodotti: any) => {

            console.log(prodotti);
            let ricavaProdotti = prodotti.body.prodotti.map(
              (prodotto: any) => prodotto.descrizione
            );
            ricavaProdotti.sort();
            // togli i duplicati
            this.listaProdotti = ricavaProdotti.filter((str: any, index: any) => {
              const set = new Set(ricavaProdotti.slice(0, index));
              return !set.has(str);
            });
            this.listaProdotti_bk = this.listaProdotti;
          });

          // ordina in ordine alfabetico
          //console.log(this.listacfpi);
          this.ApiService.GetallMacroProduct().subscribe((response: any) => {
            console.log(response);
            let ricavaMacroPro = response.body.risposta.map(
              (macroProdotto: any) => macroProdotto.descrizione
            );
            // ordina in ordine alfabetico
            ricavaMacroPro.sort();
            // togli i duplicati
            this.listaMacroPro = ricavaMacroPro.filter((str: any, index: any) => {
              const set = new Set(ricavaMacroPro.slice(0, index));
              return !set.has(str);
            });
            this.listaMacroPro_bk = this.listaMacroPro;
          })
          //console.log(this.listacfpi);

          // let ricavaMacSta = contratti.body.risposta.map(
          //   (contratto: any) =>
          //     contratto.status_contract.option_status_contract.map( (opt: any) =>
          //       opt.macro_stato
          //     )
          // );
          //console.log(contratti);
          this.ApiService.getMacroStato().subscribe((response:any)=>{
            console.log(response);
            
            let ricavaMacSta = response.body.risposta.map(
              (stato: any) =>
                stato.macro_stato
            );
            // ordina in ordine alfabetico
            ricavaMacSta.sort();
            console.log(ricavaMacSta);
            this.listaMacroStato = ricavaMacSta.filter((str: any, index: any) => {
              const set = new Set(ricavaMacSta.slice(0, index));
              return !set.has(str);
            });
            this.listaMacroStato_bk = this.listaMacroStato;
          })

          this.ApiService.getStato().subscribe((response:any)=>{
            //console.log(response);
            
            let ricavaStato = response.body.risposta.map(
              (stato: any) => stato.micro_stato
            );
            // ordina in ordine alfabetico
            ricavaStato.sort();
            console.log(ricavaStato);
            
            // togli i duplicati
            this.listaStato = ricavaStato.filter((str: any, index: any) => {
              const set = new Set(ricavaStato.slice(0, index));
              return !set.has(str);
            });
            this.listaStato_bk = this.listaStato;
          });
          //console.log(this.listacfpi);
          //console.log(contratti.body.risposta);
          
          this.ApiService.getSupplier().subscribe((response: any) => {
            //console.log(response);
            
            let ricavaSupplier = response.body.risposta.map(
              (fornitore: any) => fornitore.nome_fornitore
              
            )
            this.listaSuppliers = ricavaSupplier;
            ricavaSupplier.sort();
            this.listaSupplier = ricavaSupplier.filter((str: any, index: any) => {
              const set = new Set(ricavaSupplier.slice(0, index));
              return !set.has(str);
          });
          });
          //console.log(this.listaSupplier);
          this.listaSupplier_bk = this.listaSupplier;

          // lista contratti che va a popolare l'oggetto this.dataSourceFilters = new MatTableDataSource(this.LISTACONTRATTI)
          // e cha ha la sua interfaccia ListContrattiData[] = [];
          // può contenere dati in colonna maggiori rispetto alle colonne visualizzate in tabella ( lato html )
          // che hanno UN EFFETTO in base hai filtri sulla visualizzazione.

          this.LISTACONTRATTI = contratti.body.risposta.data.map(
            (contratto: any) => ({
              id: contratto.id,
              cliente:
                contratto.customer_data.cognome && contratto.customer_data.nome
                  ? contratto.customer_data.cognome +
                    " " +
                    contratto.customer_data.nome
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
                contratto.status_contract.option_status_contract[0]?contratto.status_contract.option_status_contract[0].macro_stato:"",
              stato: contratto.status_contract.micro_stato,
              file: contratti.body.file[contratto.id],
              ragione_sociale: contratto.customer_data.ragione_sociale,
              supplier: contratto.product.supplier.nome_fornitore,
              specific_data: contratto.specific_data || [],
              customer_data: contratto.customer_data || {},
            })
          );

          // console.log(this.LISTACONTRATTI.length);
          //console.log(this.LISTACONTRATTI);
          this.countContratti = this.LISTACONTRATTI.length;
          this.dataSourceFilters = new MatTableDataSource(this.LISTACONTRATTI);
          this.dataSourceFilters.paginator = this.paginator;
          //console.log(this.dataSourceFilters);

          // Imposta il comparatore personalizzato
          this.dataSourceFilters.sortingDataAccessor = (item, property) => {
            switch (property) {
              case "datains":
                //console.log(item.datains);
                //console.log(this.parseDate(item.datains));
                return this.parseDate(item.datains);
              case "datastipula":
                return this.parseDate(item.datastipula);
              default:
                return (item as any)[property];
            }
          };

          if (!this.filtroQueryString || this.filtroQueryString.length == 0) {
            this.dataSourceFilters.sort = this.sort;
            const sortState: Sort = { active: "id", direction: "desc" };
            this.sort.active = sortState.active;
            this.sort.direction = sortState.direction;
            this.sort.sortChange.emit(sortState);
          }
          this.paginationInfo = {
            currentPage: contratti.body.pagination.current_page,
            lastPage: contratti.body.pagination.last_page,
            perPage: contratti.body.pagination.per_page,
            total: contratti.body.pagination.total,
            from: contratti.body.pagination.from,
            to: contratti.body.pagination.to,
            nextPageUrl: contratti.body.pagination.next_page_url,
            prevPageUrl: contratti.body.pagination.prev_page_url,
          };

          this.countContratti = contratti.body.pagination.total; // Usa il totale dalla paginazione
          this.dataSourceFilters = new MatTableDataSource(this.LISTACONTRATTI);
          this.dataSourceFilters.paginator = this.paginator;
          
          // Disattiva il loader dopo aver caricato i dati iniziali
          this.isLoadingContratti = false;
        } else {
          //console.log("Nessun contratto trovato");

          this.isLoadingContratti = false;
        }

        // FUNZIONE DI FILTRO STRUTTURATA PER FAR APPARIRE LE RIGHE PER LA QUALE
        // CAMPO TABELLA CONTIENE I VALORI PASSATI SU FILTER
        // ["NOMECAMPOTABELLA" , ["VALORE1","VALORE2",..]]
        // FILTER VIENE COSTRUITO NELLA FUNZIONE selectOpt()
        this.dataSourceFilters.filterPredicate = ((
          record: any,
          filter: any
        ) => {
          //console.log(filter, record);

          const obj = JSON.parse(filter);
          let isMatch = false;
          let risultatiFiltroORprodotto: boolean[] = [];
          let risultatiFiltroORmacroprodotto: boolean[] = [];
          let risultatiFiltroORmacrostato: boolean[] = [];
          let risultatiFiltroORpivacf: boolean[] = [];
          let risultatiFiltroORstato: boolean[] = [];
          let risultatiFiltroORseu: boolean[] = [];
          let risultatiFiltroORsupplier: boolean[] = [];
          let risultatiFiltroAND: boolean[] = [];
          let cicli = 0;
          let stringEleArray: any;

          for (const item of obj) {
            const field = item[0];
            const valori = item[1];

            if (field == "id") {
              cicli++;
              stringEleArray = record[field as keyof ListContrattiData];

              if (Array.isArray(valori)) {
                // Converti l'ID del record in stringa prima del confronto
                risultatiFiltroAND.push(
                  valori.includes(stringEleArray.toString())
                );
              } else {
                risultatiFiltroAND.push(stringEleArray == valori);
              }
            }

            if (field == "datains") {
              let dtini: any = "";
              let dtfin: any = "";
              let dtver: any = "";

              const sdatain = this.trasformaData(valori[0]);
              if (sdatain != null) {
                dtini = new Date(sdatain);
              }

              const sdatafi = this.trasformaData(valori[1]);
              if (sdatafi != null) {
                dtfin = new Date(sdatafi);
              }

              cicli++;
              stringEleArray = record[field as keyof ListContrattiData];

              const sdataver = this.trasformaData(stringEleArray);
              if (sdataver != null) {
                dtver = new Date(sdataver);
              }

              //isMatch = this.isDataCompresaTra(dtver, dtini, dtfin);
              risultatiFiltroAND.push(
                this.isDataCompresaTra(dtver, dtini, dtfin)
              );
            }

            if (field == "datastipula") {
              let dtini: any = "";
              let dtfin: any = "";
              let dtver: any = "";

              const sdatain = this.trasformaData(valori[0]);
              if (sdatain != null) {
                dtini = new Date(sdatain);
              }

              const sdatafi = this.trasformaData(valori[1]);
              if (sdatafi != null) {
                dtfin = new Date(sdatafi);
              }

              cicli++;
              stringEleArray = record[field as keyof ListContrattiData];

              const sdataver = this.trasformaData(stringEleArray);
              if (sdataver != null) {
                dtver = new Date(sdataver);
              }

              risultatiFiltroAND.push(
                this.isDataCompresaTra(dtver, dtini, dtfin)
              );
            }

            if (field == "cliente") {
              cicli++;
              stringEleArray = record[field as keyof ListContrattiData];
              risultatiFiltroAND.push(
                stringEleArray.toLowerCase().includes(valori)
              );
            }

            if (field == "prodotto") {
              for (let valore of valori) {
                cicli++;
                // if (stringEleArray == valore) {
                //   isMatch = true;
                // }

                stringEleArray = record[field as keyof ListContrattiData];
                risultatiFiltroORprodotto.push(stringEleArray == valore);
              }
              risultatiFiltroAND.push(
                risultatiFiltroORprodotto.some((risultato) => risultato)
              );
            }

            if (field == "macroprodotto") {
              for (let valore of valori) {
                cicli++;
                stringEleArray = record[field as keyof ListContrattiData];
                risultatiFiltroORmacroprodotto.push(stringEleArray == valore);
              }
              risultatiFiltroAND.push(
                risultatiFiltroORmacroprodotto.some((risultato) => risultato)
              );
            }

            if (field == "macrostato") {
              for (let valore of valori) {
                cicli++;
                stringEleArray = record[field as keyof ListContrattiData];
                risultatiFiltroORmacrostato.push(stringEleArray == valore);
              }
              risultatiFiltroAND.push(
                risultatiFiltroORmacrostato.some((risultato) => risultato)
              );
            }

            if (field == "pivacf") {
              for (let valore of valori) {
                cicli++;
                stringEleArray = record[field as keyof ListContrattiData];
                risultatiFiltroORpivacf.push(stringEleArray == valore);
              }
              risultatiFiltroAND.push(
                risultatiFiltroORpivacf.some((risultato) => risultato)
              );
            }

            if (field == "stato") {
              for (let valore of valori) {
                cicli++;
                stringEleArray = record[field as keyof ListContrattiData];
                risultatiFiltroORstato.push(stringEleArray == valore);
              }
              risultatiFiltroAND.push(
                risultatiFiltroORstato.some((risultato) => risultato)
              );
            }

            if (field == "seu") {
              for (let valore of valori) {
                cicli++;
                stringEleArray = record[field as keyof ListContrattiData];
                risultatiFiltroORseu.push(stringEleArray == valore);
              }
              risultatiFiltroAND.push(
                risultatiFiltroORseu.some((risultato) => risultato)
              );
            }

            if (field == "supplier") {
              // Gestisce sia string che array per supplier
              if (Array.isArray(valori)) {
                // Multi-selezione: confronto esatto con i valori nell'array
                for (let valore of valori) {
                  cicli++;
                  stringEleArray = record[field as keyof ListContrattiData];
                  risultatiFiltroORsupplier.push(stringEleArray == valore);
                }
                risultatiFiltroAND.push(
                  risultatiFiltroORsupplier.some((risultato) => risultato)
                );
              } else {
                // Filtro singolo come stringa: confronto esatto
                cicli++;
                stringEleArray = record[field as keyof ListContrattiData];
                risultatiFiltroAND.push(stringEleArray == valori);
              }
            }
          }

          if (cicli == 0) {
            return true;
          }

          isMatch = risultatiFiltroAND.every((risultato) => risultato);
          //console.log(isMatch);

          return isMatch;
        }).bind(this);

        this.contrattiSubject.next(this.LISTACONTRATTI);

        //console.log(this.dataSourceFilters.filter.length);
      });
      //console.log(this.dataSourceFilters);
    });

    this.listaStatiAvanzamento = [];
    this.listaStatiOption = [];
    this.ApiService.getStatiAvanzamento().subscribe((statiAvanzamento: any) => {
      console.log(statiAvanzamento);

      statiAvanzamento.body.risposta.stati_avanzamento.forEach((stato: any) => {
        this.listaStatiAvanzamento.push(stato.micro_stato);
      });
      statiAvanzamento.body.risposta.status_option.forEach((stato: any) => {
        this.listaStatiOption.push(stato.macro_stato);
        //console.log(stato);
      });
      this.OptionStatus = statiAvanzamento.body.risposta.status_option.flatMap(
        (stati: any) =>
          [stati.status_contract].map((sc: any) => ({
            macro_stato: stati.macro_stato,
            id_status: stati.status_contract_id,
            id_option: stati.id,
            micro_stato: sc.micro_stato,
          }))
      );
    });

    //console.log(this.OptionStatus);
    // ... (resto del codice ngOnInit)
  }

  // Funzione per gestire il focus
  onInputFocus(classSel: string) {
    // console.log("focus");
    const targetElement = document.querySelector(
      classSel + " .mat-mdc-text-field-wrapper"
    ) as HTMLElement;
    if (targetElement) {
      targetElement.classList.add("custom-background");
    }
  }

  // Funzione per gestire la perdita di focus
  onInputBlur(classSel: string) {
    // console.log("blur");
    const targetElement = document.querySelector(
      classSel + " .mat-mdc-text-field-wrapper"
    ) as HTMLElement;
    if (targetElement) {
      targetElement.classList.remove("custom-background");
    }
  }

  parseDate(dateString: string): Date {
    const [day, month, year] = dateString.split("/");
    return new Date(+year, +month - 1, +day);
  }

  filterSelectCFPI(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    
    if (filterValue && filterValue.trim() !== '') {
      // Imposta il filtro per la ricerca server-side
      this.filterDictionary.set("pivacf", filterValue.trim());
      //console.log("Filtro CFPI impostato:", filterValue.trim());
    } else {
      this.filterDictionary.delete("pivacf");
      //console.log("Filtro CFPI rimosso");
    }

    // Costruisci la stringa JSON dei filtri
    const jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));
    this.OGGETTO_FILTRICONTRATTI = jsonString;

    //console.log("Filtro CFPI da applicare:", jsonString);
    
    // Applica i filtri specifici con ricerca server-side
    this.applySpecificFilters(jsonString);
  }

  filterSelectProdotti(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    
    // Filtra la lista dei prodotti per la ricerca nella dropdown
    this.listaProdotti = this.listaProdotti_bk;
    this.listaProdotti = this.listaProdotti.filter((item) =>
      item.toLowerCase().includes(filterValue.toLowerCase())
    );
    
    // Non impostare filtri basati sul testo digitato nella ricerca
    // I filtri vengono gestiti dalla selezione effettiva nella dropdown tramite selectOpt
  }

  filterSelectSupplier(event: Event) {
    //console.log(Event);
    
    const filterValue = (event.target as HTMLInputElement).value;
    //console.log("Filtro Supplier:", filterValue);
    
    // Filtra la lista dei supplier per la ricerca nella dropdown
    this.listaSupplier = this.listaSuppliers;
    this.listaSupplier = this.listaSuppliers.filter((item) =>
      item.toLowerCase().includes(filterValue.toLowerCase())
    );
    
    // Non impostare filtri basati sul testo digitato nella ricerca
    // I filtri vengono gestiti dalla selezione effettiva nella dropdown tramite selectOpt
  }

  filterSelectSEU(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    console.log(filterValue);
    
    // Filtra la lista dei prodotti per la ricerca nella dropdown
    this.listaSEU = this.listaSEU_bk;
    this.listaSEU = this.listaSEU.filter((item) =>
      item.toLowerCase().includes(filterValue.toLowerCase())
    );
    
    // Non impostare filtri basati sul testo digitato nella ricerca
    // I filtri vengono gestiti dalla selezione effettiva nella dropdown tramite selectOpt
  }

  filterSelectMacroProdotto(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    
    // Filtra la lista dei macro prodotti per la ricerca nella dropdown
    this.listaMacroPro = this.listaMacroPro_bk;
    this.listaMacroPro = this.listaMacroPro.filter((item) =>
      item.toLowerCase().includes(filterValue.toLowerCase())
    );
    
    // Non impostare filtri basati sul testo digitato nella ricerca
    // I filtri vengono gestiti dalla selezione effettiva nella dropdown tramite selectOpt
  }

  filterSelectMacroStato(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    
    // Filtra la lista dei macro stati per la ricerca nella dropdown
    this.listaMacroStato = this.listaMacroStato_bk;
    this.listaMacroStato = this.listaMacroStato.filter((item) =>
      item.toLowerCase().includes(filterValue.toLowerCase())
    );
    
    // Non impostare filtri basati sul testo digitato nella ricerca
    // I filtri vengono gestiti dalla selezione effettiva nella dropdown tramite selectOpt
  }

  filterSelectStato(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    
    // Filtra la lista degli stati per la ricerca nella dropdown
    this.listaStato = this.listaStato_bk;
    this.listaStato = this.listaStato.filter((item) =>
      item.toLowerCase().includes(filterValue.toLowerCase())
    );
    
    // Non impostare filtri basati sul testo digitato nella ricerca
    // I filtri vengono gestiti dalla selezione effettiva nella dropdown tramite selectOpt
  }

  //ngDoCheck(){}
  ngAfterViewInit() {
    // Configura il listener per gli eventi del paginator
    if (this.paginator) {
      this.paginator.page.subscribe((event: any) => {
        // Calcola la pagina (MatPaginator usa indice 0-based, noi usiamo 1-based)
        const page = event.pageIndex + 1;
        
        // Se la dimensione della pagina è cambiata
        if (event.pageSize !== this.paginationInfo.perPage) {
          this.changePerPage(event.pageSize);
        } else {
          // Altrimenti carica solo la nuova pagina
          this.loadPage(page);
        }
      });
    }

    // Configura il listener per gli eventi di ordinamento
    if (this.sort) {
      // L'ordinamento viene gestito localmente da Angular Material
    }

    if (this.filtroQueryString && this.filtroQueryString.length > 0) {
      this.dataSourceFilters.sort = this.sort;
      const sortState: Sort = { active: "datains", direction: "desc" };
      this.sort.active = sortState.active;
      this.sort.direction = sortState.direction;
      this.sort.sortChange.emit(sortState);

      let filtroStringa = JSON.parse(this.filtroQueryString);

      const oggetto = Object.fromEntries(filtroStringa);

      if (oggetto["datains"] != undefined) {
        // console.log(oggetto['datains'][0]);
        // console.log(oggetto['datains'][1]);

        const datainsINI = this.trasformaData(oggetto["datains"][0]);
        const datainsFIN = this.trasformaData(oggetto["datains"][1]);

        //console.log(datainsINI);
        //console.log(datainsFIN);

        if (datainsINI && datainsFIN) {
          const nuovaDatainsINI = new Date(datainsINI);
          const nuovaDatainsFIN = new Date(datainsFIN);

          this.pickerDataIns.select(nuovaDatainsINI);
          this.pickerDataIns.select(nuovaDatainsFIN);
        }
      } else {
        this.pickerDataIns.disabled = true;
        this.formDataIns.disable();
        this.isPickerformDataInsDisabled = true;
      }

      if (oggetto["datastipula"] != undefined) {
        // console.log(oggetto['datastipula'][0]);
        // console.log(oggetto['datastipula'][1]);

        const datastipulaINI = this.trasformaData(oggetto["datastipula"][0]);
        const datastipulaFIN = this.trasformaData(oggetto["datastipula"][1]);

        //console.log(datastipulaINI);
        //console.log(datastipulaFIN);

        if (datastipulaINI && datastipulaFIN) {
          const nuovadatastipulaINI = new Date(datastipulaINI);
          const nuovadatastipulaFIN = new Date(datastipulaFIN);

          this.pickerDataStipula.select(nuovadatastipulaINI);
          this.pickerDataStipula.select(nuovadatastipulaFIN);
        }
      } else {
        this.pickerDataStipula.disabled = true;
        this.formDataStipula.disable();
        this.isPickerformDataStipulaDisabled = true;
      }
    }
  }

  // ngAfterContentInit() {
  //   const sortState: Sort = {active: 'datains', direction: 'desc'};
  //   this.sort.active = sortState.active;
  //   this.sort.direction = sortState.direction;
  //   this.sort.sortChange.emit(sortState);
  // }

  ngDoCheck() {
    // apllicazione dei filtri se settati su querystring

    if (this.filtroQueryString && this.filtroQueryString.length > 0) {
      let filtroStringa = JSON.parse(this.filtroQueryString);

      // console.log(filtroStringa);
      // if(oggetto["id"]!=undefined){
      //   this.formID.setValue(oggetto['id']);
      // }else{
      //   this.formID.disable();
      // }

      const oggetto = Object.fromEntries(filtroStringa);

      this.formID.disable();
      // console.log(oggetto["seu"]);
      // console.log(oggetto["stato"]);
      // console.log(oggetto["pivacf"]);
      //console.log(oggetto["datains"]);

      if (oggetto["cliente"] != undefined) {
        this.formCliente.setValue(oggetto["cliente"]);
      } else {
        this.formCliente.disable();
      }

      if (oggetto["prodotto"] != undefined) {
        this.formProdotti.setValue(oggetto["prodotto"]);
      } else {
        this.formProdotti.disable();
      }

      if (oggetto["macroprodotto"] != undefined) {
        this.formMacroPro.setValue(oggetto["macroprodotto"]);
      } else {
        this.formMacroPro.disable();
      }

      if (oggetto["pivacf"] != undefined) {
        this.formCFPI.setValue(oggetto["pivacf"]);
      } else {
        this.formCFPI.disable();
      }

      if (oggetto["macrostato"] != undefined) {
        this.formMacroStato.setValue(oggetto["macrostato"]);
      } else {
        this.formMacroStato.disable();
      }

      if (oggetto["seu"] != undefined) {
        this.formSEU.setValue(oggetto["seu"]);
      } else {
        this.formSEU.disable();
      }

      if (oggetto["stato"] != undefined) {
        this.formStato.setValue(oggetto["stato"]);
      } else {
        this.formStato.disable();
      }

      this.OGGETTO_FILTRICONTRATTI = this.filtroQueryString;
      
      // Applica i filtri dalla query string usando la ricerca server-side
      console.log("Applicazione filtri da query string:", this.filtroQueryString);
      this.applySpecificFilters(this.filtroQueryString);

      // Non serve più il filtro locale o firstPage perché la paginazione è gestita lato server
      // this.dataSourceFilters.filter = this.filtroQueryString;
      // if (this.dataSourceFilters.paginator) {
      //   this.dataSourceFilters.paginator.firstPage();
      // }
    } else {
      // Filtro vuoto
      // console.log('Filtro vuoto');
    }

    const contenitore = document.getElementById('domande_non_compilate');
    if (!contenitore) {
      // console.log(' non esiste domande_non_compilate');      
    }else{
      // console.log(' OK esiste domande_non_compilate');
      // L'ordinamento ora è gestito direttamente da Angular nel template
      // Non serve più manipolare il DOM manualmente
    }
  }

  // ngAfterViewInit() {
  //   // this.contratti$.subscribe(contratti => {
  //   //   this.dataSourceFilters = new MatTableDataSource(contratti);
  //   //   this.dataSourceFilters.paginator = this.paginator;
  //   //   this.dataSourceFilters.sort = this.sort;
  //   //   this.changeDetectorRef.detectChanges(); // Forza la rilevazione dei cambiamenti
  //   // });
  // }

  dateRangeChange(
    matStartDate: HTMLInputElement,
    matEndDate: HTMLInputElement,
    fieldTable: string
  ) {
    // console.log(matStartDate);
    // console.log("#############################");

    const startDate = matStartDate.value;
    const endDate = matEndDate.value;
    // console.log('Data di inizio:', startDate);
    // console.log('Data di fine:', endDate);
    // Fai qualcosa con le date selezionate

    // console.log(fieldTable);
    let jsonString: string = "";
    let value: any;

    if (startDate!.length != 0 && endDate!.length != 0) {
      value = [startDate, endDate];
      this.filterDictionary.set(fieldTable, value!);
      //console.log( JSON.stringify(Array.from(this.filterDictionary)));

      this.OGGETTO_FILTRICONTRATTI = JSON.stringify(
        Array.from(this.filterDictionary.entries())
      );
      jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));
      // console.log(jsonString);

      console.log("Filtro date da applicare:", jsonString);
      
      // Applica i filtri specifici con ricerca server-side
      this.applySpecificFilters(jsonString);
      
      // Non serve più impostare il filtro locale o chiamare firstPage
      // perché la paginazione è gestita lato server
    } else {
      // Se le date sono vuote, rimuovi il filtro per questo campo
      this.filterDictionary.delete(fieldTable);
      
      this.OGGETTO_FILTRICONTRATTI = JSON.stringify(
        Array.from(this.filterDictionary.entries())
      );
      jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));
      
      console.log("Filtro date rimosso:", jsonString);
      
      // Applica i filtri aggiornati (senza il filtro date)
      this.applySpecificFilters(jsonString);
    }
  }

  onFormfieldClick(event: any) {
    // console.log(event);
    // Prevent event from bubbling up to the th
    event.stopPropagation();
    // Handle form field click event here (if needed)
    //console.log("Form field clicked!");
  }

  selectOpt(fieldTable: string, value: any) {
    //console.log("colonna " + fieldTable + " valore: " + value );
    //console.log("sono su selectOpt");
    //console.log(fieldTable);
    //console.log(value);

    let jsonString: string = "";

    if (value!.length == 0) {
      this.filterDictionary.delete(fieldTable);
    } else {
      this.filterDictionary.set(fieldTable, value);
      //console.log(this.filterDictionary);
    }

    this.OGGETTO_FILTRICONTRATTI = JSON.stringify(
      Array.from(this.filterDictionary.entries())
    );
    jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));

    console.log("JSON filtri da inviare:", jsonString);
    
    // Rimuoviamo il filtro locale che causava conflitti
    // this.dataSourceFilters.filter = jsonString;
    
    // Applica solo i filtri specifici con ricerca server-side
    this.applySpecificFilters(jsonString);
    
    // Non serve più chiamare firstPage perché la paginazione è gestita lato server
  }

  applyFilterId(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    const separators = /[\s,]+/; // Espressione regolare per virgola, tabulazione e ritorno a capo
    const ids = filterValue
      .split(separators)
      .map((id) => id.trim())
      .filter((id) => id !== "");

    if (ids.length > 0) {
      this.filterDictionary.set("id", ids);
      //console.log("Filtro ID impostato:", ids);
    } else {
      this.filterDictionary.delete("id");
      //console.log("Filtro ID rimosso");
    }

    // Costruisci la stringa JSON dei filtri
    const jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));
    this.OGGETTO_FILTRICONTRATTI = jsonString;

    //console.log("Filtro ID da applicare:", jsonString);
    
    // Applica i filtri specifici con ricerca server-side
    this.applySpecificFilters(jsonString);
  }

  applyFilterName(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    //console.log(filterValue);
    
    if (filterValue && filterValue.trim() !== '') {
      // Imposta il filtro per la ricerca server-side
      this.filterDictionary.set("cliente", filterValue.trim());
      //console.log("Filtro Cliente impostato:", filterValue.trim());
    } else {
      this.filterDictionary.delete("cliente");
      //console.log("Filtro Cliente rimosso");
    }

    // Costruisci la stringa JSON dei filtri
    const jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));
    this.OGGETTO_FILTRICONTRATTI = jsonString;

    console.log("Filtro Cliente da applicare:", jsonString);
    
    // Applica i filtri specifici con ricerca server-side
    this.applySpecificFilters(jsonString);
  }

  applyFilter2(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    //console.log("filer 2");
    //console.log(filterValue);
  }

  applyEmpFilter(ob: any, empfilter: any) {
    //console.log("sono su apply filter");
    // console.log(ob,empfilter);
    // console.log((ob.target as HTMLInputElement).value);
    //this.filterDictionary.set("macroprodotto", ["rosa","luca"]);
    //this.filterDictionary.set("cliente", ["Antonio Russotti","Alessio Scionti"]);
    // var jsonString = JSON.stringify(
    //   Array.from(this.filterDictionary.entries())
    // );
    // console.log(jsonString);
    // this.dataSourceFilters.filter = jsonString;
    // if (this.dataSourceFilters.paginator) {
    //   this.dataSourceFilters.paginator.firstPage();
    // }
  }

  resetFiltri() {
    this.router
      .navigateByUrl("/refresh", { skipLocationChange: false })
      .then(() => {
        this.router.navigate(["/contratti"], {
          queryParams: {
            filtro: null,
          },
        });
      });
  }

  clickedRows(r: any) {
    // Reset delle variabili prima di iniziare il caricamento
    this.nuovaspecific_data = [];
    this.DettagliContratto = [];
    this.fileSelezionati = [];
    this.caricacontratto = true;

    // Forza il change detection per assicurare che i dati precedenti vengano puliti
    this.changeDetectorRef.detectChanges();

    // console.log('dettagli oggetto passato a clickedRows');
    // console.log(r);
    // console.log('dettagli contratto ' + r.id);

    document.getElementById('matspin')?.classList.add('centraspinner');
    document.getElementById('over')?.classList.add('overlay');
    this.matspinner = false;

    this.idcontrattosel = r.id;
    //console.log(this.DettagliContratto);
    //this.contrattoselezionato = false;
    const checkbox = document.getElementById(
      "check-" + r.id
    ) as HTMLInputElement;
    if (checkbox) {
      checkbox.checked = !checkbox.checked;
    }
    if (this.enableMultiSelect) {
      this.isSelectMultiColumn(r, true, checkbox);
    }
    //console.log(Number(r.id));
    if (this.MODIFICAMASSIVA.length > 0) {
      this.visualizzaModificaMassiva = !this.visualizzaModificaMassiva;
      this.contrattoselezionato = true;
    } else {
      this.contrattoselezionato = false;
    }
    this.shContratto.setStatoContratto(true);
    this.shContratto.setIdContratto(Number(r.id));
    this.shContratto.setIdCliente(Number(r.id));
    this.idContrattoSelezionatoSubject.next(r.id);
    const ordineChiaviDettagliContraente = [
      "citta",
      "indirizzo",
      "cap",
      "email",
    ];
    //console.log(this.OptionStatus);
    this.populateselectCambioStatoMassivo();

    //console.log(this.microStatiPerMacroStato);
    this.ApiService.getContratto(r.id).subscribe((contratti: any) => {
      // console.log(' dettaglio da chiamat API');

      // console.clear();
      // console.log(' contratto selezionato ---------------------- ');

      //console.log(contratti.body.risposta[0].specific_data);
      // console.log(contratti.body.risposta[0]);

      // console.log(contratti);
      this.MACROPRODOTTI = contratti.body.macro_prodotti.map(
        (macro_prod: any) => ({
          id: macro_prod.id,
          descrizione: macro_prod.descrizione,
          prodottiCollegati: macro_prod.product,
        })
      );
      this.ALTRIPRODOTTI = contratti.body.altri_prodotti.map(
        (product: any) => ({
          id: product.id,
          descrizione: product.descrizione,
        })
      );

      // estrai la lista delle domande con una risposta non nulla
      // queste domande non andrammo recuperate dalla lista delle domande definite nella sezione domande per macro prodotto
      const listadomandecompilate = contratti.body.risposta[0].specific_data
        .map((dato: any) => {
          const risposta = this.getRisposta(dato);
          if (risposta !== null) {
            return dato.domanda;
          }
          return null;
        })
        .filter((domanda: string | null) => domanda !== null);

      //uso la variabile globare su cui aggiungere la lista delle domande da escludere da recuperare dalla api
      this.arrayDomandeDaEscludere = [];
      this.arrayDomandeDaEscludere = listadomandecompilate;

      this.idmacroprodottocontratto =
        contratti.body.risposta[0].product.macro_product.id;

      //richiamo la funzione che con opzione next che dopo aver recuperato le domande da api per la quale non si è espresso
      //rispota aggiorna automanticamente la parte html sulla base delle domande pushate da api su this.nuovaspecific_data
      this.getDomandeFromApi();

      this.fileSelezionati = contratti.body.file[r.id];
      this.DettagliContratto = contratti.body.risposta.map((contratto: any) => {
        console.log('Dettagli contratto:', contratto);
        
        return {
          inserito_da_user_id: contratto.inserito_da_user_id,
          id: contratto.id,
          cliente: contratto.customer_data.nome
            ? contratto.customer_data.nome +
              " " +
              contratto.customer_data.cognome
            : contratto.customer_data.ragione_sociale,
          partitaiva: contratto.customer_data.codice_fiscale
            ? contratto.customer_data.codice_fiscale
            : contratto.customer_data.partita_iva,
          datains: contratto.data_inserimento,
          microprodotto: contratto.product.descrizione,
          macroprodotto: contratto.product.macro_product.descrizione,
          macroprodotto_id: contratto.product.macro_product.id,
          microprodotto_id: contratto.product.id,
          stato: contratto.status_contract.micro_stato,
          id_stato: contratto.status_contract.id,
          file: contratti.body.file[contratto.id],
          macro_stato: contratti.body.option_status.macro_stato,
          specific_data: contratto.specific_data
            .filter((dato: any) => this.getRisposta(dato) !== null)
            .map((dato: any) => ({
              id: dato.id,
              domanda: dato.domanda,
              risposta: this.getRisposta(dato),
              tipo: this.getTipoRisposta(dato),
            })),
          note: contratto.backoffice_note[contratto.backoffice_note.length - 1]
            ? contratto.backoffice_note[contratto.backoffice_note.length - 1]
                .nota
            : "",
          dettagli_contraente: {
            citta: contratto.customer_data.citta,
            indirizzo: contratto.customer_data.indirizzo,
            cap: contratto.customer_data.cap,
            email: contratto.customer_data.email,
            telefono: contratto.customer_data.telefono,
          },
        };
      });

      // console.log(' dettagli del contratto');
      // console.log(this.DettagliContratto);

      // Forza il change detection per assicurare che Angular rilevi i cambiamenti
      this.changeDetectorRef.detectChanges();

      // Attendiamo un tick prima di rimuovere il loader per dare tempo al DOM di aggiornarsi
      setTimeout(() => {
        // Impostiamo caricacontratto a false per indicare che il caricamento è completato
        this.caricacontratto = false;
        
        document.getElementById('matspin')?.classList.remove('centraspinner');
        document.getElementById('over')?.classList.remove('overlay');
        this.matspinner = true;
        
        // Debug: verifica che i dati siano presenti
        console.log('DettagliContratto dopo aggiornamento:', this.DettagliContratto);
        console.log('nuovaspecific_data dopo aggiornamento:', this.nuovaspecific_data);
        
        // Forza un ulteriore change detection dopo aver impostato caricacontratto = false
        this.changeDetectorRef.detectChanges();
      }, 150);

      // Inizializza le select con i valori correnti del contratto
      if (this.DettagliContratto.length > 0) {
        const contratto = this.DettagliContratto[0];
        
        // Imposta il macro prodotto
        this.formMacroProdotto.setValue(contratto.macroprodotto_id);
        
        // Carica i micro prodotti per il macro prodotto corrente
        this.ApiService.allMacroProduct(contratto.macroprodotto_id).subscribe((newLista: any) => {
          if (newLista.body.risposta && newLista.body.risposta.length > 0) {
            this.ALTRIPRODOTTI = newLista.body.risposta[0].product.map((productNew: any) => ({
              id: productNew.id,
              descrizione: productNew.descrizione,
            }));
            
            // Dopo aver caricato i micro prodotti, imposta il valore selezionato
            this.formMicroProdotto.setValue(contratto.microprodotto_id);
          }
        });
        
        // Imposta lo stato di avanzamento
        this.formStatoAvanzamento.setValue(contratto.id_stato);
      }

      this.ApiService.recuperaSEU().subscribe((SEU: any) => {
        // console.log("recupera lista SEU");
        // console.log(SEU);

        this.seu = SEU.body.risposta.map((allSeu: any) => {
          // console.log(allSeu);

          return {
            id: allSeu.id,
            nominativo:
              allSeu.name || allSeu.cognome
                ? allSeu.name + " " + allSeu.cognome
                : allSeu.ragione_sociale,
          };
        });

        // console.log("contratto inserito da: ");
        // console.log(this.DettagliContratto[0].inserito_da_user_id);

        this.seuSelected = this.seu.find(
          (r) => r.id === this.DettagliContratto[0].inserito_da_user_id
        );
      });
    });

    //console.log(this.seu);
  }

  populate() {
    //console.log(this.seu);
    //console.log(this.seuSelected);
  }

  cambioMacroProdotto(event: Event) {
    this.selected = false;
    this.opzioneTEXT = "(OPZIONE PRECEDENTE)";
    const target = event.target as HTMLSelectElement;
    const selectedId = target.value;
    //console.log(selectedId);
    this.ALTRIPRODOTTI = [];
    this.ApiService.allMacroProduct(selectedId).subscribe((newLista: any) => {
      //console.log(newLista);
      newLista.body.risposta.map((product: any) => {
        this.ALTRIPRODOTTI = product.product.map((productNew: any) => ({
          id: productNew.id,
          descrizione: productNew.descrizione,
        }));
      });
    });
    /* this.MACROPRODOTTI.map((macroProdotto: any) => {
      //console.log(macroProdotto);
      this.ALTRIPRODOTTI=macroProdotto.prodottiCollegati.map((newLista:any)=>({
        id:newLista.id,
        descrizione:newLista.descrizione
      }))
          }); */
    //console.log(this.ALTRIPRODOTTI);
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

  getTipoRisposta(dato: any): "text" | "number" | "boolean" | "select" | "unknown" {
    console.log('dentro risposta tipo valuto dato', dato);
    
    // Prima controlla se esiste il campo tipo_risposta nell'oggetto dato
    if (dato.tipo_risposta == 'numero') {
      return 'number';
    } else if (dato.tipo_risposta == 'stringa' || dato.tipo_risposta == 'text') {
      return 'text';
    } else if (dato.tipo_risposta == 'select') {
      return 'select';
    } else if (dato.tipo_risposta == 'sino') {
      return 'boolean';
    } else {
      return 'unknown';
    }
  }
  deleteFile(file: any) {
    //console.log(file);
    const formData = new FormData();
    formData.append("idContratto", file.id);
    formData.append("nameFileGet", file.name);
    // 1. Chiama la tua API di eliminazione, passando l'ID del file
    this.ApiService.deleteIMG(formData).subscribe((response) => {
      //console.log(response);

      // 2. Gestisci la risposta dell'API (successo o errore)
      if (response.success) {
        // Aggiorna la lista di file rimuovendo il file eliminato
        this.fileSelezionati = this.fileSelezionati.filter(
          (f) => f.id !== file.id
        );
      } else {
        // Mostra un messaggio di errore all'utente
      }
    });
  }

  isImage(filename: string): boolean {
    const extension = filename.split(".").pop()?.toLowerCase();
    return ["jpg", "jpeg", "png", "gif"].includes(extension || "");
  }

  getDomandeFromApi(): void {
    // console.log('chiamata API per aggiornamento template domande con macro prodotto ' + this.idmacroprodottocontratto );
    // passare il codice del macro prodotto del  contratto selezionato
    // console.log(' passaggio dati a getDomandeFromApi');
    // console.log(this.idmacroprodottocontratto);
    // console.log(this.arrayDomandeDaEscludere);

    this.nuovaspecific_data = [];

    this.ApiService.getDomandeMacro(
      this.idmacroprodottocontratto,
      this.arrayDomandeDaEscludere
    ).subscribe({
      next: (Risposta: any) => {
        console.log('Dati ricevuti da getDomandeMacro:', Risposta);
        
        // Aggiorna i dati
        this.nuovaspecific_data = Risposta.ListaDomande || [];
        
        // Forza il change detection per assicurare che Angular rilevi i cambiamenti
        this.changeDetectorRef.detectChanges();
        
        // Debug: verifica i dati ricevuti
        console.log('nuovaspecific_data aggiornato:', this.nuovaspecific_data);
        
        // Piccolo timeout per dare tempo ad Angular di processare i cambiamenti
        setTimeout(() => {
          this.changeDetectorRef.detectChanges();
        }, 50);
      },
      error: (error) => {
        console.error('Errore durante il recupero delle domande:', error);
        this.nuovaspecific_data = [];
        this.changeDetectorRef.detectChanges();
      },
    });
  }

  recupera_opzioni_select(domanda: any, rispostaindicata: any) {
    // console.log(' recupera ozioni risposta ');
    // console.log(domanda.id);
    // console.log(rispostaindicata);

    const selectId = 'select_' + domanda.id;
    const select = document.getElementById(selectId) as HTMLSelectElement;
    if (select) {
      if (select.getAttribute('tag') == '0') {
        select.setAttribute('tag', '1');

        this.ApiService.getRisposteSelect(
          domanda.id,
          rispostaindicata
        ).subscribe({
          next: (Risposta: any) => {
            // console.log('dati ricevuti ', Risposta);

            // ciclare tutti gli elementi della risposta e aggiungerli al select
            Risposta.body.map((risp: any) => {
              // console.log(risp.opzione);
              const option = document.createElement('option');
              option.value = risp.opzione;
              option.text = risp.opzione;
              select.appendChild(option);
            });
          },
          error: (error) => {
            console.error('Errore durante il recupero delle domande:', error);
          },
        });
      }
    }
  }

  updateContratto(id: any) {
    //console.log(id);
    
    // Usa i FormControl per i mat-select
    const statoAvanzamento = this.formStatoAvanzamento.value;
    const macroprodotto = this.formMacroProdotto.value;
    const microprodotto = this.formMicroProdotto.value;

    // Continua a usare document.querySelector per i campi input normali
    const note_backoffice = document.querySelector(
      ".note_backoffice"
    ) as HTMLSelectElement;
    const noteBackoffice = note_backoffice?.value;

    const nomecontraente = document.querySelector(
      ".nome_contraente"
    ) as HTMLSelectElement;
    const nome_contraente = nomecontraente?.value;

    const pivacodfisccontraente = document.querySelector(
      ".pivacodfisc_contraente"
    ) as HTMLSelectElement;
    const pivacodfisc_contraente = pivacodfisccontraente?.value;

    const capp = document.querySelector(".cap") as HTMLSelectElement;
    const cap = capp?.value;
    const city = document.querySelector(".citta") as HTMLSelectElement;
    const citta = city?.value;
    const mail = document.querySelector(".email") as HTMLSelectElement;
    const email = mail?.value;
    const indi = document.querySelector(".indirizzo") as HTMLSelectElement;
    const indirizzo = indi?.value;
    const tel = document.querySelector(".telefono") as HTMLSelectElement;
    const telefono = tel?.value;

    // Raccogli i dati delle domande specifiche
    const specificData = this.collectSpecificData();
    console.log('Dati specific_data raccolti:', specificData);

    const formData = new FormData();
    formData.append("idContratto", id);
    formData.append("stato_avanzamento", statoAvanzamento || "");
    formData.append("note_backoffice", noteBackoffice || "");
    formData.append("nome_contraente", nome_contraente || "");
    formData.append("pivacodfisc_contraente", pivacodfisc_contraente || "");
    formData.append("macroprodotto", macroprodotto || "");
    formData.append("microprodotto", microprodotto || "");
    formData.append("inserito_da", this.seuSelected?.id || "");
    formData.append("cap_contraente", cap || "");
    formData.append("citta_contraente", citta || "");
    formData.append("email_contraente", email || "");
    formData.append("indirizzo_contraente", indirizzo || "");
    formData.append("telefono_contraente", telefono || "");
    
    // Aggiungi i dati delle domande specifiche
    if (specificData && specificData.length > 0) {
      const specificDataJson = JSON.stringify(specificData);
      console.log('JSON specific_data da inviare:', specificDataJson);
      formData.append("specific_data", specificDataJson);
    } else {
      console.log('Nessun dato specific_data da inviare');
    }

    console.log('FormData creato:', formData);

    this.ApiService.updateContratto(formData).subscribe(
      (ContrattoAggiornato: any) => {
        //console.log(ContrattoAggiornato);
        this.aggiornaPagineEsettaFiltri();
      }
    );
  }

  // Funzione trackBy per ottimizzare il rendering delle domande
  trackByDomanda(index: number, domanda: any): any {
    return domanda.id || domanda.domanda || index;
  }

  // Funzione trackBy per ottimizzare il rendering delle risposte
  trackByRisposta(index: number, risposta: any): any {
    return risposta.id || risposta.domanda || index;
  }

  // Funzione per ordinare le domande alfabeticamente
  getSortedDomande(domande: any[]): any[] {
    if (!domande || domande.length === 0) {
      return [];
    }
    
    return [...domande].sort((a, b) => {
      const tagA = a.domanda || '';
      const tagB = b.domanda || '';
      return tagA.localeCompare(tagB);
    });
  }

  // Metodo per raccogliere i dati delle domande specifiche
  collectSpecificData(): any[] {
    const specificDataArray: any[] = [];
    
    console.log('collectSpecificData() chiamato');
    console.log('DettagliContratto:', this.DettagliContratto);
    console.log('nuovaspecific_data:', this.nuovaspecific_data);
    
    // Raccogli i dati dalle domande esistenti (DettagliContratto)
    if (this.DettagliContratto.length > 0) {
      console.log('Processando DettagliContratto[0].specific_data:', this.DettagliContratto[0].specific_data);
      
      this.DettagliContratto[0].specific_data.forEach((item: any) => {
        console.log('Processando item:', item);
        
        // Trova l'elemento input/select corrispondente nel DOM usando il name
        const inputElement = document.querySelector(`[name="${item.domanda}"]`) as HTMLInputElement | HTMLSelectElement;
        console.log(`Elemento con name="${item.domanda}":`, inputElement);
        
        let valore: any = '';
        
        if (inputElement) {
          if (item.tipo === 'boolean') {
            // Per i mat-slide-toggle, leggi la proprietà checked
            valore = (inputElement as any).checked;
          } else {
            valore = inputElement.value;
          }
          
          console.log(`Valore trovato per "${item.domanda}": "${valore}", tipo: "${item.tipo}"`);
          
          // Crea l'oggetto risposta
          const domandaRisposta = {
            id: item.id, // ID della riga esistente per l'aggiornamento
            domanda: item.domanda,
            risposta: valore,
            tipo: item.tipo,
            risposta_tipo_stringa: (item.tipo === 'text' || item.tipo === 'select') ? valore : null,
            risposta_tipo_numero: item.tipo === 'number' ? parseFloat(valore) || 0 : null,
            risposta_tipo_bool: item.tipo === 'boolean' ? valore : null,
            tipo_risposta: this.mapTipoRispostaReverse(item.tipo)
          };
          
          console.log('Aggiunto domandaRisposta:', domandaRisposta);
          specificDataArray.push(domandaRisposta);
        } else {
          console.log(`Elemento input non trovato per domanda: "${item.domanda}"`);
          
          // Anche se non troviamo l'elemento, aggiungiamo il valore esistente per mantenere i dati
          const domandaRisposta = {
            id: item.id,
            domanda: item.domanda,
            risposta: item.risposta, // Usa il valore esistente
            tipo: item.tipo,
            risposta_tipo_stringa: (item.tipo === 'text' || item.tipo === 'select') ? item.risposta : null,
            risposta_tipo_numero: item.tipo === 'number' ? parseFloat(item.risposta) || 0 : null,
            risposta_tipo_bool: item.tipo === 'boolean' ? item.risposta : null,
            tipo_risposta: this.mapTipoRispostaReverse(item.tipo)
          };
          
          console.log('Aggiunto domandaRisposta (valore esistente):', domandaRisposta);
          specificDataArray.push(domandaRisposta);
        }
      });
    } else {
      console.log('DettagliContratto è vuoto');
    }
    
    // Raccogli i dati dalle nuove domande (nuovaspecific_data)
    if (this.nuovaspecific_data && this.nuovaspecific_data.length > 0) {
      console.log('Processando nuovaspecific_data:', this.nuovaspecific_data);
      
      this.nuovaspecific_data.forEach((item: any) => {
        console.log('Processando nuova item:', item);
        
        // Trova l'elemento input/select corrispondente nel DOM
        const inputElement = document.querySelector(`[name="${item.id}"]`) as HTMLInputElement | HTMLSelectElement;
        console.log(`Elemento con name="${item.id}":`, inputElement);
        
        if (inputElement) {
          let valore = inputElement.value;
          let tipo = this.mapTipoRisposta(item.tipo_risposta || item.tipoRisposta);
          
          console.log(`Valore trovato per "${item.domanda}": "${valore}", tipo: "${tipo}"`);
          
          const domandaRisposta = {
            id: null, // Nuova riga, non ha ID esistente
            domanda: item.domanda,
            risposta: valore,
            tipo: tipo,
            risposta_tipo_stringa: (tipo === 'text' || tipo === 'select') ? valore : null,
            risposta_tipo_numero: tipo === 'number' ? parseFloat(valore) || 0 : null,
            risposta_tipo_bool: tipo === 'boolean' ? (valore === 'true' || valore === '1' || valore === 'si') : null,
            tipo_risposta: item.tipo_risposta || item.tipoRisposta || this.mapTipoRispostaReverse(tipo)
          };
          
          console.log('Aggiunto nuova domandaRisposta:', domandaRisposta);
          specificDataArray.push(domandaRisposta);
        } else {
          console.log(`Elemento input non trovato per nuova domanda: "${item.domanda}"`);
        }
      });
    } else {
      console.log('nuovaspecific_data è vuoto o non definito');
    }
    
    console.log('specificDataArray finale:', specificDataArray);
    return specificDataArray;
  }

  // Metodo helper per mappare il tipo di risposta
  mapTipoRisposta(tipoNumerico: number | string): string {
    if (typeof tipoNumerico === 'string') {
      return tipoNumerico; // già in formato stringa
    }
    
    // Mappa i valori numerici alle stringhe come nell'enum TipoRisposta
    switch (tipoNumerico) {
      case 0: return 'stringa';
      case 1: return 'sino';
      case 2: return 'data';
      case 3: return 'select';
      case 4: return 'numero';
      default: return 'stringa';
    }
  }

  // Metodo helper per mappare il tipo di risposta dal frontend al backend
  mapTipoRispostaReverse(tipoFrontend: string): number {
    switch (tipoFrontend) {
      case 'text': return 0; // stringa
      case 'boolean': return 1; // sino
      case 'date': return 2; // data
      case 'select': return 3; // select
      case 'number': return 4; // numero
      default: return 0; // stringa
    }
  }

  getMicroStatiPerMacroStato(macroStato: string): optionStatus[] {
    
    //console.log(this.OptionStatus);
    return this.OptionStatus.filter(
      (stato) => stato.macro_stato === macroStato
    );
  }

  getMacroStati(macroStato: string) {
    this.macroStati = this.OptionStatus.reduce((acc: string[], stato: any) => {
      if (!acc.includes(stato.macro_stato)) {
        acc.push(stato.macro_stato);
      }
      return acc;
    }, []);
  }

  SelectMulti() {
    this.MODIFICAMASSIVA = [];
    this.selectMulti = !this.selectMulti;
    this.enableMultiSelect = !this.enableMultiSelect;
    // Aggiorna displayedColumns
    if (this.selectMulti) {
      this.displayedColumns = [
        "selectMulti",
        ...this.displayedColumns.filter((col) => col !== "selectMulti"),
      ];
    } else {
      this.displayedColumns = this.displayedColumns.filter(
        (col) => col !== "selectMulti"
      );
    }
  }

  contaMacroProdottoSelMassiva(macroProdotto: string): number {
    return this.MODIFICAMASSIVA.filter(
      (item) => item.macro_prodotto === macroProdotto
    ).length;
  }

  confronto(macroProdotto: string) {
    const cont2 = this.CONTEGGIOPRODOTTI.find(
      (item) => item.descrizione === macroProdotto
    );
    return cont2;
  }

  contaMacroProdottoContatore(macroProdotto: string): number {
    return this.CONTEGGIOPRODOTTI.filter(
      (item) => item.descrizione === macroProdotto
    ).length;
  }

  isSelectMultiColumn(
    row: any,
    enable: boolean,
    checkbox: HTMLInputElement
  ): any {
    // Controllo di sicurezza: non permettere la selezione per stati specifici
    if (row.stato === 'Gettonato' || row.stato === 'Stornato') {
      // Se il checkbox è presente, assicurati che rimanga non selezionato
      if (checkbox) {
        checkbox.checked = false;
      }
      return;
    }
    
    if (checkbox) {
      //console.log(checkbox.checked);
      if (checkbox.checked) {
        //console.log("checked");

        checkbox.checked = enable;
      } else {
        //console.log("not checked");

        checkbox.checked = !enable;
      }
    }
    if (enable) {
      const nuovaModifica: ModificaMassiva = {
        id: row.id,
        nome_ragSociale: row.cliente,
        stato_contratto: row.stato,
        macro_prodotto: row.macroprodotto,
      };

      const controllo = this.MODIFICAMASSIVA.find((item) => item.id === row.id);

      //console.log(controllo);
      if (!controllo) {
        this.MODIFICAMASSIVA.push(nuovaModifica);
      } else {
        const index = this.MODIFICAMASSIVA.findIndex(
          (item) => item.id === controllo.id
        );
        if (index > -1) {
          this.MODIFICAMASSIVA.splice(index, 1);
        }
      }
      //console.log(this.MODIFICAMASSIVA);
      const verificaPresenzaContatoreProdotto =
        this.contaMacroProdottoContatore(row.macroprodotto);
      const verificaPresenzaModMassiva = this.contaMacroProdottoSelMassiva(
        row.macroprodotto
      );
      const verifica = this.confronto(row.macroprodotto);
      if (
        verificaPresenzaModMassiva > 0 &&
        verificaPresenzaContatoreProdotto <= 0
      ) {
        const inserimentoProdotti: ConteggioProdotti = {
          descrizione: row.macroprodotto,
          contatore: this.contaMacroProdottoSelMassiva(row.macroprodotto),
        };
        this.CONTEGGIOPRODOTTI.push(inserimentoProdotti);
      }
      if (verifica) {
        if (verificaPresenzaModMassiva > verifica.contatore) {
          verifica.contatore = this.contaMacroProdottoSelMassiva(
            row.macroprodotto
          );
        }
        if (verifica.contatore > verificaPresenzaModMassiva) {
          verifica.contatore = this.contaMacroProdottoSelMassiva(
            row.macroprodotto
          );
        }
        if (verificaPresenzaContatoreProdotto > verificaPresenzaModMassiva) {
          const index = this.CONTEGGIOPRODOTTI.findIndex(
            (item) => item.descrizione === row.macroprodotto
          );
          if (index > -1) {
            this.CONTEGGIOPRODOTTI.splice(index, 1);
          }
        }
      }
      //console.log(this.CONTEGGIOPRODOTTI);

      if (this.MODIFICAMASSIVA.length > 0) {
        this.visualizzaModificaMassiva = !this.visualizzaModificaMassiva;
        this.contrattoselezionato = true;
      } else {
        this.contrattoselezionato = false;
      }
      //this.clickedRows(row);
      this.populateselectCambioStatoMassivo();
    }
  }

  updateStatiMassivi() {
    //console.log(this.MODIFICAMASSIVA);
    const stato_avanzamento_m = document.querySelector(
      ".stato_avanzamento_massivo"
    ) as HTMLSelectElement; // Trova l'elemento select
    const statoAvanzamento = stato_avanzamento_m?.value;
    const contrattiAll = JSON.stringify(this.MODIFICAMASSIVA);
    //console.log(contrattiAll);

    const formDataModificaMassiva = new FormData();

    formDataModificaMassiva.append("contratti", contrattiAll);
    formDataModificaMassiva.append("nuovostato", statoAvanzamento);

    this.ApiService.updateStatoMassivoContratti(
      formDataModificaMassiva
    ).subscribe((Risposta: any) => {
      //console.log(Risposta);
      if (Risposta.status == 200) {
        window.location.reload();
        this.aggiornaPagineEsettaFiltri();
      }
    });
  }

  aggiornaPagineEsettaFiltri() {
    let statofiltropieno: boolean = false;

    if (this.OGGETTO_FILTRICONTRATTI == undefined) {
      // console.log("oggetto filtri undefined");
      statofiltropieno = false;
    } else {
      if (this.OGGETTO_FILTRICONTRATTI.length <= 2) {
        statofiltropieno = false;
        // console.log("oggetto filtri vuoto");
      } else {
        statofiltropieno = true;
        // console.log("oggetto filtri pieno");
      }
    }
    //console.log(statofiltropieno);
    //console.log(this.OGGETTO_FILTRICONTRATTI);

    if (statofiltropieno) {
      const filtroSerializzato = JSON.stringify(this.OGGETTO_FILTRICONTRATTI);
      this.router
        .navigateByUrl("/refresh", { skipLocationChange: false })
        .then(() => {
          this.router.navigate(["/contratti"], {
            queryParams: {
              filtro: filtroSerializzato,
            },
          });
        });
    } else {
      this.router
        .navigateByUrl("/refresh", { skipLocationChange: false })
        .then(() => {
          this.router.navigate(["/contratti"]);
        });
    }
  }
  async esportaCSV() {
    try {
      const datiDaEsportare = this.dataSourceFilters.filteredData;

      // 1. Trova tutte le possibili domande
      const tutteLeDomande = new Set<string>();
      datiDaEsportare.forEach((contratto: any) => {
        contratto.specific_data?.forEach((s: any) => {
          tutteLeDomande.add(s.domanda);
        });
      });
      const domandeArray = Array.from(tutteLeDomande);

      // 2. Costruisci i dati del CSV riga per riga
      const risultatiFinali = datiDaEsportare.map((contratto: any) => {
        const dettagli = contratto.customer_data || {};
        const row: any = {
          id: contratto.id,
          cliente: contratto.cliente,
          pivacf: contratto.pivacf,
          datains: contratto.datains,
          datastipula: contratto.datastipula,
          prodotto: contratto.prodotto,
          macroprodotto: contratto.macroprodotto,
          seu: contratto.seu,
          stato: contratto.stato,
          email: dettagli.email ?? "",
          telefono: dettagli.telefono ?? "",
          indirizzo: dettagli.indirizzo ?? "",
          cap: dettagli.cap ?? "",
          citta: dettagli.citta ?? "",
        };

        // 3. Aggiungi ogni risposta in una colonna dedicata
        domandeArray.forEach((domanda) => {
          const rispostaObj = contratto.specific_data?.find(
            (s: any) => s.domanda === domanda
          );
          const risposta =
            rispostaObj?.risposta_tipo_stringa ??
            rispostaObj?.risposta_tipo_numero ??
            (rispostaObj?.risposta_tipo_bool != null
              ? rispostaObj.risposta_tipo_bool
                ? "Sì"
                : "No"
              : "");

          row[domanda] = risposta ?? "";
        });

        return row;
      });

      // 4. Genera ed esporta il CSV
      const csv = Papa.unparse(risultatiFinali, { delimiter: ";" });
      const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
      saveAs(blob, "contratti_completi.csv");
    } catch (error) {
      console.error("Errore durante l'esportazione CSV:", error);
    }
  }

  populateselectCambioStatoMassivo() {
    this.OptionStatus.forEach((stato: any) => {
      //console.log(stato);

      const macroStato = stato.macro_stato;
      const idMacro = stato.id_status; // Ottieni l'ID del macro stato

      if (!this.microStatiPerMacroStato[macroStato]) {
        this.microStatiPerMacroStato[macroStato] = {
          idMacro: idMacro,
          microStati: [],
        };
      }
      this.microStatiPerMacroStato[macroStato].microStati.push(
        stato.micro_stato
      );
    });
  }
  toggleSelectAll(event: any) {
    this.populateselectCambioStatoMassivo();
    //console.log(event.checked);

    this.selectAllChecked = event.checked;
    setTimeout(() => {
      this.dataSourceFilters.filteredData.forEach((row) => {
        // Salta i contratti con stati non selezionabili
        if (row.stato === 'Gettonato' || row.stato === 'Stornato') {
          return;
        }
        
        // Chiama isSelectMultiColumn solo se selectAllChecked è true
        if (this.selectAllChecked) {
          this.isSelectMultiColumn(
            row,
            this.selectAllChecked,
            document.getElementById("check-" + row.id) as HTMLInputElement
          );
        }
        const checkbox = document.getElementById(
          "check-" + row.id
        ) as HTMLInputElement;
        //console.log(checkbox);
        //console.log(this.selectAllChecked);

        if (checkbox) {
          checkbox.checked = event.checked;
        }
      });
    });
    // Se deselezioni "Seleziona tutto", pulisci MODIFICAMASSIVA e CONTEGGIOPRODOTTI
    if (!this.selectAllChecked) {
      this.MODIFICAMASSIVA = [];
      this.CONTEGGIOPRODOTTI = [];
    }
  }

  // Metodi per il design moderno
  getStatusClass(stato: string): string {
    if (!stato) return 'status-default';
    
    const statusLower = stato.toLowerCase();
    const statusMap: { [key: string]: string } = {
      'attivo': 'status-active',
      'attiva': 'status-active',
      'completato': 'status-completed',
      'completo': 'status-completed',
      'in lavorazione': 'status-processing',
      'lavorazione': 'status-processing',
      'sospeso': 'status-suspended',
      'sospesa': 'status-suspended',
      'annullato': 'status-cancelled',
      'annullata': 'status-cancelled',
      'scaduto': 'status-expired',
      'scaduta': 'status-expired'
    };
    
    return statusMap[statusLower] || 'status-default';
  }

  getStatusIcon(stato: string): string {
    if (!stato) return 'help';
    
    const statusLower = stato.toLowerCase();
    const iconMap: { [key: string]: string } = {
      'attivo': 'check_circle',
      'attiva': 'check_circle',
      'completato': 'done_all',
      'completo': 'done_all',
      'in lavorazione': 'schedule',
      'lavorazione': 'schedule',
      'sospeso': 'pause_circle',
      'sospesa': 'pause_circle',
      'annullato': 'cancel',
      'annullata': 'cancel',
      'scaduto': 'warning',
      'scaduta': 'warning'
    };
    
    return iconMap[statusLower] || 'help';
  }

  // Variabile per tracciare la riga selezionata
  selectedRow: any = null;

  // Metodo per gestire il cambiamento della checkbox
  onCheckboxChange(row: any, event: any): void {
    // Controlla se il contratto ha uno stato che non permette la selezione
    if (row.stato === 'Gettonato' || row.stato === 'Stornato') {
      // Se il contratto non è selezionabile, ferma l'evento e non procede
      event.preventDefault();
      event.stopPropagation();
      return;
    }
    
    const checkbox = event.source._elementRef.nativeElement.querySelector('input');
    this.isSelectMultiColumn(row, this.enableMultiSelect, checkbox);
  }

  // Metodo per gestire il cambio macro prodotto
  onMacroProdottoChange(event: any): void {
    // Converte l'evento MatSelectChange in Event per compatibilità
    const fakeEvent = {
      target: {
        value: event.value
      }
    } as any;
    this.cambioMacroProdotto(fakeEvent);
  }

  // Helper method per ottenere l'icona basata sul tipo di campo
  getFieldIcon(tipo: string): string {
    const iconMap: { [key: string]: string } = {
      'text': 'text_fields',
      'number': 'calculate',
      'boolean': 'toggle_on',
      'select': 'arrow_drop_down',
      'email': 'email',
      'phone': 'phone',
      'date': 'event',
      'url': 'link'
    };
    
    return iconMap[tipo] || 'info';
  }

  // Helper method per aprire un file
  openFile(file: any): void {
    const url = file.basepath + file.id + '/' + file.name;
    window.open(url, '_blank');
  }

  /**
   * Applica i filtri specifici con ricerca server-side
   * @param filterValue stringa JSON con i filtri da applicare
   */
  applySpecificFilters(filterValue: any): void {
    console.log('🔍 Applicazione filtri specifici:', filterValue);
    
    if (!filterValue || filterValue.trim() === '' || filterValue === '[]') {
      // Nessun filtro applicato, ripristina la visualizzazione normale
      console.log('❌ Nessun filtro attivo, reset filtri');
      this.dataSourceFilters.filter = '';
      this.isLoadingContratti = false;
      return;
    }
    
    // Attiva il caricamento
    this.isLoadingContratti = true;
    console.log('⏳ Avvio ricerca server-side con filtri:', filterValue);
    
    // Chiama l'endpoint di ricerca sempre dalla prima pagina quando si applicano nuovi filtri
    // Aggiunge i parametri di ordinamento
    //console.log('Chiamata API searchContratti con:', this.User.id, filterValue);
    this.ApiService.searchContratti(
      this.User.id, 
      filterValue, 
      1, 
      this.paginationInfo.perPage,
      'id',      // Campo di ordinamento di default
      'desc'     // Direzione ordinamento di default
    )
      .subscribe(
        (response: any) => {
          console.log('Risposta API ricevuta:', response);
          this.handleSearchResponse(response);
        },
        (error: any) => {
          //console.error('Errore API:', error);
          this.handleSearchError(error);
        }
      );
  }

  /**
   * Gestisce la risposta dalla ricerca API
   */
  private handleSearchResponse(response: any): void {
    //console.log('Gestione risposta ricerca:', response);
    
    // Determina quale struttura dati usare
    let contrattiData = response.body.risposta;
    let paginationData = null;
    
    // Se risposta è un oggetto paginato di Laravel
    if (response.body.risposta && response.body.risposta.data) {
      contrattiData = response.body.risposta.data;
      paginationData = response.body.risposta; // L'oggetto paginazione di Laravel
    }
    // Se risposta è un array diretto
    else if (Array.isArray(response.body.risposta)) {
      contrattiData = response.body.risposta;
    }
    
    //console.log('Dati contratti dalla risposta:', contrattiData);
    //console.log('Dati paginazione:', paginationData);
    
    if (contrattiData && contrattiData.length > 0) {
      //console.log(`Trovati ${contrattiData.length} contratti corrispondenti al filtro`);

      // Mappa i contratti nel formato della tabella
      const contrattiFiltrati = contrattiData.map((contratto: any) => ({
        id: contratto.id,
        cliente:
          contratto.customer_data.cognome && contratto.customer_data.nome
            ? contratto.customer_data.cognome + " " + contratto.customer_data.nome
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
          contratto.status_contract.option_status_contract[0]?.macro_stato || "",
        stato: contratto.status_contract.micro_stato,
        file: response.body.file ? response.body.file[contratto.id] || [] : [],
        ragione_sociale: contratto.customer_data.ragione_sociale,
        supplier: contratto.product.supplier.nome_fornitore,
        specific_data: contratto.specific_data || [],
        customer_data: contratto.customer_data,
      }));

      //console.log('Contratti filtrati elaborati:', contrattiFiltrati);

      // Aggiorna DataSource con i contratti filtrati
      this.dataSourceFilters = new MatTableDataSource(contrattiFiltrati);
      this.dataSourceFilters.sort = this.sort;
      
      // Configura il paginator per la paginazione server-side
      if (this.paginator) {
        // Disabilita la paginazione client-side di MatTableDataSource
        this.dataSourceFilters.paginator = null;
        
        // Configura il paginator con i dati server-side
        this.paginator.length = this.paginationInfo.total;
        this.paginator.pageSize = this.paginationInfo.perPage;
        this.paginator.pageIndex = this.paginationInfo.currentPage - 1; // MatPaginator usa indice 0-based
      }
      
      // Aggiorna le informazioni di paginazione usando i dati di Laravel
      if (paginationData) {
        this.paginationInfo = {
          currentPage: paginationData.current_page || 1,
          lastPage: paginationData.last_page || 1,
          perPage: paginationData.per_page || 50,
          total: paginationData.total || contrattiData.length,
          from: paginationData.from || 1,
          to: paginationData.to || contrattiData.length,
          nextPageUrl: paginationData.next_page_url || null,
          prevPageUrl: paginationData.prev_page_url || null,
        };
        
        // Usa il totale dalla paginazione del server
        this.countContratti = paginationData.total || contrattiData.length;
        //console.log('Paginazione aggiornata:', this.paginationInfo);
      } else {
        // Fallback se non ci sono dati di paginazione
        this.countContratti = contrattiData.length;
        this.paginationInfo = {
          currentPage: 1,
          lastPage: 1,
          perPage: contrattiData.length,
          total: contrattiData.length,
          from: 1,
          to: contrattiData.length,
          nextPageUrl: null,
          prevPageUrl: null,
        };
      }
      
      // Aggiorna il paginator con le nuove informazioni
      if (this.paginator) {
        this.paginator.length = this.paginationInfo.total;
        this.paginator.pageSize = this.paginationInfo.perPage;
        this.paginator.pageIndex = this.paginationInfo.currentPage - 1;
      }
      
      //console.log(`Ricerca completata con successo. ${this.countContratti} risultati trovati`);
      
      // Disattiva il loader al termine dell'elaborazione
      this.isLoadingContratti = false;
    } else {
      //console.log('Nessun dato trovato, chiamando handleEmptySearchResult');
      this.handleEmptySearchResult();
    }
  }

  /**
   * Gestisce gli errori della ricerca API
   */
  private handleSearchError(error: any): void {
    console.error('Errore durante la ricerca:', error);
    
    // Nascondi spinner
    this.isLoadingContratti = false;
    
    // Mostra risultato vuoto
    this.handleEmptySearchResult();
  }

  /**
   * Gestisce risultati di ricerca vuoti
   */
  private handleEmptySearchResult(): void {
    //console.log('Nessun contratto trovato con i filtri specificati');
    
    // Disattiva il loader
    this.isLoadingContratti = false;
    
    this.dataSourceFilters = new MatTableDataSource<ListContrattiData>([]);
    this.dataSourceFilters.paginator = null; // Disabilita la paginazione client-side
    this.dataSourceFilters.sort = this.sort;
    this.countContratti = 0;
    
    // Reset delle informazioni di paginazione
    this.paginationInfo = {
      currentPage: 1,
      lastPage: 1,
      perPage: 50,
      total: 0,
      from: 0,
      to: 0,
      nextPageUrl: null,
      prevPageUrl: null,
    };
    
    // Reset del paginator
    if (this.paginator) {
      this.paginator.length = 0;
      this.paginator.pageSize = 50;
      this.paginator.pageIndex = 0;
    }
   }

  /**
   * Naviga alla prima pagina
   */
  goToFirstPage(): void {
    if (this.paginationInfo.currentPage > 1) {
      this.loadPage(1);
    }
  }

  /**
   * Naviga alla pagina precedente
   */
  goToPreviousPage(): void {
    if (this.paginationInfo.currentPage > 1) {
      this.loadPage(this.paginationInfo.currentPage - 1);
    }
  }

  /**
   * Naviga alla pagina successiva
   */
  goToNextPage(): void {
    if (this.paginationInfo.currentPage < this.paginationInfo.lastPage) {
      this.loadPage(this.paginationInfo.currentPage + 1);
    }
  }

  /**
   * Naviga all'ultima pagina
   */
  goToLastPage(): void {
    if (this.paginationInfo.currentPage < this.paginationInfo.lastPage) {
      this.loadPage(this.paginationInfo.lastPage);
    }
  }

  /**
   * Cambia il numero di elementi per pagina
   */
  changePerPage(perPage: number): void {
    this.paginationInfo.perPage = perPage;
    this.loadPage(1); // Torna alla prima pagina quando cambi il numero di elementi
  }

  /**
   * Carica una pagina specifica
   */
  loadPage(page: number): void {
    // Attiva il caricamento
    this.isLoadingContratti = true;

    // Costruisci i filtri attuali
    const currentFilters = JSON.stringify(Array.from(this.filterDictionary.entries()));

    // Chiama l'API con la pagina specifica e parametri di ordinamento attuali
    this.ApiService.searchContratti(
      this.User.id, 
      currentFilters, 
      page, 
      this.paginationInfo.perPage,
      'id',      // Campo di ordinamento di default
      'desc'     // Direzione ordinamento di default
    )
      .subscribe(
        (response: any) => {
          this.handleSearchResponse(response);
        },
        (error: any) => {
          console.error('Errore durante il caricamento della pagina:', error);
          this.handleSearchError(error);
        }
      );
  }
}
