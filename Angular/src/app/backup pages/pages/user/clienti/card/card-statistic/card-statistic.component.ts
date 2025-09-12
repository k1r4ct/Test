import { Component, OnInit } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';
import { Subscription } from 'rxjs';
import { ChangeDetectorRef } from '@angular/core';

export interface USER {
  id: number;
  nome: string;
  cognome: string;
  email: string;
  rag_soc: string;
  indirizzo: string;
  citta: string;
  stato: string;
  cap: number;
  compensopvdiretti: number;
  qualifica: string;
  ruolo: string;
  pcNecessari: number;
  n_contratti: number;
}
export interface TEAM {
  id: number;
  name: string;
  cognome: string;
  email: string;
  rag_soc: string;
  indirizzo: string;
  citta: string;
  stato: string;
  cap: number;
  compensopvdiretti: number;
  qualifica: string;
  ruolo: string;
  pcNecessari: number;
  user_id_padre: number;
  children: TEAM[];
  foto: any;
}
@Component({
  selector: 'app-card-statistic',
  templateUrl: './card-statistic.component.html',
  styleUrl: './card-statistic.component.scss',
  standalone: false,
})
export class CardStatisticComponent implements OnInit {
  private subscriptions: Subscription[] = [];
  User: USER = {} as USER;
  Team: TEAM[] = [];
  numeroClientiAmiciInvitati: number = 0;
  compensopvdiretti: number = 0;
  compensopvdirettiSquadra: number = 0;
  compensopcdiretti: number = 0;
  compensopcdirettiSquadra: number = 0;
  countContratti = 0;
  contratti: any;
  lead: any;
  date: Date = new Date();
  placeholder = 'Seleziona un mese';
  constructor(private servzioAPI: ApiService, private cd: ChangeDetectorRef) {}

  ngOnDestroy(): void {
    this.subscriptions.forEach((sub) => sub.unsubscribe());
  }

  ngOnInit() {
    // Recupera il totale dei leads
    this.servzioAPI.getLeads().subscribe((LeadsAll: any) => {
      this.lead = LeadsAll.body.risposta;
      //console.log(this.lead);
      this.numeroClientiAmiciInvitati = LeadsAll.body.Totale_Leads;
    });
    //console.log(this.date);

    // Recupera i dati dell'utente e dei contratti
    this.subscriptions.push(
      this.servzioAPI.PrendiUtente().subscribe((users: any) => {
        const userId = users.user.id;

        console.log('user id :' + userId);

        // Recupera i dati combinati
        this.servzioAPI.getCombinedData(userId).subscribe((data) => {
          // Calcola i valori necessari dai dati ricevuti
          this.numeroClientiAmiciInvitati = data.leads.body.Totale_Leads;
          this.countContratti = data.contratti.body.risposta.length;
          this.contratti = data.contratti.body.risposta;

          // console.log(data);
          // console.log('numero contratti : ' + this.contratti);

          const CONTRATTIDELLUTENTE = this.contratti.filter(
            (contr: any) => contr.associato_a_user_id === userId
          );
          // this.countContratti = CONTRATTIDELLUTENTE.length;

          this.compensopvdiretti = CONTRATTIDELLUTENTE.reduce(
            (total: number, contratto: any) =>
              contratto.status_contract_id === 15
                ? total + contratto.product.macro_product.punti_valore
                : total,
            0
          );

          // if (
          //   data.contratti.body.risposta.map((contr: any) => {
          //     if (contr.inserito_da_user_id === userId) {
          //       this.compensopvdiretti = data.contratti.body.risposta.reduce(
          //         (total: number, contratto: any) =>
          //           contratto.status_contract_id === 15 ||
          //           contratto.status_contract_id === 10 ||
          //           contratto.status_contract_id === 14
          //             ? total + contratto.product.macro_product.punti_valore
          //             : total,
          //         0
          //       );

          //       this.compensopcdiretti = data.contratti.body.risposta.reduce(
          //         (total: number, contratto: any) =>
          //           contratto.status_contract_id === 15
          //             ? total + contratto.product.macro_product.punti_carriera
          //             : total,
          //         0
          //       );
          //     } else {
          //       this.compensopvdirettiSquadra =
          //         data.contratti.body.risposta.reduce(
          //           (total: number, contratto: any) =>
          //             contratto.status_contract_id === 15
          //               ? total + contratto.product.macro_product.punti_valore
          //               : total,
          //           0
          //         );

          //       this.compensopcdirettiSquadra =
          //         data.contratti.body.risposta.reduce(
          //           (total: number, contratto: any) =>
          //             contratto.status_contract_id === 15 ||
          //             contratto.status_contract_id === 10 ||
          //             contratto.status_contract_id === 14
          //               ? total + contratto.product.macro_product.punti_carriera
          //               : total,
          //           0
          //         );
          //     }
          //   })
          // )

          // Calcola i punti valore dei contratti con stato id 15
          //console.log(this.compensopvdiretti);
          //console.log(this.compensopvdirettiSquadra);

          //console.log(this.compensopcdiretti);
          //console.log(this.compensopcdirettiSquadra);

          // Aggiorna i dettagli dell'utente
          this.User = {
            id: userId,
            nome: users.user.name || ' ',
            cognome: users.user.cognome || ' ',
            email: users.user.email,
            rag_soc: users.user.ragione_sociale || ' ',
            indirizzo: users.user.indirizzo,
            citta: users.user.citta,
            stato: users.user.stato || 'NON INSERITO',
            cap: users.user.cap,
            compensopvdiretti: users.user.qualification.compenso_pvdiretti,
            qualifica: users.user.qualification.descrizione,
            ruolo: users.user.role.descrizione,
            pcNecessari: users.user.qualification.pc_necessari,
            n_contratti: users.numero_contratti,
          };

          // Configura il team dell'utente
          this.Team = users.team.map((us: any) => ({
            id: us.id,
            name: us.name || ' ',
            cognome: us.cognome || ' ',
            email: us.email,
            rag_soc: us.ragione_sociale || ' ',
            indirizzo: us.indirizzo,
            citta: us.citta,
            stato: us.stato || 'NON INSERITO',
            cap: us.cap,
            compensopvdiretti: us.qualification.compenso_pvdiretti,
            qualifica: us.qualification.descrizione,
            ruolo: us.role.descrizione,
            pcNecessari: us.qualification.pc_necessari,
            children: us.children,
            foto: '',
          }));

          // Genera i dati per l'organigramma

          // Trasmetti i dati combinati al componente figlio tramite il servizio
          this.servzioAPI.emitCombinedData({
            leads: data.leads,
            countContratti: this.countContratti,
          });
        });

        // Recupera l'immagine profilo
      })
    );
  }
}
