import { Component, OnDestroy, OnInit } from "@angular/core";
import { trigger, style, animate, transition } from "@angular/animations";
import { NgbDateStruct } from "@ng-bootstrap/ng-bootstrap";
import { ApiService } from "src/app/servizi/api.service";
import { ContrattoService } from "src/app/servizi/contratto.service";
import { FormControl, FormGroup, Validators } from "@angular/forms";
import { delay, tap } from "rxjs/operators";

export interface CampiForm {
  domanda: string;
  tipoRisposta: number;
  obbligatorio: boolean;
  multipla: any;
}
export interface OpzioneSelect {
  multiplaArray: string;
}
enum TipoRisposta {
  stringa,
  sino,
  data,
  select,
  numero,
}

@Component({
    selector: "app-dettagli-contratto-prodotto",
    templateUrl: "./dettagli-contratto-prodotto.component.html",
    styleUrl: "./dettagli-contratto-prodotto.component.scss",
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
export class DettagliContrattoProdottoComponent implements OnInit {
  showErrors: boolean = false;
  nameProduct: any;
  public typeProduct: any;
  model: NgbDateStruct = { year: 2024, month: 3, day: 1 };
  SetOpt: FormGroup = new FormGroup({});
  state: any;
  prodottoselezionato: any;
  tipoR: any;
  contatore: number = 0;
  contatoreOpzioni: number = 0;
  arrayRispMultiple: string[] = [];
  list: CampiForm[] = [];
  opzioniMultiple: OpzioneSelect[] = [];
  formBuilder: any;
  campi: any;
  matspinner = true;
  constructor(
    private apiService: ApiService,
    private Contratto: ContrattoService
  ) {}

  //   this.Contratto.getContratto().subscribe((oggetto) => {
  //     this.test(oggetto);
  //   });

  private pulisciLista() {
    this.list = [];
    this.contatore = 0;
    this.contatoreOpzioni = 0;
    this.opzioniMultiple = [];
    this.arrayRispMultiple = [];
  }

  ngOnInit() {
    this.SetOpt = new FormGroup({}); // Inizializza il FormGroup
    this.Contratto.getContratto().subscribe((oggetto) => {
      if (oggetto.id_prodotto) {
        this.pulisciLista();

        this.apiService
          .getDomande(oggetto.id_prodotto)
          .subscribe((Domande: any) => {
            // console.log(Domande);

            Domande.body.Domande.forEach((position: any) => {
              // Rimuovi il parametro data
              this.opzioniMultiple = [];
              this.arrayRispMultiple = [];
              this.contatoreOpzioni = 0;

              if (
                position.tipo_risposta == "Text" ||
                position.tipo_risposta == "text"
              ) {
                this.tipoR = TipoRisposta.stringa;
              } else if (
                position.tipo_risposta == "Boolean" ||
                position.tipo_risposta == "boolean"
              ) {
                this.tipoR = TipoRisposta.sino;
              } else if (
                position.tipo_risposta == "Select" ||
                position.tipo_risposta == "select"
              ) {
                this.tipoR = TipoRisposta.select;

                if (position.detail_question.length > 0) {
                  position.detail_question.forEach((element: any) => {
                    this.opzioniMultiple.push({
                      multiplaArray: element.opzione,
                    });
                  });
                  this.arrayRispMultiple = this.opzioniMultiple.map(
                    (el) => el.multiplaArray
                  );
                }
              } else if (position.tipo_risposta == "Number" || position.tipo_risposta == "number") { // Aggiunta condizione per Number
                this.tipoR = TipoRisposta.numero; 
              }

              // Crea il form dinamico per ogni domanda
              this.list.push({
                domanda: position.domanda,
                tipoRisposta: this.tipoR,
                obbligatorio: position.obbligatorio == 1 ? true : false,
                multipla: this.arrayRispMultiple,
              });
              this.createDynamicForm();
            });
          });
      }
    });
  }
  trackByFn(index: any, item: any): any {
    return item.id;
  }

  createDynamicForm() {
    var typeOPT = "";
    this.list.forEach((i: any) => {
      var nome = [i.domanda];
      // console.log(i);
      if (i.tipoRisposta == 0) {
        typeOPT = "text";
      } else if (i.tipoRisposta == 1) {
        typeOPT = "boolean";
      } else if (i.tipoRisposta == 3) {
        typeOPT = "select";
      } else if (i.tipoRisposta == 4) {
        typeOPT = "number";
      }
      this.SetOpt.addControl(
        i.domanda,
        new FormControl("", i.obbligatorio == 1 ? Validators.required : [])
      );
    });
  }

  storeOpt() {
    if (this.SetOpt.valid) {
      this.matspinner = false;
      document.getElementById('domandecontratto')?.classList.add('domande');

      //console.log(this.Contratto);
      // console.clear();
      // console.log('salva domande');

      let optProdottoString = '';
      let domendeRisposte: any[] = [];

      // Log form values with their corresponding response types
      Object.keys(this.SetOpt.controls).forEach((key) => {
        const value = this.SetOpt.get(key)?.value;
        const questionData = this.list.find((item) => item.domanda === key);
        const responseType = questionData
          ? TipoRisposta[questionData.tipoRisposta]
          : 'unknown';
        
        // Ottieni anche il tipo numerico per maggiore flessibilità
        const tipoNumerico = questionData ? questionData.tipoRisposta : -1;
        
        // console.log(`Domanda: ${key}, Risposta: ${value}, Tipo: ${responseType}, TipoNumerico: ${tipoNumerico}`);

        // creare un oggetto che nel ciclo aggiunge i dati Domanda, Risposta, Tipo e TipoNumerico
        const domandaRisposta = {
          domanda: key,
          risposta: value, // valore originale per retrocompatibilità
          tipo: responseType, // stringa, sino, data, select, numero
          tipoNumerico: tipoNumerico, // 0=stringa, 1=sino, 2=data, 3=select, 4=numero
          obbligatorio: questionData ? questionData.obbligatorio : false,
          // Aggiungi le opzioni disponibili se è un campo select
          opzioni: questionData && questionData.tipoRisposta === TipoRisposta.select 
            ? questionData.multipla 
            : null,
          // Mappatura per le colonne del database
          risposta_tipo_stringa: (tipoNumerico === 0 || tipoNumerico === 3) ? value : null, // stringa o select
          risposta_tipo_numero: tipoNumerico === 4 ? parseFloat(value) || 0 : null, // numero
          risposta_tipo_bool: tipoNumerico === 1 ? (value === true || value === 'true' || value === 1) : null, // boolean
          tipo_risposta: responseType, // per la colonna tipo_risposta (stringa, sino, select, numero)
          // Aggiungi informazioni aggiuntive per distinguere meglio i tipi
          is_select: tipoNumerico === 3, // flag per identificare facilmente i campi select
          has_options: tipoNumerico === 3 && questionData?.multipla && questionData.multipla.length > 0 // verifica se ha opzioni
        };

        // aggiungi domandaRisposta all'array optProdotto
        domendeRisposte.push(domandaRisposta);
      });

      // console.log(domendeRisposte);
      optProdottoString = JSON.stringify(domendeRisposte);
      // trasforma l'array in stringa

      this.Contratto.setOptionProdotto(optProdottoString);
      this.Contratto.getContratto()
        .pipe(delay(1000))
        .subscribe((Oggetto: any) => {
          if (Oggetto.opt_prodotto != null) {
            this.matspinner = true;
            document
              .getElementById('domandecontratto')
              ?.classList.remove('domande');
          }
        });

      //console.log(this.Contratto);
    } else {
      this.showErrors = true;
    }
  }
}
