import { AfterViewInit, Component, DoCheck, OnInit, ViewChild,    } from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
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


  ngOnInit() {
    //console.log('init lista prodotti');
    this.show_product = true;

    this.servizioApi.ListaProdotti().subscribe((response) => {
      console.clear();
      //console.log('carico lista prodotti');
      console.log(response);

      this.listaprodotti = response.body.prodotti as ListaProdotti[];
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

          if(field=="nome_fornitore"){
            for (const valore of valori) {
              nome_fornitore = true;
              //console.log(valore);
              cicli++;
              stringEleArray = record[field as keyof ListaProdotti];
              if (stringEleArray == valore ) {
                isMatch_nome_fornitore = true;
              }
            }
          }


          if(field=="macro_product"){
            for (const valore of valori) {
              macro_product = true;
              //console.log(valore);
              cicli++;
              stringEleArray = record[field as keyof ListaProdotti];
              // Gestisci sia oggetto che stringa per macro_product
              const macroProductValue = typeof stringEleArray === 'object' && stringEleArray?.codice_macro 
                ? stringEleArray.codice_macro 
                : stringEleArray;
              if (macroProductValue == valore ) {
                isMatch_macro_product = true;
              }
            }
          }


          if(field=="macro_descrizione"){
            for (const valore of valori) {
              macro_descriz = true;
              //console.log(valore);
              cicli++;
              stringEleArray = record[field as keyof ListaProdotti];
              if (stringEleArray == valore ) {
                isMatch_macro_descriz = true;
              }
            }
          }


          if(field=="descrizione"){
            descriz = true;
            cicli++;
            stringEleArray = record[field as keyof ListaProdotti];
            if (stringEleArray.includes(valori)  ) {
              isMatch_descriz = true;
              // isMatch = true;
            }else{
              isMatch = false;
            }
          }else{
            //console.log("nessuna descrizione");
            // isMatch = true;
          }


          if (nome_fornitore && macro_product && macro_descriz && descriz) {
            if (isMatch_nome_fornitore && isMatch_macro_product && isMatch_macro_descriz && isMatch_descriz) {
              isMatch = true;
            }
          } else if (nome_fornitore && macro_product && descriz) {
            if (isMatch_nome_fornitore && isMatch_macro_product && isMatch_descriz) {
              isMatch = true;
            }
          } else if (nome_fornitore && macro_product && macro_descriz) {
            if (isMatch_nome_fornitore && isMatch_macro_product && isMatch_macro_descriz) {
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


    let FilterListaProdotti: any[] = [];
    let FilterListaFornitori: any[] = [];
    this.dataSource.filteredData.forEach(element => {
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


    let FilterListaProdotti: any[] = [];
    let FilterListaFornitori: any[] = [];
    this.dataSource.filteredData.forEach(element => {
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

