import { Component,HostBinding } from '@angular/core';
import {trigger,state,style,animate,transition} from '@angular/animations';
import { Router } from '@angular/router';
@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.scss'],
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
export class AppComponent {
  constructor(private router: Router) {}
  state = 'pagina1';

  

  getRouteAnimationData() {
    return this.router.url; // Ora puoi utilizzare this.router
  }

  
}
