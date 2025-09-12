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
  macro_product: string;
  nome_fornitore: string;
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

  mprToppings = new FormControl("");
  mpdToppings = new FormControl("");
  forToppings = new FormControl("");

  prodottiList: string[] = [];
  fornitoriList: string[] = [];
  MacroProList: string[] = [];
  MacroDescrizione: string[] = [];
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


  ngAfterViewChecked () {
    // console.log(this.nuovoprodottoHidden);
    // console.log(this.nuovoFornitoreHidden);
    this.srvScroll.triggerScroll();
  }



  nuovoFornitore(){
    this.nuovoFornitoreHidden = false;
    this.nuovoprodottoHidden = true;
    this.prodottoselezionato = true;
  }

  nuovoprodotto() {
    //console.log("nuovo");
    this.nuovoprodottoHidden = false;
    this.nuovoFornitoreHidden = true;
    this.prodottoselezionato = true;
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
      console.clear();
      //console.log("carico lista prodotti");
      //console.log(response);

      this.listaprodotti = response as ListaProdotti[];
      this.dataSource = new MatTableDataSource(this.listaprodotti);

      this.dataSource.paginator = this.paginator;
      this.dataSource.sort = this.sort;

      // setto this.prodottiList  bind MAT-SELECT MAT-OPTION
      // la select viene caricata con la lista ordinata alfabeticamente
      // per il campo descrizione
      // setto this.fornitoriList bind MAT-SELECT MAT-OPTION
      // la select viene caricata con la lista ordinata alafebiticamente
      // dei fornitori (in questo caso vengolo eliminati i duplicati)

      this.riempicampifiltri();

      // FUNZIONE DI FILTRO STRUTTURATA PER FAR APPARIRE LE RIGHE PER LA QUALE
      // CAMPO TABELLA CONTIENE I VALORI PASSATI SU FILTER
      // ["NOMECAMPOTABELLA" , ["VALORE1","VALORE2",..]]
      // FILTER VIENE COSTRUITO NELLA FUNZIONE selectOpt()
      this.dataSource.filterPredicate = function (record, filter) {
        //console.log(filter,record);

        const obj = JSON.parse(filter);
        let isMatch = false;
        let isMatch_nome_fornitore = false;
        let isMatch_macro_product = false;
        let isMatch_macro_descriz = false;
        let isMatch_descriz = false;

        let nome_fornitore = false;
        let macro_product = false;
        let macro_descriz = false;
        let descriz = false;
        
        let statoField: boolean[] = [];
        let cicli = 0;
        let stringEleArray: any;

        for (const item of obj) {
          const field = item[0];
          const valori = item[1];
          isMatch = false;

          //console.log(item);

          if (field == "nome_fornitore") {
            for (const valore of valori) {
              nome_fornitore = true;
              //console.log(valore);
              cicli++;
              stringEleArray = record[field as keyof ListaProdotti];
              if (stringEleArray == valore) {
                isMatch_nome_fornitore = true;
              }
            }
          }

          if (field == "macro_product") {
            for (const valore of valori) {
              macro_product = true;
              //console.log(valore);
              cicli++;
              stringEleArray = record[field as keyof ListaProdotti];
              if (stringEleArray == valore) {
                isMatch_macro_product = true;
              }
            }
          }

          if (field == "macro_descrizione") {
            for (const valore of valori) {
              macro_descriz = true;
              //console.log(valore);
              cicli++;
              stringEleArray = record[field as keyof ListaProdotti];
              if (stringEleArray == valore) {
                isMatch_macro_descriz = true;
              }
            }
          }

          if (field == "descrizione") {
            descriz = true;
            cicli++;
            stringEleArray = record[field as keyof ListaProdotti];
            if (stringEleArray.includes(valori)) {
              isMatch_descriz = true;
            }else{
              isMatch = false;
            }
          } else {
            //console.log("nessuna descrizione");
            // isMatch = true;
          }

          if (nome_fornitore && macro_product && macro_descriz && descriz) {
            if (
              isMatch_nome_fornitore &&
              isMatch_macro_product &&
              isMatch_macro_descriz &&
              isMatch_descriz
            ) {
              isMatch = true;
            }
          } else if (nome_fornitore && macro_product && descriz) {
            if (isMatch_nome_fornitore && isMatch_macro_product && isMatch_descriz) {
              isMatch = true;
            }
          } else if (nome_fornitore && macro_product && macro_descriz) {
            if (
              isMatch_nome_fornitore &&
              isMatch_macro_product &&
              isMatch_macro_descriz
            ) {
              isMatch = true;
            }
          } else if (nome_fornitore && macro_descriz && descriz) {
            if (isMatch_nome_fornitore && isMatch_macro_descriz && isMatch_descriz) {
              isMatch = true;
            }
          } else if (macro_product && macro_descriz && descriz) {
            if (isMatch_macro_product && isMatch_macro_descriz && isMatch_descriz) {
              isMatch = true;
            }
          } else if (macro_product && macro_descriz) {
            if (isMatch_macro_product && isMatch_macro_descriz) {
              isMatch = true;
            }
          } else if (macro_product && descriz) {
            if (isMatch_macro_product && isMatch_descriz) {
              isMatch = true;
            }
          } else if (macro_descriz && descriz) {
            if (isMatch_macro_descriz && isMatch_descriz) {
              isMatch = true;
            }
          } else if (nome_fornitore && descriz) {
            if (isMatch_nome_fornitore && isMatch_descriz) {
              isMatch = true;
            }
          } else if (nome_fornitore && macro_product) {
            if (isMatch_nome_fornitore && isMatch_macro_product) {
              isMatch = true;
            }
          } else if (nome_fornitore && macro_descriz) {
            if (isMatch_nome_fornitore && isMatch_macro_descriz) {
              isMatch = true;
            }
          } else if (nome_fornitore) {
            isMatch = isMatch_nome_fornitore;
          } else if (macro_product) {
            isMatch = isMatch_macro_product;
          } else if (macro_descriz) {
            isMatch = isMatch_macro_descriz;
          } else if (descriz) {
            isMatch = isMatch_descriz;
          }

          statoField.push(isMatch);
        }

        if (cicli == 0) {
          return true;
        }

        statoField = [];
        return isMatch;
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


  riempicampifiltri() {
    
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

          let MacroPDaFitrare = this.listaprodotti.map(
            (prodotto) => prodotto.macro_product
          );

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

    let jsonString: string = "";

    if (value!.length == 0) {
      this.filterDictionary.delete(fieldTable);
    } else {
      this.filterDictionary.set(fieldTable, value!);
    }

    jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));

    //console.log(jsonString);

    this.dataSource.filter = jsonString;

    let FilterListaProdotti: any[] = [];
    let FilterListaFornitori: any[] = [];
    this.dataSource.filteredData.forEach((element) => {
      //console.log(element);
      //FilterListaFornitori.push(element["nome_fornitore"]);
      FilterListaProdotti.push(element["descrizione"]);
    });
    this.prodottiList = FilterListaProdotti.sort();

    this.listaprodotti = this.dataSource.filteredData;
    this.riempicampifiltri();

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

    let FilterListaProdotti: any[] = [];
    let FilterListaFornitori: any[] = [];
    this.dataSource.filteredData.forEach((element) => {
      //console.log(element);
      //FilterListaFornitori.push(element["nome_fornitore"]);
      FilterListaProdotti.push(element["descrizione"]);
    });
    this.prodottiList = FilterListaProdotti.sort();

    this.listaprodotti = this.dataSource.filteredData;
    this.riempicampifiltri();
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
    this.riempicampifiltri();
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
        punti_carriera:prod.macro_product.punti_carriera
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

    console.log(contratto);
    const descrizione = document.querySelector(
      '.descrizione'
    ) as HTMLSelectElement; // Trova l'elemento select
    const descrizioneProdotto = descrizione?.value;

    const descrizioneMP = document.querySelector(
      '.macro_product'
    ) as HTMLSelectElement; // Trova l'elemento select
    const descrizioneMacroProdotto = descrizioneMP?.value;

    const idMP = document.querySelector(
      '.id_macro_product'
    ) as HTMLSelectElement; // Trova l'elemento select
    const idMacroProdotto = idMP?.value;


    const formData= new FormData();
    formData.append('descrizione',descrizioneProdotto);
    formData.append('idProdotto',contratto.idProd);
    formData.append('idMacroProdotto',idMacroProdotto);
    formData.append('descrizioneMacroProdotto',descrizioneMacroProdotto);
    this.servizioApi.updateProdotto(formData).subscribe((Result:any)=>{
      console.log(Result);
      if (Result.body.risposta=1) {
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
