import { Component } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';

@Component({
    selector: 'app-home',
    templateUrl: './home.component.html',
    styleUrls: ['./home.component.scss'],
    standalone: false
})
export class HomeComponent {

  title = 'Angular';
  bottone1 = "Carica Dati";
  bottone2 = "Carica Dati con AUTH";
  
  constructor(private servzioAPI: ApiService){}

  prendidati(){
    //console.log("prendo dati");
    this.servzioAPI.LeggiQualifiche();
  }

  prendidati2(){
    //console.log("prendo dati con auth");
    this.servzioAPI.LeggiQualificheAuth();
  }

}
