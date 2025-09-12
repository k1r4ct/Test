import {
  AfterViewInit,
  Component,
  OnInit,
  ViewChild,
  ChangeDetectorRef,
  DoCheck,   
  AfterContentInit,
} from '@angular/core';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort, Sort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { ContrattoService } from 'src/app/servizi/contratto.service';
import { SharedService } from 'src/app/servizi/shared.service';
import { ApiService } from 'src/app/servizi/api.service';
import { BehaviorSubject, isEmpty, Subject } from 'rxjs'; // Import BehaviorSubject
import { trigger, transition, style, animate } from '@angular/animations';
import { MatIconRegistry } from '@angular/material/icon';
import { DomSanitizer } from '@angular/platform-browser';
import { FormControl, FormGroup } from '@angular/forms';
import { Router } from '@angular/router';
import { DropzoneComponent } from 'ngx-dropzone-wrapper';
import { ActivatedRoute } from '@angular/router';
import { MatDateRangePicker } from '@angular/material/datepicker';
import { RicercaclientiService } from 'src/app/servizi/ricercaclienti.service';
import { saveAs } from 'file-saver';
import * as Papa from 'papaparse';
import { MatDialog } from '@angular/material/dialog';
import { ConfirmDialogComponent } from 'src/app/confirm-dialog/confirm-dialog.component';

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
  tipo: 'text' | 'number' | 'boolean' | 'select' | 'unknown'; // Tipo della risposta
}
// ... (costanti FRUITS, NAMES e USERS)

@Component({
  selector: 'app-lista-contratti',
  standalone: false,
  templateUrl: './listaContratti.component.html',
  styleUrl: './listaContratti.component.scss',
  animations: [
    trigger('pageTransition', [
      transition(':enter', [
        style({ opacity: 0, transform: 'scale(0.1)' }),
        animate(
          '500ms ease-in-out',
          style({ opacity: 1, transform: 'scale(1)' })
        ),
      ]),
      transition(':leave', [
        animate(
          '500ms ease-in-out',
          style({ opacity: 0, transform: 'scale(0.1)' })
        ),
      ]),
    ]),
  ],
})
export class ListaContrattiComponent
  implements OnInit, DoCheck, AfterViewInit
{
  DettagliContratto: DettagliContratto[] = [];
  LISTACONTRATTI: ListContrattiData[] = [];
  LISTACLIENTI: ListaClienti[] = [];
  ALTRIPRODOTTI: AltriProdotti[] = [];
  MACROPRODOTTI: MacroProdotti[] = [];
  MODIFICAMASSIVA: ModificaMassiva[] = [];
  CONTEGGIOPRODOTTI: ConteggioProdotti[] = [];
  fileSelezionati: any[] = [];
  visualizzaModificaMassiva = false;
  formID = new FormControl('');
  formCliente = new FormControl('');
  selectAllChecked = false;
  formDataIns = new FormControl('');
  isPickerformDataInsDisabled = false;

  formDataStipula = new FormControl('');
  isPickerformDataStipulaDisabled = false;

  listacfpi: string[] = [];
  listacfpi_bk: string[] = [];
  formCFPI = new FormControl('');

  listaProdotti: string[] = [];
  listaProdotti_bk: string[] = [];
  formProdotti = new FormControl('');
  seu: SEU[] = [];
  seuSelected: any;
  listaSEU: string[] = [];
  formSEU = new FormControl('');

  contrattoSelezionato = new FormControl('');

  listaMacroPro: string[] = [];
  formMacroPro = new FormControl('');

  listaMacroStato: string[] = [];
  formMacroStato = new FormControl('');

  listaStato: string[] = [];
  formStato = new FormControl('');

  listaSupplier: string[] = [];
  listaSupplier_bk: string[] = [];
  formSupplier = new FormControl('');

  listaStatiAvanzamento: any[] = [];
  listaStatiOption: any[] = [];
  OptionStatus: optionStatus[] = [];
  displayedColumns: string[] = [
    'id',
    'cliente',
    'pivacf',
    'datains',
    'datastipula',
    'prodotto',
    'seu',
    'stato',
    'file',
    'azioni',
  ];
  selected = true;
  opzioneTEXT = '';
  dataSourceFilters!: MatTableDataSource<ListContrattiData>;
  matspinner = true;

  selectMulti = false;
  enableMultiSelect = false;
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;
  @ViewChild(DropzoneComponent) dropzoneComponent!: DropzoneComponent; // Referenza al componente figlio Dropzone

  @ViewChild('pickerDataIns') pickerDataIns!: MatDateRangePicker<Date>;
  @ViewChild('pickerDataStipula') pickerDataStipula!: MatDateRangePicker<Date>;

  idContrattoSelezionatoSubject: Subject<number> = new Subject<number>();
  state: any;
  filterSelectObj!: any[];
  //filterDictionary = new Map<string, string[]>();
  filterDictionary = new Map<string, string | string[]>();
  idcontratto: any;
  contrattoselezionato = true;

  arrayDomandeDaEscludere: string[] = [];
  nuovaspecific_data: any[] = [];

  caricacontratto: boolean = false;  

  private contrattiSubject = new BehaviorSubject<ListContrattiData[]>([]);
  contratti$ = this.contrattiSubject.asObservable();
  User: any;
  NomeTabella: any;
  public countContratti = 0;
  toppings = new FormControl('');
  toppingList: string[] = [
    'Extra cheese',
    'Mushroom',
    'Onion',
    'Pepperoni',
    'Sausage',
    'Tomato',
  ];
  row: any;
  filtroQueryString: any;
  OGGETTO_FILTRICONTRATTI: any;
  macroStati: string[] = [];
  microStatiPerMacroStato: { [macroStato: string]: MicroStatoItem } = {};
  abilitaDownload = false;
  non_modificare_risposta = false;
  idcontrattosel: number = 0;
  idmacroprodottocontratto: number = 0;

  constructor(
    private sharedservice: SharedService,
    private shContratto: ContrattoService,
    private ApiService: ApiService,
    private matIconRegistry: MatIconRegistry,
    private domSanitizer: DomSanitizer,
    private router: Router,
    private activatedRoute: ActivatedRoute,
    private changeDetectorRef: ChangeDetectorRef,
    private ricercaCliente: RicercaclientiService,
    private dialog: MatDialog
  ) {
    this.matIconRegistry.addSvgIcon(
      'file-jpg',
      this.domSanitizer.bypassSecurityTrustResourceUrl(
        'assets/icons/file-jpg.svg'
      ) // Percorso dell'icona JPG
    );
    this.matIconRegistry.addSvgIcon(
      'file-png',
      this.domSanitizer.bypassSecurityTrustResourceUrl(
        'assets/icons/file-jpg.svg'
      ) // Percorso dell'icona JPG
    );
    this.matIconRegistry.addSvgIcon(
      'file-pdf',
      this.domSanitizer.bypassSecurityTrustResourceUrl(
        'assets/icons/file-pdf.svg'
      ) // Percorso dell'icona PDF
    );
  }

  recuperaAuth() {
    this.ApiService.PrendiUtente().subscribe((Auth: any) => {
      //console.log(Auth);
      // console.log(Auth.user.role.descrizione);
      // console.log(Auth.user.name);
      // console.log(Auth.user.role_id);      

      if (Auth.user.role_id == 1 ) {
        this.non_modificare_risposta = false;
        this.abilitaDownload = true;
      }else if(Auth.user.role_id == 5){  
        this.non_modificare_risposta = false;
        this.abilitaDownload = false;        
      } else {
        this.non_modificare_risposta = true;
        this.abilitaDownload = false;
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
    this.router.navigate(['/clienti']);
  }

  vaiAClientiCodFPiva(codFpiva: any) {
    //console.log(codFpiva);
    this.ricercaCliente.setRicerca(codFpiva);
    this.router.navigate(['/clienti']);
  }

  getFileIcon(filename: string): string {
    const extension = filename.split('.').pop()?.toLowerCase(); // Ottieni l'estensione del file

    switch (extension) {
      case 'jpg':
      case 'png':
      case 'jpeg':
        return 'insert_drive_file';
      case 'pdf':
        return 'picture_as_pdf';
      default:
        return 'insert_drive_file'; // Icona predefinita per altri tipi di file
    }
  }

  trasformaData(dataString: string): string | null {
    // Regex più flessibile per i formati accettati
    const regex = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/;
    const match = dataString.match(regex);

    if (match) {
      let [, giorno, mese, anno] = match;

      // Aggiungi zeri iniziali se necessario
      giorno = giorno.padStart(2, '0');
      mese = mese.padStart(2, '0');

      return `${anno}/${mese}/${giorno}`;
    } else {
      console.error(
        'Formato data non valido. Utilizzare d/M/yyyy, dd/MM/yyyy, ecc.'
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
    this.matspinner = false;
    this.sharedservice.hideRicercaContratto();

    this.activatedRoute.queryParams.subscribe((params) => {
      // console.log(params);

      if (params['filtro'] && typeof params['filtro'] === 'string') {
        // Controlla se il parametro esiste ed è una stringa
        try {
          this.filtroQueryString = JSON.parse(params['filtro']);
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
        // console.log(contratti);
        //console.log(contratti.body.risposta);
        this.dataSourceFilters = new MatTableDataSource(this.LISTACONTRATTI);
        if (contratti.body.risposta && contratti.body.risposta.length > 0) {
          this.matspinner = true;
          document.getElementById('over')?.classList.remove('overlay');

          this.LISTACLIENTI = contratti.body.risposta.map((contratto: any) => ({
            cliente:
              contratto.customer_data.cognome && contratto.customer_data.nome
                ? contratto.customer_data.cognome +
                  ' ' +
                  contratto.customer_data.nome
                : contratto.customer_data.ragione_sociale, // Fallback se ragione_sociale è nullo
          }));

          let ricavacfpi = contratti.body.risposta.map((contratto: any) =>
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

          let ricavaSEU = contratti.body.risposta.map(
            (contratto: any) =>
              contratto.user_seu.cognome + ' ' + contratto.user_seu.name
          );
          // ordina in ordine alfabetico
          ricavaSEU.sort();
          this.listaSEU = ricavaSEU.filter((str: any, index: any) => {
            const set = new Set(ricavaSEU.slice(0, index));
            return !set.has(str);
          });
          //console.log(this.listaSEU);

          let ricavaProdotti = contratti.body.risposta.map(
            (contratto: any) => contratto.product.descrizione
          );
          // ordina in ordine alfabetico
          ricavaProdotti.sort();
          // togli i duplicati
          this.listaProdotti = ricavaProdotti.filter((str: any, index: any) => {
            const set = new Set(ricavaProdotti.slice(0, index));
            return !set.has(str);
          });
          this.listaProdotti_bk = this.listaProdotti;
          //console.log(this.listacfpi);

          let ricavaMacroPro = contratti.body.risposta.map(
            (contratto: any) => contratto.product.macro_product.descrizione
          );
          // ordina in ordine alfabetico
          ricavaMacroPro.sort();
          // togli i duplicati
          this.listaMacroPro = ricavaMacroPro.filter((str: any, index: any) => {
            const set = new Set(ricavaMacroPro.slice(0, index));
            return !set.has(str);
          });
          //console.log(this.listacfpi);

          // let ricavaMacSta = contratti.body.risposta.map(
          //   (contratto: any) =>
          //     contratto.status_contract.option_status_contract.map( (opt: any) =>
          //       opt.macro_stato
          //     )
          // );
          //console.log(contratti);

          let ricavaMacSta = contratti.body.risposta.map(
            (contratto: any) =>
              contratto.status_contract.option_status_contract[0].macro_stato
          );
          // ordina in ordine alfabetico
          ricavaMacSta.sort();
          this.listaMacroStato = ricavaMacSta.filter((str: any, index: any) => {
            const set = new Set(ricavaMacSta.slice(0, index));
            return !set.has(str);
          });
          // console.log(this.listaMacroStato);

          let ricavaStato = contratti.body.risposta.map(
            (contratto: any) => contratto.status_contract.micro_stato
          );
          // ordina in ordine alfabetico
          ricavaMacroPro.sort();
          // togli i duplicati
          this.listaStato = ricavaStato.filter((str: any, index: any) => {
            const set = new Set(ricavaStato.slice(0, index));
            return !set.has(str);
          });
          //console.log(this.listacfpi);

          let ricavaSupplier = contratti.body.risposta.map(
            (contratto: any) => contratto.product.supplier.nome_fornitore
          );
          ricavaSupplier.sort();
          this.listaSupplier = ricavaSupplier.filter((str: any, index: any) => {
            const set = new Set(ricavaSupplier.slice(0, index));
            return !set.has(str);
          });
          //console.log(this.listaSupplier);
          this.listaSupplier_bk = this.listaSupplier;

          // lista contratti che va a popolare l'oggetto this.dataSourceFilters = new MatTableDataSource(this.LISTACONTRATTI)
          // e cha ha la sua interfaccia ListContrattiData[] = [];
          // può contenere dati in colonna maggiori rispetto alle colonne visualizzate in tabella ( lato html )
          // che hanno UN EFFETTO in base hai filtri sulla visualizzazione.

          this.LISTACONTRATTI = contratti.body.risposta.map(
            (contratto: any) => ({
              id: contratto.id,
              cliente:
                contratto.customer_data.cognome && contratto.customer_data.nome
                  ? contratto.customer_data.cognome +
                    ' ' +
                    contratto.customer_data.nome
                  : contratto.customer_data.ragione_sociale,
              pivacf: contratto.customer_data.codice_fiscale
                ? contratto.customer_data.codice_fiscale
                : contratto.customer_data.partita_iva,
              datains: contratto.data_inserimento,
              datastipula: contratto.data_stipula,
              prodotto: contratto.product.descrizione,
              seu: contratto.user_seu.cognome + ' ' + contratto.user_seu.name,
              macroprodotto: contratto.product.macro_product.descrizione,
              macrostato:
                contratto.status_contract.option_status_contract[0].macro_stato,
              stato: contratto.status_contract.micro_stato,
              file: contratti.body.file[contratto.id],
              ragione_sociale: contratto.customer_data.ragione_sociale,
              supplier: contratto.product.supplier.nome_fornitore,
              specific_data: contratto.specific_data || [],
              customer_data: contratto.customer_data || {},
            })
          );

          // console.log(this.LISTACONTRATTI.length);
          // console.log(this.LISTACONTRATTI);
          this.countContratti = this.LISTACONTRATTI.length;
          this.dataSourceFilters = new MatTableDataSource(this.LISTACONTRATTI);
          this.dataSourceFilters.paginator = this.paginator;
          //console.log(this.dataSourceFilters);

          // Imposta il comparatore personalizzato
          this.dataSourceFilters.sortingDataAccessor = (item, property) => {
            switch (property) {
              case 'datains':
                //console.log(item.datains);
                //console.log(this.parseDate(item.datains));
                return this.parseDate(item.datains);
              case 'datastipula':
                return this.parseDate(item.datastipula);
              default:
                return (item as any)[property];
            }
          };

          if (!this.filtroQueryString || this.filtroQueryString.length == 0) {
            this.dataSourceFilters.sort = this.sort;
            const sortState: Sort = { active: 'id', direction: 'desc' };
            this.sort.active = sortState.active;
            this.sort.direction = sortState.direction;
            this.sort.sortChange.emit(sortState);
          }
        } else {
          //console.log("Nessun contratto trovato");

          this.matspinner = true;
          document.getElementById('over')?.classList.remove('overlay');
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

            if (field == 'id') {
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

            if (field == 'datains') {
              let dtini: any = '';
              let dtfin: any = '';
              let dtver: any = '';

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

            if (field == 'datastipula') {
              let dtini: any = '';
              let dtfin: any = '';
              let dtver: any = '';

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

            if (field == 'cliente') {
              cicli++;
              stringEleArray = record[field as keyof ListContrattiData];
              risultatiFiltroAND.push(
                stringEleArray.toLowerCase().includes(valori)
              );
            }

            if (field == 'prodotto') {
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

            if (field == 'macroprodotto') {
              for (let valore of valori) {
                cicli++;
                stringEleArray = record[field as keyof ListContrattiData];
                risultatiFiltroORmacroprodotto.push(stringEleArray == valore);
              }
              risultatiFiltroAND.push(
                risultatiFiltroORmacroprodotto.some((risultato) => risultato)
              );
            }

            if (field == 'macrostato') {
              for (let valore of valori) {
                cicli++;
                stringEleArray = record[field as keyof ListContrattiData];
                risultatiFiltroORmacrostato.push(stringEleArray == valore);
              }
              risultatiFiltroAND.push(
                risultatiFiltroORmacrostato.some((risultato) => risultato)
              );
            }

            if (field == 'pivacf') {
              for (let valore of valori) {
                cicli++;
                stringEleArray = record[field as keyof ListContrattiData];
                risultatiFiltroORpivacf.push(stringEleArray == valore);
              }
              risultatiFiltroAND.push(
                risultatiFiltroORpivacf.some((risultato) => risultato)
              );
            }

            if (field == 'stato') {
              for (let valore of valori) {
                cicli++;
                stringEleArray = record[field as keyof ListContrattiData];
                risultatiFiltroORstato.push(stringEleArray == valore);
              }
              risultatiFiltroAND.push(
                risultatiFiltroORstato.some((risultato) => risultato)
              );
            }

            if (field == 'seu') {
              for (let valore of valori) {
                cicli++;
                stringEleArray = record[field as keyof ListContrattiData];
                risultatiFiltroORseu.push(stringEleArray == valore);
              }
              risultatiFiltroAND.push(
                risultatiFiltroORseu.some((risultato) => risultato)
              );
            }

            if (field == 'supplier') {
              for (let valore of valori) {
                cicli++;
                stringEleArray = record[field as keyof ListContrattiData];
                risultatiFiltroORsupplier.push(stringEleArray == valore);
              }
              risultatiFiltroAND.push(
                risultatiFiltroORsupplier.some((risultato) => risultato)
              );
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
      //console.log(statiAvanzamento);

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
      classSel + ' .mat-mdc-text-field-wrapper'
    ) as HTMLElement;
    if (targetElement) {
      targetElement.classList.add('custom-background');
    }
  }

  // Funzione per gestire la perdita di focus
  onInputBlur(classSel: string) {
    // console.log("blur");
    const targetElement = document.querySelector(
      classSel + ' .mat-mdc-text-field-wrapper'
    ) as HTMLElement;
    if (targetElement) {
      targetElement.classList.remove('custom-background');
    }
  }

  parseDate(dateString: string): Date {
    const [day, month, year] = dateString.split('/');
    return new Date(+year, +month - 1, +day);
  }

  filterSelectCFPI(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    this.listacfpi = this.listacfpi_bk;
    this.listacfpi = this.listacfpi.filter((item) =>
      item.toLowerCase().includes(filterValue.toLowerCase())
    );
  }

  filterSelectProdotti(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    // console.log(filterValue);
    this.listaProdotti = this.listaProdotti_bk;
    this.listaProdotti = this.listaProdotti.filter((item) =>
      item.toLowerCase().includes(filterValue.toLowerCase())
    );
  }

  filterSelectSupplier(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    // console.log(filterValue);
    this.listaSupplier = this.listaSupplier_bk;
    this.listaSupplier = this.listaSupplier.filter((item) =>
      item.toLowerCase().includes(filterValue.toLowerCase())
    );
  }

  ngAfterViewInit() {
    // console.log('ngAfterViewInit');

    if (this.filtroQueryString && this.filtroQueryString.length > 0) {
      this.dataSourceFilters.sort = this.sort;
      const sortState: Sort = { active: 'datains', direction: 'desc' };
      this.sort.active = sortState.active;
      this.sort.direction = sortState.direction;
      this.sort.sortChange.emit(sortState);

      let filtroStringa = JSON.parse(this.filtroQueryString);

      const oggetto = Object.fromEntries(filtroStringa);

      if (oggetto['datains'] != undefined) {
        // console.log(oggetto['datains'][0]);
        // console.log(oggetto['datains'][1]);

        const datainsINI = this.trasformaData(oggetto['datains'][0]);
        const datainsFIN = this.trasformaData(oggetto['datains'][1]);

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

      if (oggetto['datastipula'] != undefined) {
        // console.log(oggetto['datastipula'][0]);
        // console.log(oggetto['datastipula'][1]);

        const datastipulaINI = this.trasformaData(oggetto['datastipula'][0]);
        const datastipulaFIN = this.trasformaData(oggetto['datastipula'][1]);

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

      if (oggetto['cliente'] != undefined) {
        this.formCliente.setValue(oggetto['cliente']);
      } else {
        this.formCliente.disable();
      }

      if (oggetto['prodotto'] != undefined) {
        this.formProdotti.setValue(oggetto['prodotto']);
      } else {
        this.formProdotti.disable();
      }

      if (oggetto['macroprodotto'] != undefined) {
        this.formMacroPro.setValue(oggetto['macroprodotto']);
      } else {
        this.formMacroPro.disable();
      }

      if (oggetto['pivacf'] != undefined) {
        this.formCFPI.setValue(oggetto['pivacf']);
      } else {
        this.formCFPI.disable();
      }

      if (oggetto['macrostato'] != undefined) {
        this.formMacroStato.setValue(oggetto['macrostato']);
      } else {
        this.formMacroStato.disable();
      }

      if (oggetto['seu'] != undefined) {
        this.formSEU.setValue(oggetto['seu']);
      } else {
        this.formSEU.disable();
      }

      if (oggetto['stato'] != undefined) {
        this.formStato.setValue(oggetto['stato']);
      } else {
        this.formStato.disable();
      }

      this.OGGETTO_FILTRICONTRATTI = this.filtroQueryString;
      this.dataSourceFilters.filter = this.filtroQueryString;

      if (this.dataSourceFilters.paginator) {
        this.dataSourceFilters.paginator.firstPage();
      }
    } else {
      // Filtro vuoto
      // console.log('Filtro vuoto');
    }


    const contenitore = document.getElementById('domande_non_compilate');
    if (!contenitore) {
      // console.log(' non esiste domande_non_compilate');      
    }else{
      // console.log(' OK esiste domande_non_compilate');

      if (this.caricacontratto) {
        this.ordinaDomandeForm();
        this.caricacontratto = false;
      }
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
    let jsonString: string = '';
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

      this.dataSourceFilters.filter = jsonString;

      if (this.dataSourceFilters.paginator) {
        this.dataSourceFilters.paginator.firstPage();
      }
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

    let jsonString: string = '';

    if (value!.length == 0) {
      this.filterDictionary.delete(fieldTable);
    } else {
      this.filterDictionary.set(fieldTable, value!);
      // console.log(this.filterDictionary);
    }

    this.OGGETTO_FILTRICONTRATTI = JSON.stringify(
      Array.from(this.filterDictionary.entries())
    );
    jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));

    this.dataSourceFilters.filter = jsonString;

    if (this.dataSourceFilters.paginator) {
      this.dataSourceFilters.paginator.firstPage();
    }
  }

  applyFilterId(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    const separators = /[\s,]+/; // Espressione regolare per virgola, tabulazione e ritorno a capo
    const ids = filterValue
      .split(separators)
      .map((id) => id.trim())
      .filter((id) => id !== '');

    if (ids.length > 0) {
      this.filterDictionary.set('id', ids);
    } else {
      this.filterDictionary.delete('id');
    }

    this.OGGETTO_FILTRICONTRATTI = JSON.stringify(
      Array.from(this.filterDictionary.entries())
    );
    const jsonString = JSON.stringify(
      Array.from(this.filterDictionary.entries())
    );

    this.dataSourceFilters.filter = jsonString;

    if (this.dataSourceFilters.paginator) {
      this.dataSourceFilters.paginator.firstPage();
    }
  }

  applyFilterName(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    //console.log(filterValue);
    //console.log(filterValue.trim().toLowerCase());

    let jsonString: string = '';

    this.filterDictionary.set('cliente', filterValue.trim().toLowerCase());

    this.OGGETTO_FILTRICONTRATTI = JSON.stringify(
      Array.from(this.filterDictionary.entries())
    );
    jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));

    this.dataSourceFilters.filter = jsonString;

    if (this.dataSourceFilters.paginator) {
      this.dataSourceFilters.paginator.firstPage();
    }
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
      .navigateByUrl('/refresh', { skipLocationChange: false })
      .then(() => {
        this.router.navigate(['/contratti'], {
          queryParams: {
            filtro: null,
          },
        });
      });
  }

  // Assicurati che la funzione che contiene questo codice sia marcata come 'async'
  // async RecuperSpecifiData(contratto: any) {
  //   // Itera su ogni 'dato' e attendi il risultato delle chiamate asincrone
  //   for (const dato of contratto.specific_data) {
  //     const domanda = dato.domanda;
  //     const risposta = await this.getRisposta(dato); // 'await' qui blocca l'esecuzione finché la Promise non si risolve
  //     const tipo = await this.getTipoRisposta(dato); // 'await' qui blocca l'esecuzione finché la Promise non si risolve

  //     this.nuovaspecific_data.push({ domanda, risposta, tipo });
  //   }
  // }

  clickedRows(r: any) {
    //  con riferimento a contenitore = document.getElementById('domande_specific_data'); svuota il contenitore
    const contenitore = document.getElementById('domande_specific_data');
    if (contenitore) {
      contenitore.innerHTML = '';
    }

    this.nuovaspecific_data = [];
    this.caricacontratto = true;

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
      'check-' + r.id
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
      'citta',
      'indirizzo',
      'cap',
      'email',
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

      document.getElementById('matspin')?.classList.remove('centraspinner');
      document.getElementById('over')?.classList.remove('overlay');
      this.matspinner = true;

      this.fileSelezionati = contratti.body.file[r.id];
      this.DettagliContratto = contratti.body.risposta.map((contratto: any) => {
        return {
          inserito_da_user_id: contratto.inserito_da_user_id,
          id: contratto.id,
          cliente: contratto.customer_data.nome
            ? contratto.customer_data.nome +
              ' ' +
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
            : '',
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

      this.ApiService.recuperaSEU().subscribe((SEU: any) => {
        // console.log("recupera lista SEU");
        // console.log(SEU);

        this.seu = SEU.body.risposta.map((allSeu: any) => {
          // console.log(allSeu);

          return {
            id: allSeu.id,
            nominativo:
              allSeu.name || allSeu.cognome
                ? allSeu.name + ' ' + allSeu.cognome
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
    this.opzioneTEXT = '(OPZIONE PRECEDENTE)';
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

  getTipoRisposta(dato: any): any {
    // console.log('dentro risposta tipo valuto dato ' + dato.tipo_risposta);

    if (dato.tipo_risposta == 'numero') {
      return 'number';
    } else if (dato.tipo_risposta == 'stringa') {
      return 'text';
    } else if (dato.tipo_risposta == 'select') {
      return 'select';
    } else if (dato.tipo_risposta == 'sino') {
      return 'boolean';
    } else {
      return 'unknown';
    }
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
        // console.log('dati ricevuti', Risposta.ListaDomande);
        this.nuovaspecific_data = Risposta.ListaDomande;
      },
      error: (error) => {
        console.error('Errore durante il recupero delle domande:', error);
      },
    });
  }

  ordinaDomandeForm() {

    const contenitore = document.getElementById('domande_specific_data'); // Ottieni il contenitore padre
    if (!contenitore) {
      // console.error('Contenitore non trovato!');
      return;
    } else {
      // Seleziona tutti i div con la classe 'miac1' all'interno del contenitore
      const divElements = contenitore.querySelectorAll('.domanda_sp_dt');
      // console.log(divElements);

      // Converti la NodeList in un Array per poter usare il metodo sort()
      const divArray = Array.from(divElements);
      // console.log(divElements);
      // console.log(divArray);

      // Ordina l'array in base al valore alfabetico (stringa) dell'attributo 'tag'
      divArray.sort((a, b) => {
        const tagA = a.getAttribute('tag'); // Ottiene il valore del tag come stringa
        const tagB = b.getAttribute('tag'); // Ottiene il valore del tag come stringa

        // Usa localeCompare per un confronto alfabetico corretto tra stringhe
        // Gestisce anche caratteri speciali e maiuscole/minuscole in modo più robusto
        return tagA!.localeCompare(tagB!);
      });

      // Rimuovi tutti gli elementi esistenti dal contenitore
      while (contenitore.firstChild) {
        contenitore.removeChild(contenitore.firstChild);
      }

      // Riappendi gli elementi ordinati al contenitore
      divArray.forEach((div) => {
        contenitore.appendChild(div);
      });
    }
  }

  // ListaDomandeMacro(idmacroproduct: any, domandedaescludere: any) : any{
  //   console.log('funzione ListaDomandeMacro');

  //   this.ApiService.getDomandeMacro(
  //     idmacroproduct,
  //     domandedaescludere
  //   ).subscribe((Risposta: any) => {
  //     console.log(Risposta.idcontratto);
  //     console.log(Risposta.ListaDomande);
  //     return Risposta.ListaDomande;
  //   });
  // }

  recupera_opzioni_select(domanda: any, rispostaindicata: any) {
    // console.log(' recupera ozioni risposta ');
    // console.log(domanda.id);
    // console.log(rispostaindicata);

    const select = document.getElementById(domanda.id) as HTMLSelectElement;
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

  deleteFile(file: any) {
    let domanda = file.id;
    let reparto = 'allegatocontratto';
    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      data: { id: domanda, reparto: reparto },
      disableClose: true,
      panelClass: 'custom-dialog-container',
    });
    dialogRef.afterClosed().subscribe((result) => {
      // console.log(result);
      if (result) {
        // L'utente ha confermato l'eliminazione
        //console.log(file);
        const formData = new FormData();
        formData.append('idContratto', file.id);
        formData.append('nameFileGet', file.name);
        // 1. Chiama la tua API di eliminazione, passando l'ID del file
        this.ApiService.deleteIMG(formData).subscribe({
          next: (response) => {
            // console.log(this.fileSelezionati);
            // console.log("fileid -->> " + file.id);
            // console.log('fileid -->> ' + file.name);
            // console.log(response.response);

            if (response.response == 'ok') {
              // Update file list by removing deleted file
              this.fileSelezionati = this.fileSelezionati.filter(
                (f) => f.name !== file.name
              );
              // Show success message
              // console.log('File deleted successfully');
            } else {
              // Show warning for unsuccessful deletion
              // console.log('Unable to delete file');
            }
          },
          error: (error) => {
            // Handle API error
            // console.error('Error deleting file:', error);
            // console.log('Error deleting file. Please try again later.');
          },
          complete: () => {
            // Optional: Any cleanup or final actions
            // console.log(this.fileSelezionati);
          },
        });
      } else {
        // L'utente ha annullato l'eliminazione
      }
    });
  }

  isImage(filename: string): boolean {
    const extension = filename.split('.').pop()?.toLowerCase();
    return ['jpg', 'jpeg', 'png', 'gif'].includes(extension || '');
  }

  updateContratto(id: any) {
    //console.log(id);
    const stato_avanzamento = document.querySelector(
      '.stato_avanzamento'
    ) as HTMLSelectElement; // Trova l'elemento select
    const statoAvanzamento = stato_avanzamento?.value;

    const note_backoffice = document.querySelector(
      '.note_backoffice'
    ) as HTMLSelectElement; // Trova l'elemento select
    const noteBackoffice = note_backoffice?.value;
    //console.log(statoAvanzamento);

    const nomecontraente = document.querySelector(
      '.nome_contraente'
    ) as HTMLSelectElement; // Trova l'elemento select
    const nome_contraente = nomecontraente?.value;
    //console.log(statoAvanzamento);

    const pivacodfisccontraente = document.querySelector(
      '.pivacodfisc_contraente'
    ) as HTMLSelectElement; // Trova l'elemento select
    const pivacodfisc_contraente = pivacodfisccontraente?.value;

    const capp = document.querySelector('.cap') as HTMLSelectElement; // Trova l'elemento select
    const cap = capp?.value;
    const city = document.querySelector('.citta') as HTMLSelectElement; // Trova l'elemento select
    const citta = city?.value;
    const mail = document.querySelector('.email') as HTMLSelectElement; // Trova l'elemento select
    const email = mail?.value;
    const indi = document.querySelector('.indirizzo') as HTMLSelectElement; // Trova l'elemento select
    const indirizzo = indi?.value;
    const tel = document.querySelector('.telefono') as HTMLSelectElement; // Trova l'elemento select
    const telefono = tel?.value;

    const macroproduct = document.querySelector(
      '.macroprodotto'
    ) as HTMLSelectElement; // Trova l'elemento select
    const macroprodotto = macroproduct?.value;

    const microproduct = document.querySelector(
      '.microprodotto'
    ) as HTMLSelectElement; // Trova l'elemento select
    const microprodotto = microproduct?.value;
    //console.log(statoAvanzamento);

    const formData = new FormData();
    formData.append('idContratto', id);
    formData.append('stato_avanzamento', statoAvanzamento);
    formData.append('note_backoffice', noteBackoffice);
    formData.append('nome_contraente', nome_contraente);
    formData.append('pivacodfisc_contraente', pivacodfisc_contraente);
    formData.append('macroprodotto', macroprodotto);
    formData.append('idmacroprodotto', this.idmacroprodottocontratto.toString());
    formData.append('microprodotto', microprodotto);
    formData.append('inserito_da', this.seuSelected.id);
    formData.append('cap_contraente', cap);
    formData.append('citta_contraente', citta);
    formData.append('email_contraente', email);
    formData.append('indirizzo_contraente', indirizzo);
    formData.append('telefono_contraente', telefono);

    const oggettoDomande: any = {};

    // dal <form> con name="domande_specific_data" nel componente cicla tutti campi recupernado i name e i value e stampali su console.log
    const specific_data = document.querySelectorAll('form[name="domande_specific_data"] input, form[name="domande_specific_data"] select');
    specific_data.forEach((element: any) => {
      // console.log(element.name, element.value);

      // Handle checkbox values by converting to 0/1
      if (element.type === 'checkbox') {
        let checkval: string = element.checked ? '1' : '0';
        oggettoDomande[element.name] = checkval;
      } else {
        oggettoDomande[element.name] = element.value;
      }
    });

    const domandejson = JSON.stringify(oggettoDomande);

    formData.append('specific_data', domandejson);
    // console.log(formData);

    this.ApiService.updateContratto(formData).subscribe(
      (ContrattoAggiornato: any) => {
        // console.log(ContrattoAggiornato);
        // crea oggetto con chiave id
        const contratto = { id: id };
        // richiamare la funzione clickedRows passando id del contratto appena salvato
        this.clickedRows(contratto);
        //this.aggiornaPagineEsettaFiltri();
      }
    );
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
        'selectMulti',
        ...this.displayedColumns.filter((col) => col !== 'selectMulti'),
      ];
    } else {
      this.displayedColumns = this.displayedColumns.filter(
        (col) => col !== 'selectMulti'
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
      '.stato_avanzamento_massivo'
    ) as HTMLSelectElement; // Trova l'elemento select
    const statoAvanzamento = stato_avanzamento_m?.value;
    const contrattiAll = JSON.stringify(this.MODIFICAMASSIVA);
    //console.log(contrattiAll);

    const formDataModificaMassiva = new FormData();

    formDataModificaMassiva.append('contratti', contrattiAll);
    formDataModificaMassiva.append('nuovostato', statoAvanzamento);

    this.ApiService.updateStatoMassivoContratti(
      formDataModificaMassiva
    ).subscribe((Risposta: any) => {
      // console.log(Risposta);
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
    // console.log(statofiltropieno);
    // console.log(this.OGGETTO_FILTRICONTRATTI);

    if (statofiltropieno) {
      const filtroSerializzato = JSON.stringify(this.OGGETTO_FILTRICONTRATTI);
      this.router
        .navigateByUrl('/refresh', { skipLocationChange: false })
        .then(() => {
          this.router.navigate(['/contratti'], {
            queryParams: {
              filtro: filtroSerializzato,
            },
          });
        });
    } else {
      this.router
        .navigateByUrl('/refresh', { skipLocationChange: false })
        .then(() => {
          this.router.navigate(['/contratti']);
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
          email: dettagli.email ?? '',
          telefono: dettagli.telefono ?? '',
          indirizzo: dettagli.indirizzo ?? '',
          cap: dettagli.cap ?? '',
          citta: dettagli.citta ?? '',
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
                ? 'Sì'
                : 'No'
              : '');

          row[domanda] = risposta ?? '';
        });

        return row;
      });

      // 4. Genera ed esporta il CSV
      const csv = Papa.unparse(risultatiFinali, { delimiter: ';' });
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      saveAs(blob, 'contratti_completi.csv');
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
        // Chiama isSelectMultiColumn solo se selectAllChecked è true
        if (this.selectAllChecked) {
          this.isSelectMultiColumn(
            row,
            this.selectAllChecked,
            document.getElementById('check-' + row.id) as HTMLInputElement
          );
        }
        const checkbox = document.getElementById(
          'check-' + row.id
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
}
