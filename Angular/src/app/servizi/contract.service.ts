import { Injectable } from '@angular/core';
import { MatSelectChange } from '@angular/material/select';

@Injectable({
  providedIn: 'root'
})
export class ContractService {

  constructor() { }

  public hidden = true;
  public hidden2 = true;
  public codicefiscale=true;
  public partitaiva=true;
  public prova=2;
  prenditipocontratto(event: MatSelectChange) {
    
    const tipocontratto=event.value
    //console.log(tipocontratto);
    this.showmenucontratto(tipocontratto);
  }


  showmenucontratto(tipocontr:any) {
    //console.log(tipocontr);
    
    this.prova=1;
    if (tipocontr == true) {
      this.hidden = false;
    }
    
    /* if (tipocontr=="Partita iva") {
      this.hidden2 = false;
      this.partitaiva=false;
      this.codicefiscale=true;
    }else if(tipocontr=="Consumer"){
      this.codicefiscale=false;
      this.partitaiva=true;
    } */
  }
  
}
