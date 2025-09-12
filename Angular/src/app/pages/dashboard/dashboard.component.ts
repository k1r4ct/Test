import { AfterViewInit, Component, OnInit } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';
import {trigger,style,animate,transition} from '@angular/animations';

/* import Chart from 'chart.js'; */


@Component({
    selector: 'dashboard-cmp',
    moduleId: module.id,
    templateUrl: 'dashboard.component.html',
    styleUrl: 'dashboard.component.scss',
    animations: [
        trigger("pageTransition", [
            transition(":enter,:leave", [
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

export class DashboardComponent implements OnInit{
  
  public canvas : any;
  public ctx: any;
  public chartColor!: string;
  public chartEmail: any;
  public chartHours: any;
  User:any;
  state:any;
  constructor(private servizioAPI:ApiService){}
    /* ngOnInit(){
      this.user=this.servzioAPI.PrendiUtente('alessioscionti@gmail.com');
      console.log(this.servzioAPI.utente);
    } */
    
    ngOnInit() {     
      this.servizioAPI.PrendiUtente().subscribe(user => {
        this.User = user.user;
        //console.log(this.User);
    });
    }
    
    animationDone(event: any) { // Metodo per gestire la fine dell'animazione
      if (event.toState === 'out' && event.phaseName === 'done') {
        // Qui puoi aggiungere la logica per navigare alla pagina successiva o eseguire altre azioni dopo l'animazione
      }
    }
    
}
