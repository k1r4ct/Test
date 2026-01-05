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

// Dichiarazione globale per JSCharting
declare var JSC: any;

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
  styleUrls: ["./user.component.scss", "./user.component.jscharting.scss"],
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
        animate(
          "500ms ease-in-out",
          style({ opacity: 0, transform: "scale(0.1)" })
        ),
      ]),
    ])
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
  urlImageProfileUrl="https://semprechiaro.com/storage/app/public/";
  
  // User data properties (read-only display)
  name: string | undefined;
  email: string | undefined;
  surname: string | undefined;
  countContratti = 0;
  compensopvdiretti: number = 0;
  pcNecessari: any;
  hideTeamMembers=true;
  message="";
  
  // REMOVED: showProfileForm - Profile editing is now in modal
  
  textClienti:string="Amici invitati"
  textLead="";
  numeroClientiAmiciInvitati:number=0;
  IconnumeroClientiAmiciInvitati:string="";
  RuoloCliente=false;
  contratti:any;
  dataChartPie: any;

  options: any;

  // JSCharting properties
  jsChart: any = null;
  jsChartData: any[] = [];
  private resizeTimeout: any;

  platformId = inject(PLATFORM_ID);

  // ⭐ WALLET INTEGRATION - NEW PROPERTY ⭐
  isCliente: boolean = false;


  vaiALead() {
    this.router.navigate(["/leads"]);
  }

  vaiASchedaPersonale() {
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
          this.countContratti = data.contratti.body.risposta.data.length;
          this.contratti=data.contratti.body.risposta.data;
          
          // Calcola i punti valore dei contratti con stato id 15
          this.compensopvdiretti = data.contratti.body.risposta.data.reduce(
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

          // ⭐ WALLET INTEGRATION - CHECK USER ROLE ⭐
          if (users.user.role.id === 3) {
            this.isCliente = true;
            this.hideTeamMembers = true;
          } else {
            this.isCliente = false;
            this.hideTeamMembers = false;
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
            user_id_padre: us.user_id_padre,
            children: us.children,
            foto: us.foto || "",
          }));
          
          this.Team.forEach((member, index) => {
            if (member.foto) {
              member.foto = this.urlImageProfileUrl + member.foto;
            } else {
              member.foto = 'assets/img/default-avatar.png';
            }
          });

          // Recupera l'immagine profilo
          this.urlImageProfile = users.immagine;
          let parts = users.immagine.split("/");
          this.urlImageProfileUrl = this.urlImageProfileUrl + parts[4]+"/"+parts[5]+"/"+parts[6]+"/"+parts[7];

          this.data = this.buildHierarchy(this.Team);

          // Create org chart data
          this.createOrgChartData();

          // Trasmetti i dati combinati al componente figlio
          this.servzioAPI.emitCombinedData({
            leads: data.leads,
            countContratti: this.countContratti,
          });

          this.dataChartPie = {
            labels: ["Contratti", "Rimanenti per Obiettivo"],
            datasets: [
              {
                data: [
                  this.countContratti,
                  this.User.pcNecessari - this.countContratti,
                ],
                backgroundColor: ["#FF6384", "#36A2EB"],
                hoverBackgroundColor: ["#FF6384", "#36A2EB"],
              },
            ],
          };

          this.options = {
            plugins: {
              legend: {
                labels: {
                  usePointStyle: true,
                  color: "white",
                },
              },
            },
          };
        });
      })
    );

    // Monitora i cambiamenti di rotta
    this.router.events.subscribe((event) => {
      if (event instanceof NavigationStart) {
        this.currentUrl = event.url;
      }
    });
  }

  buildHierarchy(teamData: TEAM[]): TEAM[] {
    const map: { [key: number]: TEAM } = {};
    const roots: TEAM[] = [];

    teamData.forEach((member) => {
      member.children = [];
      map[member.id] = member;
    });

    teamData.forEach((member) => {
      if (member.user_id_padre && map[member.user_id_padre]) {
        map[member.user_id_padre].children.push(member);
      } else {
        roots.push(member);
      }
    });

    return roots;
  }

  ngOnDestroy(): void {
    this.subscriptions.forEach((sub) => sub.unsubscribe());
    
    if (this.resizeTimeout) {
      clearTimeout(this.resizeTimeout);
    }
    
    window.removeEventListener('resize', this.handleResize);
    
    if (this.jsChart) {
      this.jsChart.dispose();
      this.jsChart = null;
    }
  }

  private handleResize = () => {
    if (this.jsChart) {
      clearTimeout(this.resizeTimeout);
      this.resizeTimeout = setTimeout(() => {
        this.renderJSChart();
      }, 300);
    }
  };

  // =========================================
  // REMOVED METHODS - Now in ProfileSettingsModalComponent
  // =========================================
  // prova(id) - Profile update moved to modal
  // modificaPassword(id) - Password change moved to modal
  // toggleProfileForm() - No longer needed
  // =========================================

  openSnackBar(message:any) {
    this._snackBar.open(message, "Chiudi", {
      horizontalPosition: this.horizontalPosition,
      verticalPosition: this.verticalPosition,
    });
  }

  showContrast(message:any,severity:any) {
    this.messageService.add({
      severity: severity,
      summary: 'Notifica',
      detail: message,
      life: 30000
    });
  }

  animationDone(event: any) {
    if (event.toState === "out" && event.phaseName === "done") {
      // Logic for post-animation actions
    }
  }

  // REMOVED: toggleProfileForm() - Profile editing is now in modal

  createOrgChartData(): void {
    const transformToTreeNode = (member: any): any => {
      return {
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
        foto: member.foto || "",
        children: member.children && member.children.length > 0 
          ? member.children.map((child: any) => transformToTreeNode(child)) : []
      };
    };

    const currentUserNode: any = {
      id: this.User.id,
      name: this.User.nome,
      cognome: this.User.cognome,
      email: this.User.email,
      rag_soc: this.User.rag_soc,
      indirizzo: this.User.indirizzo,
      citta: this.User.citta,
      stato: this.User.stato,
      cap: this.User.cap,
      compensopvdiretti: this.User.compensopvdiretti,
      qualifica: this.User.qualifica,
      ruolo: this.User.ruolo,
      pcNecessari: this.User.pcNecessari,
      user_id_padre: 0,
      label: this.User.nome + ' ' + this.User.cognome,
      expanded: true,
      foto: this.urlImageProfile || "",
      children: []
    };

    if (this.Team && this.Team.length > 0) {
      const teamNodes: any[] = this.Team.map(member => transformToTreeNode(member));
      currentUserNode.children = teamNodes;
    }

    this.data = [currentUserNode];
    this.hideTeamMembers = false;
    
    setTimeout(() => {
      this.renderJSChart();
    }, 500);
  }

  private useJSChartingView: boolean = true;

  toggleView(): void {
    this.useJSChartingView = !this.useJSChartingView;
    
    if (this.useJSChartingView) {
      setTimeout(() => {
        this.renderJSChart();
      }, 100);
    }
  }

  getTotalMembersCount(): number {
    if (!this.data || this.data.length === 0) {
      return 0;
    }
    
    const countAllNodes = (nodes: any[]): number => {
      let count = 0;
      for (const node of nodes) {
        count += 1;
        if (node.children && node.children.length > 0) {
          count += countAllNodes(node.children);
        }
      }
      return count;
    };
    
    return countAllNodes(this.data);
  }

  useJSChartingStructure(): boolean {
    return this.useJSChartingView;
  }

  prepareJSChartingData(): any[] {
    if (!this.data || this.data.length === 0) {
      return [];
    }

    const jsChartData: any[] = [];
    
    const transformNodeForJSChart = (node: any, parentId: string = ''): void => {
      const nodeId = node.id.toString();
      
      const jsNode = {
        name: node.name + ' ' + node.cognome,
        id: nodeId,
        parent: parentId || undefined,
        attributes: {
          position: `<span style="font-size:13px;">${node.qualifica}</span>`,
          phone: node.email || 'N/A',
          address: `${node.citta || 'N/A'}, ${node.stato || 'N/A'}`,
          email: node.email || 'N/A',
          photo: node.foto ? this.urlImageProfileUrl + node.foto : 'assets/img/default-avatar.png',
          ruolo: node.ruolo || 'N/A',
          isCurrentUser: node.id === this.User.id
        }
      };
      
      jsChartData.push(jsNode);
      
      if (node.children && node.children.length > 0) {
        node.children.forEach((child: any) => {
          transformNodeForJSChart(child, nodeId);
        });
      }
    };
    
    this.data.forEach(rootNode => {
      transformNodeForJSChart(rootNode);
    });
    
    return jsChartData;
  }

  renderJSChart(): void {
    if (typeof JSC === 'undefined') {
      return;
    }

    const chartData = this.prepareJSChartingData();
    
    if (chartData.length === 0) {
      return;
    }

    if (this.jsChart) {
      this.jsChart.dispose();
    }

    const container = document.getElementById('jsChartingContainer');
    if (!container) {
      return;
    }

    const containerWidth = container.offsetWidth;
    const isMobile = window.innerWidth < 576;
    const isTablet = window.innerWidth >= 576 && window.innerWidth < 768;
    const isDesktop = window.innerWidth >= 1200;
    
    let chartHeight = 400;
    if (isMobile) {
      chartHeight = 300;
    } else if (isTablet) {
      chartHeight = 350;
    } else if (isDesktop) {
      chartHeight = 500;
    } else {
      chartHeight = 450;
    }

    const tooltipConfig = {
      asHTML: true,
      outline: 'none',
      zIndex: 10,
      template: `
        <div class="jsc-tooltip-box" style="
          background: white;
          border: 1px solid #ddd;
          border-radius: 8px;
          padding: ${isMobile ? '10px' : '15px'};
          box-shadow: 0 4px 12px rgba(0,0,0,0.15);
          font-family: 'Segoe UI', sans-serif;
          font-size: ${isMobile ? '12px' : '14px'};
        ">
          <div style="font-weight: bold; margin-bottom: 8px; color: #2c3e50;">📧 Email: <span style="color: #3498db;">%email</span></div>
          <div style="margin-bottom: 6px; color: #34495e;">📍 Indirizzo: <b>%address</b></div>
          <div style="margin-bottom: 6px; color: #34495e;">💼 Ruolo: <b>%ruolo</b></div>
        </div>
      `
    };

    const annotationConfig = {
      padding: isMobile ? 6 : 8,
      asHTML: true,
      margin: isMobile ? [10, 3] : [15, 5],
      label: {
        text: `
          <div style="
            text-align: center;
            background: white;
            border-radius: ${isMobile ? '8px' : '12px'};
            padding: ${isMobile ? '12px' : '20px'};
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            border: 2px solid #3498db;
            min-width: ${isMobile ? '140px' : '180px'};
            max-width: ${isMobile ? '160px' : '220px'};
          ">
            <img 
              width="${isMobile ? '40' : '60'}" 
              height="${isMobile ? '40' : '60'}" 
              src="%photo" 
              style="
                border-radius: 50%;
                margin-bottom: ${isMobile ? '8px' : '12px'};
                border: 3px solid #3498db;
                object-fit: cover;
              "
              onerror="this.src='assets/img/default-avatar.png'"
            />
            <div style="
              font-size: ${isMobile ? '12px' : '14px'};
              font-weight: bold;
              color: #2c3e50;
              margin-bottom: ${isMobile ? '6px' : '8px'};
              white-space: nowrap;
              overflow: hidden;
              text-overflow: ellipsis;
            ">%name</div>
            <div style="
              font-size: ${isMobile ? '10px' : '12px'};
              color: #3498db;
              font-weight: 600;
              background: #ecf0f1;
              padding: ${isMobile ? '3px 6px' : '4px 8px'};
              border-radius: 15px;
              display: inline-block;
              white-space: nowrap;
              overflow: hidden;
              text-overflow: ellipsis;
              max-width: ${isMobile ? '120px' : '160px'};
            ">%position</div>
          </div>
        `,
        autoWrap: false
      }
    };

    this.jsChart = JSC.chart('jsChartingContainer', {
      type: 'organizational down',
      width: '100%',
      height: chartHeight,
      box: {
        fill: 'white'
      },
      defaultTooltip: tooltipConfig,
      defaultPoint: {
        focusGlow: false,
        connectorLine: {
          width: isMobile ? 1 : 2,
          color: '#3498db'
        },
        tooltip: tooltipConfig.template,
        annotation: annotationConfig,
        outline_width: 0,
        color: (point: any) => {
          if (point.attributes.isCurrentUser) {
            return '#3498db';
          }
          const ruolo = point.attributes.ruolo?.toLowerCase() || '';
          if (ruolo.includes('ceo') || ruolo.includes('direttore')) {
            return '#e74c3c';
          } else if (ruolo.includes('manager') || ruolo.includes('responsabile')) {
            return '#2ecc71';
          } else if (ruolo.includes('supervisor') || ruolo.includes('coordinatore')) {
            return '#f39c12';
          } else {
            return '#9b59b6';
          }
        }
      },
      series: [{
        points: chartData
      }]
    });
    
    window.addEventListener('resize', this.handleResize);
    this.cd.detectChanges();
  }
}
