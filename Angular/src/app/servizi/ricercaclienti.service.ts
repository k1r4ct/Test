import {Injectable } from '@angular/core';
import { BehaviorSubject} from 'rxjs';

export interface Ricerca {
  codF_Piva: null | string;

  unsubscribe(): void;
}

@Injectable({
  providedIn: 'root',
})
export class RicercaclientiService {
  public codFPartitaiva = new BehaviorSubject<Ricerca>(Object.assign({}));

  constructor() {}

  resetNuovaRicerca(){
    this.codFPartitaiva.next({
      codF_Piva: null,
      unsubscribe: () => {},
    });
  }


  setRicerca(par: string | null) {
    this.codFPartitaiva.next({
      codF_Piva: par,
      unsubscribe: () => {},
    });
  }

}
