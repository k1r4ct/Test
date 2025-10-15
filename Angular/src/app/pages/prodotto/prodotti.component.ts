import { AfterViewInit, Component, DoCheck, OnInit, ViewChild, ViewChildren, QueryList, HostListener } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { MatSelect } from '@angular/material/select';
import { ApiService } from 'src/app/servizi/api.service';
import { FormControl } from '@angular/forms';
import { ContrattoService } from 'src/app/servizi/contratto.service';
import { trigger, transition, style, animate } from '@angular/animations';


export interface ListaProdotti {
  id: number;
  descrizione: string;
  macro_descrizione: string;
  macro_product: {
    codice_macro: string;
    id: number;
    descrizione: string;
  } | string; // Può essere sia oggetto che stringa per compatibilità
  nome_fornitore: string;
}

@Component({
  selector: 'app-prodotti',
  standalone: false,
  templateUrl: './prodotti.component.html',
  styleUrl: './prodotti.component.scss',
  animations: [
    trigger("pageTransition", [
      transition(":enter", [
        style({ opacity: 0, transform: "scale(0.1)" }), // Inizia piccolo al centro
        animate("500ms ease-in-out", style({ opacity: 1, transform: "scale(1)" })) // Espandi e rendi visibile
      ]),
      transition(":leave", [
        animate("500ms ease-in-out", style({ opacity: 0, transform: "scale(0.1)" })) // Riduci e rendi invisibile
      ])
    ])
  ]
})
export class ProdottiComponent implements AfterViewInit, OnInit  {

  //DoCheck
  state:any;
  displayedColumns: string[] = ['id', 'descrizione', 'macro_descrizione', 'macro_product', 'nome_fornitore'];
  dataSource: MatTableDataSource<ListaProdotti>;
  listaprodotti: ListaProdotti[];
  filterDictionary = new Map<string, string>();
  show_product: boolean = true;
  descrizione_prodotto_sel: string = "";

  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;
  @ViewChildren(MatSelect) selects!: QueryList<MatSelect>;


  mprToppings = new FormControl('');
  mpdToppings = new FormControl('');
  forToppings = new FormControl('');

  prodottiList: string[] = [];
  fornitoriList: string[] = [];
  MacroProList: string[] = [];
  MacroDescrizione: string[] = [];

  constructor(private servizioApi: ApiService,private Contratto: ContrattoService) {
    this.dataSource = new MatTableDataSource();
    this.listaprodotti = [];
  }

  // ===== Helper semplici e riusabili (allineati a GestioneProdotti) =====
  // Converte qualunque valore in stringa ripulita (senza spazi)
  private normalizza(val: any): string {
    return (val ?? '').toString().trim();
  }

  // Versione maiuscola per confronti case-insensitive
  private normalizzaUpper(val: any): string {
    return this.normalizza(val).toUpperCase();
  }

  // Estrae il codice macro da stringa o oggetto
  private codiceMacroDa(rec: any): string {
    const mp = rec?.macro_product;
    const codice = (typeof mp === 'object' && mp?.codice_macro) ? mp.codice_macro : mp;
    return this.normalizza(codice);
  }

  // Estrae la descrizione macro da campo dedicato o da macro_product.descrizione
  private descrizioneMacroDa(rec: any): string {
    const diretta = this.normalizza((rec as any)?.macro_descrizione);
    if (diretta) return diretta;
    const mp = (rec as any)?.macro_product;
    const fallback = (typeof mp === 'object') ? (mp?.descrizione || '') : '';
    return this.normalizza(fallback);
  }

  // Estrae il nome fornitore sia piatto che annidato
  private nomeFornitoreDa(rec: any): string {
    return this.normalizza((rec as any)?.nome_fornitore || (rec as any)?.supplier?.nome_fornitore || '');
  }

  // Aggiorna le opzioni visibili nelle select in base ai dati attualmente filtrati (effetto AND)
  private aggiornaOpzioniDisponibili(dati: any[]): void {
    // Codici macro
    this.MacroProList = Array.from(new Set(
      (dati || [])
        .map((r) => this.codiceMacroDa(r))
        .filter((v) => !!v)
    )).sort();

    // Macro prodotto (descrizione)
    this.MacroDescrizione = Array.from(new Set(
      (dati || [])
        .map((r) => this.descrizioneMacroDa(r))
        .filter((v) => !!v)
    )).sort();

    // Fornitori
    this.fornitoriList = Array.from(new Set(
      (dati || [])
        .map((r) => this.nomeFornitoreDa(r))
        .filter((v) => !!v)
    )).sort();

    // Prodotti (descrizione)
    this.prodottiList = Array.from(new Set(
      (dati || [])
        .map((r: any) => this.normalizza((r as any)?.descrizione))
        .filter((v) => !!v)
    )).sort();
  }

  ngOnInit() {
    //console.log('init lista prodotti');
    this.show_product = true;

    this.servizioApi.ListaProdotti().subscribe((response) => {
      //console.log('carico lista prodotti');
      //console.log(response);

      this.listaprodotti = response.body.prodotti as ListaProdotti[];
      this.dataSource = new MatTableDataSource(this.listaprodotti);

      this.dataSource.paginator = this.paginator;
      this.dataSource.sort = this.sort;

      // Popola inizialmente le opzioni delle select dai dati completi
      this.aggiornaOpzioniDisponibili(this.listaprodotti as any[]);

      // FUNZIONE DI FILTRO STRUTTURATA PER FAR APPARIRE LE RIGHE PER LA QUALE
      // CAMPO TABELLA CONTIENE I VALORI PASSATI SU FILTER
      // ["NOMECAMPOTABELLA" , ["VALORE1","VALORE2",..]]
      // FILTER VIENE COSTRUITO NELLA FUNZIONE selectOpt()
      this.dataSource.filterPredicate = (record: any, filter: string) => {
        const voci: any[] = (() => { try { return JSON.parse(filter || '[]'); } catch { return []; } })();
        if (!voci || voci.length === 0) return true;

        for (const [campo, valoriGrezzi] of voci) {
          // normalizza a array di stringhe maiuscole
          const valori: string[] = Array.isArray(valoriGrezzi)
            ? valoriGrezzi.map((v: any) => this.normalizzaUpper(v)).filter((v: string) => !!v)
            : [this.normalizzaUpper(valoriGrezzi)].filter((v: string) => !!v);
          if (valori.length === 0) continue;

          let corrisponde = true;
          switch (campo) {
            case 'nome_fornitore': {
              const nome = this.normalizzaUpper(this.nomeFornitoreDa(record));
              corrisponde = valori.includes(nome);
              break;
            }
            case 'macro_product': { // Codice Macro
              const codice = this.normalizzaUpper(this.codiceMacroDa(record));
              corrisponde = valori.includes(codice);
              break;
            }
            case 'macro_descrizione': { // Macro Prodotto (descrizione)
              const descr = this.normalizzaUpper(this.descrizioneMacroDa(record));
              corrisponde = valori.includes(descr);
              break;
            }
            case 'descrizione': {
              const testo = this.normalizzaUpper((record as any)?.descrizione);
              const ago = valori[0] || '';
              corrisponde = testo.includes(ago);
              break;
            }
            default:
              corrisponde = true;
          }

          if (!corrisponde) return false; // logica AND
        }
        return true;
      };
    });
  }


  
riempicampifiltri(){
        let prodottiDaFiltrare = this.listaprodotti.map(
          (prodotto) => prodotto.descrizione
        );
        this.prodottiList = prodottiDaFiltrare.sort();

        let fornitoriDaFitrare = this.listaprodotti.map(
          (prodotto) => prodotto.nome_fornitore
        );

        fornitoriDaFitrare.sort();
        this.fornitoriList = fornitoriDaFitrare.filter((str, index) => {
          const set = new Set(fornitoriDaFitrare.slice(0, index));
          return !set.has(str);
        });

        let MacroPDaFitrare = this.listaprodotti.map((prodotto) => {
          if (typeof prodotto.macro_product === 'object' && prodotto.macro_product?.codice_macro) {
            return prodotto.macro_product.codice_macro;
          }
          return prodotto.macro_product as string;
        });

        MacroPDaFitrare.sort();
        this.MacroProList = MacroPDaFitrare.filter((str, index) => {
          const set = new Set(MacroPDaFitrare.slice(0, index));
          return !set.has(str);
        });

        let MacroDescri = this.listaprodotti.map(
          (prodotto) => prodotto.macro_descrizione
        );

        MacroDescri.sort();
        this.MacroDescrizione = MacroDescri.filter((str, index) => {
          const set = new Set(MacroDescri.slice(0, index));
          return !set.has(str);
        });
}





  selectOpt(fieldTable: string, value: any) {

    //console.log("sono su selectOpt");
    //console.log(fieldTable);
    //console.log(value);




    let jsonString: string = '';

    if (value!.length == 0) {
      this.filterDictionary.delete(fieldTable);
    } else {
      this.filterDictionary.set(fieldTable, value!);
    }

    jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));

    //console.log(jsonString);


    this.dataSource.filter = jsonString;
    // Aggiorna le opzioni visibili in base ai dati filtrati
    this.aggiornaOpzioniDisponibili(this.dataSource.filteredData as any[]);

    // FilterListaFornitori.sort();
    // this.fornitoriList = FilterListaFornitori.filter((str, index) => {
    //   const set = new Set(FilterListaFornitori.slice(0, index));
    //   return !set.has(str);
    // });


    if (this.dataSource.paginator) {
      this.dataSource.paginator.firstPage();
    }
  }


  selectValue(fieldTable: string, event: any) {

    //console.log("sono su selectValue");

    const filterValue = (event.target as HTMLInputElement).value;
    let value = filterValue.toUpperCase();

    let jsonString: string = '';
    //console.log(jsonString);
    if (value!.length == 0) {
      this.filterDictionary.delete(fieldTable);
    } else {
      this.filterDictionary.set(fieldTable, value!);
    }

    jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));

    //console.log(jsonString);


    this.dataSource.filter = jsonString;
    // Aggiorna le opzioni anche per la ricerca testuale
    this.aggiornaOpzioniDisponibili(this.dataSource.filteredData as any[]);

    // FilterListaFornitori.sort();
    // this.fornitoriList = FilterListaFornitori.filter((str, index) => {
    //   const set = new Set(FilterListaFornitori.slice(0, index));
    //   return !set.has(str);
    // });


    if (this.dataSource.paginator) {
      this.dataSource.paginator.firstPage();
    }
  }


  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
    this.dataSource.sort = this.sort;
    // Opzioni già inizializzate in ngOnInit
  }

  // Chiude i mat-select aperti quando si clicca fuori dal pannello/trigger
  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent) {
    const target = event.target as HTMLElement | null;
    if (!target) return;

    const overlayContainer = document.querySelector('.cdk-overlay-container');
    const clickedInOverlay = overlayContainer ? overlayContainer.contains(target) : false;

    // Se il click è nel pannello (overlay) lasciamo gestire al mat-select
    if (clickedInOverlay) return;

    // Altrimenti, se il click è fuori dal trigger di ogni select aperta, chiudila
    this.selects?.forEach((sel) => {
      if (!sel.panelOpen) return;
      const triggerEl: HTMLElement | undefined = (sel as any)?._elementRef?.nativeElement;
      const clickedInTrigger = !!triggerEl && triggerEl.contains(target);
      if (!clickedInTrigger) {
        sel.close();
      }
    });
  }


  // ngDoCheck() {

  //   console.log("docheck prodotti");


  //   this.Contratto.getContratto().subscribe( (oggetto) => {

  //     if(oggetto.id_prodotto===null){
  //       this.show_product = true;
  //     }else{
  //       this.show_product = false;
  //     }

  //   });

  // }


  clickedRows(r: any) {
    //console.log(r.id);

    this.descrizione_prodotto_sel = r.descrizione;
    this.show_product = false;

    this.servizioApi.getProdotto(r.id).subscribe((Prodotto:any)=>{
      this.Contratto.setProdotto(r.id,Prodotto.body.prodotto[0].descrizione);
    })
  }
}

