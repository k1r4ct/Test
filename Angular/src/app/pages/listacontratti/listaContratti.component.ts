import {
  AfterViewInit,
  Component,
  OnInit,
  ViewChild,
  ChangeDetectorRef,
  DoCheck,
} from "@angular/core";
import { MatPaginator } from "@angular/material/paginator";
import { MatSort, Sort } from "@angular/material/sort";
import { MatTableDataSource } from "@angular/material/table";
import { ContrattoService } from "src/app/servizi/contratto.service";
import { SharedService } from "src/app/servizi/shared.service";
import { ApiService } from "src/app/servizi/api.service";
import {
  BehaviorSubject,
  Subject,
  firstValueFrom,
  of,
  Subscription,
} from "rxjs";
import {
  debounceTime,
  distinctUntilChanged,
  switchMap,
  tap,
  catchError,
  takeUntil,
} from "rxjs/operators";
import { trigger, transition, style, animate } from "@angular/animations";
import { MatIconRegistry } from "@angular/material/icon";
import { DomSanitizer } from "@angular/platform-browser";
import { FormControl } from "@angular/forms";
import { Router } from "@angular/router";
import { ActivatedRoute } from "@angular/router";
import { MatDateRangePicker } from "@angular/material/datepicker";
import { RicercaclientiService } from "src/app/servizi/ricercaclienti.service";
import { saveAs } from "file-saver";
import * as Papa from "papaparse";
import { MatSnackBar } from '@angular/material/snack-bar';
import { ContrattoDetailsDialogComponent } from 'src/app/modal/modal.component';
import { MatDialog } from '@angular/material/dialog';
import { Overlay } from '@angular/cdk/overlay';
import { take } from 'rxjs';

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
  ticketExists: boolean;
  ticketUnreadCount?: number;
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
  idMacro: any;
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
  risposta: string | number | boolean | null;
  tipo: "text" | "number" | "boolean" | "select" | "unknown";
}

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
    prevPageUrl: null,
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
  selectTotale: number = 0;
  public searchMessage = "";
  allContrattiCache: Map<number, any[]> = new Map();
  totalCachedContratti: any[] = [];
  isLoadingContratti: boolean = false;

  private ticketStatusCache: Map<number, any> = new Map();
  private savedScrollPosition: number = 0;
  private lastTicketCheck: Date = new Date();
  private readonly FILTERS_STORAGE_KEY = "listaContratti_filtri";
  private pendingDateFilters: Map<string, any[]> = new Map();
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

  private isBackoffice(): boolean {
    const role = this.User?.role_id;
    return role === 1 || role === 5;
  }

  private shouldLockForContract(contratto: any): boolean {
    const role = this.User?.role_id;
    const allowedStatusForSeu = [1, 4, 5, 2];
    const statusIdRaw =
      contratto?.status_contract?.id ?? contratto?.id_stato ?? null;
    const statusId = statusIdRaw != null ? Number(statusIdRaw) : NaN;

    if (role === 1 || role === 5) {
      return false;
    }
    if (role === 2 || role === 3 || role === 4) {
      if (!Number.isFinite(statusId)) return true;
      return !allowedStatusForSeu.includes(statusId);
    }
    return true;
  }

  private syncFormLocks(): void {
    const shouldDisable = !!this.non_modificare_risposta;
    const controls = [
      this.formStatoAvanzamento,
      this.formMacroProdotto,
      this.formMicroProdotto,
    ];

    controls.forEach((ctrl) => {
      if (!ctrl) return;
      if (shouldDisable && ctrl.enabled) {
        ctrl.disable({ emitEvent: false });
      } else if (!shouldDisable && ctrl.disabled) {
        ctrl.enable({ emitEvent: false });
      }
    });
  }

  selectMulti = false;
  enableMultiSelect = false;
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;

  @ViewChild("pickerDataIns") pickerDataIns!: MatDateRangePicker<Date>;
  @ViewChild("pickerDataStipula") pickerDataStipula!: MatDateRangePicker<Date>;

  idContrattoSelezionatoSubject: Subject<number> = new Subject<number>();
  state: any;
  filterSelectObj!: any[];
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
  abilitaSelezioneMultipla = false;
  private hasUserLoaded = false;
  private lastAppliedFilterValue: string | null = null;
  private filterApply$ = new Subject<string>();
  private filterApplySub?: Subscription;
  private destroy$ = new Subject<void>();
  private currentSortField: string = "id";
  private currentSortDirection: "asc" | "desc" = "desc";
  selectedMassiviIds: Set<number> = new Set<number>();

  get isHeaderChecked(): boolean {
    const pageRows = this.dataSourceFilters?.filteredData || [];
    if (!pageRows.length) return false;
    if (this.User?.role_id === 1) {
      return pageRows.every((r) => this.selectedMassiviIds.has(Number(r.id)));
    }
    return pageRows.every(
      (r) =>
        this.selectedMassiviIds.has(Number(r.id)) ||
        r.stato === "Gettonato" ||
        r.stato === "Stornato"
    );
  }

  get isHeaderIndeterminate(): boolean {
    const pageRows = this.dataSourceFilters?.filteredData || [];
    if (!pageRows.length) return false;
    const selectable =
      this.User?.role_id === 1
        ? pageRows
        : pageRows.filter(
            (r) => r.stato !== "Gettonato" && r.stato !== "Stornato"
          );
    if (!selectable.length) return false;
    const selectedOnPage = selectable.filter((r) =>
      this.selectedMassiviIds.has(Number(r.id))
    ).length;
    return selectedOnPage > 0 && selectedOnPage < selectable.length;
  }

  constructor(
    private sharedservice: SharedService,
    private shContratto: ContrattoService,
    private ApiService: ApiService,
    private dialog: MatDialog,
    private snackBar: MatSnackBar,
    private matIconRegistry: MatIconRegistry,
    private domSanitizer: DomSanitizer,
    private router: Router,
    private activatedRoute: ActivatedRoute,
    private changeDetectorRef: ChangeDetectorRef,
    private ricercaCliente: RicercaclientiService,
    private overlay: Overlay
  ) {
    this.matIconRegistry.addSvgIcon(
      "file-jpg",
      this.domSanitizer.bypassSecurityTrustResourceUrl(
        "assets/icons/file-jpg.svg"
      )
    );
    this.matIconRegistry.addSvgIcon(
      "file-png",
      this.domSanitizer.bypassSecurityTrustResourceUrl(
        "assets/icons/file-jpg.svg"
      )
    );
    this.matIconRegistry.addSvgIcon(
      "file-pdf",
      this.domSanitizer.bypassSecurityTrustResourceUrl(
        "assets/icons/file-pdf.svg"
      )
    );
  }

  private pulisci(val: any): string {
    return (val ?? "").toString().trim();
  }

  private pulisciMaiuscolo(val: any): string {
    return this.pulisci(val).toUpperCase();
  }

  private filesById: Record<number, any[]> = {};

  private prendiValoreCampo(riga: any, campo: string): string {
    switch (campo) {
      case "cliente":
        return this.pulisci(riga?.cliente);
      case "prodotto":
        return this.pulisci(riga?.prodotto);
      case "macroprodotto":
        return this.pulisci(riga?.macroprodotto);
      case "macrostato":
        return this.pulisci(riga?.macrostato);
      case "pivacf":
        return this.pulisci(riga?.pivacf);
      case "stato":
        return this.pulisci(riga?.stato);
      case "seu":
        return this.pulisci(riga?.seu);
      case "supplier":
        return this.pulisci(riga?.supplier);
      default:
        return this.pulisci(riga?.[campo]);
    }
  }

  recuperaAuth() {
    this.ApiService.PrendiUtente().subscribe((Auth: any) => {
      if (Auth.user.role_id == 1) {
        this.non_modificare_risposta = false;
        this.abilitaDownload = true;
        this.abilitaSelezioneMultipla = true;
      } else if (Auth.user.role_id == 5) {
        this.non_modificare_risposta = false;
        this.abilitaDownload = false;
        this.abilitaSelezioneMultipla = true;
      } else {
        this.non_modificare_risposta = true;
        this.abilitaDownload = false;
        this.abilitaSelezioneMultipla = false;
      }
    });
  }

  vaiAClienti() {
    this.router.navigate(["/clienti"]);
  }

  vaiAClientiCodFPiva(codFpiva: any) {
    this.ricercaCliente.setRicerca(codFpiva);
    this.router.navigate(["/clienti"]);
  }

  getFileIcon(filename: string): string {
    const extension = filename.split(".").pop()?.toLowerCase();
    switch (extension) {
      case "jpg":
      case "png":
      case "jpeg":
        return "image";
      case "pdf":
        return "picture_as_pdf";
      default:
        return "insert_drive_file";
    }
  }

  trasformaData(dataString: string): string | null {
    const regex = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/;
    const match = dataString.match(regex);

    if (match) {
      let [, giorno, mese, anno] = match;
      giorno = giorno.padStart(2, "0");
      mese = mese.padStart(2, "0");
      return `${anno}/${mese}/${giorno}`;
    } else {
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
    this.setupFilterApplyPipeline();
    this.loadFiltersFromStorage();
    this.finishInit();
    this.loadTicketStatuses();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
    if (this.filterApplySub) {
      this.filterApplySub.unsubscribe();
    }
  }

  private loadFiltersFromStorage(): void {
    try {
      const savedFilters = localStorage.getItem(this.FILTERS_STORAGE_KEY);
      if (savedFilters) {
        const filtersArray = JSON.parse(savedFilters);
        this.filterDictionary = new Map(filtersArray);
        this.OGGETTO_FILTRICONTRATTI = savedFilters;

        filtersArray.forEach(([key, value]: [string, any]) => {
          if (
            (key === "datains" || key === "datastipula") &&
            Array.isArray(value) &&
            value.length === 2
          ) {
            this.pendingDateFilters.set(key, value);
          }
        });

        this.applyFiltersToControls(filtersArray);
        const filterString = JSON.stringify(filtersArray);
        this.applySpecificFilters(filterString);
      }
    } catch (error) {
      localStorage.removeItem(this.FILTERS_STORAGE_KEY);
    }
  }

  private saveFiltersToStorage(): void {
    try {
      const filtersArray = Array.from(this.filterDictionary.entries());
      const filtersJson = JSON.stringify(filtersArray);
      localStorage.setItem(this.FILTERS_STORAGE_KEY, filtersJson);
    } catch (error) {
      // Silent fail
    }
  }

  private clearFiltersFromStorage(): void {
    try {
      localStorage.removeItem(this.FILTERS_STORAGE_KEY);
    } catch (error) {
      // Silent fail
    }
  }

  private applyFiltersToControls(filtersArray: any[]): void {
    filtersArray.forEach(([key, value]) => {
      if (value === null || value === undefined) return;

      switch (key) {
        case "id":
          const idValue = Array.isArray(value) ? value.join(", ") : value;
          this.formID.setValue(idValue);
          break;
        case "cliente":
          this.formCliente.setValue(value);
          break;
        case "pivacf":
          this.formCFPI.setValue(value);
          break;
        case "prodotto":
          const prodValue = Array.isArray(value) ? value : [value];
          (this.formProdotti as any).setValue(prodValue);
          break;
        case "macroprodotto":
          const macroValue = Array.isArray(value) ? value : [value];
          (this.formMacroPro as any).setValue(macroValue);
          break;
        case "macrostato":
          const macroStatoValue = Array.isArray(value) ? value : [value];
          (this.formMacroStato as any).setValue(macroStatoValue);
          break;
        case "stato":
          const statoValue = Array.isArray(value) ? value : [value];
          (this.formStato as any).setValue(statoValue);
          break;
        case "seu":
          const seuValue = Array.isArray(value) ? value : [value];
          (this.formSEU as any).setValue(seuValue);
          break;
        case "supplier":
          const supplierValue = Array.isArray(value) ? value : [value];
          (this.formSupplier as any).setValue(supplierValue);
          break;
        case "datains":
        case "datastipula":
          break;
      }
    });
  }

  private setupFilterApplyPipeline(): void {
    if (this.filterApplySub) return;
    this.filterApplySub = this.filterApply$
      .pipe(
        debounceTime(400),
        distinctUntilChanged(),
        tap(() => (this.isLoadingContratti = true)),
        switchMap((filterString: string) => {
          if (!this.User || !this.User.id) {
            return of({ skip: true } as any);
          }
          this.lastAppliedFilterValue = filterString;
          return this.ApiService.searchContratti(
            this.User.id,
            filterString,
            1,
            this.paginationInfo.perPage,
            this.currentSortField,
            this.currentSortDirection
          ).pipe(
            catchError((error) => {
              this.handleSearchError(error);
              return of(null);
            })
          );
        }),
        takeUntil(this.destroy$)
      )
      .subscribe((response: any) => {
        if (!response || (response && (response as any).skip)) {
          this.isLoadingContratti = false;
          return;
        }
        this.handleSearchResponse(response);
      });
  }

  private finishInit(): void {
    this.recuperaAuth();
    this.ricercaCliente.resetNuovaRicerca();
    this.isLoadingContratti = false;
    this.sharedservice.hideRicercaContratto();

    this.activatedRoute.queryParams.subscribe((params) => {
      if (params["filtro"] && typeof params["filtro"] === "string") {
        try {
          this.filtroQueryString = JSON.parse(params["filtro"]);
        } catch (error) {
          this.filtroQueryString = null;
        }
      }
    });

    this.ApiService.PrendiUtente().subscribe((oggetto: any) => {
      this.User = oggetto.user;
      this.hasUserLoaded = true;

      this.ApiService.getContratti(this.User.id).subscribe((contratti: any) => {
        this.filesById = contratti.body?.file || {};
        this.dataSourceFilters = new MatTableDataSource(this.LISTACONTRATTI);
        if (
          contratti.body.risposta &&
          contratti.body.risposta.data &&
          contratti.body.risposta.data.length > 0
        ) {
          this.LISTACLIENTI = contratti.body.risposta.data.map(
            (contratto: any) => ({
              cliente:
                contratto.customer_data.cognome && contratto.customer_data.nome
                  ? contratto.customer_data.cognome +
                    " " +
                    contratto.customer_data.nome
                  : contratto.customer_data.ragione_sociale,
            })
          );

          let ricavacfpi = contratti.body.risposta.data.map((contratto: any) =>
            contratto.customer_data.codice_fiscale
              ? contratto.customer_data.codice_fiscale
              : contratto.customer_data.partita_iva
          );
          ricavacfpi.sort();
          this.listacfpi = ricavacfpi.filter((str: any, index: any) => {
            const set = new Set(ricavacfpi.slice(0, index));
            return !set.has(str);
          });
          this.listacfpi_bk = this.listacfpi;

          this.ApiService.recuperaSEU().subscribe((SEU: any) => {
            const tuttiSEU = (SEU.body.risposta || [])
              .map((s: any) => {
                const nome = (s.name || "").toString().trim();
                const cognome = (s.cognome || "").toString().trim();
                return [cognome, nome].filter(Boolean).join(" ").trim();
              })
              .filter((v: string) => !!v);
            const unici = Array.from(new Set(tuttiSEU)).sort() as string[];
            this.listaSEU = unici as string[];
            this.listaSEU_bk = unici as string[];
            this.checkAndApplySavedFilters();
          });

          this.ApiService.ListaProdotti().subscribe((prodotti: any) => {
            let ricavaProdotti = prodotti.body.prodotti.map(
              (prodotto: any) => prodotto.descrizione
            );
            ricavaProdotti.sort();
            this.listaProdotti = ricavaProdotti.filter(
              (str: any, index: any) => {
                const set = new Set(ricavaProdotti.slice(0, index));
                return !set.has(str);
              }
            );
            this.listaProdotti_bk = this.listaProdotti;
            this.checkAndApplySavedFilters();
          });

          this.ApiService.GetallMacroProduct().subscribe((response: any) => {
            let ricavaMacroPro = response.body.risposta.map(
              (macroProdotto: any) => macroProdotto.descrizione
            );
            ricavaMacroPro.sort();
            this.listaMacroPro = ricavaMacroPro.filter(
              (str: any, index: any) => {
                const set = new Set(ricavaMacroPro.slice(0, index));
                return !set.has(str);
              }
            );
            this.listaMacroPro_bk = this.listaMacroPro;
            this.checkAndApplySavedFilters();
          });

          this.ApiService.getMacroStato().subscribe((response: any) => {
            let ricavaMacSta = response.body.risposta.map(
              (stato: any) => stato.macro_stato
            );
            ricavaMacSta.sort();
            this.listaMacroStato = ricavaMacSta.filter(
              (str: any, index: any) => {
                const set = new Set(ricavaMacSta.slice(0, index));
                return !set.has(str);
              }
            );
            this.listaMacroStato_bk = this.listaMacroStato;
            this.checkAndApplySavedFilters();
          });

          this.ApiService.getStato().subscribe((response: any) => {
            let ricavaStato = response.body.risposta.map(
              (stato: any) => stato.micro_stato
            );
            ricavaStato.sort();
            this.listaStato = ricavaStato.filter((str: any, index: any) => {
              const set = new Set(ricavaStato.slice(0, index));
              return !set.has(str);
            });
            this.listaStato_bk = this.listaStato;
            this.checkAndApplySavedFilters();
          });

          this.ApiService.getSupplier().subscribe((response: any) => {
            let ricavaSupplier = response.body.risposta.map(
              (fornitore: any) => fornitore.nome_fornitore
            );
            this.listaSuppliers = ricavaSupplier;
            ricavaSupplier.sort();
            this.listaSupplier = ricavaSupplier.filter(
              (str: any, index: any) => {
                const set = new Set(ricavaSupplier.slice(0, index));
                return !set.has(str);
              }
            );
          });
          this.listaSupplier_bk = this.listaSupplier;
          this.checkAndApplySavedFilters();

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
              macrostato: contratto.status_contract.option_status_contract[0]
                ? contratto.status_contract.option_status_contract[0].macro_stato
                : "",
              stato: contratto.status_contract.micro_stato,
              file: contratti.body.file[contratto.id],
              file_count: (this.filesById[contratto.id] || []).length,
              ragione_sociale: contratto.customer_data.ragione_sociale,
              supplier: contratto.product.supplier.nome_fornitore,
              specific_data: contratto.specific_data || [],
              customer_data: contratto.customer_data || {},
              ticketExists: contratto.ticket && contratto.ticket.length > 0 && contratto.ticket[0]?.created_by_user_id === this.User.id,
              ticketUnreadCount:
                contratto.ticket && contratto.ticket.length > 0 && contratto.ticket[0]?.created_by_user_id === this.User.id 
                  ? contratto.ticket[0]?.messages.length > 0 ? 1 : 0 
                  : 0,
            })
          );

          this.countContratti = this.LISTACONTRATTI.length;
          this.dataSourceFilters = new MatTableDataSource(this.LISTACONTRATTI);
          this.dataSourceFilters.paginator = this.paginator;

          this.dataSourceFilters.sortingDataAccessor = (item, property) => {
            switch (property) {
              case "datains":
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

          this.countContratti = contratti.body.pagination.total;
          this.dataSourceFilters = new MatTableDataSource(this.LISTACONTRATTI);
          this.dataSourceFilters.paginator = this.paginator;
          this.isLoadingContratti = false;
        } else {
          this.isLoadingContratti = false;
        }

        this.dataSourceFilters.filterPredicate = ((riga: any, filtro: any) => {
          if (!filtro) return true;
          let listaFiltri: any[] = [];
          try {
            listaFiltri = JSON.parse(filtro);
          } catch {
            return true;
          }
          if (!Array.isArray(listaFiltri) || listaFiltri.length === 0)
            return true;

          for (const [campo, valoreGrezzo] of listaFiltri) {
            if (
              valoreGrezzo == null ||
              (Array.isArray(valoreGrezzo) && valoreGrezzo.length === 0) ||
              valoreGrezzo === ""
            ) {
              continue;
            }

            if (campo === "id") {
              const listaId = Array.isArray(valoreGrezzo)
                ? valoreGrezzo.map((v: any) => this.pulisci(v))
                : [this.pulisci(valoreGrezzo)];
              if (!listaId.includes(this.pulisci(riga.id))) return false;
              continue;
            }

            if (campo === "datains" || campo === "datastipula") {
              if (Array.isArray(valoreGrezzo) && valoreGrezzo.length === 2) {
                const da = this.trasformaData(valoreGrezzo[0]);
                const a = this.trasformaData(valoreGrezzo[1]);
                if (da && a) {
                  const dataDa = new Date(da);
                  const dataA = new Date(a);
                  const testoData =
                    campo === "datains" ? riga.datains : riga.datastipula;
                  const dataRigaStr = this.trasformaData(testoData);
                  if (dataRigaStr) {
                    const dataRiga = new Date(dataRigaStr);
                    if (!this.isDataCompresaTra(dataRiga, dataDa, dataA))
                      return false;
                  }
                }
              }
              continue;
            }

            if (campo === "cliente") {
              const testoCercato = this.pulisciMaiuscolo(valoreGrezzo);
              const testoRiga = this.pulisciMaiuscolo(riga.cliente);
              if (!testoRiga.includes(testoCercato)) return false;
              continue;
            }

            const campiLista = [
              "prodotto",
              "macroprodotto",
              "macrostato",
              "pivacf",
              "stato",
              "seu",
              "supplier",
            ];
            if (campiLista.includes(campo)) {
              const valori = Array.isArray(valoreGrezzo)
                ? valoreGrezzo
                : [valoreGrezzo];
              const valoreRiga = this.pulisciMaiuscolo(
                this.prendiValoreCampo(riga, campo)
              );
              const ok = valori
                .map((v) => this.pulisciMaiuscolo(v))
                .some((v) => v === valoreRiga);
              if (!ok) return false;
              continue;
            }
          }
          return true;
        }).bind(this);

        this.contrattiSubject.next(this.LISTACONTRATTI);
      });
    });

    this.listaStatiAvanzamento = [];
    this.listaStatiOption = [];
    this.ApiService.getStatiAvanzamento().subscribe((statiAvanzamento: any) => {
      statiAvanzamento.body.risposta.stati_avanzamento.forEach((stato: any) => {
        this.listaStatiAvanzamento.push(stato.micro_stato);
      });
      statiAvanzamento.body.risposta.status_option.forEach((stato: any) => {
        this.listaStatiOption.push(stato.macro_stato);
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
  }

  private listsLoadedStatus = {
    seu: false,
    prodotti: false,
    macroProdotti: false,
    macroStato: false,
    stato: false,
    supplier: false,
  };
  private filtersApplied = false;

  private checkAndApplySavedFilters(): void {
    this.listsLoadedStatus.seu = this.listaSEU_bk.length > 0;
    this.listsLoadedStatus.prodotti = this.listaProdotti_bk.length > 0;
    this.listsLoadedStatus.macroProdotti = this.listaMacroPro_bk.length > 0;
    this.listsLoadedStatus.macroStato = this.listaMacroStato_bk.length > 0;
    this.listsLoadedStatus.stato = this.listaStato_bk.length > 0;
    this.listsLoadedStatus.supplier = this.listaSupplier_bk.length > 0;

    const allListsLoaded = Object.values(this.listsLoadedStatus).every(
      (status) => status
    );

    if (
      allListsLoaded &&
      !this.filtersApplied &&
      (!this.filtroQueryString || this.filtroQueryString.length === 0)
    ) {
      this.filtersApplied = true;
      this.loadFiltersFromStorage();
    }
  }

  onInputFocus(classSel: string) {
    const targetElement = document.querySelector(
      classSel + " .mat-mdc-text-field-wrapper"
    ) as HTMLElement;
    if (targetElement) {
      targetElement.classList.add("custom-background");
    }
  }

  onInputBlur(classSel: string) {
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

    if (filterValue && filterValue.trim() !== "") {
      this.filterDictionary.set("pivacf", filterValue.trim());
    } else {
      this.filterDictionary.delete("pivacf");
    }

    const jsonString = JSON.stringify(
      Array.from(this.filterDictionary.entries())
    );
    this.OGGETTO_FILTRICONTRATTI = jsonString;
    this.applySpecificFilters(jsonString);
  }

  filterSelectProdotti(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    this.listaProdotti = this.listaProdotti_bk;
    this.listaProdotti = this.listaProdotti.filter((item) =>
      item.toLowerCase().includes(filterValue.toLowerCase())
    );
  }

  filterSelectSupplier(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    this.listaSupplier = this.listaSuppliers;
    this.listaSupplier = this.listaSuppliers.filter((item) =>
      item.toLowerCase().includes(filterValue.toLowerCase())
    );
  }

  filterSelectSEU(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    this.listaSEU = this.listaSEU_bk;
    this.listaSEU = this.listaSEU.filter((item) =>
      item.toLowerCase().includes(filterValue.toLowerCase())
    );
  }

  filterSelectMacroProdotto(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    this.listaMacroPro = this.listaMacroPro_bk;
    this.listaMacroPro = this.listaMacroPro.filter((item) =>
      item.toLowerCase().includes(filterValue.toLowerCase())
    );
  }

  filterSelectMacroStato(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    this.listaMacroStato = this.listaMacroStato_bk;
    this.listaMacroStato = this.listaMacroStato.filter((item) =>
      item.toLowerCase().includes(filterValue.toLowerCase())
    );
  }

  filterSelectStato(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    this.listaStato = this.listaStato_bk;
    this.listaStato = this.listaStato.filter((item) =>
      item.toLowerCase().includes(filterValue.toLowerCase())
    );
  }

  ngAfterViewInit() {
    if (this.paginator) {
      this.paginator.page.subscribe((event: any) => {
        const page = event.pageIndex + 1;
        if (event.pageSize !== this.paginationInfo.perPage) {
          this.changePerPage(event.pageSize);
        } else {
          this.loadPage(page);
        }
      });
    }

    if (this.sort) {
      this.sort.sortChange.subscribe((sortState: Sort) => {
        const fieldMap: Record<string, string> = {
          id: "id",
          cliente: "cliente",
          pivacf: "pivacf",
          datains: "data_inserimento",
          datastipula: "data_stipula",
          prodotto: "prodotto",
          macroprodotto: "macro_prodotto",
          seu: "seu",
          stato: "stato",
          macrostato: "macro_stato",
          supplier: "supplier",
        };

        this.currentSortField = fieldMap[sortState.active] || "id";
        this.currentSortDirection = (sortState.direction || "desc") as
          | "asc"
          | "desc";
        this.loadPage(1);
      });
    }

    if (this.pendingDateFilters.size > 0) {
      setTimeout(() => {
        this.applyPendingDateFilters();
      }, 500);
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
        const datainsINI = this.trasformaData(oggetto["datains"][0]);
        const datainsFIN = this.trasformaData(oggetto["datains"][1]);

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
        const datastipulaINI = this.trasformaData(oggetto["datastipula"][0]);
        const datastipulaFIN = this.trasformaData(oggetto["datastipula"][1]);

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

  private applyPendingDateFilters(): void {
    this.pendingDateFilters.forEach((value, key) => {
      if (key === "datains" && this.pickerDataIns) {
        const dataInsINI = this.trasformaData(value[0]);
        const dataInsFIN = this.trasformaData(value[1]);
        if (dataInsINI && dataInsFIN) {
          try {
            const nuovaDataInsINI = new Date(dataInsINI);
            const nuovaDataInsFIN = new Date(dataInsFIN);
            this.pickerDataIns.select(nuovaDataInsINI);
            this.pickerDataIns.select(nuovaDataInsFIN);
          } catch (error) {
            // Silent fail
          }
        }
      } else if (key === "datastipula" && this.pickerDataStipula) {
        const dataStipulaINI = this.trasformaData(value[0]);
        const dataStipulaFIN = this.trasformaData(value[1]);
        if (dataStipulaINI && dataStipulaFIN) {
          try {
            const nuovaDataStipulaINI = new Date(dataStipulaINI);
            const nuovaDataStipulaFIN = new Date(dataStipulaFIN);
            this.pickerDataStipula.select(nuovaDataStipulaINI);
            this.pickerDataStipula.select(nuovaDataStipulaFIN);
          } catch (error) {
            // Silent fail
          }
        }
      }
    });

    this.pendingDateFilters.clear();
    this.changeDetectorRef.detectChanges();
  }

  ngDoCheck() {
    if (!this.hasUserLoaded) {
      return;
    }

    if (this.filtroQueryString && this.filtroQueryString.length > 0) {
      const filtroAsString =
        typeof this.filtroQueryString === "string"
          ? this.filtroQueryString
          : JSON.stringify(this.filtroQueryString);
      let filtroStringa: any[] = [];
      try {
        filtroStringa =
          typeof this.filtroQueryString === "string"
            ? JSON.parse(this.filtroQueryString)
            : this.filtroQueryString;
      } catch {
        filtroStringa = [];
      }

      const oggetto = Object.fromEntries(filtroStringa);

      this.formID.disable();

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

      this.OGGETTO_FILTRICONTRATTI = filtroAsString;

      if (this.lastAppliedFilterValue !== filtroAsString) {
        this.lastAppliedFilterValue = filtroAsString;
        this.applySpecificFilters(filtroAsString);
      }
    }
  }

  dateRangeChange(
    matStartDate: HTMLInputElement,
    matEndDate: HTMLInputElement,
    fieldTable: string
  ) {
    const startDate = matStartDate.value;
    const endDate = matEndDate.value;
    let jsonString: string = "";
    let value: any;

    if (startDate!.length != 0 && endDate!.length != 0) {
      value = [startDate, endDate];
      this.filterDictionary.set(fieldTable, value!);

      this.OGGETTO_FILTRICONTRATTI = JSON.stringify(
        Array.from(this.filterDictionary.entries())
      );
      jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));
      this.applySpecificFilters(jsonString);
    } else {
      this.filterDictionary.delete(fieldTable);

      this.OGGETTO_FILTRICONTRATTI = JSON.stringify(
        Array.from(this.filterDictionary.entries())
      );
      jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));
      this.applySpecificFilters(jsonString);
    }
  }

  onFormfieldClick(event: any) {
    event.stopPropagation();
  }

  selectOpt(fieldTable: string, value: any) {
    let jsonString: string = "";

    if (value!.length == 0) {
      this.filterDictionary.delete(fieldTable);
    } else {
      this.filterDictionary.set(fieldTable, value);
    }

    this.OGGETTO_FILTRICONTRATTI = JSON.stringify(
      Array.from(this.filterDictionary.entries())
    );
    jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));
    this.applySpecificFilters(jsonString);
  }

  applyFilterId(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    const separators = /[\s,]+/;
    const ids = filterValue
      .split(separators)
      .map((id) => id.trim())
      .filter((id) => id !== "");

    if (ids.length > 0) {
      this.filterDictionary.set("id", ids);
    } else {
      this.filterDictionary.delete("id");
    }

    const jsonString = JSON.stringify(
      Array.from(this.filterDictionary.entries())
    );
    this.OGGETTO_FILTRICONTRATTI = jsonString;
    this.applySpecificFilters(jsonString);
  }

  applyFilterName(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;

    if (filterValue && filterValue.trim() !== "") {
      this.filterDictionary.set("cliente", filterValue.trim());
    } else {
      this.filterDictionary.delete("cliente");
    }

    const jsonString = JSON.stringify(
      Array.from(this.filterDictionary.entries())
    );
    this.OGGETTO_FILTRICONTRATTI = jsonString;
    this.applySpecificFilters(jsonString);
  }

  applyFilter2(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
  }

  applyEmpFilter(ob: any, empfilter: any) {
    // Empty implementation
  }

  resetFiltri() {
    this.clearFiltersFromStorage();
    this.filterDictionary.clear();

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
    this.nuovaspecific_data = [];
    this.DettagliContratto = [];
    this.fileSelezionati = [];
    this.caricacontratto = true;

    this.non_modificare_risposta = true;
    this.syncFormLocks();
    this.changeDetectorRef.detectChanges();

    document.getElementById("matspin")?.classList.add("centraspinner");
    document.getElementById("over")?.classList.add("overlay");
    this.matspinner = false;

    this.idcontrattosel = r.id;

    const checkbox = document.getElementById(
      "check-" + r.id
    ) as HTMLInputElement;
    if (checkbox) {
      checkbox.checked = !checkbox.checked;
    }
    if (this.enableMultiSelect) {
      this.isSelectMultiColumn(r, true, checkbox);
    }

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

    this.populateselectCambioStatoMassivo();

    this.ApiService.getContratto(r.id).subscribe((contratti: any) => {
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

      const listadomandecompilate = contratti.body.risposta[0].specific_data
        .map((dato: any) => {
          const risposta = this.getRisposta(dato);
          if (risposta !== null) {
            return dato.domanda;
          }
          return null;
        })
        .filter((domanda: string | null) => domanda !== null);

      this.arrayDomandeDaEscludere = [];
      this.arrayDomandeDaEscludere = listadomandecompilate;

      this.idmacroprodottocontratto =
        contratti.body.risposta[0].product.macro_product.id;

      this.getDomandeFromApi();

      this.fileSelezionati = contratti.body.file[r.id];

      const primoContratto = contratti?.body?.risposta?.[0];
      if (primoContratto) {
        this.non_modificare_risposta =
          this.shouldLockForContract(primoContratto);
        this.syncFormLocks();
      }

      this.DettagliContratto = contratti.body.risposta.map((contratto: any) => {
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
            ? contratto.backoffice_note[contratto.backoffice_note.length - 1].nota
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

      this.changeDetectorRef.detectChanges();

      setTimeout(() => {
        this.caricacontratto = false;

        document.getElementById("matspin")?.classList.remove("centraspinner");
        document.getElementById("over")?.classList.remove("overlay");
        this.matspinner = true;

        this.changeDetectorRef.detectChanges();
      }, 150);

      if (this.DettagliContratto.length > 0) {
        const contratto = this.DettagliContratto[0];

        this.formMacroProdotto.setValue(contratto.macroprodotto_id);

        this.ApiService.allMacroProduct(contratto.macroprodotto_id).subscribe(
          (newLista: any) => {
            if (newLista.body.risposta && newLista.body.risposta.length > 0) {
              this.ALTRIPRODOTTI = newLista.body.risposta[0].product.map(
                (productNew: any) => ({
                  id: productNew.id,
                  descrizione: productNew.descrizione,
                })
              );
              this.formMicroProdotto.setValue(contratto.microprodotto_id);
            }
          }
        );

        this.formStatoAvanzamento.setValue(contratto.id_stato);
        this.syncFormLocks();
      }

      this.ApiService.recuperaSEU().subscribe((SEU: any) => {
        this.seu = SEU.body.risposta.map((allSeu: any) => {
          return {
            id: allSeu.id,
            nominativo:
              allSeu.name || allSeu.cognome
                ? allSeu.name + " " + allSeu.cognome
                : allSeu.ragione_sociale,
          };
        });

        this.seuSelected = this.seu.find(
          (r) => r.id === this.DettagliContratto[0].inserito_da_user_id
        );
      });
    });
  }

  populate() {
    // Empty implementation
  }

  cambioMacroProdotto(event: Event) {
    this.selected = false;
    this.opzioneTEXT = "(OPZIONE PRECEDENTE)";
    const target = event.target as HTMLSelectElement;
    const selectedId = target.value;
    this.ALTRIPRODOTTI = [];
    this.ApiService.allMacroProduct(selectedId).subscribe((newLista: any) => {
      newLista.body.risposta.map((product: any) => {
        this.ALTRIPRODOTTI = product.product.map((productNew: any) => ({
          id: productNew.id,
          descrizione: productNew.descrizione,
        }));
      });
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

  getTipoRisposta(
    dato: any
  ): "text" | "number" | "boolean" | "select" | "unknown" {
    if (dato.tipo_risposta == "numero") {
      return "number";
    } else if (
      dato.tipo_risposta == "stringa" ||
      dato.tipo_risposta == "text"
    ) {
      return "text";
    } else if (dato.tipo_risposta == "select") {
      return "select";
    } else if (dato.tipo_risposta == "sino") {
      return "boolean";
    } else {
      return "unknown";
    }
  }

  deleteFile(file: any) {
    const formData = new FormData();
    formData.append("idContratto", file.id);
    formData.append("nameFileGet", file.name);
    this.ApiService.deleteIMG(formData).subscribe((response) => {
      if (response.success) {
        this.fileSelezionati = this.fileSelezionati.filter(
          (f) => f.id !== file.id
        );
      }
    });
  }

  isImage(filename: string): boolean {
    const extension = filename.split(".").pop()?.toLowerCase();
    return ["jpg", "jpeg", "png", "gif", "pdf"].includes(extension || "");
  }

  getDomandeFromApi(): void {
    this.nuovaspecific_data = [];

    this.ApiService.getDomandeMacro(
      this.idmacroprodottocontratto,
      this.arrayDomandeDaEscludere
    ).subscribe({
      next: (Risposta: any) => {
        this.nuovaspecific_data = Risposta.ListaDomande || [];
        this.changeDetectorRef.detectChanges();

        setTimeout(() => {
          this.changeDetectorRef.detectChanges();
        }, 50);
      },
      error: (error) => {
        this.nuovaspecific_data = [];
        this.changeDetectorRef.detectChanges();
      },
    });
  }

  recupera_opzioni_select(domanda: any, rispostaindicata: any) {
    const selectId = "select_" + domanda.id;
    const select = document.getElementById(selectId) as HTMLSelectElement;
    if (select) {
      if (select.getAttribute("tag") == "0") {
        select.setAttribute("tag", "1");

        this.ApiService.getRisposteSelect(
          domanda.id,
          rispostaindicata
        ).subscribe({
          next: (Risposta: any) => {
            Risposta.body.map((risp: any) => {
              const option = document.createElement("option");
              option.value = risp.opzione;
              option.text = risp.opzione;
              select.appendChild(option);
            });
          },
          error: (error) => {
            // Silent fail
          },
        });
      }
    }
  }

  updateContratto(id: any) {
    const statoAvanzamento = this.formStatoAvanzamento.value;
    const macroprodotto = this.formMacroProdotto.value;
    const microprodotto = this.formMicroProdotto.value;

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

    const specificData = this.collectSpecificData();

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

    if (specificData && specificData.length > 0) {
      const specificDataJson = JSON.stringify(specificData);
      formData.append("specific_data", specificDataJson);
    }

    this.ApiService.updateContratto(formData).subscribe(
      (ContrattoAggiornato: any) => {
        this.reloadContrattiWithCurrentFilters();

        this.snackBar.open("Contratto aggiornato con successo", "Chiudi", {
          duration: 3000,
          horizontalPosition: "center",
          verticalPosition: "bottom",
          panelClass: ["success-snackbar"],
        });

        this.contrattoselezionato = true;
      },
      (error) => {
        this.snackBar.open(
          "Errore durante l'aggiornamento del contratto",
          "Chiudi",
          {
            duration: 3000,
            horizontalPosition: "center",
            verticalPosition: "bottom",
            panelClass: ["error-snackbar"],
          }
        );
      }
    );
  }

  private reloadContrattiWithCurrentFilters(): void {
    const currentFilters = JSON.stringify(
      Array.from(this.filterDictionary.entries())
    );

    this.isLoadingContratti = true;

    this.ApiService.searchContratti(
      this.User.id,
      currentFilters,
      this.paginationInfo.currentPage,
      this.paginationInfo.perPage,
      this.currentSortField,
      this.currentSortDirection
    ).subscribe(
      (response: any) => {
        this.handleSearchResponse(response);
        this.isLoadingContratti = false;
      },
      (error: any) => {
        this.handleSearchError(error);
      }
    );
  }

  trackByDomanda(index: number, domanda: any): any {
    return domanda.id || domanda.domanda || index;
  }

  trackByRisposta(index: number, risposta: any): any {
    return risposta.id || risposta.domanda || index;
  }

  getSortedDomande(domande: any[]): any[] {
    if (!domande || domande.length === 0) {
      return [];
    }

    return [...domande].sort((a, b) => {
      const tagA = a.domanda || "";
      const tagB = b.domanda || "";
      return tagA.localeCompare(tagB);
    });
  }

  collectSpecificData(): any[] {
    const specificDataArray: any[] = [];

    if (this.DettagliContratto.length > 0) {
      this.DettagliContratto[0].specific_data.forEach((item: any) => {
        const inputElement = document.querySelector(
          `[name="${item.domanda}"]`
        ) as HTMLInputElement | HTMLSelectElement;

        let valore: any = "";

        if (inputElement) {
          if (item.tipo === "boolean") {
            valore = (inputElement as any).checked;
          } else {
            valore = inputElement.value;
          }

          const domandaRisposta = {
            id: item.id,
            domanda: item.domanda,
            risposta: valore,
            tipo: item.tipo,
            risposta_tipo_stringa:
              item.tipo === "text" || item.tipo === "select" ? valore : null,
            risposta_tipo_numero:
              item.tipo === "number" ? parseFloat(valore) || 0 : null,
            risposta_tipo_bool: item.tipo === "boolean" ? valore : null,
            tipo_risposta: this.mapTipoRispostaReverse(item.tipo),
          };

          specificDataArray.push(domandaRisposta);
        } else {
          const domandaRisposta = {
            id: item.id,
            domanda: item.domanda,
            risposta: item.risposta,
            tipo: item.tipo,
            risposta_tipo_stringa:
              item.tipo === "text" || item.tipo === "select"
                ? item.risposta
                : null,
            risposta_tipo_numero:
              item.tipo === "number" ? parseFloat(item.risposta) || 0 : null,
            risposta_tipo_bool: item.tipo === "boolean" ? item.risposta : null,
            tipo_risposta: this.mapTipoRispostaReverse(item.tipo),
          };

          specificDataArray.push(domandaRisposta);
        }
      });
    }

    if (this.nuovaspecific_data && this.nuovaspecific_data.length > 0) {
      this.nuovaspecific_data.forEach((item: any) => {
        const inputElement = document.querySelector(`[name="${item.id}"]`) as
          | HTMLInputElement
          | HTMLSelectElement;

        if (inputElement) {
          let valore = inputElement.value;
          if (!valore || valore.trim() === "") {
            return;
          }
          if (inputElement.type === 'checkbox') {
            valore = (inputElement as HTMLInputElement).checked ? 'true' : 'false';
            if (valore === 'false') {
              return;
            }
          }
          let tipo = this.mapTipoRisposta(
            item.tipo_risposta || item.tipoRisposta
          );

          const domandaRisposta = {
            id: null,
            domanda: item.domanda,
            risposta: valore,
            tipo: tipo,
            risposta_tipo_stringa:
              tipo === "text" || tipo === "select" ? valore : null,
            risposta_tipo_numero:
              tipo === "number" ? parseFloat(valore) || 0 : null,
            risposta_tipo_bool:
              tipo === "boolean"
                ? valore === "true" || valore === "1" || valore === "si"
                : null,
            tipo_risposta:
              item.tipo_risposta ||
              item.tipoRisposta ||
              this.mapTipoRispostaReverse(tipo),
          };

          specificDataArray.push(domandaRisposta);
        }
      });
    }

    return specificDataArray;
  }

  mapTipoRisposta(tipoNumerico: number | string): string {
    if (typeof tipoNumerico === "string") {
      return tipoNumerico;
    }

    switch (tipoNumerico) {
      case 0:
        return "stringa";
      case 1:
        return "sino";
      case 2:
        return "data";
      case 3:
        return "select";
      case 4:
        return "numero";
      default:
        return "stringa";
    }
  }

  mapTipoRispostaReverse(tipoFrontend: string): number {
    switch (tipoFrontend) {
      case "text":
        return 0;
      case "boolean":
        return 1;
      case "date":
        return 2;
      case "select":
        return 3;
      case "number":
        return 4;
      default:
        return 0;
    }
  }

  getMicroStatiPerMacroStato(macroStato: string): optionStatus[] {
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
    if (
      (row.stato === "Gettonato" || row.stato === "Stornato") &&
      this.User?.role_id !== 1
    ) {
      if (checkbox) {
        checkbox.checked = false;
      }
      return;
    }

    if (checkbox) {
      if (checkbox.checked) {
        checkbox.checked = enable;
      } else {
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

      if (!controllo) {
        this.MODIFICAMASSIVA.push(nuovaModifica);
        this.selectedMassiviIds.add(Number(row.id));
      } else {
        const index = this.MODIFICAMASSIVA.findIndex(
          (item) => item.id === controllo.id
        );
        if (index > -1) {
          this.MODIFICAMASSIVA.splice(index, 1);
          this.selectedMassiviIds.delete(Number(row.id));
        }
      }

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

      if (this.MODIFICAMASSIVA.length > 0) {
        this.visualizzaModificaMassiva = !this.visualizzaModificaMassiva;
        this.contrattoselezionato = true;
      } else {
        this.contrattoselezionato = false;
      }
      this.populateselectCambioStatoMassivo();
    } else {
      const index = this.MODIFICAMASSIVA.findIndex((i) => i.id === row.id);
      if (index > -1) this.MODIFICAMASSIVA.splice(index, 1);
      this.selectedMassiviIds.delete(Number(row.id));

      const verificaPresenzaModMassiva = this.contaMacroProdottoSelMassiva(
        row.macroprodotto
      );
      const verifica = this.confronto(row.macroprodotto);
      if (verifica) {
        if (verifica.contatore > verificaPresenzaModMassiva) {
          verifica.contatore = verificaPresenzaModMassiva;
        }
        if (verifica.contatore <= 0) {
          const idx = this.CONTEGGIOPRODOTTI.findIndex(
            (item) => item.descrizione === row.macroprodotto
          );
          if (idx > -1) this.CONTEGGIOPRODOTTI.splice(idx, 1);
        }
      }

      if (this.MODIFICAMASSIVA.length <= 0) {
        this.visualizzaModificaMassiva = false;
        this.contrattoselezionato = false;
      }
    }
  }

  updateStatiMassivi() {
    const stato_avanzamento_m = document.querySelector(
      ".stato_avanzamento_massivo"
    ) as HTMLSelectElement;
    const statoAvanzamento = stato_avanzamento_m?.value;
    const contrattiAll = JSON.stringify(this.MODIFICAMASSIVA);

    const formDataModificaMassiva = new FormData();

    formDataModificaMassiva.append("contratti", contrattiAll);
    formDataModificaMassiva.append("nuovostato", statoAvanzamento);

    this.ApiService.updateStatoMassivoContratti(
      formDataModificaMassiva
    ).subscribe(
      (Risposta: any) => {
        if (Risposta.status == 200) {
          this.reloadContrattiWithCurrentFilters();

          this.MODIFICAMASSIVA = [];
          this.selectedMassiviIds.clear();
          this.CONTEGGIOPRODOTTI = [];
          this.visualizzaModificaMassiva = false;

          this.snackBar.open("Stati aggiornati con successo", "Chiudi", {
            duration: 3000,
            horizontalPosition: "center",
            verticalPosition: "bottom",
            panelClass: ["success-snackbar"],
          });
        }
      },
      (error) => {
        this.snackBar.open(
          "Errore durante l'aggiornamento degli stati",
          "Chiudi",
          {
            duration: 3000,
            horizontalPosition: "center",
            verticalPosition: "bottom",
            panelClass: ["error-snackbar"],
          }
        );
      }
    );
  }

  aggiornaPagineEsettaFiltri() {
    let statofiltropieno: boolean = false;

    if (this.OGGETTO_FILTRICONTRATTI == undefined) {
      statofiltropieno = false;
    } else {
      if (this.OGGETTO_FILTRICONTRATTI.length <= 2) {
        statofiltropieno = false;
      } else {
        statofiltropieno = true;
      }
    }
    this.saveFiltersToStorage();
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
      const tuttiIContratti = await this.fetchAllContrattiForExport();

      const tutteLeDomande = new Set<string>();
      tuttiIContratti.forEach((contratto: any) => {
        contratto.specific_data?.forEach((s: any) => {
          tutteLeDomande.add(s.domanda);
        });
      });
      const domandeArray = Array.from(tutteLeDomande);

      const risultatiFinali = tuttiIContratti.map((contratto: any) => {
        const dettagli = contratto.customer_data || {};
        const row: any = {
          id: contratto.id,
          cliente_associato: contratto.cliente_associato,
          contraente: contratto.cliente,
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

      const csv = Papa.unparse(risultatiFinali, { delimiter: ";" });
      const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
      saveAs(blob, "contratti_completi.csv");
    } catch (error) {
      // Silent fail
    }
  }

  private async fetchAllContrattiForExport(): Promise<any[]> {
    if (!this.User || !this.User.id) {
      return this.dataSourceFilters?.filteredData || [];
    }

    const currentFilters = JSON.stringify(
      Array.from(this.filterDictionary.entries())
    );

    const sortField = this.currentSortField || "id";
    const sortDirection = this.currentSortDirection || "desc";

    const firstResp: any = await firstValueFrom(
      this.ApiService.searchContratti(
        this.User.id,
        currentFilters,
        1,
        this.paginationInfo.perPage,
        sortField,
        sortDirection
      )
    );

    let contrattiData = firstResp.body.risposta;
    let paginationData = null;
    if (contrattiData && contrattiData.data) {
      paginationData = contrattiData;
      contrattiData = contrattiData.data;
    }

    const mapRow = (contratto: any) => ({
      id: contratto.id,
      cliente_associato: contratto.associato_a_user_id,
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
        contratto.status_contract.option_status_contract?.[0]?.macro_stato ||
        "",
      stato: contratto.status_contract.micro_stato,
      file: firstResp.body.file ? firstResp.body.file[contratto.id] || [] : [],
      ragione_sociale: contratto.customer_data.ragione_sociale,
      supplier: contratto.product.supplier.nome_fornitore,
      specific_data: contratto.specific_data || [],
      customer_data: contratto.customer_data,
      ticketExists: contratto.ticket && contratto.ticket.length > 0 && contratto.ticket[0]?.created_by_user_id === this.User.id,
    });

    const risultati: any[] = Array.isArray(contrattiData)
      ? contrattiData.map(mapRow)
      : [];

    if (!paginationData) {
      return risultati;
    }

    const lastPage = paginationData.last_page || 1;
    const perPage =
      paginationData.per_page || this.paginationInfo.perPage || 50;

    if (lastPage <= 1) {
      return risultati;
    }

    for (let page = 2; page <= lastPage; page++) {
      try {
        const resp: any = await firstValueFrom(
          this.ApiService.searchContratti(
            this.User.id,
            currentFilters,
            page,
            perPage,
            sortField,
            sortDirection
          )
        );

        let pageData = resp.body.risposta;
        if (pageData && pageData.data) {
          pageData = pageData.data;
        }

        if (Array.isArray(pageData) && pageData.length) {
          risultati.push(
            ...pageData.map((c: any) => ({
              ...mapRow(c),
              file: resp.body.file ? resp.body.file[c.id] || [] : [],
            }))
          );
        }
      } catch (e) {
        // Continue with other pages
      }
    }

    return risultati;
  }

  populateselectCambioStatoMassivo() {
    this.OptionStatus.forEach((stato: any) => {
      const macroStato = stato.macro_stato;
      const idMacro = stato.id_status;

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

  async toggleSelectAll(event: any) {
    this.selectAllChecked = event.checked;

    this.isLoadingContratti = true;
    if (this.selectAllChecked) {
      const tutti = await this.fetchAllContrattiForExport();

      this.MODIFICAMASSIVA = [];
      this.CONTEGGIOPRODOTTI = [];
      this.selectedMassiviIds.clear();
      for (const row of tutti) {
        if (
          this.User?.role_id !== 1 &&
          (row.stato === "Gettonato" || row.stato === "Stornato")
        )
          continue;

        this.selectedMassiviIds.add(Number(row.id));
        const nuovaModifica: ModificaMassiva = {
          id: row.id,
          nome_ragSociale: row.cliente,
          stato_contratto: row.stato,
          macro_prodotto: row.macroprodotto,
        };
        this.MODIFICAMASSIVA.push(nuovaModifica);

        const esiste = this.CONTEGGIOPRODOTTI.find(
          (i) => i.descrizione === row.macroprodotto
        );
        if (!esiste) {
          this.CONTEGGIOPRODOTTI.push({
            descrizione: row.macroprodotto,
            contatore: 1,
          });
        } else {
          esiste.contatore += 1;
        }
      }
      this.selectTotale = 1;

      setTimeout(() => {
        this.dataSourceFilters.filteredData.forEach((row) => {
          const checkbox = document.getElementById(
            "check-" + row.id + "-input"
          ) as HTMLInputElement;
          if (checkbox) {
            (checkbox as any).checked = this.selectedMassiviIds.has(
              Number(row.id)
            );
          }
        });
      });

      this.visualizzaModificaMassiva = this.MODIFICAMASSIVA.length > 0;
      this.contrattoselezionato = this.visualizzaModificaMassiva;
      this.populateselectCambioStatoMassivo();
    } else {
      this.selectedMassiviIds.clear();
      this.MODIFICAMASSIVA = [];
      this.CONTEGGIOPRODOTTI = [];
      setTimeout(() => {
        this.dataSourceFilters.filteredData.forEach((row) => {
          const checkbox = document.getElementById(
            "check-" + row.id + "-input"
          ) as HTMLInputElement;
          if (checkbox) (checkbox as any).checked = false;
        });
      });
      this.visualizzaModificaMassiva = false;
      this.contrattoselezionato = false;
    }
    this.isLoadingContratti = false;
  }

  getStatusClass(stato: string): string {
    if (!stato) return "status-default";

    const statusLower = stato.toLowerCase();
    const statusMap: { [key: string]: string } = {
      attivo: "status-active",
      attiva: "status-active",
      completato: "status-completed",
      completo: "status-completed",
      "in lavorazione": "status-processing",
      lavorazione: "status-processing",
      sospeso: "status-suspended",
      sospesa: "status-suspended",
      annullato: "status-cancelled",
      annullata: "status-cancelled",
      scaduto: "status-expired",
      scaduta: "status-expired",
    };

    return statusMap[statusLower] || "status-default";
  }

  getStatusIcon(stato: string): string {
    if (!stato) return "help";

    const statusLower = stato.toLowerCase();
    const iconMap: { [key: string]: string } = {
      attivo: "check_circle",
      attiva: "check_circle",
      completato: "done_all",
      completo: "done_all",
      "in lavorazione": "schedule",
      lavorazione: "schedule",
      sospeso: "pause_circle",
      sospesa: "pause_circle",
      annullato: "cancel",
      annullata: "cancel",
      scaduto: "warning",
      scaduta: "warning",
    };

    return iconMap[statusLower] || "help";
  }

  selectedRow: any = null;

  onCheckboxChange(row: any, event: any): void {
    if (
      this.User?.role_id !== 1 &&
      (row.stato === "Gettonato" || row.stato === "Stornato")
    ) {
      event.preventDefault();
      event.stopPropagation();
      return;
    }

    const checkbox =
      event.source._elementRef.nativeElement.querySelector("input");
    const shouldSelect = !this.selectedMassiviIds.has(Number(row.id));
    this.isSelectMultiColumn(row, shouldSelect, checkbox);
  }

  onMacroProdottoChange(event: any): void {
    const fakeEvent = {
      target: {
        value: event.value,
      },
    } as any;
    this.cambioMacroProdotto(fakeEvent);
  }

  getFieldIcon(tipo: string): string {
    const iconMap: { [key: string]: string } = {
      text: "text_fields",
      number: "calculate",
      boolean: "toggle_on",
      select: "arrow_drop_down",
      email: "email",
      phone: "phone",
      date: "event",
      url: "link",
    };

    return iconMap[tipo] || "info";
  }

  openFile(file: any): void {
    const url = file.basepath + file.id + "/" + file.name;
    window.open(url, "_blank");
  }

  applySpecificFilters(filterValue: any): void {
    let entries: any[] = [];
    try {
      entries = Array.from(this.filterDictionary.entries());
    } catch {
      try {
        const arr =
          typeof filterValue === "string"
            ? JSON.parse(filterValue)
            : filterValue;
        if (Array.isArray(arr)) entries = arr;
      } catch {}
    }
    const sorted = entries.sort((a: any, b: any) =>
      String(a[0]).localeCompare(String(b[0]))
    );
    const filterString = JSON.stringify(sorted);

    if (this.lastAppliedFilterValue === filterString) return;

    this.saveFiltersToStorage();
    this.filterApply$.next(filterString);
  }

  private handleSearchResponse(response: any): void {
    let contrattiData = response.body.risposta;
    let paginationData = null;

    if (response.body.risposta && response.body.risposta.data) {
      contrattiData = response.body.risposta.data;
      paginationData = response.body.risposta;
    } else if (Array.isArray(response.body.risposta)) {
      contrattiData = response.body.risposta;
    }

    if (response?.body?.file) {
      this.filesById = { ...this.filesById, ...response.body.file };
    }
    if (contrattiData && contrattiData.length > 0) {
      const contrattiFiltrati = contrattiData.map((contratto: any) => {
        const files = this.filesById[contratto.id] || [];
        return {
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
          macrostato: contratto.status_contract.option_status_contract[0]
            ? contratto.status_contract.option_status_contract[0].macro_stato
            : "",
          stato: contratto.status_contract.micro_stato,
          file: files,
          file_count: files.length,
          ragione_sociale: contratto.customer_data.ragione_sociale,
          supplier: contratto.product.supplier.nome_fornitore,
          specific_data: contratto.specific_data || [],
          customer_data: contratto.customer_data,
          ticketExists: contratto.ticket && contratto.ticket.length > 0 && contratto.ticket[0]?.created_by_user_id === this.User.id,
          ticketUnreadCount: contratto.ticket && contratto.ticket.length > 0 && contratto.ticket[0]?.created_by_user_id === this.User.id 
            ? contratto.ticket[0]?.messages.length > 0 ? 1 : 0 
            : 0,
        };
      });

      this.dataSourceFilters = new MatTableDataSource(contrattiFiltrati);
      this.dataSourceFilters.sort = this.sort;

      contrattiFiltrati.forEach((c: any) => {
        const id = Number(c.id);
        this.ticketStatusCache.set(id, {
          ticket: c.ticketExists ? {} : null,
          hasUnreadMessages: Number(c.ticket?.messages.length > 0 ? 1 : 0) > 0,
        });
      });

      if (this.paginator) {
        this.dataSourceFilters.paginator = null;
        this.paginator.length = this.paginationInfo.total;
        this.paginator.pageSize = this.paginationInfo.perPage;
        this.paginator.pageIndex = this.paginationInfo.currentPage - 1;
      }

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

        this.countContratti = paginationData.total || contrattiData.length;
      } else {
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

      if (this.paginator) {
        this.paginator.length = this.paginationInfo.total;
        this.paginator.pageSize = this.paginationInfo.perPage;
        this.paginator.pageIndex = this.paginationInfo.currentPage - 1;
      }

      this.isLoadingContratti = false;

      setTimeout(() => {
        (this.dataSourceFilters?.filteredData || []).forEach((row: any) => {
          const checkbox = document.getElementById(
            "check-" + row.id
          ) as HTMLInputElement;
          if (checkbox) {
            (checkbox as any).checked = this.selectedMassiviIds.has(
              Number(row.id)
            );
          }
        });
      });
    } else {
      this.handleEmptySearchResult();
    }
  }

  private handleSearchError(error: any): void {
    this.isLoadingContratti = false;
    this.handleEmptySearchResult();
  }

  private handleEmptySearchResult(): void {
    this.isLoadingContratti = false;
    this.dataSourceFilters = new MatTableDataSource<ListContrattiData>([]);
    this.dataSourceFilters.paginator = null;
    this.dataSourceFilters.sort = this.sort;
    this.countContratti = 0;

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

    if (this.paginator) {
      this.paginator.length = 0;
      this.paginator.pageSize = 50;
      this.paginator.pageIndex = 0;
    }
  }

  goToFirstPage(): void {
    if (this.paginationInfo.currentPage > 1) {
      this.loadPage(1);
    }
  }

  goToPreviousPage(): void {
    if (this.paginationInfo.currentPage > 1) {
      this.loadPage(this.paginationInfo.currentPage - 1);
    }
  }

  goToNextPage(): void {
    if (this.paginationInfo.currentPage < this.paginationInfo.lastPage) {
      this.loadPage(this.paginationInfo.currentPage + 1);
    }
  }

  goToLastPage(): void {
    if (this.paginationInfo.currentPage < this.paginationInfo.lastPage) {
      this.loadPage(this.paginationInfo.lastPage);
    }
  }

  changePerPage(perPage: number): void {
    this.paginationInfo.perPage = perPage;
    this.loadPage(1);
  }

  loadPage(page: number): void {
    this.isLoadingContratti = true;

    const currentFilters = JSON.stringify(
      Array.from(this.filterDictionary.entries())
    );

    this.ApiService.searchContratti(
      this.User.id,
      currentFilters,
      page,
      this.paginationInfo.perPage,
      this.currentSortField,
      this.currentSortDirection
    ).subscribe(
      (response: any) => {
        this.handleSearchResponse(response);
      },
      (error: any) => {
        this.handleSearchError(error);
      }
    );
  }

  filesCount(row: any): number {
    const f = row?.file;

    if (Array.isArray(f)) {
      return f.length;
    }

    if (f && typeof f === "object") {
      return Object.keys(f).length;
    }
    const n = (row as any)?.file_count ?? (row as any)?.files_count ?? 0;
    return Number(n) || 0;
  }

  hasFiles(row: any): boolean {
    return this.filesCount(row) > 0;
  }

  private loadTicketStatuses(): void {
    if (this.LISTACONTRATTI && this.LISTACONTRATTI.length > 0) {
      this.LISTACONTRATTI.forEach((c: any) => {
        const id = Number(c.id);
        if (!this.ticketStatusCache.has(id)) {
          this.ticketStatusCache.set(id, {
            ticket: c.ticketExists ? {} : null,
            hasUnreadMessages:
              Number(c.ticket?.messages.length > 0 ? 1 : 0) > 0,
          });
        }
      });
      this.changeDetectorRef.detectChanges();
    }
  }

  private checkTicketStatus(contractId: number): void {
    this.ApiService.getTicketByContractId(contractId).subscribe({
      next: (response: any) => {
        if (response.response === "ok" && response.body?.ticket) {
          this.ticketStatusCache.set(contractId, {
            ticket: response.body.ticket,
          });
        } else {
          this.ticketStatusCache.set(contractId, {
            ticket: null,
          });
        }
        this.changeDetectorRef.detectChanges();
      },
      error: (error) => {
        this.ticketStatusCache.set(contractId, {
          ticket: null,
        });
      },
    });
  }

  getTicketIcon(contractId: number, contr?: any): string {
    const unreadFromRow = Number(contr?.ticketUnreadCount || 0) > 0;

    if (unreadFromRow) return "mark_chat_unread";
    if (contr?.ticketExists) return "chat";
    return "chat_bubble_outline";
  }

  getTicketButtonColor(contractId: number, contr?: any): string {
    const icon = this.getTicketIcon(contractId, contr);
    switch (icon) {
      case "mark_chat_unread":
        return "ticket-unread";
      case "chat":
        return "ticket-exists";
      default:
        return "ticket-new";
    }
  }

  getTicketTooltip(contractId: number, contr?: any): string {
    const icon = this.getTicketIcon(contractId, contr);
    switch (icon) {
      case "mark_chat_unread":
        return "Hai messaggi non letti";
      case "chat":
        return "Visualizza ticket esistente";
      default:
        return "Crea nuovo ticket assistenza";
    }
  }

  openTicketModal(row: any, event?: Event): void {
    this.ApiService.PrendiUtente().subscribe((Auth: any) => {
      const userRole = Auth.user?.role_id;

      const contratto = {
        id: row.id,
        cliente: row.cliente,
        pivacf: row.pivacf,
        datains: row.datains,
        prodotto: row.prodotto,
        seu: row.seu,
        stato: row.stato,
      };

      this.ApiService.getTicketByContractId(row.id).subscribe({
        next: (response: any) => {
          if (response.response === "ok" && response.body?.ticket) {
            this.openExistingTicketModal(response.body.ticket, contratto);
          } else {
            this.checkAndCreateTicket(contratto, userRole);
          }
        },
        error: (error) => {
          this.snackBar.open("Errore nella verifica del ticket", "Chiudi", {
            duration: 3000,
            horizontalPosition: "center",
            verticalPosition: "bottom",
            panelClass: ["error-snackbar"],
          });
          this.checkAndCreateTicket(contratto, userRole);
        },
      });
    });
  }

  private checkAndCreateTicket(contratto: any, userRole: number): void {
    const restrictedRoles = [2, 3];

    if (restrictedRoles.includes(userRole)) {
      this.ApiService.getTicketByContractId(contratto.id).subscribe({
        next: (response: any) => {
          if (response.response === "ok" && response.body?.ticket) {
            this.snackBar.open(
              "Esiste già un ticket per questo contratto. Apertura chat esistente...",
              "Chiudi",
              {
                duration: 3000,
                horizontalPosition: "center",
                verticalPosition: "bottom",
                panelClass: ["warning-snackbar"],
              }
            );
            this.openExistingTicketModal(response.body.ticket, contratto);
          } else {
            this.openTicketCreationModal(contratto);
          }
        },
        error: (error) => {
          this.openTicketCreationModal(contratto);
        },
      });
    } else {
      this.openTicketCreationModal(contratto);
    }
  }

  private saveScrollPosition(): void {
    this.savedScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
  }

  private restoreScrollPosition(): void {
    setTimeout(() => {
      window.scrollTo(0, this.savedScrollPosition);
    }, 0);
  }

  private openExistingTicketModal(ticket: any, contratto: any): void {
    const contractData = {
      contractId: contratto.id,
      contractCode: contratto.id.toString(),
      clientName: contratto.cliente,
      pivacf: contratto.pivacf,
      dateIns: contratto.datains,
      productName: contratto.prodotto,
      seuName: contratto.seu,
      status: contratto.stato,
    };

    this.saveScrollPosition();
    this.contrattoselezionato = true;
    this.cleanupBackdrops();

    const dialogRef = this.dialog.open(ContrattoDetailsDialogComponent, {
      width: "900px",
      maxWidth: "95vw",
      maxHeight: "90vh",
      data: {
        reparto: "ticket",
        contractData: contractData,
        existingTicket: ticket,
      },
      disableClose: false,
      hasBackdrop: true,
      backdropClass: 'custom-backdrop',
      panelClass: 'ticket-modal-panel',
      autoFocus: false,
      restoreFocus: false,
      scrollStrategy: this.overlay.scrollStrategies.block()
    });

    dialogRef.afterOpened().subscribe(() => {
      this.restoreScrollPosition();
    });

    dialogRef.afterClosed().pipe(take(1)).subscribe(result => {
      if (this.DettagliContratto && this.DettagliContratto.length > 0) {
        this.contrattoselezionato = false;
      }

      this.checkTicketStatus(Number(contratto.id));
      
      if (result && result.success) {
        this.snackBar.open(
          'Conversazione aggiornata',
          'Chiudi',
          {
            duration: 2000,
            horizontalPosition: "center",
            verticalPosition: "bottom",
            panelClass: ["success-snackbar"],
          });
        }
        setTimeout(() => this.cleanupBackdrops(), 100);
      });
  }

  private openTicketCreationModal(contratto: any): void {
    const contractData = {
      contractId: contratto.id,
      contractCode: contratto.id.toString(),
      clientName: contratto.cliente,
      pivacf: contratto.pivacf,
      dateIns: contratto.datains,
      productName: contratto.prodotto,
      seuName: contratto.seu,
      status: contratto.stato,
    };

    this.saveScrollPosition();
    this.contrattoselezionato = true;
    this.cleanupBackdrops();

    const dialogRef = this.dialog.open(ContrattoDetailsDialogComponent, {
      width: "800px",
      maxWidth: "95vw",
      maxHeight: "90vh",
      data: {
        reparto: "ticket",
        contractData: contractData,
        existingTicket: null,
      },
      disableClose: false,
      hasBackdrop: true,
      backdropClass: 'custom-backdrop',
      panelClass: 'ticket-modal-panel',
      autoFocus: false,
      restoreFocus: false,
      scrollStrategy: this.overlay.scrollStrategies.block()
    });

    dialogRef.afterOpened().subscribe(() => {
      this.restoreScrollPosition();
    });

    dialogRef.afterClosed().pipe(take(1)).subscribe(result => {
      if (this.DettagliContratto && this.DettagliContratto.length > 0) {
        this.contrattoselezionato = false;
      }

      if (result && result.success) {
        this.checkTicketStatus(Number(contratto.id));
        
        this.snackBar.open(
          'Ticket creato con successo! Il backoffice è stato notificato.',
          'Chiudi',
          {
            duration: 4000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['success-snackbar']
          }
        );
      }
      setTimeout(() => this.cleanupBackdrops(), 100);
    });
  }

  private cleanupBackdrops(): void {
    const transparentBackdrops = document.querySelectorAll(
      ".cdk-overlay-transparent-backdrop"
    );
    transparentBackdrops.forEach((backdrop) => {
      if (backdrop instanceof HTMLElement) {
        backdrop.style.display = "none";
        backdrop.style.visibility = "hidden";
        backdrop.style.pointerEvents = "none";
      }
    });
  }
}