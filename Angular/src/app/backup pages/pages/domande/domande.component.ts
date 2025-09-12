import { Component, OnInit, ViewChild, ElementRef, OnDestroy } from '@angular/core';
import { FormGroup, FormBuilder, Validators, FormArray } from '@angular/forms';
import { ApiService } from 'src/app/servizi/api.service';
import { MatSelectChange } from '@angular/material/select';
import { animate, style, transition, trigger } from '@angular/animations';
import { Router } from '@angular/router';
import { MatDialog } from '@angular/material/dialog';
import { ConfirmDialogComponent } from 'src/app/confirm-dialog/confirm-dialog.component';

interface ApiResponse {
  body: {
    risposta: any[];
  };
}

interface Question {
  categoryId: number;
  text: string;
  type: string;
  options:string[] ;
}

interface EditQuestion{
  questionId: number;
  domanda:string;
  tipo_risposta:string;
  obbligatorio:string;
  options:{
    id: string
    opt:string
   }[]
   optionDeleted:{
    id:any
   }[];

}

interface OpzioniRimosse{
  id:number
}
interface OpzioniAggiunte{
  opzione:string[];
}
@Component({
  selector: 'app-domande',
  styleUrl: 'domande.component.scss',
  templateUrl: './domande.component.html',
  animations: [
    trigger('pageTransition', [
      transition(':enter', [
        style({ opacity: 0, transform: 'scale(0.1)' }), // Inizia piccolo al centro
        animate(
          '500ms ease-in-out',
          style({ opacity: 1, transform: 'scale(1)' })
        ), // Espandi e rendi visibile
      ]),
      transition(':leave', [
        animate(
          '500ms ease-in-out',
          style({ opacity: 0, transform: 'scale(0.1)' })
        ), // Riduci e rendi invisibile
      ]),
    ]),
  ],
  standalone: false,
})
export class DomandeComponent implements OnInit {
  categorie: any[] = [];
  selectedCategory: number | null = null;
  createdQuestions: Question[] = [];
  state: any;
  listaDomande: any[] = [];
  editQuest: EditQuestion[] = [];
  codiceProd: number;
  hideForm = false;
  hideForm2 = true;
  hideDomandeCreate = false;
  sezioneCategoria = false;
  questionToEdit: any = [];
  questionCreatedEdit: any = [];
  opzionirimosse: OpzioniRimosse[] = [];
  opzioniAggiunte: OpzioniAggiunte[] = [];
  @ViewChild('questionContainer') questionContainer!: ElementRef;

  form: FormGroup = this.fb.group({
    categoryId: [null, Validators.required],
    text: ['', Validators.required],
    type: ['text', Validators.required],
    obbligatorio: ['text', Validators.required],
    options: this.fb.array([]),
  });

  form2: FormGroup = this.fb.group({
    questionId: [null, Validators.required],
    domanda: ['', Validators.required],
    tipo_risposta: ['text', Validators.required],
    obbligatorio: ['text', Validators.required],
    options: this.fb.array([]),
    opzioniRimosse: [''],
    opzioniAggiuntive: this.fb.array([]),
  });

  private shouldRefresh = false;
  constructor(
    private fb: FormBuilder,
    private apiService: ApiService,
    private router: Router,
    private dialog: MatDialog
  ) {
    this.codiceProd = 0;
  }
  log(i: any) {
    //console.log(i);
  }
  ngOnInit(): void {
    //console.log("inizio domande");
    this.apiService.LeggiMacroCategorie().subscribe((response: any) => {
      this.categorie = response.body.risposta;
    });

    this.apiService.getListaDomande().subscribe((response: any) => {
      this.listaDomande = response.body.Domande;
      console.log(this.listaDomande);
    });
  }

  get options(): FormArray {
    return this.form.get('options') as FormArray;
  }

  get options2(): FormArray {
    return this.form2.get('options') as FormArray;
  }

  get options3(): FormArray {
    return this.form2.get('opzioniAggiuntive') as FormArray;
  }

  setCodiceProd(codiceProd: number) {
    //console.log("assegna codicePro: " + codiceProd);
    this.codiceProd = codiceProd;
  }

  getCodiceProd() {
    //console.log("leggi codicePro " + this.codiceProd );
    return this.codiceProd;
  }

  addOption(): void {
    this.options.push(this.fb.control(''));
  }

  addOption2(): void {
    this.options3.push(this.fb.control(''));
    this.options3.updateValueAndValidity();
  }

  removeOption(index: number): void {
    this.options.removeAt(index);
  }

  removeOption3(index: number): void {
    this.options3.removeAt(index);
    this.options3.updateValueAndValidity();
  }
  removeOption2(index: number, id: any): void {
    //console.log(id);

    this.options2.removeAt(index);
    if (id != undefined) {
      const eliminated: OpzioniRimosse[] = id ? id : null;
      this.questionCreatedEdit[0].optionDeleted?.push(eliminated);
      this.form2
        .get('opzioniRimosse')
        ?.setValue(this.questionCreatedEdit[0].optionDeleted);
    }
    //console.log(this.questionCreatedEdit);
  }

  onCategorySelect(event: MatSelectChange) {
    this.selectedCategory = event.value as number;
    this.form.reset({ categoryId: this.selectedCategory, type: 'text' });
  }

  aprichiudidomande(idmacro: any){
    // console.log(idmacro);
    // seleziona tutti gli elementi con la classe "domandamacpro"
    const domande = document.querySelectorAll('.domandamacpro' + idmacro);
    // cambia lo stile di tutti gli oggetti su domande in modo che style sia display block
    domande.forEach((domanda) => {
      if (domanda instanceof HTMLElement) {
        domanda.style.display = domanda.style.display === 'block ruby' ? 'none' : 'block ruby';
      }
    });
  }

  createQuestion(type: string): void {
    // console.log(type);
    // console.log(this.form.valid);
    // console.log(this.form2.valid);

    if (type == 'new') {
      if (this.form.valid) {
        const newQuestion: Question = this.form.value;
        this.createdQuestions.push(newQuestion);
        this.form.reset({ categoryId: this.selectedCategory, type: 'text' });
      }
    } else {
      //this.options2.updateValueAndValidity();
      //console.log(this.form2.valid);
      //console.log(this.form2.value);
      if (this.form2.valid) {
        this.questionCreatedEdit = [];
        const newQuestion: EditQuestion = this.form2.value;
        this.questionCreatedEdit.push(newQuestion);
        //console.log(this.questionCreatedEdit);
        //console.log(this.createdQuestions.length);

        this.submitQuestions();
        /* this.form.reset({ categoryId: this.selectedCategory, type: 'text' }); */
      }
    }
  }

  editQuestion(index: any, type: string): void {
    //console.log(type);

    if (type == 'new') {
      this.questionToEdit = this.createdQuestions[index];

      //console.log(this.questionToEdit);
    } else if (type == 'exist') {
      this.questionCreatedEdit = this.editQuest;
      //console.log(this.questionCreatedEdit[0]);
    }

    if (this.questionToEdit.categoryId) {
      //console.log(this.questionToEdit);

      this.form.patchValue(this.questionToEdit);

      if (this.questionToEdit.type === 'select') {
        const optionsArray = this.form.get('options') as FormArray;

        optionsArray.clear();
        this.questionToEdit.options?.forEach((option: any) =>
          optionsArray.push(this.fb.control(option))
        );
      }
      //console.log(this.form);
    } else if (this.questionCreatedEdit.length > 0) {
      this.hideForm = true;
      this.hideForm2 = false;
      this.hideDomandeCreate = true;
      this.sezioneCategoria = true;
      this.form2.patchValue(this.questionCreatedEdit[0]);
      this.form2
        .get('obbligatorio')
        ?.setValue(this.questionCreatedEdit[0].obbligatorio == 1 ? '1' : '0');
      if (this.questionCreatedEdit[0].tipo_risposta === 'select') {
        //console.log(this.questionCreatedEdit[0]);

        const optionsArray2 = this.form2.get('options') as FormArray;
        optionsArray2.clear(); // Svuota il FormArray
        this.questionCreatedEdit[0].options.forEach(
          (option: any, i: number) => {
            // Aggiorna il FormArray con i nuovi valori delle opzioni
            optionsArray2.setControl(
              i,
              this.fb.group({
                id: [option.id],
                opt: [option.opt, Validators.required],
              })
            );
          }
        );
      }
      //console.log(this.form2);
    }
  }

  compareFn(o1: any, o2: any): boolean {
    return o1 && o2 ? o1 === o2 : o1 === o2;
  }

  deleteQuestion(index: number): void {
    this.createdQuestions.splice(index, 1);
  }

  deletequestionExist(domanda: any, reparto: string) {
    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      data: { id: domanda, reparto: reparto },
      disableClose: true,
      panelClass: 'custom-dialog-container',
    });
    dialogRef.afterClosed().subscribe((result) => {
      //console.log(result);
      if (result) {
        // L'utente ha confermato l'eliminazione
        this.apiService
          .deleteQuestion(domanda.id)
          .subscribe((risposta: any) => {
            //console.log(risposta);
            // Aggiorna la lista delle domande o esegui altre azioni necessarie
          });
      } else {
        // L'utente ha annullato l'eliminazione
      }
    });
  }

  submitQuestions(): void {
    //console.log(this.questionCreatedEdit);
    if (this.createdQuestions.length > 0) {
      // console.log(this.createdQuestions);

      this.apiService
        .salvaDomande(this.createdQuestions)
        .subscribe((response) => {
          // Gestisci la risposta dell'API
          // console.log(response);
          this.createdQuestions = [];
          this.shouldRefresh = true; // Attiva l'aggiornamento dopo la navigazione
          this.router
            .navigateByUrl('/refresh', { skipLocationChange: true })
            .then(() => {
              this.router.navigateByUrl('/gestionedomande'); // Torna al componente
            });
        });
    } else if (this.questionCreatedEdit.length) {
      //console.log(this.form2.value);
      //console.log(this.questionCreatedEdit);

      this.apiService
        .salvaDomande(this.questionCreatedEdit)
        .subscribe((response) => {
          // Gestisci la risposta dell'API
          //console.log(response);
          this.questionCreatedEdit = [];
          this.shouldRefresh = true; // Attiva l'aggiornamento dopo la navigazione
          this.router
            .navigateByUrl('/refresh', { skipLocationChange: true })
            .then(() => {
              this.router.navigateByUrl('/gestionedomande'); // Torna al componente
            });
        });
    }
  }
  click(r: any) {
    //console.log(r);
    this.editQuest = [
      {
        questionId: r.id,
        domanda: r.domanda,
        tipo_risposta: r.tipo_risposta,
        obbligatorio: r.obbligatorio,
        options: r.detail_question.map((option: any) => ({
          id: option.id,
          opt: option.opzione, // Assegna il valore di option.opzione a opt
        })),
        optionDeleted: [], // Estrai le opzioni
      },
    ];
    //console.log(this.editQuest);
    this.editQuestion(this.editQuest, 'exist');
  }

  modificaDomanda(): void {
    //console.log(this.form2.value);
  }
}
