import { trigger, transition, style, animate } from '@angular/animations';
import { AfterViewInit, Component, OnDestroy, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Subscription } from 'rxjs';
import { ContrattoService } from 'src/app/servizi/contratto.service';
import { LayoutScrollService } from 'src/app/servizi/layout-scroll.service';
import {
  MatSnackBar,
  MatSnackBarHorizontalPosition,
  MatSnackBarVerticalPosition,
  MatSnackBarRef, TextOnlySnackBar
} from '@angular/material/snack-bar';
import { ContractServiceStatus } from 'src/app/servizi/contract-status-guard.service';

@Component({
    selector: 'app-nuovocontrattoComponent',
    templateUrl: 'nuovocontratto.component.html',
    styleUrl: './nuovocontratto.component.scss',
    animations: [
        trigger('pageTransition', [
            transition(':enter', [
                style({ opacity: 0, transform: 'scale(0.1)' }),
                animate('500ms ease-in-out', style({ opacity: 1, transform: 'scale(1)' })),
            ]),
            transition(':leave', [
                // Una sola transizione :leave
                style({ opacity: 1, transform: 'scale(1)' }),
                animate('500ms ease-in-out', style({ opacity: 0, transform: 'scale(0.1)' })),
            ]),
        ]),
    ],
    standalone: false
})
export class NuovocontrattoComponent implements OnInit, AfterViewInit, OnDestroy  {

  // @ViewChild('content') content!: ElementRef;
  // @ViewChild('stepper') private stepper!: MatStepper;
  // @Output() scrollToFine = new EventEmitter<void>();

  firstFormGroup = this._formBuilder.group({
    firstCtrl: ['', Validators.required],
  });

  secondFormGroup = this._formBuilder.group({
    secondCtrl: ['', Validators.required],
  });

  thirdFormGroup = this._formBuilder.group({
    thirdCtrl: ['', Validators.required],
  });

  fourFormGroup = this._formBuilder.group({
    fourCtrl: ['', Validators.required],
  });

  fiveFormGroup = this._formBuilder.group({
    fiveCtrl: ['', Validators.required],
  });

  isLinear = false;

  ClassStepEna: string = 'btnStepperEna';
  ClassStepDis: string = 'btnStepperDis';

  public titolo: string = '';
  public stato: any;
  public idute: any;
  public idcli: any;
  public tipCl: any;
  public idCont: any;
  public typeCont: any;
  public typeProduct: any;
  public nameProduct: any;
  public optProduct: any;
  public idContratto: any;
  public stepperDis1: boolean = true;
  public stepperDis2: boolean = true;
  public stepperDis3: boolean = true;
  public stepperDis4: boolean = true;

  private subscription: Subscription | null = null;
  pageChanged: boolean = false;
  state: any;
  horizontalPosition: MatSnackBarHorizontalPosition = 'center';
  verticalPosition: MatSnackBarVerticalPosition = 'top';
  snackbarRef!: MatSnackBarRef<TextOnlySnackBar>;
  constructor(
    private _formBuilder: FormBuilder,
    private shContratto: ContrattoService,
    private srvScroll: LayoutScrollService,
    private _snackBar: MatSnackBar,
    private ContractService:ContractServiceStatus,

  ) {}

  ngOnInit(): void {
    this.titolo = 'dati nuovo contratto ...';

    this.subscription = this.shContratto.getContratto().subscribe((oggetto) => {
      //console.log("aggiornamento da subscribe contratto");
      //console.log(oggetto);
      this.stato = oggetto.stato_contratto;
      this.idute = oggetto.id_utente;
      this.idcli = oggetto.id_cliente;
      this.tipCl = oggetto.tipoCliente;
      this.idCont = oggetto.id_contraente;
      this.typeCont = oggetto.tipoContraente;
      this.typeProduct = oggetto.id_prodotto;
      this.nameProduct = oggetto.nome_prodotto;
      this.optProduct = oggetto.opt_prodotto;
      this.idContratto = oggetto.id_contratto;
      this.stepperDis1 = oggetto.stepperDis1;
      this.stepperDis2 = oggetto.stepperDis2;
      this.stepperDis3 = oggetto.stepperDis3;
      this.stepperDis4 = oggetto.stepperDis4;

      if(!oggetto.stepperDis1){
        this.firstFormGroup.controls['firstCtrl'].setValue('Valori Inseriti');
      }else{
        this.firstFormGroup.controls['firstCtrl'].setValue(null);
      }

      if(!oggetto.stepperDis2){
        this.secondFormGroup.controls['secondCtrl'].setValue('Valori Inseriti');
        this.srvScroll.triggerScroll();
      }else{
        this.secondFormGroup.controls['secondCtrl'].setValue(null);
      }

      if(!oggetto.stepperDis3){
        this.thirdFormGroup.controls['thirdCtrl'].setValue('Valori Inseriti');
      }else{
        this.thirdFormGroup.controls['thirdCtrl'].setValue(null);
      }

      if(!oggetto.stepperDis4){
        this.fourFormGroup.controls['fourCtrl'].setValue('Valori Inseriti');
      }else{
        this.fourFormGroup.controls['fourCtrl'].setValue(null);
      }




    });
  }

  // markFormGroupAsCompleted(): void {
  //   this.firstFormGroup.markAllAsTouched();
  //   this.isFormCompleted = true;
  // }


  onStepChange(event: any) {
    // Gestisci gli eventi di cambio step
    //console.log('Indice step corrente:', event.selectedIndex);
    //console.log("lancio nuovo emitter");
    //this.srvScroll.triggerScroll();
    this.pageChanged = true;
  }

  ngAfterViewInit() {
    this.srvScroll.triggerScroll();
  }

  ngAfterViewChecked(){
    if(this.pageChanged){
      this.srvScroll.triggerScroll();
      this.pageChanged = false;
    }
  }

  ngOnDestroy(){
    //console.log("componente nuovocontratto distrutto");
    this.subscription!.unsubscribe();
    this.shContratto.resetNuovoContratto();
  }

  salvadocumenti(){
    
    this.ContractService.setContrattoSalvato(true);
    this.openSnackBar();
  }
  openSnackBar() {
    this.snackbarRef = this._snackBar.open('Documenti caricati correttamente e contratto salvato', 'Chiudi', {
      horizontalPosition: this.horizontalPosition,
      verticalPosition: this.verticalPosition,
      panelClass: ['centered-snackbar'] 
    });

    this.snackbarRef.afterDismissed().subscribe(evento => {
      if (evento.dismissedByAction) {
        window.location.reload();
      }
    });
  }



}

