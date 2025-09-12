import { trigger, transition, style, animate } from "@angular/animations";
import {
  AfterViewInit,
  Component,
  DoCheck,
  OnInit,
  ViewChild,
} from "@angular/core";
import { ApiService } from "src/app/servizi/api.service";
import { MatTableDataSource } from "@angular/material/table"; // Importa MatTableDataSource
import { MatPaginator } from "@angular/material/paginator";
import { MatSort } from "@angular/material/sort";
import { MessageService } from 'primeng/api';
interface City {
  name: string;
  code: string;
}

interface Ruolo {
  id: number;
  descrizione: string;
}
interface Qualifiche {
  id: number;
  descrizione: string;
}
interface Macro_product {
  id: number;
  codice_macro:string;
  descrizione: string;
}
export interface Utenti {
  id: number;
  nominativo_ragSoc: string;
  email: string;
  ruolo: string;
  qualifica: string;
  codice: string;
  codf_Piva: string;
}
export interface SEU {
  id: number;
  nominativo: string;
}
export interface DettagliUtente {
  id: string;
  nome:string;
  cognome:string;
  ragione_sociale:string;
  nominativo_ragSoc: string;
  email: string;
  ruolo: string;
  qualifica: string;
  codice: string;
  codf_Piva: string;
  contract_management:{
    id:number;
    codice_macro:string;
    descrizione:string;
  }[];
  seu_riferimento:any;
}
@Component({
    selector: "app-gestione-utenti",
    templateUrl: "./gestione-utenti.component.html",
    styleUrl: "./gestione-utenti.component.scss",
    animations: [
        trigger("pageTransition", [
            transition(":enter", [
                style({ opacity: 0, transform: "scale(0.1)" }), // Inizia piccolo al centro
                animate("500ms ease-in-out", style({ opacity: 1, transform: "scale(1)" })), // Espandi e rendi visibile
            ]),
            transition(":leave", [
                animate("500ms ease-in-out", style({ opacity: 0, transform: "scale(0.1)" })), // Riduci e rendi invisibile
            ]),
        ]),
    ],
    standalone: false
})
export class GestioneUtentiComponent implements OnInit {
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;
  displayedColumns: string[] = [
    "id",
    "nominativo/Rag.Sociale",
    "email",
    "ruolo",
    "qualifica",
    "codice",
    "codice_fiscale/P.iva",
    "azioni",
  ];
  Utenti: Utenti[] = [];
  selectedUtenti: Utenti[] = [];
  selectedIdUtenti: Utenti[] = [];
  dettagliUtente:DettagliUtente[]=[];
  dataSource = new MatTableDataSource<Utenti>();
  state: any;
  showRegistrazione = true;
  cities: City[] = [];
  selectedCities: City[] = [];
  //GESTIONE PRIMENG SELECT
  ruolo: Ruolo[] = [];
  selectedRuolo: Ruolo[] = [];
  selectedRuoloUser: any;
  qualifiche:Qualifiche[]=[];
  selectedQualifiche:any;
  macro_product:Macro_product[]=[];
  macro_productSelected:Macro_product[]=[];
  seu: SEU[] = [];
  seuSelected: any;

  userselezionato=true;
  constructor(private apiService: ApiService,private MessageSystem:MessageService) {
    this.cities = [
      { name: "New York", code: "NY" },
      { name: "Rome", code: "RM" },
      { name: "London", code: "LDN" },
      { name: "Istanbul", code: "IST" },
      { name: "Paris", code: "PRS" },
    ];
  }

  filterRuoli(row: any, filter: string): boolean {
    console.log(filter);

    if (!filter) { // Controlla se filter è una stringa vuota
      return true; // Nessun filtro attivo, mostra tutte le righe
    }

    const filterObj = JSON.parse(filter);
    const ruoliSelezionati = filterObj.ruolo || []; // Inizializza come array vuoto se undefined
    const utentiSelezionati = filterObj.utente || []; // Inizializza come array vuoto se undefined
    const idUtentiSelezionati = filterObj.id || []; // Inizializza come array vuoto se undefined

    const matchRuolo = !ruoliSelezionati.length || ruoliSelezionati.includes(row.ruolo);
    const matchUtente = !utentiSelezionati.length || utentiSelezionati.includes(row.nominativo_ragSoc);
    const matchIdUtente = !idUtentiSelezionati.length || idUtentiSelezionati.includes(row.id);
    console.log(matchUtente);

    return matchRuolo && matchUtente && matchIdUtente;
  }

  ngOnInit(): void {
    this.apiService.getAllUser().subscribe((Us: any) => {
      //console.log(Us);
      this.Utenti = Us.body.risposta.map((Utente: any) => ({
        id: Utente.id,
        nome:Utente.name?Utente.name:"---",
        cognome:Utente.cognome?Utente.cognome:"---",
        nominativo_ragSoc:
          Utente.name && Utente.cognome
            ? Utente.name + " " + Utente.cognome
            : Utente.ragione_sociale,
        email: Utente.email,
        ruolo: Utente.role.descrizione,
        qualifica: Utente.qualification.descrizione,
        codice: Utente.codice,
        codf_Piva: Utente.codice_fiscale
          ? Utente.codice_fiscale
          : Utente.partita_iva,
        contract_management:Utente.contract_management.map((CM:any)=>({
          id:CM.macro_product_id,
          codice_macro:CM.codice_macro,
          descrizione:CM.descrizione
        }))
      }));
      //console.log(this.Utenti);

      this.apiService.richiestaRuolieQualifiche().subscribe((Risposta:any)=>{
        this.qualifiche=Risposta.qualifiche.map((Qualifiche:any)=>({
          id:Qualifiche.id,
          descrizione:Qualifiche.descrizione,
        }))
        this.ruolo = Risposta.ruoli.map((Ruolo: any) => ({
          id: Ruolo.id,
          descrizione: Ruolo.descrizione,
        }));
      })
      //console.log(this.selectedUtenti);
      this.dataSource.data = this.Utenti;
    });
  }

  applyFilter() {
    this.userselezionato=true;
    const filterValue = {
      ruolo: this.selectedRuolo.map(ruolo => ruolo.descrizione),
      utente: this.selectedUtenti.map(utente => utente.nominativo_ragSoc),
      id: this.selectedIdUtenti.map(id=>id.id)
    };
    this.dataSource.filterPredicate = this.filterRuoli;
    this.dataSource.filter = JSON.stringify(filterValue);
    console.log(this.dataSource.filter);

  }
  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
    this.dataSource.sort = this.sort;
  }
  nuovoUtente() {
    this.showRegistrazione = false;
  }

  editUser(user: any) {
    //console.log(user.id);
  this.userselezionato=false;
    this.apiService.dettagliUtente(user.id).subscribe((Utente:any)=>{
      //console.log(Utente);

      this.dettagliUtente = Utente.body.risposta.map((Ut: any) => ({
        id: Ut.id,
        nome:Ut.name?Ut.name:"---",
        cognome:Ut.cognome?Ut.cognome:"---",
        nominativo_ragSoc:Ut.ragione_sociale?Ut.ragione_sociale:"---",
        email: Ut.email,
        ruolo: Ut.role.descrizione,
        qualifica: Ut.qualification.descrizione,
        codice: Ut.codice,
        codf_Piva: Ut.codice_fiscale
          ? Ut.codice_fiscale
          : Ut.partita_iva,
        contract_management:Ut.contract_management.map((CM:any)=>({
          id:CM.macro_product_id,
          codice_macro:CM.codice_macro,
          descrizione:CM.descrizione
        })),
        seu_riferimento:Ut.user_id_padre,
      }));

      this.macro_product=Utente.body.macro_product.map((macro_p:any)=>({
        id:macro_p.id,
        codice_macro:macro_p.codice_macro,
        descrizione:macro_p.descrizione,
      }))
      //console.log(this.macro_product);

      this.selectedRuoloUser = this.ruolo.find(r => r.descrizione === this.dettagliUtente[0].ruolo)
      this.selectedQualifiche = this.qualifiche.find(r => r.descrizione === this.dettagliUtente[0].qualifica)
      if (this.dettagliUtente && this.dettagliUtente.length > 0 && this.dettagliUtente[0].contract_management) {
        this.macro_productSelected = this.macro_product.filter(r =>
          this.dettagliUtente[0].contract_management.some(cm => cm.id === r.id)
        );
      }
      //console.log(this.dettagliUtente);

    })
    this.apiService.recuperaSEU().subscribe((SEU: any) => {
      //console.log(SEU);

      this.seu = SEU.body.risposta.map((allSeu: any) => {
        //console.log(allSeu);
        return {
          id: allSeu.id,
          nominativo: allSeu.name + " " + allSeu.cognome,
        };
      });
      this.seuSelected = this.seu.find(
        (r) => r.id === this.dettagliUtente[0].seu_riferimento
      );
    });
  }

  SalvaModificheUtente(user:any){
    const id = document.querySelector(
      '.id'
    ) as HTMLSelectElement; // Trova l'elemento select
    const idUtente = id?.value;

    const nome = document.querySelector(
      '.nome'
    ) as HTMLSelectElement; // Trova l'elemento select
    const nomeutente = nome?.value;

    const cognome = document.querySelector(
      '.cognome'
    ) as HTMLSelectElement; // Trova l'elemento select
    const cognomeUtente = cognome?.value;

    const rag_soc = document.querySelector(
      '.rag_soc'
    ) as HTMLSelectElement; // Trova l'elemento select
    const ragione_soc = rag_soc?.value;

    const email = document.querySelector(
      '.email'
    ) as HTMLSelectElement; // Trova l'elemento select
    const emailUtente = email?.value;

    const cod_utente = document.querySelector(
      '.cod_utente'
    ) as HTMLSelectElement; // Trova l'elemento select
    const cod_Utente = cod_utente?.value;

    const cod_fPiva = document.querySelector(
      '.cod_fPiva'
    ) as HTMLSelectElement; // Trova l'elemento select
    const cod_fPivaUtente = cod_fPiva?.value;

    const resetp = document.querySelector(
      '.resetpwd'
    ) as HTMLSelectElement; // Trova l'elemento select
    const resetpwd = resetp?.value;

     const formData = new FormData();

    formData.append('idUtente',idUtente)
    formData.append('nomeutente',nomeutente)
    formData.append('cognomeUtente',cognomeUtente)
    formData.append('ragione_soc',ragione_soc)
    formData.append('emailUtente',emailUtente)
    formData.append('ruolo',this.selectedRuoloUser.id)
    formData.append('qualifica',this.selectedQualifiche.id)
    formData.append('seu',this.seuSelected.id)
    formData.append('cod_Utente',cod_Utente)
    formData.append('cod_fPivaUtente',cod_fPivaUtente)
    formData.append('resetpwd',resetpwd)
    this.macro_productSelected.forEach((product, index) => {
      formData.append(`contract_management[${index}][id]`, product.id.toString()); // Aggiungi l'ID come chiave
      formData.append(`contract_management[${index}][descrizione]`, product.descrizione); // Aggiungi il valore come valore
    });

    this.apiService.updateUtente(formData).subscribe((Risp:any)=>{
      //console.log(Risp);
      this.showMessage();
    })
  }
  showMessage(){
    this.MessageSystem.add({
      severity: 'success',
      summary: 'Modifica Utente',
      detail: 'Utente Modificato',
      life: 3000
    });
  }

  resetpwd(obj: any){
    let str = document.getElementById(obj)?.getAttribute('value');
    let status: string;
    status = (str == "true") ?  "false" : "true";
    document.getElementById(obj)?.setAttribute('value', status);
  }


}
