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
    selector: "app-nuovocliente",
    templateUrl: "./nuovocliente.component.html",
    styleUrl: "./nuovocliente.component.scss",
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
export class NuovoclienteComponent implements OnInit {
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

    this.initializeForm();
    this.updateValidators();
    console.log(this.newCliente);
    //console.log(this.user_padre);

    this.role_qualification();


  }

  initializeForm(): void {
    this.newCliente = new FormGroup({
      nome: new FormControl(''),
      cognome: new FormControl(''),
      ragione_sociale: new FormControl(''),
      email: new FormControl('', [Validators.required, Validators.email]),
      telefono: new FormControl('', [Validators.required]),
      codice_fiscale: new FormControl(''),
      partita_iva: new FormControl(''),
      indirizzo: new FormControl('', [Validators.required]),
      provincia: new FormControl('', [Validators.required,Validators.minLength(2),Validators.pattern(/^[a-zA-Z]+$/)]),
      citta: new FormControl('', [Validators.required]),
      nazione: new FormControl('', [Validators.required]),
      cap: new FormControl('', [Validators.required,Validators.pattern(/\d+$/),Validators.minLength(5)]),
      qualifica: new FormControl(),
      ruolo: new FormControl(),
      us_padre: new FormControl(),
      tipo: new FormControl(),
      milli: new FormControl(),
    });
  }

  updateValidators(): void {
    // Reset tutti gli errori prima di aggiornare i validatori
    this.newCliente.get('nome')?.setErrors(null);
    this.newCliente.get('cognome')?.setErrors(null);
    this.newCliente.get('ragione_sociale')?.setErrors(null);
    this.newCliente.get('codice_fiscale')?.setErrors(null);
    this.newCliente.get('partita_iva')?.setErrors(null);

    if (this.selectTipecliente === 'consumer') {
      // Per i clienti consumer: nome, cognome e codice fiscale obbligatori
      this.newCliente.get('nome')?.setValidators([Validators.required]);
      this.newCliente.get('cognome')?.setValidators([Validators.required]);
      this.newCliente.get('codice_fiscale')?.setValidators([
        Validators.required, 
        Validators.minLength(16), 
        Validators.maxLength(16)
      ]);
      this.newCliente.get('ragione_sociale')?.clearValidators();
      this.newCliente.get('partita_iva')?.clearValidators();
    } else {
      // Per i clienti business: ragione sociale e partita iva obbligatori
      this.newCliente.get('ragione_sociale')?.setValidators([Validators.required]);
      this.newCliente.get('partita_iva')?.setValidators([
        Validators.required, 
        Validators.minLength(11), 
        Validators.maxLength(11),
        Validators.pattern(/^\d{11}$/)
      ]);
      this.newCliente.get('nome')?.clearValidators();
      this.newCliente.get('cognome')?.clearValidators();
      this.newCliente.get('codice_fiscale')?.clearValidators();
    }

    // Aggiorna i validatori
    this.newCliente.get('nome')?.updateValueAndValidity();
    this.newCliente.get('cognome')?.updateValueAndValidity();
    this.newCliente.get('ragione_sociale')?.updateValueAndValidity();
    this.newCliente.get('codice_fiscale')?.updateValueAndValidity();
    this.newCliente.get('partita_iva')?.updateValueAndValidity();
  }


  copiaCF() {
    console.log(this.newCliente);
    
    this.newCliente.get("codice_fiscale")?.setValue(this.SharedService.CodiceFiscale);
  }

  copiaPI() {
    console.log(this.newCliente);
    this.newCliente.get("partita_iva")?.setValue(this.SharedService.PartitaIva);
  }

  onTipoClienteChange(tipo: string): void {
    this.selectTipecliente = tipo;
    this.showError = false; // Reset degli errori di visualizzazione
    this.updateValidators();
  }

  storeCliente() {
    this.showError = true;

    // Prepara i dati in base al tipo di cliente
    const clienteData: any = {
      email: this.newCliente.value.email,
      telefono: this.newCliente.value.telefono,
      indirizzo: this.newCliente.value.indirizzo,
      provincia: this.newCliente.value.provincia,
      citta: this.newCliente.value.citta,
      nazione: this.newCliente.value.nazione,
      cap: this.newCliente.value.cap,
      qualifica: 9,
      ruolo: 3,
      us_padre: localStorage.getItem('userLogin'),
      tipo: this.selectTipecliente,
      password: "Benvenutoinsemprechiaro",
    };

    if (this.selectTipecliente === 'consumer') {
      // Cliente consumer: usa nome, cognome e codice fiscale
      clienteData.nome = this.newCliente.value.nome;
      clienteData.cognome = this.newCliente.value.cognome;
      clienteData.codice_fiscale = this.newCliente.value.codice_fiscale;
      clienteData.ragione_sociale = "-";
      clienteData.partita_iva = "00000000000";
    } else {
      // Cliente business: usa ragione sociale e partita iva
      clienteData.ragione_sociale = this.newCliente.value.ragione_sociale;
      clienteData.partita_iva = this.newCliente.value.partita_iva;
      clienteData.nome = "-";
      clienteData.cognome = "-";
      clienteData.codice_fiscale = "0000000000000000";
    }

    // Verifica validità del form in base al tipo di cliente
    const isValid = this.isFormValidForClientType();

    if (isValid) {
      console.log(clienteData);
      this.apiService.nuovoUtente(clienteData).subscribe((risultato: any) => {
        console.log(risultato);

        if (risultato.response == "ok") {
          // Successo
          this.shContratto.setStatoContratto(true);
          this.shContratto.setIdUtente(Number(localStorage.getItem('userLogin')));
          this.shContratto.setIdCliente(Number(risultato.body.id));
          this.shContratto.setTipoCliente(risultato.body.tipo);
          this.SharedService.showNewContratto();
        } else {
          this.openSnackBar();
        }
      });
    } else {
      console.log('Il form non è valido!');
      this.messageSnackBar();
    }
  }

  private isFormValidForClientType(): boolean {
    // Campi comuni sempre obbligatori
    const commonFields = ['email', 'telefono', 'indirizzo', 'provincia', 'citta', 'nazione', 'cap'];
    
    for (const field of commonFields) {
      const control = this.newCliente.get(field);
      if (!control || control.invalid) {
        console.log(`Campo ${field} non valido:`, control?.errors);
        return false;
      }
    }

    // Verifica campi specifici per tipo cliente
    if (this.selectTipecliente === 'consumer') {
      const consumerFields = ['nome', 'cognome', 'codice_fiscale'];
      for (const field of consumerFields) {
        const control = this.newCliente.get(field);
        if (!control || control.invalid || !control.value || control.value.trim() === '') {
          console.log(`Campo consumer ${field} non valido:`, control?.errors);
          return false;
        }
      }
    } else {
      const businessFields = ['ragione_sociale', 'partita_iva'];
      for (const field of businessFields) {
        const control = this.newCliente.get(field);
        if (!control || control.invalid || !control.value || control.value.trim() === '') {
          console.log(`Campo business ${field} non valido:`, control?.errors);
          return false;
        }
      }
    }

    return true;
  }

  getFieldErrorMessage(fieldName: string): string {
    const control = this.newCliente.get(fieldName);
    if (control && control.errors && this.showError) {
      if (control.errors['required']) {
        return `Il campo ${fieldName} è obbligatorio`;
      }
      if (control.errors['minlength']) {
        return `Il campo ${fieldName} deve avere almeno ${control.errors['minlength'].requiredLength} caratteri`;
      }
      if (control.errors['maxlength']) {
        return `Il campo ${fieldName} deve avere massimo ${control.errors['maxlength'].requiredLength} caratteri`;
      }
      if (control.errors['pattern']) {
        if (fieldName === 'partita_iva') {
          return 'La partita IVA deve contenere esattamente 11 cifre';
        }
        return `Il formato del campo ${fieldName} non è valido`;
      }
      if (control.errors['email']) {
        return 'Inserire un indirizzo email valido';
      }
    }
    return '';
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
