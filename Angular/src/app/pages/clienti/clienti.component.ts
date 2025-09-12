import { Component, OnDestroy, OnInit } from '@angular/core';
import {
  trigger,
  state,
  style,
  animate,
  transition,
} from '@angular/animations';
import { MatSelectChange } from '@angular/material/select';
import { SharedService } from 'src/app/servizi/shared.service';
@Component({
    selector: 'app-clienti',
    templateUrl: './clienti.component.html',
    styleUrls: ['./clienti.component.scss'],
    animations: [
        trigger('pageTransition', [
            transition(':enter', [
                style({ opacity: 0, transform: 'scale(0.1)' }),
                animate('500ms ease-in-out', style({ opacity: 1, transform: 'scale(1)' })),
            ]),
            transition(':leave', [
                // Una sola transizione :leave
                style({ opacity: 1, transform: 'scale(1)' }),
                animate('500ms ease-in-out', style({ opacity: 0, transform: 'scale(0.1)' })),
            ]),
        ]),
    ],
    standalone: false
})
export class ClientiComponent implements OnInit, OnDestroy {
  constructor(private readonly SharedService: SharedService) {}
  
  hiddencomponentRicerca= true;
  hiddencomponent = true;
  hiddenNuovocontratto = true;
  // array={'nome':'Alessio','cognome':'Scionti'}

  selectedData: any;
  state: any;
  clientehidden = true;
  clientehidden2 = true;
  plusminus = 'Apri ';
  codicefiscale = true;
  partitaiva = true;
  contrattohidden = true;
  contrattohidden2 = true;
  nome: any;
  cognome: any;

  ngOnInit(): void {
    this.SharedService.Component$.subscribe((show) => {
       console.log("SharedService.Component$: ");
       //console.log(show);
      this.hiddencomponent = !show;
    });
    this.SharedService.NewContratto$.subscribe((show) => {
      // console.log("SharedService.NewContratto$: ");
       //console.log(show);
      this.hiddenNuovocontratto = !show;
    });
    this.SharedService.contract_search$.subscribe((show) => {
      // console.log("SharedService.NewContratto$: ");
       //console.log(show);
      this.hiddencomponentRicerca = !show;
    });
  }
  prenditipocontratto(event: MatSelectChange) {
    const tipocontratto = event.value;
    //console.log(tipocontratto);
    this.showmenucontratto(tipocontratto);
  }

  prendicliente(event: MatSelectChange) {
    const tipocliente = event.value;
    //console.log("prenidi cliente");
    //console.log(tipocliente);
    this.nuovocliente(tipocliente);
  }

  showmenucontratto(tipocontr: any) {
    //console.log(tipocontr);

    if (this.contrattohidden == true) {
      this.contrattohidden = false;
      this.clientehidden = true;
      this.clientehidden2 = true;
      //this.plusminus = "Chiudi ";
    }
    if (tipocontr == 'Partita iva') {
      this.contrattohidden2 = false;
      this.partitaiva = false;
      this.codicefiscale = true;
    } else if (tipocontr == 'Consumer') {
      this.contrattohidden2 = false;
      this.codicefiscale = false;
      this.partitaiva = true;
    }
  }

  nuovocliente(tipocliente: any) {
    //console.log(tipocliente);

    if (this.clientehidden == true) {
      this.clientehidden = false;

      //this.plusminus = "Chiudi ";
    }
    if (tipocliente == 'Partita iva') {
      this.clientehidden2 = false;
      this.partitaiva = false;
      this.codicefiscale = true;
    } else if (tipocliente == 'Consumer') {
      this.clientehidden2 = false;
      this.codicefiscale = false;
      this.partitaiva = true;
    }
  }

  ngOnDestroy(): void {
    this.SharedService.hideComponent();
    this.SharedService.hideNewContratto();
  }

  // contraente(event: MouseEvent){
  //   console.log(event);

  //   this.nome=document.getElementById('nomeC');
  //   this.cognome=document.getElementById('cognomeC');
  //   this.nome.value=this.array.nome
  //   this.cognome.value=this.array.cognome
  //   console.log(this.nome.value);
  // }
}
