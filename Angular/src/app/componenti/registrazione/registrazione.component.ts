import { Component, OnInit } from "@angular/core";
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

@Component({
    selector: 'app-registrazione',
    templateUrl: './registrazione.component.html',
    styleUrls: ['./registrazione.component.scss'],
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
export class RegistrazioneComponent implements OnInit {
  constructor(private apiService: ApiService, private readonly SharedService: SharedService, private shContratto: ContrattoService, private toastr: ToastrService, private _snackBar: MatSnackBar) { }
  risposta: any;
  ruoli: any;
  qualifiche: any;
  state: any;
  ruoliequalifiche: any;
  showError: boolean = false;
  selectTipecliente: string = "business";

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
    //console.log(this.newCliente);
    //console.log(this.user_padre);

    this.role_qualification();


  }


  copiaCF() {
    this.newCliente.get("codice_fiscale")?.setValue(this.SharedService.CodiceFiscale);
  }

  copiaPI() {
    this.newCliente.get("partita_iva")?.setValue(this.SharedService.PartitaIva);
  }
  changetype(event: Event){
    const tipologia=(event.target as HTMLSelectElement).value;
    console.log(tipologia); 
    if (tipologia=="business") {
      this.newCliente.get('nome')?.setValue("-");
      this.newCliente.get('cognome')?.setValue("-")
      this.newCliente.get('codice_fiscale')?.setValue("0000000000000000");
    }else{
      this.newCliente.get('ragione_sociale')?.setValue("-")
      this.newCliente.get('partita_iva')?.setValue("00000000000");
    }
  }
  storeCliente() {

    this.showError = true;


    console.log(this.SharedService.TipoCliente);

    const typecli=document.getElementById('tipocliente')  as HTMLSelectElement

    if(typecli.value=="business"){
      //console.log("cliente buisiness setto nome cognome e codicefiscale vuoti per superare i controlli");
      this.newCliente.get('nome')?.setValue("-");
      this.newCliente.get('cognome')?.setValue("-")
      this.newCliente.get('codice_fiscale')?.setValue("0000000000000000");
    }else{
      //console.log("cliente consumer setto ragonesociale e partitaiva vuoti per superare i controlli ");
      this.newCliente.get('ragione_sociale')?.setValue("-")
      this.newCliente.get('partita_iva')?.setValue("00000000000");
    }


    //console.log(this.newCliente);


    if (this.newCliente.valid) {

      const newCliente = {
        nome: this.newCliente.value.nome,
        cognome: this.newCliente.value.cognome,
        ragione_sociale: this.newCliente.value.ragione_sociale,
        email: this.newCliente.value.email,
        telefono: this.newCliente.value.telefono,
        codice_fiscale: this.newCliente.value.codice_fiscale,
        partita_iva: this.newCliente.value.partita_iva,
        indirizzo: this.newCliente.value.indirizzo,
        provincia: this.newCliente.value.provincia,
        citta: this.newCliente.value.citta,
        nazione: this.newCliente.value.nazione,
        cap: this.newCliente.value.cap,
        qualifica: this.newCliente.value.qualifica,
        ruolo: this.newCliente.value.ruolo,
        us_padre: localStorage.getItem('userLogin'),
        tipo: this.selectTipecliente,
        password: "Benvenutoinsemprechiaro",
      };


      //console.log(newCliente);
      this.apiService.nuovoUtente(newCliente).subscribe((risultato: any) => {
        //console.log(risultato);

        //console.log(risultato);
        if (risultato.response == "ok") {
          window.location.reload();
          /* this.shContratto.setStatoContratto(true);
          this.shContratto.setIdUtente(Number(localStorage.getItem('userLogin')));
          this.shContratto.setIdCliente(Number(risultato.body.id));
          this.shContratto.setTipoCliente(risultato.body.tipo);
          this.SharedService.showNewContratto(); */
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
    this._snackBar.open('UTENTE GIA PRESENTE!', 'Chiudi', {
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
