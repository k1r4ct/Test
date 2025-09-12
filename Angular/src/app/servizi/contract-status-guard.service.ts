import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ContractServiceStatus {
  private contrattoSalvatoSubject = new BehaviorSubject<boolean>(true); 
  contrattoSalvato$ = this.contrattoSalvatoSubject.asObservable();

  setContrattoSalvato(salvato: boolean) {
    this.contrattoSalvatoSubject.next(salvato);
  }

  isContrattoSalvato() {
    return this.contrattoSalvatoSubject.value;
  }
}