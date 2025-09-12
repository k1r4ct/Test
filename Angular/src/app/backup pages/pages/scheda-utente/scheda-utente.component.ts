import { animate, style, transition, trigger } from "@angular/animations";
import { Component, OnInit } from "@angular/core";
import { ApiService } from "src/app/servizi/api.service";

export interface USER {
  id: number;
  nome: string;
  cognome: string;
  email: string;
  rag_soc: string;
  indirizzo: string;
  citta: string;
  cap: number;
  codice_fiscalePiva: string;
  telefono: string;
  nominativo:string;
}

@Component({
  standalone:false,
  selector: 'app-scheda-utente',
  templateUrl: './scheda-utente.component.html',
  styleUrl: './scheda-utente.component.scss',
  animations: [
    trigger("pageTransition", [
      transition(":enter", [
        style({ opacity: 0, transform: "scale(0.1)" }),
        animate(
          "500ms ease-in-out",
          style({ opacity: 1, transform: "scale(1)" })
        ),
      ]),
      transition(":leave", [
        // Una sola transizione :leave

        animate(
          "500ms ease-in-out",
          style({ opacity: 0, transform: "scale(0.1)" })
        ),
      ]),
    ]),
  ]
})
export class SchedaUtenteComponent implements OnInit {
    User: USER = {} as USER;
    test:string="";
    querystring: string = "";
  
    constructor(private servzioAPI: ApiService) {}
  
    ngOnInit(): void {
      this.servzioAPI.PrendiUtente().subscribe((data) => {
        console.log(data);
  
        this.User = {
          id: data.user.id,
          nome: data.user.name?data.user.name:null,
          cognome: data.user.cognome?data.user.cognome:null,
          email: data.user.email,
          rag_soc: data.user.ragione_sociale || " ",
          indirizzo: data.user.indirizzo,
          citta: data.user.citta,
          cap: data.user.cap,
          codice_fiscalePiva: data.user.codice_fiscale?data.user.codice_fiscale:data.user.partita_iva,
          telefono: data.user.telefono,
          nominativo:((data.user.name!=null) && (data.user.cognome!=null))?(data.user.name) + " " + (data.user.cognome):data.user.ragione_sociale
        };
        console.log(this.User);
        this.querystring = `https://docs.google.com/forms/d/e/1FAIpQLSf3rxbvnwORMxaujz4y_Vz2bYfNQubf-V8xSQ0jUaB9yRo6uA/viewform?usp=pp_url&entry.1743659458=${this.User.nominativo}&entry.928339509=${this.User.codice_fiscalePiva}&entry.1580003453=${this.User.telefono}&entry.1041122301=${this.User.email}&entry.1833683546=${this.User.indirizzo}&entry.1104637523=${this.User.cap} `;
      });
    }
}
