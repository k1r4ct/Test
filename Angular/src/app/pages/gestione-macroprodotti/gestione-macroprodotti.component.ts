import {
  AfterViewInit,
  Component,
  OnInit,
  ViewChild,
} from "@angular/core";
import { MatTableDataSource } from "@angular/material/table";
import { MatPaginator } from "@angular/material/paginator";
import { MatSort } from "@angular/material/sort";
import { ApiService } from "src/app/servizi/api.service";
import { FormControl, FormGroup, Validators } from "@angular/forms";
import { trigger, transition, style, animate } from "@angular/animations";
import { ConfirmDialogComponent } from "src/app/confirm-dialog/confirm-dialog.component";
import { MatDialog } from "@angular/material/dialog";
import { MessageService } from 'primeng/api';
import { LayoutScrollService } from "src/app/servizi/layout-scroll.service";


export interface DettaglioMacroProdotto {
  idMacroProd: number;
  codice_macro: string;
  descrizioneMacroProd: string;
  punti_valore:number;
  punti_carriera:number;
}


export interface ListaMacroProdotti {
  id: number;
  codice_macro: string;
  descrizione: string;
  punti_valore: number;
  punti_carriera: number;
}


export interface ProdottiNew {
  macro_product: any[];
  supplier: any[];
  supplier_category: any[];
}

export interface NuovoMacroProdotto {
  codice_macro: string;
  descrizione: string;
  punti_valore: number;
  punti_carriera: number;
  supplier_category_id: number;
}

export interface CategoriaFornitore {
  id: number;
  nome: string;
}

@Component({
    selector: "app-gestione-macroprodotti",
    templateUrl: "./gestione-macroprodotti.component.html",
    styleUrl: "./gestione-macroprodotti.component.scss",
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
        trigger("slideIn", [
            transition(":enter", [
                style({ opacity: 0, transform: "translateY(-20px)" }),
                animate("300ms ease-out", style({ opacity: 1, transform: "translateY(0)" })),
            ]),
            transition(":leave", [
                animate("300ms ease-in", style({ opacity: 0, transform: "translateY(-20px)" })),
            ]),
        ]),
    ],
    standalone: false
})
export class GestioneMacroprodottiComponent {
  state: any;

  displayedColumns: string[] = [
    "id",
    "codice_macro",
    "descrizione",
    "punti_valore",
    "punti_carriera",
    "azioni",
  ];

  dataSource: MatTableDataSource<ListaMacroProdotti>;
  listamacroprodotti: ListaMacroProdotti[];
  filterDictionary = new Map<string, string>();

  show_product: boolean = true;
  descrizione_prodotto_sel: string = "";

  DettagliProduct: DettaglioMacroProdotto[] = [];
  DettaglioMacroProdotto: DettaglioMacroProdotto[] = [];
  macroprodottoselezionato = true;
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

  // Form per nuovo macro prodotto
  nuovoMacroProdottoForm: FormGroup;
  isSubmittingNuovoMacro = false;
  listaCategorieFornitori: CategoriaFornitore[] = [];
  isLoadingCategorie = false;


  constructor(
    private servizioApi: ApiService,
    private dialog: MatDialog,
    private MessageSystem:MessageService,
    private srvScroll: LayoutScrollService,
  ) {
    this.dataSource = new MatTableDataSource();
    this.listamacroprodotti = [];
    
    // Inizializza il form per nuovo macro prodotto
    this.nuovoMacroProdottoForm = new FormGroup({
      codice_macro: new FormControl('', [Validators.required, Validators.minLength(2)]),
      descrizione: new FormControl('', [Validators.required, Validators.minLength(3)]),
      punti_valore: new FormControl(0, [Validators.required, Validators.min(0)]),
      punti_carriera: new FormControl(0, [Validators.required, Validators.min(0)]),
      supplier_category_id: new FormControl('', [Validators.required])
    });
  }


  ngOnInit() {
    //console.log('init lista prodotti');
    this.show_product = true;
    this.servizioApi.PrendiUtente().subscribe((Auth:any)=>{
      console.log(Auth);
      if (Auth.user.role_id==1) {
        this.abilitaTasti=true;
      }
    })

    // ho creato DettaglioMacroProdotto ed è collegato all'interface DettaglioMacroProdotto
    // adesso devo settare i valori di default
    this.DettaglioMacroProdotto = [
      {
        idMacroProd: 0,
        codice_macro: "",
        descrizioneMacroProd: "",
        punti_valore:0,
        punti_carriera:0
      }
    ];

    console.log(" ----------- ");
    console.log(this.DettaglioMacroProdotto);

    this.caricaListaMacroProdotti();
  }

  /**
   * Carica la lista dei macro prodotti dal backend
   */
  private caricaListaMacroProdotti(): void {
    this.servizioApi.LeggiMacroCategorie().subscribe((response) => {
      console.log("carico lista MACRO prodotti");
      console.log(response.body.risposta);
      this.listamacroprodotti = response.body.risposta as ListaMacroProdotti[];
      this.dataSource = new MatTableDataSource(this.listamacroprodotti);
      this.dataSource.paginator = this.paginator;
      this.dataSource.sort = this.sort;



      // FUNZIONE DI FILTRO STRUTTURATA PER FAR APPARIRE LE RIGHE PER LA QUALE
      // CAMPO TABELLA CONTIENE I VALORI PASSATI SU FILTER
      // ["NOMECAMPOTABELLA" , ["VALORE1","VALORE2",..]]
      // FILTER VIENE COSTRUITO NELLA FUNZIONE selectOpt()
      this.dataSource.filterPredicate = function (record, filter) {
        //console.log(filter,record);

        const obj = JSON.parse(filter);
        let isMatch_codice_macro = false;
        let isMatch_descrizione = false;
        let isMatch = false;

        let codice_macro = false;
        let descrizione = false;

        let statoField: boolean[] = [];
        let cicli = 0;
        let stringEleArray: any;

        for (const item of obj) {
          const field = item[0];
          const valori = item[1];
          isMatch = false;

          console.log(valori);


          if (field == "codice_macro") {
            cicli++;
            codice_macro = true;
            stringEleArray = record[field as keyof ListaMacroProdotti];
            if (stringEleArray.includes(valori)) {
              isMatch_codice_macro = true;
            }
          } else {
            //console.log("nessuna descrizione");
            isMatch_codice_macro = true;
          }

          if (field == "descrizione") {
            cicli++;
            descrizione = true;
            stringEleArray = record[field as keyof ListaMacroProdotti];
            if (stringEleArray.includes(valori)) {
              isMatch_descrizione = true;
            }
          } else {
            //console.log("nessuna descrizione");
            isMatch_descrizione = true;
          }

          if (codice_macro && !descrizione) {
            isMatch = isMatch_codice_macro;
          }else if (!codice_macro && descrizione) {
            isMatch = isMatch_descrizione;
          }else if (codice_macro && descrizione) {
            isMatch = isMatch_codice_macro;
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



  selectValue(fieldTable: string, event: any) {
    // console.log("sono su selectValue");

    const filterValue = (event.target as HTMLInputElement).value;
    let value = filterValue.toUpperCase();

    let jsonString: string = "";
    console.log(value);

    if (value!.length == 0) {
      this.filterDictionary.delete(fieldTable);
    } else {
      this.filterDictionary.set(fieldTable, value!);
    }

    jsonString = JSON.stringify(Array.from(this.filterDictionary.entries()));

    console.log(jsonString);

    this.dataSource.filter = jsonString;

    if (this.dataSource.paginator) {
      this.dataSource.paginator.firstPage();
    }
  }

  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
    this.dataSource.sort = this.sort;
  }

  clickedRows(r: any) {
    console.log("Macroprodotto selezionato " + r.id) ;

    this.descrizione_prodotto_sel = r.descrizione;
    this.macroprodottoselezionato = false;
    
    // Chiudi il form di nuovo macro prodotto se è aperto
    this.nuovoprodottoHidden = true;

    this.servizioApi.allMacroProduct(r.id).subscribe((response: any) => {
        // console.log(response);

        this.DettaglioMacroProdotto = response.body.risposta.map((prod: any) => ({
          idMacroProd: prod.id,
          codice_macro: prod.codice_macro,
          descrizioneMacroProd: prod.descrizione,
          punti_valore:prod.punti_valore,
          punti_carriera:prod.punti_carriera,
        }));

        console.log(this.DettaglioMacroProdotto);

        this.srvScroll.triggerScroll();

    });

  }

  deleteMacroProduct(r: any) {
    console.log("delete macro prodotto " + r);


  }

  SalvaModificheMacroProdotto(idmacroProdotto:any){
    // console.log(idmacroProdotto);

    const codice_macro = document.querySelector(
      '.codice_macro'
    ) as HTMLSelectElement; // Trova l'elemento select
    const codiceMacro = codice_macro?.value;

    const descrizione = document.querySelector(
      '.descrizioneMacroProd'
    ) as HTMLSelectElement; // Trova l'elemento select
    const descrizioneProdotto = descrizione?.value;

    // recuperare allo stesso modo dall'html gli altri campi
    const punti_valore = document.querySelector(
      '.punti_valore'
    ) as HTMLSelectElement; // Trova l'elemento select
    const puntiValore = punti_valore?.value;

    const punti_carriera = document.querySelector(
      '.punti_carriera'
    ) as HTMLSelectElement; // Trova l'elemento select
    const puntiCarriera = punti_carriera?.value;

    // console.log(codiceMacro);
    // console.log(descrizioneProdotto);
    // console.log(puntiValore);
    // console.log(puntiCarriera);

    const formData= new FormData();
    formData.append('id',idmacroProdotto);
    formData.append('codice_macro',codiceMacro);
    formData.append('descrizione',descrizioneProdotto);
    formData.append('punti_valore',puntiValore);
    formData.append('punti_carriera',puntiCarriera);

    this.servizioApi.updateMacroProdotto(formData).subscribe((Result:any)=>{
      console.log(Result);

      if (Result.body.risposta=1) {
        this.messageProduct='Macro Prodotto Modificato'
        this.severityProduct='success'
        this.summaryProduct='Modifica Macro Prodotto'
        this.showMessage(this.messageProduct,'success',this.summaryProduct)
        
        // Ricarica la lista dopo la modifica
        this.caricaListaMacroProdotti();
      }else{
        this.messageProduct='Macro Prodotto non Modificato'
        this.severityProduct='error'
        this.summaryProduct='Modifica Macro Prodotto'
        this.showMessage(this.messageProduct,'error',this.summaryProduct)
      }

    })

  }

  /**
   * Mostra/nascondi il form per nuovo macro prodotto
   */
  nuovoprodotto() {
    this.nuovoprodottoHidden = !this.nuovoprodottoHidden;
    
    // Se stiamo aprendo il form, carica le categorie fornitori
    if (!this.nuovoprodottoHidden) {
      this.macroprodottoselezionato = true;
      
      // Carica le categorie fornitori
      this.caricaCategorieFornitori();
      
      // Reset del form
      this.nuovoMacroProdottoForm.reset({
        codice_macro: '',
        descrizione: '',
        punti_valore: 0,
        punti_carriera: 0,
        supplier_category_id: ''
      });
      
      // Scroll verso il form
      /* setTimeout(() => {
        this.srvScroll.triggerScroll();
      }, 100); */
    }
  }

  /**
   * Carica le categorie fornitori dal backend
   */
  private caricaCategorieFornitori(): void {
    this.isLoadingCategorie = true;
    this.servizioApi.recuperaCategorieFornitori().subscribe(
      (response: any) => {
        console.log('Categorie fornitori:', response);
        
        // Adatta la struttura della risposta in base al formato del tuo backend
        // Esempio: response.body.risposta o response.data o response direttamente
        if (response.body && response.body.risposta) {
          this.listaCategorieFornitori = response.body.risposta.map((cat: any) => ({
            id: cat.id,
            nome: cat.nome_categoria 
          }));
        }
        
        console.log('Lista categorie mappate:', this.listaCategorieFornitori);
        this.isLoadingCategorie = false;
      },
      (error) => {
        console.error('Errore nel caricamento delle categorie:', error);
        this.isLoadingCategorie = false;
        this.showMessage(
          'Errore nel caricamento delle categorie fornitori',
          'error',
          'Caricamento Categorie'
        );
      }
    );
  }

  /**
   * Annulla l'inserimento di un nuovo macro prodotto
   */
  annullaNuovoMacroProdotto(): void {
    this.nuovoprodottoHidden = true;
    this.nuovoMacroProdottoForm.reset({
      codice_macro: '',
      descrizione: '',
      punti_valore: 0,
      punti_carriera: 0,
      supplier_category_id: ''
    });
  }

  /**
   * Salva il nuovo macro prodotto
   */
  salvaNuovoMacroProdotto(): void {
    // Valida il form
    if (this.nuovoMacroProdottoForm.invalid) {
      // Marca tutti i campi come touched per mostrare gli errori
      Object.keys(this.nuovoMacroProdottoForm.controls).forEach(key => {
        this.nuovoMacroProdottoForm.get(key)?.markAsTouched();
      });
      
      this.showMessage(
        'Compila tutti i campi obbligatori correttamente',
        'warn',
        'Validazione Form'
      );
      return;
    }

    this.isSubmittingNuovoMacro = true;

    const formData = new FormData();
    formData.append('codice_macro', this.nuovoMacroProdottoForm.get('codice_macro')?.value || '');
    formData.append('descrizione', this.nuovoMacroProdottoForm.get('descrizione')?.value || '');
    formData.append('punti_valore', this.nuovoMacroProdottoForm.get('punti_valore')?.value || '0');
    formData.append('punti_carriera', this.nuovoMacroProdottoForm.get('punti_carriera')?.value || '0');
    formData.append('supplier_category_id', this.nuovoMacroProdottoForm.get('supplier_category_id')?.value || '');

    // TODO: Chiama la tua API backend quando sarà pronta
    // Esempio: this.servizioApi.creaNuovoMacroProdotto(formData).subscribe(...)
    
    console.log('Dati da inviare al backend:', {
      codice_macro: this.nuovoMacroProdottoForm.get('codice_macro')?.value,
      descrizione: this.nuovoMacroProdottoForm.get('descrizione')?.value,
      punti_valore: this.nuovoMacroProdottoForm.get('punti_valore')?.value,
      punti_carriera: this.nuovoMacroProdottoForm.get('punti_carriera')?.value,
      supplier_category_id: this.nuovoMacroProdottoForm.get('supplier_category_id')?.value
    });

    
    
    this.servizioApi.creaNuovoMacroProdotto(formData).subscribe(
      (result: any) => {
        console.log('Risposta API:', result);
        this.isSubmittingNuovoMacro = false;

        if (result.status === "200") {
          this.showMessage(
            'Nuovo Macro Prodotto creato con successo',
            'success',
            'Creazione Macro Prodotto'
          );

          // Reset e chiusura form
          this.nuovoprodottoHidden = true;
          this.nuovoMacroProdottoForm.reset({
            codice_macro: '',
            descrizione: '',
            punti_valore: 0,
            punti_carriera: 0,
            supplier_category_id: ''
          });

          // Ricarica la lista
          this.caricaListaMacroProdotti();
        } else {
          this.showMessage(
            'Errore durante la creazione del Macro Prodotto',
            'error',
            'Creazione Macro Prodotto'
          );
        }
      },
      (error) => {
        console.error('Errore API:', error);
        this.isSubmittingNuovoMacro = false;
        this.showMessage(
          'Errore durante la creazione del Macro Prodotto',
          'error',
          'Creazione Macro Prodotto'
        );
      }
    );
  }

  /**
   * Verifica se un campo del form è invalido e touched
   */
  isFieldInvalid(fieldName: string): boolean {
    const field = this.nuovoMacroProdottoForm.get(fieldName);
    return !!(field && field.invalid && (field.dirty || field.touched));
  }

  /**
   * Ottiene il messaggio di errore per un campo
   */
  getFieldError(fieldName: string): string {
    const field = this.nuovoMacroProdottoForm.get(fieldName);
    
    if (field?.hasError('required')) {
      return 'Questo campo è obbligatorio';
    }
    if (field?.hasError('minlength')) {
      const minLength = field.errors?.['minlength'].requiredLength;
      return `Minimo ${minLength} caratteri richiesti`;
    }
    if (field?.hasError('min')) {
      return 'Il valore deve essere maggiore o uguale a 0';
    }
    
    return '';
  }

  showMessage(message:any,severity:any,summary:any){
    console.log(message);
    console.log(severity);
    console.log(summary);

    this.MessageSystem.add({
      severity: severity,
      summary: summary,
      detail: message,
      life: 5000
    });

  }

  showMessageMP(message:any,severity:any,summary:any){
    console.log(message);
    console.log(severity);

    this.MessageSystem.add({
      severity: severity,
      summary: summary,
      detail: message,
      life: 5000
    });

  }
}
