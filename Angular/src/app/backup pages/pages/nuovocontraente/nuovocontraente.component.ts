import { trigger, transition, style, animate } from '@angular/animations';
import { Component, OnInit } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { delay } from 'rxjs';
import { ApiService } from 'src/app/servizi/api.service';
import { ContrattoService } from 'src/app/servizi/contratto.service';
import {
  MatSnackBar,
  MatSnackBarHorizontalPosition,
  MatSnackBarVerticalPosition,
} from '@angular/material/snack-bar';

@Component({
  selector: 'app-nuovocontraente',
  standalone: false,
  templateUrl: './nuovocontraente.component.html',
  styleUrl: './nuovocontraente.component.scss',
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
})
export class NuovocontraenteComponent implements OnInit {
  constructor(
    private apiService: ApiService,
    private _snackBar: MatSnackBar,
    private contraente: ContrattoService
  ) {}

  ruoli: any;
  qualifiche: any;
  state: any;
  ruoliequalifiche: any;
  selectTipecliente: number = 0;
  newContraente: FormGroup = new FormGroup({});
  user_padre = localStorage.getItem('userLogin');
  public tipCl: any;
  contraenteType: any;
  tipocli: any = 0;
  valoretypecli: any;
  idcli: any;
  matspinner = true;
  inputerror: boolean = false;
  showError: boolean = false;
  horizontalPosition: MatSnackBarHorizontalPosition = 'center';
  verticalPosition: MatSnackBarVerticalPosition = 'top';

  ngOnInit(): void {
    this.newContraente = new FormGroup({
      nome: new FormControl('', [Validators.required]),
      cognome: new FormControl('', [Validators.required]),
      ragione_sociale: new FormControl('', [Validators.required]),
      email: new FormControl('', [Validators.required, Validators.email]),
      telefono: new FormControl('', [Validators.required]),
      codice_fiscale: new FormControl('', [
        Validators.required,
        Validators.minLength(16),
      ]),
      partita_iva: new FormControl('', [
        Validators.required,
        Validators.minLength(11),
      ]),
      indirizzo: new FormControl('', [Validators.required]),
      citta: new FormControl('', [Validators.required]),
      nazione: new FormControl('', [Validators.required]),
      cap: new FormControl('', [Validators.required]),
      us_padre: new FormControl(),
    });

    // qualifica: new FormControl('', [Validators.required]),
    // ruolo: new FormControl('', [Validators.required]),

    //console.log(this.newContraente);

    this.contraente.getContratto().subscribe((oggetto) => {
      this.tipCl = oggetto.tipoCliente;
      this.idcli = oggetto.id_cliente;
      //console.log("tipo " + oggetto.tipoCliente);
      this.contraenteType = oggetto.tipoCliente;
      if (this.contraenteType == 'consumer') {
        this.valoretypecli = 1;
      } else if (this.contraenteType == 'businness') {
        this.valoretypecli = 0;
      }
    });
  }

  role_qualification() {
    this.apiService
      .richiestaRuolieQualifiche()
      .subscribe((datiraccolti: any) => {
        this.ruoliequalifiche = datiraccolti;
        //console.log(this.ruoliequalifiche);
        this.ruoli = this.ruoliequalifiche.ruoli;
        this.qualifiche = this.ruoliequalifiche.qualifiche;
        //console.log(this.ruoli);
      });
  }

  datiContraenteTrovato() {
    this.contraente.getContratto().subscribe((oggetto) => {
      this.tipCl = oggetto.tipoCliente;
      //console.log("tipo "+oggetto.tipoCliente);
    });
  }

  changeType(event: any) {
    this.tipocli = event.target.value;
    //console.log("tipo selezionat "+this.tipocli+ " tipologia cliente "+ this.valoretypecli);
    //console.log(this.tipocli);
    const split = this.tipocli.split(':');
    //console.log(split);
    this.tipocli = parseInt(split[0]);
    this.showError = false;
    this.newContraente.reset();
  }

  copiadati() {
    //console.log(this.idcli);

    this.apiService.copiaUtente(this.idcli).subscribe((cliente: any) => {
      const cli = cliente;
      console.log(cliente.body[0]);
      if (this.valoretypecli == 1) {
        this.newContraente.get('nome')?.setValue(cli.body[0].name);
        this.newContraente.get('cognome')?.setValue(cli.body[0].cognome);
        this.newContraente
          .get('codice_fiscale')
          ?.setValue(cli.body[0].codice_fiscale);
        this.newContraente.get('ragione_sociale')?.setValue('-');
        this.newContraente.get('partita_iva')?.setValue('-');
        this.newContraente.get('email')?.setValue(cli.body[0].email);
        this.newContraente.get('indirizzo')?.setValue(cli.body[0].indirizzo);
        this.newContraente.get('citta')?.setValue(cli.body[0].citta);
        this.newContraente.get('nazione')?.setValue(cli.body[0].nazione);
        this.newContraente.get('cap')?.setValue(cli.body[0].cap);
        this.newContraente.get('telefono')?.setValue(cli.body[0].telefono);
      } else {
        this.newContraente.get('nome')?.setValue('-');
        this.newContraente.get('cognome')?.setValue('-');
        this.newContraente.get('codice_fiscale')?.setValue('-');
        this.newContraente
          .get('ragione_sociale')
          ?.setValue(cli.body[0].ragione_sociale);
        this.newContraente
          .get('partita_iva')
          ?.setValue(cli.body[0].partita_iva);
        this.newContraente.get('email')?.setValue(cli.body[0].email);
        this.newContraente.get('indirizzo')?.setValue(cli.body[0].indirizzo);
        this.newContraente.get('citta')?.setValue(cli.body[0].citta);
        this.newContraente.get('nazione')?.setValue(cli.body[0].nazione);
        this.newContraente.get('cap')?.setValue(cli.body[0].cap);
        this.newContraente.get('telefono')?.setValue(cli.body[0].telefono);
      }
    });
  }

  storeContraente() {
    this.showError = true;
    //console.log('salva contraente');
    console.log(this.tipocli);

    if (this.tipocli == 0) {
      //console.log("buisiness setto nome cognome e codicefiscale vuoti per superare i controlli");
      this.newContraente.get('nome')?.setValue('-');
      this.newContraente.get('cognome')?.setValue('-');
      this.newContraente.get('codice_fiscale')?.setValue('----------------');
    } else {
      //console.log("consumer setto ragonesociale e partitaiva vuoti per superare i controlli ");
      this.newContraente.get('ragione_sociale')?.setValue('-');
      this.newContraente.get('partita_iva')?.setValue('-----------');
    }

    console.log(this.newContraente.value);
    console.log(this.newContraente.controls);

    if (this.newContraente.valid) {
      if (this.tipocli == 0) {
        //console.log("buisiness setto nome cognome e codicefiscale vuoti per superare i controlli");
        this.newContraente.get('nome')?.setValue(null);
        this.newContraente.get('cognome')?.setValue(null);
        this.newContraente.get('codice_fiscale')?.setValue(null);
      } else {
        //console.log("consumer setto ragonesociale e partitaiva vuoti per superare i controlli ");
        this.newContraente.get('ragione_sociale')?.setValue(null);
        this.newContraente.get('partita_iva')?.setValue(null);
      }

      //console.log(this.newContraente.value);
      this.matspinner = false;
      document
        .getElementById('nuovocontraente')
        ?.classList.add('nuovocontraente');
      this.apiService
        .nuovoContraente(this.newContraente.value)
        .pipe(delay(2000))
        .subscribe((risposta: any) => {
          this.matspinner = true;
          console.log(risposta);

          const snackBarRef = this._snackBar.open(
            'Contraente Registrato',
            'Chiudi',
            {
              horizontalPosition: this.horizontalPosition,
              verticalPosition: this.verticalPosition,
              duration: 8000, // Auto close after 5 seconds
            }
          );

          document
            .getElementById('nuovocontraente')
            ?.classList.remove('nuovocontraente');
          this.contraente.setIdContraente(risposta.body.id);
          this.contraente.setTipoContraente(this.contraenteType);
        });
    } else {
      //console.log('Il form non è valido!');
      for (const controlName in this.newContraente.controls) {
        if (this.newContraente.controls[controlName].invalid) {
          console.log(`Il campo ${controlName} non è valido`);
          console.log(this.newContraente.controls[controlName].errors); // Stampa gli errori specifici
        }
      }
    }
  }
}
