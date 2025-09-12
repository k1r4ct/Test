import { EventEmitter, Injectable } from '@angular/core';
import { BehaviorSubject, Observable, Subscription } from 'rxjs';
import { DettagliContrattoProdottoComponent } from '../pages/dettagli-contratto-prodotto/dettagli-contratto-prodotto.component';
import { Data } from '@angular/router';

export interface CONTRATTO {
  stato_contratto: null | boolean;
  id_utente: null | number;
  id_cliente: null | number;
  tipoCliente: null | string;
  id_contraente: null | number;
  tipoContraente: null | string;
  id_prodotto: null | number;
  nome_prodotto: null | string;
  opt_prodotto: null | string;
  id_contratto: null | number;
  stepperDis1: boolean;
  stepperDis2: boolean;
  stepperDis3: boolean;
  stepperDis4: boolean;
  data_Stipula: null | string;
  tipo_pagamento: null | string;
  codFpIvaRicerca:null | string;
  contratto_salvato:null | boolean,

  unsubscribe(): void;
}

@Injectable({
  providedIn: 'root',
})
export class ContrattoService {
  public contratto_sub = new BehaviorSubject<CONTRATTO>(Object.assign({}));
  public prodotto_nuovo = new BehaviorSubject<boolean>(false);
  public prodottoObs = this.prodotto_nuovo.asObservable();
  public prodottotemp: number = 0;
  private idContrattoSelezionatoSubject = new BehaviorSubject<number | null>(null);
  idContrattoSelezionato$ = this.idContrattoSelezionatoSubject.asObservable();
  private contrattoSalvato=new BehaviorSubject<boolean | null>(null);
  contrattoSalvatoBool= this.contrattoSalvato.asObservable();
  constructor() {}

  resetNuovoContratto(){
    this.contratto_sub.next({
      stato_contratto: null,
      id_utente: null,
      id_cliente: null,
      tipoCliente: null,
      id_contraente: null,
      tipoContraente: null,
      id_prodotto: null,
      nome_prodotto: null,
      opt_prodotto: null,
      id_contratto: null,
      stepperDis1: true,
      stepperDis2: true,
      stepperDis3: true,
      stepperDis4: true,
      data_Stipula: null,
      tipo_pagamento: null,
      codFpIvaRicerca: null,
      contratto_salvato:null,

      unsubscribe: () => {},
    });
  }


  setStatoContratto(par: boolean | null) {
    this.contratto_sub.next({
      stato_contratto: par,
      id_utente: this.contratto_sub.getValue().id_utente,
      id_cliente: this.contratto_sub.getValue().id_cliente,
      tipoCliente: this.contratto_sub.getValue().tipoCliente,
      id_contraente: this.contratto_sub.getValue().id_contraente,
      tipoContraente: this.contratto_sub.getValue().tipoContraente,
      id_prodotto: this.contratto_sub.getValue().id_prodotto,
      nome_prodotto: this.contratto_sub.getValue().nome_prodotto,
      opt_prodotto: this.contratto_sub.getValue().opt_prodotto,
      id_contratto: this.contratto_sub.getValue().id_contratto,
      stepperDis1: true,
      stepperDis2: true,
      stepperDis3: true,
      stepperDis4: true,
      data_Stipula: this.contratto_sub.getValue().data_Stipula,
      tipo_pagamento: this.contratto_sub.getValue().tipo_pagamento,
      codFpIvaRicerca:this.contratto_sub.getValue().codFpIvaRicerca,
      contratto_salvato:this.contratto_sub.getValue().contratto_salvato,

      unsubscribe: () => {},
    });
  }

  setIdUtente(par: number | null) {
    this.contratto_sub.next({
      stato_contratto: this.contratto_sub.getValue().stato_contratto,
      id_utente: par,
      id_cliente: this.contratto_sub.getValue().id_cliente,
      tipoCliente: this.contratto_sub.getValue().tipoCliente,
      id_contraente: this.contratto_sub.getValue().id_contraente,
      tipoContraente: this.contratto_sub.getValue().tipoContraente,
      id_prodotto: this.contratto_sub.getValue().id_prodotto,
      nome_prodotto: this.contratto_sub.getValue().nome_prodotto,
      opt_prodotto: this.contratto_sub.getValue().opt_prodotto,
      id_contratto: this.contratto_sub.getValue().id_contratto,
      stepperDis1: true,
      stepperDis2: true,
      stepperDis3: true,
      stepperDis4: true,
      data_Stipula: this.contratto_sub.getValue().data_Stipula,
      tipo_pagamento: this.contratto_sub.getValue().tipo_pagamento,
      codFpIvaRicerca:this.contratto_sub.getValue().codFpIvaRicerca,
      contratto_salvato:this.contratto_sub.getValue().contratto_salvato,

      unsubscribe: () => {},
    });
  }

  setIdCliente(par: number | null) {
    this.contratto_sub.next({
      stato_contratto: this.contratto_sub.getValue().stato_contratto,
      id_utente: this.contratto_sub.getValue().id_utente,
      id_cliente: par,
      tipoCliente: this.contratto_sub.getValue().tipoCliente,
      id_contraente: this.contratto_sub.getValue().id_contraente,
      tipoContraente: this.contratto_sub.getValue().tipoContraente,
      id_prodotto: this.contratto_sub.getValue().id_prodotto,
      nome_prodotto: this.contratto_sub.getValue().nome_prodotto,
      opt_prodotto: this.contratto_sub.getValue().opt_prodotto,
      id_contratto: this.contratto_sub.getValue().id_contratto,
      stepperDis1: true,
      stepperDis2: true,
      stepperDis3: true,
      stepperDis4: true,
      data_Stipula: this.contratto_sub.getValue().data_Stipula,
      tipo_pagamento: this.contratto_sub.getValue().tipo_pagamento,
      codFpIvaRicerca:this.contratto_sub.getValue().codFpIvaRicerca,
      contratto_salvato:this.contratto_sub.getValue().contratto_salvato,

      unsubscribe: () => {},
    });
  }

  setTipoCliente(par: string | null) {
    this.contratto_sub.next({
      stato_contratto: this.contratto_sub.getValue().stato_contratto,
      id_utente: this.contratto_sub.getValue().id_utente,
      id_cliente: this.contratto_sub.getValue().id_cliente,
      tipoCliente: par,
      id_contraente: this.contratto_sub.getValue().id_contraente,
      tipoContraente: this.contratto_sub.getValue().tipoContraente,
      id_prodotto: this.contratto_sub.getValue().id_prodotto,
      nome_prodotto: this.contratto_sub.getValue().nome_prodotto,
      opt_prodotto: this.contratto_sub.getValue().opt_prodotto,
      id_contratto: this.contratto_sub.getValue().id_contratto,
      stepperDis1: true,
      stepperDis2: true,
      stepperDis3: true,
      stepperDis4: true,
      data_Stipula: this.contratto_sub.getValue().data_Stipula,
      tipo_pagamento: this.contratto_sub.getValue().tipo_pagamento,
      codFpIvaRicerca:this.contratto_sub.getValue().codFpIvaRicerca,
      contratto_salvato:this.contratto_sub.getValue().contratto_salvato,

      unsubscribe: () => {},
    });
  }

  setIdContraente(par: number | null) {
    this.contratto_sub.next({
      stato_contratto: this.contratto_sub.getValue().stato_contratto,
      id_utente: this.contratto_sub.getValue().id_utente,
      id_cliente: this.contratto_sub.getValue().id_cliente,
      tipoCliente: this.contratto_sub.getValue().tipoCliente,
      id_contraente: par,
      tipoContraente: this.contratto_sub.getValue().tipoContraente,
      id_prodotto: this.contratto_sub.getValue().id_prodotto,
      nome_prodotto: this.contratto_sub.getValue().nome_prodotto,
      opt_prodotto: this.contratto_sub.getValue().opt_prodotto,
      id_contratto: this.contratto_sub.getValue().id_contratto,
      stepperDis1: false,
      stepperDis2: true,
      stepperDis3: true,
      stepperDis4: true,
      data_Stipula: this.contratto_sub.getValue().data_Stipula,
      tipo_pagamento: this.contratto_sub.getValue().tipo_pagamento,
      codFpIvaRicerca:this.contratto_sub.getValue().codFpIvaRicerca,
      contratto_salvato:this.contratto_sub.getValue().contratto_salvato,

      unsubscribe: () => {},
    });
  }

  setTipoContraente(par: string | null) {
    this.contratto_sub.next({
      stato_contratto: this.contratto_sub.getValue().stato_contratto,
      id_utente: this.contratto_sub.getValue().id_utente,
      id_cliente: this.contratto_sub.getValue().id_cliente,
      tipoCliente: this.contratto_sub.getValue().tipoCliente,
      id_contraente: this.contratto_sub.getValue().id_contraente,
      tipoContraente: par,
      id_prodotto: this.contratto_sub.getValue().id_prodotto,
      nome_prodotto: this.contratto_sub.getValue().nome_prodotto,
      opt_prodotto: this.contratto_sub.getValue().opt_prodotto,
      id_contratto: this.contratto_sub.getValue().id_contratto,
      stepperDis1: false,
      stepperDis2: true,
      stepperDis3: true,
      stepperDis4: true,
      data_Stipula: this.contratto_sub.getValue().data_Stipula,
      tipo_pagamento: this.contratto_sub.getValue().tipo_pagamento,
      codFpIvaRicerca:this.contratto_sub.getValue().codFpIvaRicerca,
      contratto_salvato:this.contratto_sub.getValue().contratto_salvato,

      unsubscribe: () => {},
    });
  }

  setProdotto(par: number | null, desc: string | null) {
    //console.log('setto prodotto ' + par);

    this.contratto_sub.next({
      stato_contratto: this.contratto_sub.getValue().stato_contratto,
      id_utente: this.contratto_sub.getValue().id_utente,
      id_cliente: this.contratto_sub.getValue().id_cliente,
      tipoCliente: this.contratto_sub.getValue().tipoCliente,
      id_contraente: this.contratto_sub.getValue().id_contraente,
      tipoContraente: this.contratto_sub.getValue().tipoContraente,
      id_prodotto: par,
      nome_prodotto: desc,
      opt_prodotto: this.contratto_sub.getValue().opt_prodotto,
      id_contratto: this.contratto_sub.getValue().id_contratto,
      stepperDis1: false,
      stepperDis2: false,
      stepperDis3: true,
      stepperDis4: true,
      data_Stipula: this.contratto_sub.getValue().data_Stipula,
      tipo_pagamento: this.contratto_sub.getValue().tipo_pagamento,
      codFpIvaRicerca:this.contratto_sub.getValue().codFpIvaRicerca,
      contratto_salvato:this.contratto_sub.getValue().contratto_salvato,

      unsubscribe: () => {},
    });

    if (typeof par === 'number') {
      if (this.prodottotemp != par) {
        this.prodottotemp = par;
        this.prodotto_nuovo.next(true);
      } else {
        this.prodotto_nuovo.next(false);
      }
    }

    if (par === null) {
      this.prodotto_nuovo.next(false);
    }
  }

  setOptionProdotto(par: any | null) {

    
    let optProdottoString = par;
    //const optProdottoString = JSON.stringify(par);
    //var test=JSON.parse(optProdottoString);
    console.log(" dati risposta salvati in SetOptionProdotto del contratto ");    
    console.log(optProdottoString);

    this.contratto_sub.next({
      stato_contratto: this.contratto_sub.getValue().stato_contratto,
      id_utente: this.contratto_sub.getValue().id_utente,
      id_cliente: this.contratto_sub.getValue().id_cliente,
      tipoCliente: this.contratto_sub.getValue().tipoCliente,
      id_contraente: this.contratto_sub.getValue().id_contraente,
      tipoContraente: this.contratto_sub.getValue().tipoContraente,
      id_prodotto: this.contratto_sub.getValue().id_prodotto,
      nome_prodotto: this.contratto_sub.getValue().nome_prodotto,
      opt_prodotto: JSON.parse(optProdottoString),
      id_contratto: this.contratto_sub.getValue().id_contratto,
      stepperDis1: false,
      stepperDis2: false,
      stepperDis3: false,
      stepperDis4: true,
      data_Stipula: this.contratto_sub.getValue().data_Stipula,
      tipo_pagamento: this.contratto_sub.getValue().tipo_pagamento,
      codFpIvaRicerca:this.contratto_sub.getValue().codFpIvaRicerca,
      contratto_salvato:this.contratto_sub.getValue().contratto_salvato,

      unsubscribe: () => {},
    });
  }

  setCodFiscalePiva(par: string | null) {
    //console.log(par);

    const ricercaCodFpIva = JSON.stringify(par);
    //var test=JSON.parse(optProdottoString);
    //console.log(test);

    this.contratto_sub.next({
      stato_contratto: this.contratto_sub.getValue().stato_contratto,
      id_utente: this.contratto_sub.getValue().id_utente,
      id_cliente: this.contratto_sub.getValue().id_cliente,
      tipoCliente: this.contratto_sub.getValue().tipoCliente,
      id_contraente: this.contratto_sub.getValue().id_contraente,
      tipoContraente: this.contratto_sub.getValue().tipoContraente,
      id_prodotto: this.contratto_sub.getValue().id_prodotto,
      nome_prodotto: this.contratto_sub.getValue().nome_prodotto,
      opt_prodotto: this.contratto_sub.getValue().opt_prodotto,
      id_contratto: this.contratto_sub.getValue().id_contratto,
      stepperDis1: false,
      stepperDis2: false,
      stepperDis3: false,
      stepperDis4: true,
      data_Stipula: this.contratto_sub.getValue().data_Stipula,
      tipo_pagamento: this.contratto_sub.getValue().tipo_pagamento,
      codFpIvaRicerca:JSON.parse(ricercaCodFpIva),
      contratto_salvato:this.contratto_sub.getValue().contratto_salvato,

      unsubscribe: () => {},
    });
  }

  // setNomeProdotto(par: string | null) {
  //   this.contratto_sub.next({
  //     stato_contratto: this.contratto_sub.getValue().stato_contratto,
  //     id_utente: this.contratto_sub.getValue().id_utente,
  //     id_cliente: this.contratto_sub.getValue().id_cliente,
  //     tipoCliente: this.contratto_sub.getValue().tipoCliente,
  //     id_contraente: this.contratto_sub.getValue().id_contraente,
  //     tipoContraente: this.contratto_sub.getValue().tipoContraente,
  //     id_prodotto: this.contratto_sub.getValue().id_prodotto,
  //     nome_prodotto:par,
  //     unsubscribe:()=> {}
  //   });
  // }

  getContratto(): Observable<CONTRATTO> {
    //console.log(this,this.contratto_sub);
    return this.contratto_sub.asObservable();
  }

  resetCambioProdotto() {
    //console.log(this.evento);
  }

  setIdContratto(par: number | null) {
    //console.log('setto contratto ' + par);
    this.idContrattoSelezionatoSubject.next(par); 

    this.contratto_sub.next({
      stato_contratto: this.contratto_sub.getValue().stato_contratto,
      id_utente: this.contratto_sub.getValue().id_utente,
      id_cliente: this.contratto_sub.getValue().id_cliente,
      tipoCliente: this.contratto_sub.getValue().tipoCliente,
      id_contraente: this.contratto_sub.getValue().id_contraente,
      tipoContraente: this.contratto_sub.getValue().tipoContraente,
      id_prodotto: this.contratto_sub.getValue().id_prodotto,
      nome_prodotto: this.contratto_sub.getValue().nome_prodotto,
      opt_prodotto: this.contratto_sub.getValue().opt_prodotto,
      id_contratto: par,
      stepperDis1: false,
      stepperDis2: false,
      stepperDis3: false,
      stepperDis4: false,
      data_Stipula: this.contratto_sub.getValue().data_Stipula,
      tipo_pagamento: this.contratto_sub.getValue().tipo_pagamento,
      codFpIvaRicerca:this.contratto_sub.getValue().codFpIvaRicerca,
      contratto_salvato:this.contratto_sub.getValue().contratto_salvato,
      
      unsubscribe: () => {},
    });
  }

  setDataePagamento(tipo_pagamento: any | null, data_Pagamento: any | null) {
    this.contratto_sub.next({
      stato_contratto: this.contratto_sub.getValue().stato_contratto,
      id_utente: this.contratto_sub.getValue().id_utente,
      id_cliente: this.contratto_sub.getValue().id_cliente,
      tipoCliente: this.contratto_sub.getValue().tipoCliente,
      id_contraente: this.contratto_sub.getValue().id_contraente,
      tipoContraente: this.contratto_sub.getValue().tipoContraente,
      id_prodotto: this.contratto_sub.getValue().id_prodotto,
      nome_prodotto: this.contratto_sub.getValue().nome_prodotto,
      opt_prodotto: this.contratto_sub.getValue().opt_prodotto,
      id_contratto: this.contratto_sub.getValue().id_contratto,
      stepperDis1: false,
      stepperDis2: false,
      stepperDis3: false,
      stepperDis4: false,
      data_Stipula: data_Pagamento,
      tipo_pagamento: tipo_pagamento,
      codFpIvaRicerca:this.contratto_sub.getValue().codFpIvaRicerca,
      contratto_salvato:this.contratto_sub.getValue().contratto_salvato,
      unsubscribe: () => {},
    });
  }

  setContrattoSalvato(salvato:any) {
    this.contratto_sub.next({
      stato_contratto: this.contratto_sub.getValue().stato_contratto,
      id_utente: this.contratto_sub.getValue().id_utente,
      id_cliente: this.contratto_sub.getValue().id_cliente,
      tipoCliente: this.contratto_sub.getValue().tipoCliente,
      id_contraente: this.contratto_sub.getValue().id_contraente,
      tipoContraente: this.contratto_sub.getValue().tipoContraente,
      id_prodotto: this.contratto_sub.getValue().id_prodotto,
      nome_prodotto: this.contratto_sub.getValue().nome_prodotto,
      opt_prodotto: this.contratto_sub.getValue().opt_prodotto,
      id_contratto: this.contratto_sub.getValue().id_contratto,
      stepperDis1: false,
      stepperDis2: false,
      stepperDis3: false,
      stepperDis4: false,
      data_Stipula: this.contratto_sub.getValue().data_Stipula,
      tipo_pagamento: this.contratto_sub.getValue().tipo_pagamento,
      codFpIvaRicerca:this.contratto_sub.getValue().codFpIvaRicerca,
      contratto_salvato:salvato,
      unsubscribe: () => {},
    });
  }
}
