import { trigger, transition, style, animate } from '@angular/animations';
import { Component, OnInit } from '@angular/core';
import {
  FormControl,
  FormGroup,
  Validator,
  FormBuilder,
  Validators,
} from '@angular/forms';

import { delay } from 'rxjs';
import { ApiService } from 'src/app/servizi/api.service';
import { ContrattoService } from 'src/app/servizi/contratto.service';
import { ContractServiceStatus } from 'src/app/servizi/contract-status-guard.service';


export interface tipoDiPagamento {
  id: Number;
  label: String;
  tipo_pagamento: String;
}

@Component({
  selector: 'app-pagamento',
  standalone: false,

  templateUrl: './pagamento.component.html',
  styleUrl: './pagamento.component.scss',
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
export class PagamentoComponent implements OnInit {
  constructor(
    private ApiService: ApiService,
    private Contratto: ContrattoService,
    private formBuilder: FormBuilder,
    private ContractService:ContractServiceStatus,

    
  ) {}
  SetOpt: FormGroup = new FormGroup({});
  tipiDiPagamento: tipoDiPagamento[] = [];
  matspinner = true;
  showError: boolean = false;
  buttonStoreOpt=false;
  ngOnInit(): void {
    this.SetOpt = this.formBuilder.group({
      Tipo_pagamento: new FormControl('', [Validators.required]),
      data_Pagamento_mat: new FormControl('', [Validators.required]),
    });
    
    
    //data_Pagamento: new FormControl(), // Aggiungi il controllo data_Pagamento

    //console.log(this.SetOpt);

    this.ApiService.getPagamentoSystem().subscribe((Pagamento: any) => {
      //console.log(Pagamento);
      this.tipiDiPagamento = Pagamento.body.risposta.map((dati: any) => ({
        id: dati.id,
        label: 'tipo di pagamento',
        tipo_pagamento: dati.tipo_pagamento,
      }));
      //console.log(this.tipiDiPagamento);
    });
  }

  storeOpt() {
    this.ContractService.setContrattoSalvato(false);
    //console.log(this.ContractService);
    this.showError = true;

    if (this.SetOpt.valid) {

    // decommentare quando finito
    this.matspinner = false;
    document
      .getElementById('tipopagamento')
      ?.classList.add('tipopagamentoload');

    const dataString = this.SetOpt.controls['data_Pagamento_mat'].value;

    // 1. Crea un oggetto Date dalla stringa
    const data = new Date(dataString);

    // 2. Estrai anno, mese e giorno
    const anno = data.getFullYear();
    const mese = data.getMonth() + 1; // I mesi in JavaScript partono da 0
    const giorno = data.getDate();

    // 3. Crea l'oggetto desiderato
    const dataStipula = {
      year: anno,
      month: mese,
      day: giorno,
    };

    //console.log(dataStipula);
    //console.log(this.SetOpt.controls['Tipo_pagamento'].value, dataStipula);

    this.Contratto.setDataePagamento(
      this.SetOpt.controls['Tipo_pagamento'].value,
      dataStipula
    );
    
    // Debug: Controlla il contenuto prima di inviare
    const contrattoDaInviare = this.Contratto.contratto_sub.value;
    console.log('Dati contratto da inviare (originali):', contrattoDaInviare);
    
    // Converti opt_prodotto in stringa JSON se è un array/oggetto
    const contrattoPulito = { ...contrattoDaInviare };
    if (contrattoPulito.opt_prodotto && typeof contrattoPulito.opt_prodotto === 'object') {
      contrattoPulito.opt_prodotto = JSON.stringify(contrattoPulito.opt_prodotto);
      console.log('opt_prodotto convertito in stringa:', contrattoPulito.opt_prodotto);
    }
    
    console.log('Dati contratto da inviare (processati):', contrattoPulito);
    
    // Verifica che tutti i campi necessari siano presenti
    const campiObbligatori = ['id_utente', 'id_cliente', 'id_prodotto', 'tipo_pagamento', 'data_Stipula'];
    const campiMancanti = campiObbligatori.filter(campo => !(contrattoPulito as any)[campo]);
    
    if (campiMancanti.length > 0) {
      console.error('Campi obbligatori mancanti:', campiMancanti);
      alert('Errore: alcuni dati obbligatori sono mancanti: ' + campiMancanti.join(', '));
      this.matspinner = true;
      document.getElementById('tipopagamento')?.classList.remove('tipopagamentoload');
      return;
    }
    
    // Verifica formato data
    if (contrattoPulito.data_Stipula && typeof contrattoPulito.data_Stipula === 'object') {
      const dataStipula = contrattoPulito.data_Stipula as any;
      if (!dataStipula.year || !dataStipula.month || !dataStipula.day) {
        console.error('Formato data non valido:', dataStipula);
        alert('Errore: formato data non valido');
        this.matspinner = true;
        document.getElementById('tipopagamento')?.classList.remove('tipopagamentoload');
        return;
      }
    }
    
    //console.log(this.Contratto);

    this.ApiService.storeContratto(contrattoPulito)
      .pipe(delay(1000))
      .subscribe({
        next: (Risposta: any) => {
          console.log('Risposta API successo:', Risposta);
          
          if (Risposta.status == 200) {
            this.buttonStoreOpt = true;
          }
          this.Contratto.setIdContratto(Risposta.body.id_Contratto);

          console.clear();
          console.log(Risposta);

          this.matspinner = true;
          document
            .getElementById('tipopagamento')
            ?.classList.remove('tipopagamentoload');
        },
        error: (errore) => {
          console.error('Errore API:', errore);
          console.log('Status:', errore.status);
          console.log('Error body:', errore.error);
          
          // Mostra un messaggio di errore all'utente
          alert('Errore nel salvataggio del contratto. Controlla la console per i dettagli.');
          
          this.matspinner = true;
          document
            .getElementById('tipopagamento')
            ?.classList.remove('tipopagamentoload');
        }
      });

    }


  }

}
