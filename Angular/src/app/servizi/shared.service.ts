import { BehaviorSubject } from 'rxjs';
import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root',
})
export class SharedService {
  private readonly _showComponent = new BehaviorSubject<boolean>(false);
  private readonly _showNewContratto = new BehaviorSubject<boolean>(false);
  private readonly _showRicercaContratto = new BehaviorSubject<boolean>(false);
  
  public TipoCliente:string = "";
  public CodiceFiscale: string = "";
  public PartitaIva:string = "";

  get Component$(): BehaviorSubject<boolean> {
    return this._showComponent;
  }

  get NewContratto$(): BehaviorSubject<boolean> {
    return this._showNewContratto;
  }

  get contract_search$(): BehaviorSubject<boolean> {
    return this._showRicercaContratto;
  }

  setTipoCliente(par: string){
    this.TipoCliente = par;
    //console.log(par);
  }

  setCodiceFiscale(par: string){
    this.CodiceFiscale = par;
    // console.log(par);
  }

  setPartitaIva(par: string){
    this.PartitaIva = par;
    // console.log(par);
  }

  showComponent() {
    this._showComponent.next(true);
  }

  hideComponent() {
    this._showComponent.next(false);
  }

  showNewContratto(){
    this._showNewContratto.next(true);
  }

  hideNewContratto(){
    this._showNewContratto.next(false);
  }

  showRicercaContratto(){
    this._showRicercaContratto.next(true);
  }

  hideRicercaContratto(){
    this._showRicercaContratto.next(false);
  }


}
