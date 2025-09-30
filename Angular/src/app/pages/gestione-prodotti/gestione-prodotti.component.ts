import {
  AfterViewInit,
  Component,
  DoCheck,
  HostListener,
  OnInit,
  OnChanges,
  ViewChild,
} from "@angular/core";
import { MatTableDataSource } from "@angular/material/table";
import { MatPaginator } from "@angular/material/paginator";
import { MatSort } from "@angular/material/sort";
import { ApiService } from "src/app/servizi/api.service";
import { FormControl } from "@angular/forms";
import { ContrattoService } from "src/app/servizi/contratto.service";
import { trigger, transition, style, animate } from "@angular/animations";
import { ConfirmDialogComponent } from "src/app/confirm-dialog/confirm-dialog.component";
import { MatDialog } from "@angular/material/dialog";
import { MessageService } from 'primeng/api';
import { log } from "node:console";
import { LayoutScrollService } from "src/app/servizi/layout-scroll.service";


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
  supplier: {
    id: number;
    nome_fornitore: string;
  };
}

export interface DettaglioProdotto {
  idProd: string;
  descrizioneProd: string;
  idMacroProd: number;
  codice_macro: string;
  descrizioneMacroProd: string;
  idSup: number;
  nome_fornitore: string;
  supplier_category: string;
  nome_categoria: string;
  punti_valore:number;
  punti_carriera:number;
  attivo: number | boolean; // Aggiunto campo per stato attivo/non attivo
}
export interface ProdottiNew {
  macro_product: any[];
  supplier: any[];
  supplier_category: any[];
}
@Component({
    selector: "app-gestione-prodotti",
    templateUrl: "./gestione-prodotti.component.html",
    styleUrl: "./gestione-prodotti.component.scss",
    animations: [
        trigger("pageTransition", [
            transition(":enter", [
                style({ opacity: 0, transform: "scale(0.1)" }), // Inizia piccolo al centro
                animate("500ms ease-in-out", style({ opacity: 1, transform: "scale(1)" })), // Espandi e rendi visibile
            ]),
            transition(":leave", [
                animate("500ms ease-in-out", style({ opacity: 0, transform: "scale(0.1)" })), // Riduci e rendi invisibile
            ]),
        ]),
    ],
    standalone: false
})
export class GestioneProdottiComponent {

  state: any;
  displayedColumns: string[] = [
    "id",
    "descrizione",
    "macro_descrizione",
    "macro_product",
    "nome_fornitore",
    "azioni",
  ];
  dataSource: MatTableDataSource<ListaProdotti>;
  listaprodotti: ListaProdotti[];
  filterDictionary = new Map<string, string>();
  show_product: boolean = true;
  descrizione_prodotto_sel: string = "";
  DettagliProduct: DettaglioProdotto[] = [];
  prodottoselezionato = true;
  nuovoprodottoHidden = true;
  nuovoFornitoreHidden=true;
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;
  @ViewChild('attivoToggle') attivoToggle: any;

  mprToppings = new FormControl("");
  mpdToppings = new FormControl("");
  forToppings = new FormControl("");

  prodottiList: string[] = [];
  fornitoriList: string[] = [];
  MacroProList: string[] = [];
  MacroDescrizione: string[] = [];
  ListaFornitori: string[] = [];
  NewProdotto: ProdottiNew[] = [];
  prodottoNeiContratti: any;
  messageProduct='';
  severityProduct='';
  summaryProduct='';
  abilitaTasti=false;
  messageMacroProduct='';
  severityMacroProduct='';
  summaryMacroProduct='';
  constructor(
    private servizioApi: ApiService,
    private Contratto: ContrattoService,
    private dialog: MatDialog,
    private MessageSystem:MessageService,
    private srvScroll: LayoutScrollService
  ) {
    this.dataSource = new MatTableDataSource();
    this.listaprodotti = [];
  }

  // ===== Helper semplici e riusabili =====
  // Converte qualunque valore in stringa ripulita (senza spazi iniziali/finali)
  private normalizza(val: any): string {
    return (val ?? '').toString().trim();
  }

  // Come sopra ma in maiuscolo: utile per confronti insensibili a maiuscole/minuscole
  private normalizzaUpper(val: any): string {
    return this.normalizza(val).toUpperCase();
  }

  // Estrae il CODICE MACRO dal record (può essere già stringa oppure dentro macro_product.codice_macro)
  private codiceMacroDa(rec: any): string {
    const mp = rec?.macro_product;
    const codice = (typeof mp === 'object' && mp?.codice_macro) ? mp.codice_macro : mp;
    return this.normalizza(codice);
  }

  // Estrae la DESCRIZIONE macro prodotto, con fallback a macro_product.descrizione
  private descrizioneMacroDa(rec: any): string {
    const diretta = this.normalizza(rec?.macro_descrizione);
    if (diretta) return diretta;
    const mp = rec?.macro_product;
    const fallback = (typeof mp === 'object') ? (mp?.descrizione || '') : '';
    return this.normalizza(fallback);
  }

  // Estrae il NOME FORNITORE sia dal campo piatto che da supplier.nome_fornitore
  private nomeFornitoreDa(rec: any): string {
    return this.normalizza(rec?.nome_fornitore || rec?.supplier?.nome_fornitore || '');
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
    this.ListaFornitori = Array.from(new Set(
      (dati || [])
        .map((r) => this.nomeFornitoreDa(r))
        .filter((v) => !!v)
    )).sort();
  }


  // Flag per eseguire scroll solo quando richiesto
  private pendingScroll = false;

  ngAfterViewChecked () {
    // Esegui scroll solo quando esplicitamente settato
    if (this.pendingScroll) {
      this.pendingScroll = false;
      this.srvScroll.triggerScroll();
    }
  }



  nuovoFornitore(){
    this.nuovoFornitoreHidden = false;
    this.nuovoprodottoHidden = true;
    this.prodottoselezionato = true;
  // Richiedi scroll quando apro la sezione
  this.pendingScroll = true;
  }

  nuovoprodotto() {
    //console.log("nuovo");
    this.nuovoprodottoHidden = false;
    this.nuovoFornitoreHidden = true;
    this.prodottoselezionato = true;
  // Richiedi scroll quando apro la sezione
  this.pendingScroll = true;
  }


  ngOnInit() {
    //console.log('init lista prodotti');
    this.show_product = true;
    this.servizioApi.PrendiUtente().subscribe((Auth:any)=>{
      //console.log(Auth);
      if (Auth.user.role_id==1) {
        this.abilitaTasti=true;
      }
    })
    this.servizioApi.ListaProdotti().subscribe((response) => {
      //console.log(response.body.prodotti);
      
      //console.clear();
      //console.log("carico lista prodotti");
      //console.log(response);

      this.listaprodotti = response.body.prodotti as ListaProdotti[];
      //console.log(this.listaprodotti);
      
      this.dataSource = new MatTableDataSource(this.listaprodotti);

      this.dataSource.paginator = this.paginator;
      this.dataSource.sort = this.sort;

      // setto this.prodottiList  bind MAT-SELECT MAT-OPTION
      // la select viene caricata con la lista ordinata alfabeticamente
      // per il campo descrizione
      // setto this.fornitoriList bind MAT-SELECT MAT-OPTION
      // la select viene caricata con la lista ordinata alafebiticamente
      // dei fornitori (in questo caso vengolo eliminati i duplicati)

      let prodottiDaFiltrare = this.listaprodotti.map(
        (prodotto) => prodotto.descrizione
      );
      //console.log(prodottiDaFiltrare);
      
      this.prodottiList = prodottiDaFiltrare.sort();

      let fornitoriDaFitrare = this.listaprodotti.map(
        (prodotto) => prodotto.nome_fornitore
      );

      fornitoriDaFitrare.sort();
      /* this.fornitoriList = fornitoriDaFitrare.filter((str, index) => {
        const set = new Set(fornitoriDaFitrare.slice(0, index));
        return !set.has(str);
      }); */

      // Codice Macro: usa codice_macro se oggetto, altrimenti la stringa; normalizza, filtra vuoti e deduplica
      const MacroPDaFitrare = this.listaprodotti
        .map((prodotto) => {
          if (typeof prodotto.macro_product === 'object' && prodotto.macro_product?.codice_macro) {
            return (prodotto.macro_product.codice_macro || '').toString().trim();
          }
          return ((prodotto.macro_product as string) || '').toString().trim();
        })
        .filter((v) => !!v);
      this.MacroProList = Array.from(new Set(MacroPDaFitrare)).sort();

      // Macro Prodotto (descrizione): usa macro_descrizione, fallback a macro_product.descrizione; normalizza, filtra vuoti e deduplica
      const MacroDescri = this.listaprodotti
        .map((prodotto) => {
          const md = (prodotto.macro_descrizione as unknown as string) || '';
          if (typeof md === 'string' && md.trim()) return md.trim();
          const mp: any = prodotto.macro_product as any;
          const fallback = mp && typeof mp === 'object' ? (mp.descrizione || '') : '';
          return fallback.toString().trim();
        })
        .filter((v) => !!v);

      // Fornitore: usa campo flat o supplier.nome_fornitore; normalizza, filtra vuoti e deduplica
      const ListaProd = this.listaprodotti
        .map((prodotto) => {
          const flat = (prodotto.nome_fornitore || '') as string;
          const nested = (prodotto.supplier?.nome_fornitore || '') as string;
          return (flat || nested).toString().trim();
        })
        .filter((v) => !!v);

      this.MacroDescrizione = Array.from(new Set(MacroDescri)).sort();
      // Fallback: se per qualche motivo la lista è vuota, prova da macro_product.descrizione o, in ultima istanza, da descrizione prodotto
      if (!this.MacroDescrizione.length) {
        const fallbackMacroDescr = this.listaprodotti
          .map((p: any) => {
            const fromMp = p?.macro_product && typeof p.macro_product === 'object' ? (p.macro_product.descrizione || '') : '';
            const fromMacro = (p?.macro_descrizione || '') as string;
            const fromDesc = (p?.descrizione || '') as string;
            return (fromMp || fromMacro || fromDesc).toString().trim();
          })
          .filter((v: string) => !!v);
        this.MacroDescrizione = Array.from(new Set(fallbackMacroDescr)).sort();
      }

  this.ListaFornitori = Array.from(new Set(ListaProd)).sort();
      // Filtro "tutti insieme" (AND): ogni filtro attivo deve essere rispettato
      // Nota: usiamo una funzione freccia per poter usare i metodi helper di questa classe
      this.dataSource.filterPredicate = (record, filter) => {
        const voci: any[] = JSON.parse(filter || '[]');
        if (!voci || voci.length === 0) return true;

        for (const [campo, valoriGrezzi] of voci) {
          // Raccogli i valori del filtro ripuliti (array garantito)
          const valori: string[] = Array.isArray(valoriGrezzi)
            ? valoriGrezzi.map((v) => this.normalizzaUpper(v)).filter((v) => !!v)
            : [this.normalizzaUpper(valoriGrezzi)].filter((v) => !!v);
          if (valori.length === 0) continue; // niente da verificare per questo campo

          // Verifica corrispondenza in base al campo
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

          // Logica AND: se un solo filtro non corrisponde, la riga è esclusa
          if (!corrisponde) return false;
        }

        // Tutti i filtri sono rispettati
        return true;
      };
    });

    this.servizioApi.nuovoProdotto().subscribe((Oggetti: any) => {
      //console.log(Oggetti.body.risposta);
      const prodottiNew: ProdottiNew = {
        macro_product: Oggetti.body.risposta.macro_product.filter(
          (item: any) => Object.keys(item).length > 0
        ),
        supplier: Oggetti.body.risposta.supplier.filter(
          (item: any) => Object.keys(item).length > 0
        ),
        supplier_category: Oggetti.body.risposta.supplier_category.filter(
          (item: any) => Object.keys(item).length > 0
        ),
      };

      //console.log(prodottiNew);
    });
    //console.log(this.NewProdotto);
  }

  selectOpt(fieldTable: string, value: any) {
    //console.log("sono su selectOpt");
    
    //console.log(value);

    let jsonString: string = "";

    if (value!.length == 0) {
      this.filterDictionary.delete(fieldTable);
    } else {
      this.filterDictionary.set(fieldTable, value!);
    }
    console.log(this.filterDictionary);
    jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));

    console.log("json string"+jsonString);
    console.log(this.dataSource);
    
    this.dataSource.filter = jsonString;

    // Aggiorna le opzioni degli altri filtri in base ai dati attualmente visibili
    this.aggiornaOpzioniDisponibili(this.dataSource.filteredData as any[]);

    if (this.dataSource.paginator) {
      this.dataSource.paginator.firstPage();
    }
  }

  selectValue(fieldTable: string, event: any) {
    console.log("sono su selectValue");

    const filterValue = (event.target as HTMLInputElement).value;
    let value = filterValue.toUpperCase();

    let jsonString: string = "";
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

    if (this.dataSource.paginator) {
      this.dataSource.paginator.firstPage();
    }
  }


  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
    this.dataSource.sort = this.sort;
  }



  clickedRows(r: any) {
    //console.log(r.id);

    this.nuovoFornitoreHidden = true;
    this.nuovoprodottoHidden = true;

    this.descrizione_prodotto_sel = r.descrizione;
    this.prodottoselezionato = false;

    this.servizioApi.getProdotto(r.id).subscribe((response: any) => {
      console.log(response);

      this.DettagliProduct = response.body.prodotto.map((prod: any) => ({
        idProd: prod.id,
        descrizioneProd: prod.descrizione,
        // Accesso diretto alle proprietà di macro_product
        idMacroProd: prod.macro_product.id,
        codice_macro: prod.macro_product.codice_macro,
        descrizioneMacroProd: prod.macro_product.descrizione,
        // Accesso diretto alle proprietà di supplier
        idSup: prod.supplier.id,
        nome_fornitore: prod.supplier.nome_fornitore,
        // Accesso diretto a supplier_category (se è un oggetto)
        nome_categoria: prod.supplier.supplier_category.nome_categoria,
        punti_valore:prod.macro_product.punti_valore,
        punti_carriera:prod.macro_product.punti_carriera,
        attivo: prod.attivo

      }));
      //console.log(this.DettagliProduct);
    });
  }

  deleteProduct(r: any, reparto: string) {
    //console.log(r);
    this.servizioApi
      .controlloProdottoNeiContratti(r.id)
      .subscribe((Risposta: any) => {
        //console.log(Risposta.body.risposta);
        if (Risposta.body.risposta == 1) {
          const dialogRef = this.dialog.open(ConfirmDialogComponent, {
            data: {
              id: r.id,
              reparto: reparto,
              descrizione: r.descrizione,
              bloccatoDaContratti: true,
            },
            disableClose: true,
            panelClass: "custom-dialog-container",
          });
          dialogRef.afterClosed().subscribe((result) => {
            //console.log(result);
            this.servizioApi.disabilitaProdotto(r.id).subscribe((Result:any)=>{
              //console.log(Result.body.risposta);

            })
          });
        } else {
          const dialogRef = this.dialog.open(ConfirmDialogComponent, {
            data: {
              id: r.id,
              reparto: reparto,
              descrizione: r.descrizione,
              bloccatoDaContratti: false,
            },
            disableClose: true,
            panelClass: "custom-dialog-container",
          });
          dialogRef.afterClosed().subscribe((result) => {
            //console.log(result);
            this.servizioApi.cancellaProdotto(r.id).subscribe((Result:any)=>{
              //console.log(Result.body.risposta);

            })
          });
        }
      });
  }
  SalvaModificheProdotto(contratto:any){

    //questa funzione console.log(contratto);
    const descrizione = document.querySelector(
      '.descrizione'
    ) as HTMLInputElement; // Trova l'elemento input
    const descrizioneProdotto = descrizione?.value;

    const descrizioneMP = document.querySelector(
      '.macro_product'
    ) as HTMLInputElement; // Trova l'elemento input
    const descrizioneMacroProdotto = descrizioneMP?.value;

    const idMP = document.querySelector(
      '.id_macro_product'
    ) as HTMLInputElement; // Trova l'elemento input
    const idMacroProdotto = idMP?.value;

    // Gestione del mat-slide-toggle per lo stato attivo
    const attivoValore = this.attivoToggle?.checked ? '1' : '0';

    console.log('Stato attivo:', attivoValore);
    console.log('State del toggle ViewChild:', this.attivoToggle?.checked);

    const formData= new FormData();
    formData.append('descrizione',descrizioneProdotto);
    formData.append('idProdotto',contratto.idProd);
    formData.append('idMacroProdotto',idMacroProdotto);
    formData.append('descrizioneMacroProdotto',descrizioneMacroProdotto);
    formData.append('attivo',attivoValore);
    this.servizioApi.updateProdotto(formData).subscribe((Result:any)=>{
      console.log(Result);
      if (Result.body.risposta==1) {
        this.messageProduct='Prodotto Modificato'
        this.severityProduct='success'
        this.summaryProduct='Modifica Prodotto'
        this.showMessage(this.messageProduct,'success',this.summaryProduct)
      }else{
        this.messageProduct='Prodotto non Modificato'
        this.severityProduct='error'
        this.summaryProduct='Modifica Prodotto'

        this.showMessage(this.messageProduct,'error',this.summaryProduct)
      }
      if (Result.body.updateMacroProd=="Macro Prodotto Modificato") {
        this.messageMacroProduct='Macro Prodotto Modificato'
        this.severityMacroProduct='success'
        this.summaryMacroProduct='Modifica Macro Prodotto'

        this.showMessageMP(this.messageMacroProduct,'success',this.summaryMacroProduct)
      }else{
        this.messageMacroProduct='Macro Prodotto non Modificato'
        this.severityMacroProduct='error'
        this.summaryMacroProduct='Modifica Macro Prodotto'

        this.showMessageMP(this.messageMacroProduct,'error',this.summaryMacroProduct)

      }
    })



  }

  showMessage(message:any,severity:any,summary:any){
    console.log(message);
    console.log(severity);
    console.log(summary);

    this.MessageSystem.add({
      severity: severity,
      summary: summary,
      detail: message,
      life: 90000
    });

  }

  showMessageMP(message:any,severity:any,summary:any){
    console.log(message);
    console.log(severity);

    this.MessageSystem.add({
      severity: severity,
      summary: summary,
      detail: message,
      life: 90000
    });

  }










}
