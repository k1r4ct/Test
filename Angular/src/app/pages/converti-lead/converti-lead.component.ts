import { Component, Input, input, OnChanges, OnInit, SimpleChanges } from "@angular/core";
import {
  trigger,
  state,
  style,
  animate,
  transition,
} from "@angular/animations";
import { FormControl, FormGroup, Validators } from "@angular/forms";
import { ApiService } from "src/app/servizi/api.service";
import { SharedService } from "src/app/servizi/shared.service";
import { ContrattoService } from "src/app/servizi/contratto.service";
import { ToastrService } from "ngx-toastr";
import {
  MatSnackBar,
  MatSnackBarHorizontalPosition,
  MatSnackBarVerticalPosition,
} from '@angular/material/snack-bar';
export interface Lead {
  email: string;
  telefono: string;
  id_lead: number;
}
@Component({
    selector: 'app-converti-lead',
    templateUrl: './converti-lead.component.html',
    styleUrl: './converti-lead.component.scss',
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
    ],
    standalone: false
})
export class ConvertiLeadComponent implements OnInit, OnChanges {
  @Input() lead: Lead = {
    email: "",
    telefono: "",
    id_lead:0,
  };
  
  constructor(private apiService: ApiService, private readonly SharedService: SharedService, private shContratto: ContrattoService, private toastr: ToastrService, private _snackBar: MatSnackBar) { }
  risposta: any;
  ruoli: any;
  qualifiche: any;
  state: any;
  ruoliequalifiche: any;
  showError: boolean = false;
  selectTipecliente: string = "business";
  tipoCliente:any;
  user_padre = localStorage.getItem('userLogin');
  newCliente: FormGroup = new FormGroup({});
  horizontalPosition: MatSnackBarHorizontalPosition = 'center';
  verticalPosition: MatSnackBarVerticalPosition = 'top';




  ngOnInit(): void {

    
    
    if (this.SharedService.TipoCliente == "CODICE FISCALE") {
      this.selectTipecliente = "consumer";
    }

    if (this.SharedService.TipoCliente == "PARTITA IVA") {
      this.selectTipecliente = "business";
    }


    this.newCliente = new FormGroup({
      nome: new FormControl('', [Validators.required]),
      cognome: new FormControl('', [Validators.required]),
      ragione_sociale: new FormControl('', [Validators.required]),
      email: new FormControl('', [Validators.required, Validators.email]), // controlla email
      telefono: new FormControl('', [Validators.required]), // controlla email
      codice_fiscale: new FormControl('', [Validators.required,Validators.minLength(16)]),
      partita_iva: new FormControl('', [Validators.required,Validators.minLength(11)]),
      indirizzo: new FormControl('', [Validators.required]),
      provincia: new FormControl('', [Validators.required,Validators.minLength(2),Validators.pattern(/^[a-zA-Z]+$/)]),
      citta: new FormControl('', [Validators.required]),
      nazione: new FormControl('', [Validators.required]),
      cap: new FormControl('', [Validators.required,Validators.pattern(/\d+$/),Validators.minLength(5)]), // accetta solo numeri
      qualifica: new FormControl(),
      ruolo: new FormControl(),
      us_padre: new FormControl(),
      tipo: new FormControl(),
      milli: new FormControl(),
    });
    console.log(this.newCliente);
    //console.log(this.user_padre);

    this.role_qualification();


  }
   ngOnChanges(changes: SimpleChanges): void {
    if (changes['lead'] && this.lead) {
      this.newCliente.patchValue({
        email: this.lead.email,
        telefono: this.lead.telefono,
        id_lead:this.lead.id_lead,
      });
    }
   }
  settaTipoCliente(event:any){
    console.log(event.target.value);
    const typeCli=event.target.value
    
  }
  copiaCF() {
    console.log(this.newCliente);
    
    this.newCliente.get("codice_fiscale")?.setValue(this.SharedService.CodiceFiscale);
  }

  copiaPI() {
    console.log(this.newCliente);
    this.newCliente.get("partita_iva")?.setValue(this.SharedService.PartitaIva);
  }

  storeCliente() {

    this.showError = true;


    //console.log("registra cliente");


    console.log("tipocliente",this.selectTipecliente);
    
    if(this.selectTipecliente === "business"){
      //console.log("cliente buisiness setto nome cognome e codicefiscale vuoti per superare i controlli");
      this.newCliente.get('nome')?.setValue("-");
      this.newCliente.get('cognome')?.setValue("-")
      this.newCliente.get('codice_fiscale')?.setValue("0000000000000000");
      /* this.newCliente.get('provincia')?.setValue("-"); */
      this.newCliente.get('cap')?.setValue("00000");
    }else{
      //console.log("cliente consumer setto ragonesociale e partitaiva vuoti per superare i controlli ");
      this.newCliente.get('ragione_sociale')?.setValue("-")
      this.newCliente.get('partita_iva')?.setValue("00000000000");
    }


    console.log(this.newCliente);


    if (this.newCliente.valid) {

      const newCliente = {
        nome: this.newCliente.value.nome,
        cognome: this.newCliente.value.cognome,
        ragione_sociale: this.newCliente?.value.ragione_sociale,
        email: this.newCliente.value.email,
        telefono: this.newCliente.value.telefono,
        codice_fiscale: this.newCliente.value.codice_fiscale,
        partita_iva: this.newCliente.value?.partita_iva,
        indirizzo: this.newCliente.value.indirizzo,
        provincia: this.newCliente.value.provincia,
        citta: this.newCliente.value.citta,
        nazione: this.newCliente.value.nazione,
        cap: this.newCliente.value.cap,
        qualifica: 9, //this.newCliente.value.qualifica
        ruolo: 3, //this.newCliente.value.ruolo
        us_padre: localStorage.getItem('userLogin'),
        tipo: this.selectTipecliente,
        password: "000000",
        id_lead:this.lead.id_lead
      };


      //console.log(newCliente);
      this.apiService.nuovoClienteLead(newCliente).subscribe((risultato: any) => {
        console.log(risultato);

        //console.log(risultato);
        if (risultato.response == "ok") {
          console.log("cliente registrato");
          
          
        } else {
          this.openSnackBar();
        }

      });
      //console.log(nome,cognome, email,codice_fiscale,indirizzo,citta,nazione,cap,qualifica,ruolo);

    } else {
      //console.log('Il form non è valido!');
      this.messageSnackBar();
    }
  }


  openSnackBar() {
    this._snackBar.open('CLIENTE GIA PRESENTE!', 'Chiudi', {
      horizontalPosition: this.horizontalPosition,
      verticalPosition: this.verticalPosition,
    });
  }


  messageSnackBar(){
    this._snackBar.open('Compilare correttamente tutti i campi', 'Chiudi', {
      horizontalPosition: this.horizontalPosition,
      verticalPosition: this.verticalPosition,
    })
  }


  role_qualification() {
    this.apiService.richiestaRuolieQualifiche().subscribe((datiraccolti: any) => {
        //console.log(datiraccolti);
        this.ruoliequalifiche = datiraccolti;
        //console.log(this.ruoliequalifiche);
        this.ruoli = this.ruoliequalifiche.ruoli;
        this.qualifiche = this.ruoliequalifiche.qualifiche;
        //console.log(this.ruoli);
      });
  }


}
