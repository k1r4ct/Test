import { Component, inject, OnDestroy, OnInit, PLATFORM_ID, ChangeDetectorRef } from "@angular/core";
import { ApiService } from "src/app/servizi/api.service";
import { trigger, style, animate, transition } from "@angular/animations";
import { ObservedValueOf } from "rxjs";
import { AuthService } from "src/app/servizi/auth.service";
import { Router, NavigationStart } from "@angular/router";
import { Subscription } from "rxjs";
import { FormBuilder, FormControl } from "@angular/forms";
import {
  MatSnackBar,
  MatSnackBarHorizontalPosition,
  MatSnackBarVerticalPosition,
} from "@angular/material/snack-bar";
import { MessageService } from 'primeng/api';

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
  n_contratti:number;
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
  foto:any;
  
}
@Component({
  selector: "user-cmp",
  templateUrl: "user.component.html",
  styleUrls: ["./user.component.scss"],
  standalone: false,
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
  ],
  providers: [MessageService] 
})
export class UserComponent implements OnInit, OnDestroy {
  private subscriptions: Subscription[] = [];
  constructor(
    private authService: AuthService,
    private servzioAPI: ApiService,
    private router: Router,
    private _snackBar: MatSnackBar,
    private messageService: MessageService,
    private cd: ChangeDetectorRef
  ) {
    this.router.events.subscribe((event) => {
      if (event instanceof NavigationStart) {
        this.state = "out";

        // Reperisci l'URL di navigazione
        const navigationUrl = event.url;
        //console.log("Navigazione verso:", navigationUrl);

        // Puoi utilizzare navigationUrl per eseguire altre azioni
        // ad esempio, per determinare se l'animazione deve essere applicata
        // o per inviare l'URL a un servizio di analisi
      }
    });
  }
  public user: Object | undefined;
  public datinelcomponente: any;
  horizontalPosition: MatSnackBarHorizontalPosition = "center";
  verticalPosition: MatSnackBarVerticalPosition = "top";
  User: USER = {} as USER;
  Team: TEAM[] = [];
  data: any[] = [];
  dati: any;
  state: any;
  hideComponent = true;
  currentUrl: any;
  isHidden = false;
  DropEnable = true;
  urlImageProfile: any;
  urlImageProfileUrl="https://clearportal.semprechiaro.com/public/storage/";
  //dati utente
  name: string | undefined;
  email: string | undefined;
  surname: string | undefined;
  indirizzo: string | undefined;
  citta: string | undefined;
  paese: string | undefined;
  cap: string | undefined;
  countContratti = 0;
  compensopvdiretti: number = 0;
  pcNecessari: any;
  hideTeamMembers=true;
  message="";
  showProfileForm = false;
  textClienti:string="Amici invitati"
  textLead="";
  numeroClientiAmiciInvitati:number=0;
  IconnumeroClientiAmiciInvitati:string=""; // Inizialmente mostrato
  RuoloCliente=false;
  contratti:any;
  dataChartPie: any;

  options: any;

  platformId = inject(PLATFORM_ID);


  vaiALead() {
    // Naviga alla rotta '/clienti'
    this.router.navigate(["/leads"]);
  }

  vaiASchedaPersonale() {
    // Naviga alla rotta '/clienti'
    this.router.navigate(["/schedapr"]);
  }
  ngOnInit() {
    // Recupera il totale dei leads
    this.servzioAPI.getLeads().subscribe((LeadsAll: any) => {
      this.numeroClientiAmiciInvitati = LeadsAll.body.Totale_Leads;
    });
  
    // Recupera i dati dell'utente e dei contratti
    this.subscriptions.push(
      this.servzioAPI.PrendiUtente().subscribe((users: any) => {
        const userId = users.user.id;
  
        // Recupera i dati combinati
        this.servzioAPI.getCombinedData(userId).subscribe((data) => {
          // Calcola i valori necessari dai dati ricevuti
          this.numeroClientiAmiciInvitati = data.leads.body.Totale_Leads;
          this.countContratti = data.contratti.body.risposta.length;
          this.contratti=data.contratti.body.risposta;
          // Calcola i punti valore dei contratti con stato id 15
          this.compensopvdiretti = data.contratti.body.risposta.reduce(
            (total: number, contratto: any) =>
              contratto.status_contract_id === 15
                ? total + contratto.product.macro_product.punti_valore
                : total,
            0
          );
  
  
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
           if (this.User.ruolo=="Cliente") {
              this.textLead="Amici";
           }else{
              this.textLead="Leads";
           }
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
          this.createOrgChartData();
  
          // Trasmetti i dati combinati al componente figlio tramite il servizio
          this.servzioAPI.emitCombinedData({
            leads: data.leads,
            countContratti: this.countContratti,
          });
        });
  
        // Recupera l'immagine profilo
        this.urlImageProfile = users.immagine;
      })
    );
  
    // Monitora i cambiamenti di rotta
    this.router.events.subscribe((event) => {
      if (event instanceof NavigationStart) {
        this.currentUrl = event.url; // Aggiorna l'URL corrente all'inizio della navigazione
      }
    });
  }
  
  ngOnDestroy(): void {
    this.subscriptions.forEach((sub) => sub.unsubscribe());
  }
  showContrast(message:any,severity:any) {
    //console.log(message);
    //console.log(severity);
    
    //console.log("Show Contrast chiamato");
    this.messageService.add({
      severity: severity,
      summary: 'Modifica Password',
      detail: message,
      life: 30000
    });
  }
  prova() {
    //console.log("verifica utente loggato: " + this.authService.isUserLogin());

    this.servzioAPI.LeggiQualifiche();

    // this.servzioAPI.getData().subscribe( (data) => {
    //   //this.datinelcomponente = data;
    //   console.log("dal BehaviorSubject ");
    //   console.log(data);
    // });

    // this.servzioAPI.LeggiQualifiche2().subscribe( result => {
    //   console.log("da component ");
    //   console.log(result);
    // });
  }
  modificaPassword(id: any) {
    //console.log(id);

    const formData = new FormData();

    const oldPassw = (
      document.querySelector(".oldpassword") as HTMLInputElement
    ).value;
    const newPassword = (
      document.querySelector(".newPassword") as HTMLInputElement
    ).value;
    const repeatNwePassword = (
      document.querySelector(".repeatNewPassword") as HTMLInputElement
    ).value;
    const idUser = id;
    //console.log(newPassword);
    //console.log(repeatNwePassword);
    
    if (newPassword != repeatNwePassword) {
      this.message="Le password nuove non coincidono";
      this.showContrast(this.message,'error');
    }else{

      formData.append("oldPw", oldPassw);
      formData.append("newPw", newPassword);
      formData.append("rNewPw", repeatNwePassword);
      formData.append("idUser", idUser);
      //console.log(formData);
      this.servzioAPI.updatePassw(formData).subscribe((Risposta: any) => {
        //console.log(Risposta);
        if (Risposta.status==200) {
          this.message="PASSWORD MODIFICATA";
          
          this.showContrast(this.message,'success');
        }else{
          this.message="LA PASSWORD VECCHIA è ERRATA";
          this.showContrast(this.message,"error");
        }
      });
    }
  }
  openSnackBar(message:any) {
    this._snackBar.open(message, "Chiudi", {
      horizontalPosition: this.horizontalPosition,
      verticalPosition: this.verticalPosition,
    });
  }
  createOrgChartData() {
    interface TreeNode extends TEAM {
      label: string;
      expanded: boolean;
      children: TreeNode[];
    }
  
    const orgChartData: TreeNode[] = [];
    //console.log(this.Team);
    
    // Crea i nodi prima
    const nodes: TreeNode[] = this.Team.map(member => {
    //console.log(member);
      if (member) {
        this.hideTeamMembers=false;
      }
      return{

        id: member.id,
        name: member.name,
        cognome: member.cognome,
        email: member.email,
        rag_soc: member.rag_soc,
        indirizzo: member.indirizzo,
        citta: member.citta,
        stato: member.stato,
        cap: member.cap,
        compensopvdiretti: member.compensopvdiretti,
        qualifica: member.qualifica,
        ruolo: member.ruolo,
        pcNecessari: member.pcNecessari,
        user_id_padre: member.user_id_padre,
        label: member.name + ' ' + member.cognome,
        expanded: true,
        foto:"",
        children: member.children.map(child => {
          //console.log(child);
          
          return {

            ...child, // Copia le proprietà di child
            label: child.name + ' ' + child.cognome,
            foto:child.foto,
            expanded: true,
            children: [] // Inizializza children per i nodi figli
          }
        })
      }
    });
    //console.log(nodes);
    
    // Crea la mappa dopo aver creato i nodi
    const teamMembersById = new Map(nodes.map(member => [member.id, member]));
    //console.log(teamMembersById);
    const rootIds = this.Team.filter(member => {
      // Controlla se esiste un altro membro in this.Team che ha l'ID di questo membro come user_id_padre
      return !this.Team.some(otherMember => otherMember.user_id_padre === member.id);
    }).map(member => member.id);
    //console.log(rootIds);
    
    nodes.forEach(node => {
      if (rootIds.includes(node.id)) { // Controlla se il nodo è una radice
        orgChartData.push(node);
      } else if (node.user_id_padre) { // Questa condizione gestisce i nodi figli
        // Trova il parent node usando l'ID del padre del nodo corrente
        const parentNode = teamMembersById.get(node.user_id_padre);
        if (parentNode) {
          parentNode.children = parentNode.children || [];
          parentNode.children.push(node);
        }
      }
    });
    
    this.data = orgChartData;
  }

  
}
