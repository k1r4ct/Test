import { Component, OnInit } from "@angular/core";
import { ApiService } from "src/app/servizi/api.service";
import { Subscription } from "rxjs";
import { ChangeDetectorRef } from "@angular/core";
import { log } from "console";
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
  selector: "app-card-statisticSeu",
  templateUrl: "./card-statistic.component.html",
  styleUrl: "./card-statistic.component.scss",
  standalone: false,
})
export class CardStatisticComponentSeu implements OnInit {
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
  lead:any;
  date: Date = new Date();
  placeholder = "Seleziona un mese";
  UserID: number = 0;

  contacontrUSER: number = 0;
  contacontrFIGLI: number = 0;

  constructor(private servzioAPI: ApiService, private cd: ChangeDetectorRef) {}

  ngOnDestroy(): void {
    this.subscriptions.forEach((sub) => sub.unsubscribe());
  }

  ngOnInit() {

    //console.log(this.date);

    // Recupera i dati dell'utente e dei contratti
    this.subscriptions.push(
      this.servzioAPI.PrendiUtente().subscribe((users: any) => {
        const userId = users.user.id;
        this.UserID = userId;


        // Recupera i dati combinati
        this.servzioAPI.getCombinedData(userId).subscribe((data) => {
          console.log(data);
          
          // Calcola i valori necessari dai dati ricevuti
          //this.countContratti = data.contratti.body.risposta.length;
          this.contratti = data.contratti.body.risposta.data;

          if(this.contratti.length>0){

            const CONTRATTIINSERITIDAUTENTE = this.contratti.filter( (contr: any) => contr.inserito_da_user_id === userId);
             //console.log("numero contratti (di user " + this.UserID + ") diretti filtrati : " + CONTRATTIINSERITIDAUTENTE.length);
            this.countContratti = CONTRATTIINSERITIDAUTENTE.length;

            const CONTRATTIINSERITIDASQUADRA = this.contratti.filter( (contr: any) => contr.inserito_da_user_id !== userId);
             //console.log("numero contratti (NON di user " + this.UserID + ") squadra filtrati : " + CONTRATTIINSERITIDASQUADRA.length);

                // i PV sono generati dallo stato contratto 15
                this.compensopvdiretti = CONTRATTIINSERITIDAUTENTE.reduce(
                  (total: number, contratto: any) =>
                    contratto.status_contract_id === 15
                      ? total + contratto.product.macro_product.punti_valore
                      : total,
                  0
                );

                // i PC sono generati dallo stato contratto 15, 10, 14
                this.compensopcdiretti = CONTRATTIINSERITIDAUTENTE.reduce(
                  (total: number, contratto: any) =>
                    contratto.status_contract_id === 15 ||
                    contratto.status_contract_id === 10 ||
                    contratto.status_contract_id === 14
                      ? total + contratto.product.macro_product.punti_carriera
                      : total,
                  0
                );

                // i PV sono generati dallo stato contratto 15
                this.compensopvdirettiSquadra = CONTRATTIINSERITIDASQUADRA.reduce(
                  (total: number, contratto: any) =>
                    contratto.status_contract_id === 15
                      ? total + contratto.product.macro_product.punti_valore
                      : total,
                  0
                );

                // i PC sono generati dallo stato contratto 15, 10, 14
                this.compensopcdirettiSquadra = CONTRATTIINSERITIDASQUADRA.reduce(
                  (total: number, contratto: any) =>
                    contratto.status_contract_id === 15 ||
                    contratto.status_contract_id === 10 ||
                    contratto.status_contract_id === 14
                      ? total + contratto.product.macro_product.punti_carriera
                      : total,
                  0
                );


          }

          // console.log(this.compensopvdiretti);
          // console.log(this.compensopvdirettiSquadra);
          // console.log(this.compensopcdiretti);
          // console.log(this.compensopcdirettiSquadra);

          // console.log("contratti totali utente e di squadra: " + data.contratti.body.risposta.length);

          //console.log(this.compensopcdiretti);
          //console.log(this.compensopcdirettiSquadra);

          // Aggiorna i dettagli dell'utente
          this.User = {
            id: userId,
            nome: users.user.name || " ",
            cognome: users.user.cognome || " ",
            email: users.user.email,
            rag_soc: users.user.ragione_sociale || " ",
            indirizzo: users.user.indirizzo,
            citta: users.user.citta,
            stato: users.user.stato || "NON INSERITO",
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
            name: us.name || " ",
            cognome: us.cognome || " ",
            email: us.email,
            rag_soc: us.ragione_sociale || " ",
            indirizzo: us.indirizzo,
            citta: us.citta,
            stato: us.stato || "NON INSERITO",
            cap: us.cap,
            compensopvdiretti: us.qualification.compenso_pvdiretti,
            qualifica: us.qualification.descrizione,
            ruolo: us.role.descrizione,
            pcNecessari: us.qualification.pc_necessari,
            children: us.children,
            foto: "",
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


    // Recupera il totale dei leads
    this.servzioAPI.getLeads().subscribe((LeadsAll: any) => {
      this.lead=LeadsAll.body.risposta;
      //console.log(this.lead);
      const LEADSINSERITIDAUTENTE = this.lead.filter( (leads: any) => leads.invitato_da_user_id === this.UserID);
      //console.log("inizio:" + LEADSINSERITIDAUTENTE.length);
      //console.log(LEADSINSERITIDAUTENTE);
      this.numeroClientiAmiciInvitati = LEADSINSERITIDAUTENTE.length;
      //this.numeroClientiAmiciInvitati = LeadsAll.body.Totale_Leads;
    });

  }
  onDateSelect(event: any) {
    //console.log("Data selezionata:", event);

    const selectedDate = event; // Data selezionata dal datepicker
    const year = selectedDate.getFullYear();
    const month = (selectedDate.getMonth() + 1).toString().padStart(2, "0"); // +1 perché i mesi partono da 0 (Gennaio = 0)
    const newdate = `${year}-${month}`;
    
    // Crea filtri per la ricerca server-side
    const startDate = `01/${month}/${year}`;
    const lastDay = new Date(year, selectedDate.getMonth() + 1, 0).getDate();
    const endDate = `${lastDay}/${month}/${year}`;
    
    const filters = [
      ['datains', [startDate, endDate]] // Filtro per data inserimento
    ];
    
    console.log('Filtro date applicato:', filters);
    
    // Mantieni il filtro per i leads (dato che non hai un endpoint specifico)
    const filteredLead = this.lead.filter((lead: any) => {
      const dataStringaLead = lead.created_at;
      const partiDataLead1 = dataStringaLead.toString().split("T");
      const partiDataLead = partiDataLead1[0].toString().split("-");
      const dataFormattedLead = partiDataLead[0] + "-" + partiDataLead[1];

      if (dataFormattedLead === newdate) {
        return true;
      } else {
        return false;
      }
    });
    
    // Chiama la ricerca server-side invece del filtraggio locale
    this.servzioAPI.searchContratti(this.UserID, JSON.stringify(filters), 1, 1000).subscribe((result: any) => {
      if (result && result.body && result.body.risposta && result.body.risposta.data) {
        const filteredContratti = result.body.risposta.data;
        console.log('Contratti filtrati dal server:', filteredContratti);
        
        // Aggiorna le statistiche con i contratti filtrati
        this.updateStatistics(filteredContratti);
        
        // Emetti i dati filtrati per data ai componenti chart
        this.servzioAPI.emitFilteredData(selectedDate, filteredContratti, filteredLead);
      }
    });

    const LEADSINSERITIDAUTENTE = filteredLead.filter((leads: any) => leads.invitato_da_user_id === this.UserID);
    this.numeroClientiAmiciInvitati = LEADSINSERITIDAUTENTE.length;

    // Aggiorna il placeholder
    this.aggiornaPlaceholder();
    this.cd.detectChanges();
  }

  // Nuova funzione per aggiornare le statistiche
  private updateStatistics(contratti: any[]) {
    const CONTRATTIINSERITIDAUTENTE = contratti.filter((contr: any) => contr.inserito_da_user_id === this.UserID);
    const CONTRATTIINSERITIDASQUADRA = contratti.filter((contr: any) => contr.inserito_da_user_id !== this.UserID);

    this.compensopvdiretti = CONTRATTIINSERITIDAUTENTE.reduce(
      (total: number, contratto: any) =>
        contratto.status_contract_id === 15
          ? total + contratto.product.macro_product.punti_valore
          : total,
      0
    );

    this.compensopvdirettiSquadra = CONTRATTIINSERITIDASQUADRA.reduce(
      (total: number, contratto: any) =>
        contratto.status_contract_id === 15
          ? total + contratto.product.macro_product.punti_valore
          : total,
      0
    );

    this.compensopcdiretti = CONTRATTIINSERITIDAUTENTE.reduce(
      (total: number, contratto: any) =>
        contratto.status_contract_id === 15 ||
        contratto.status_contract_id === 10 ||
        contratto.status_contract_id === 14
          ? total + contratto.product.macro_product.punti_carriera
          : total,
      0
    );

    this.compensopcdirettiSquadra = CONTRATTIINSERITIDASQUADRA.reduce(
      (total: number, contratto: any) =>
        contratto.status_contract_id === 15 ||
        contratto.status_contract_id === 10 ||
        contratto.status_contract_id === 14
          ? total + contratto.product.macro_product.punti_carriera
          : total,
      0
    );

    this.countContratti = CONTRATTIINSERITIDAUTENTE.length;
  }
  aggiornaPlaceholder() {
    if (this.date) {
      this.placeholder = `${this.date.toLocaleString('it-IT', { month: 'long' , year: 'numeric'})}`;
    }
  }

  // Metodo per resettare i filtri e tornare ai dati originali
  resetFilters() {
    this.date = new Date();
    this.placeholder = "Seleziona un mese";
    
    // Ripristina i valori originali
    if (this.contratti && this.contratti.length > 0) {
      this.updateStatistics(this.contratti);
    }
    
    // Reset dei leads
    if (this.lead && this.lead.length > 0) {
      const LEADSINSERITIDAUTENTE = this.lead.filter((leads: any) => leads.invitato_da_user_id === this.UserID);
      this.numeroClientiAmiciInvitati = LEADSINSERITIDAUTENTE.length;
    }
    
    // Resetta i filtri nel servizio
    this.servzioAPI.resetFilters();
    
    this.cd.detectChanges();
  }
}
