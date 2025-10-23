import { HttpClient, HttpHeaders, HttpParams } from "@angular/common/http";
import { Injectable, OnDestroy } from "@angular/core";
import { AuthService } from "./auth.service";
import { Router } from "@angular/router";
import {
  BehaviorSubject,
  Observable,
  Subject,
  takeUntil,
  tap,
  forkJoin,
} from "rxjs";
import { environment } from "src/environments/environment";

@Injectable({
  providedIn: "root",
})
export class ApiService implements OnDestroy {
  private dataSubject = new BehaviorSubject<any>(null);
  private leadsSubject = new BehaviorSubject<any>(null);
  private countLeads = new BehaviorSubject<any>({
    leads: null,
    countContratti: 0,
  });
  public leads$ = this.leadsSubject.asObservable();
  private headers;
  public utente: any;
  private destroy$ = new Subject<void>(); // Subject per gestire la disiscrizione
  private global = { API_URL: "", passwdUrl: "" };
  private combinedDataSubject = new BehaviorSubject<any>(null);
  public combinedData$ = this.combinedDataSubject.asObservable();

  constructor(
    private http: HttpClient,
    private AuthService: AuthService,
    private router: Router
  ) {
    this.headers = new HttpHeaders({
      Authorization: "Bearer " + this.AuthService.getToken(),
    });
    this.global.API_URL = environment.apiUrl;
    this.global.passwdUrl = environment.passwdUrl;
  }
  emitCombinedData(data: any) {
    if (data) {
      const currentData = this.combinedDataSubject.value || {};
      this.combinedDataSubject.next({ ...currentData, ...data });
    } else {
      console.warn("Dati non validi passati a emitCombinedData:", data);
    }
  }

  // Nuovo metodo per emettere i dati filtrati per data
  emitFilteredData(selectedDate: Date, filteredContratti: any[], filteredLeads?: any[]) {
    const currentData = this.combinedDataSubject.value || {};
    
    const updatedData = {
      ...currentData,
      selectedDate: selectedDate,
      filteredContratti: filteredContratti,
      filteredLeads: filteredLeads,
      isFiltered: true,
      filterType: 'date'
    };

    this.combinedDataSubject.next(updatedData);
  }

  // Metodo per resettare i filtri
  resetFilters() {
    const currentData = this.combinedDataSubject.value || {};
    const resetData = {
      ...currentData,
      selectedDate: null,
      filteredContratti: null,
      filteredLeads: null,
      isFiltered: false,
      filterType: null
    };

    this.combinedDataSubject.next(resetData);
  }
  getCombinedData(userId: any): Observable<any> {
    return forkJoin({
      leads: this.getLeads(),
      contratti: this.getContratti(userId),
    }).pipe(
      tap((combinedData) => {
        const currentData = this.combinedDataSubject.value || {};
        this.combinedDataSubject.next({
          ...currentData,
          leads: combinedData.leads,
          countContratti: combinedData.contratti.body.risposta.data.length,
          contrattiUtente: combinedData.contratti.body.risposta.data,
        });
      })
    );
  }

  resetPwd(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.passwdUrl + "forgot-password", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  storeLeadExternal(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.passwdUrl + "storeLeadExternal", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getApiUrl(): string {
    return this.global.API_URL;
  }

  getAuthHeaders(): HttpHeaders {
    const token = localStorage.getItem("jwt");
    if (token) {
      return new HttpHeaders({
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      });
    } else {
      return new HttpHeaders({ Accept: "application/json" });
    }
  }

  getData() {
    return this.dataSubject.asObservable();
  }

  LeggiQualifiche(): Observable<any> {
    return this.http
      .get(this.global.API_URL + "ruolo")
      .pipe(takeUntil(this.destroy$));
  }

  LeggiQualificheAuth(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "ruoli", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  LeggiMacroCategorie(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "macroCat", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  public refreshToken(token: any): Observable<any> {
    let headers = this.headers;
    return this.http.post(this.global.API_URL + "refresh", token, { headers });
  }

  getDomandeMacro(idMacroProdotto: any, domande: any): Observable<any> {
    let headers = this.headers;

    // console.log(' detro chiamata api getDomandeMacro ');
    // console.log(' macroprodotto: ' + idMacroProdotto);
    // console.log(' domande da escludere: ' + domande);
    // console.log(domande);

    return this.http
      .post(this.global.API_URL + 'getDomandeMacro/' + idMacroProdotto,  { domande: domande}, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getRisposteSelect(idDomanda: any, risposta: any): Observable<any> { 
    let headers = this.headers;

    // console.log("-------------------------------- ger risp select");
    // console.log(" idDomanda: " + idDomanda);
    // console.log(" risposta: " + risposta);       

    return this.http
     .post(this.global.API_URL + 'getRisposteSelect/' + idDomanda, { rispostafornita: risposta }, { headers })
     .pipe(takeUntil(this.destroy$));    
  }
  //  LISTA DELLE CHIAMATE API PER DEI VARI COMPONENTI

  ListaProdotti(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "prodotti", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  PrendiUtente(): Observable<any> {
    const headers = this.getAuthHeaders();
    return this.http
      .get(this.global.API_URL + "me?cacheBust=" + Date.now(), { headers })
      .pipe(takeUntil(this.destroy$));
  }

  ContrattiPersonali(id: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "contrattiPersonali" + id, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  codFiscale_PartitaIva(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "codFPIva", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  nuovoUtente(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "nuovoCliente", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  nuovoClienteLead(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "nuovoClienteLead", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  richiestaRuolieQualifiche(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "ruoliequalifiche", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  copiaUtente(id: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "copiautente" + id, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  nuovoContraente(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "nuovoContraente", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getProdotto(id: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getProdotto" + id, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getContractType(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "contractType", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getDomande(id: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getDomande" + id, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getListaDomande() {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getListaDomande", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  storeContratto(form: any): Observable<any> {
    //console.log(form);
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "nuovoContratto", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  storeIMG(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "storeIMG", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  salvaDomande(form: any): Observable<any> {
    //console.log(form);
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "salvaDomande", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getContratti(
    id: any,
    page: number = 1,
    perPage: number = 50
  ): Observable<any> {
    let headers = this.headers;
    const params = {
      page: page.toString(),
      per_page: perPage.toString(),
    };

    //console.log(`API - Richiesta contratti: page=${page}, per_page=${perPage}`);

    return this.http
      .post(this.global.API_URL + "getContratti" + id, params, { headers })
      .pipe(
        tap((response: any) => {
          //console.log('API - Risposta contratti ricevuta:', response);

          // Verifica struttura dati della risposta
          if (response && response.body && response.body.pagination) {
            //console.log('API - Dati paginazione:', response.body.pagination);
          } else {
            //console.warn('API - Nessun dato di paginazione nella risposta');
          }
        }),
        takeUntil(this.destroy$)
      );
  }

  /**
   * Cerca contratti con filtri lato server
   * @param id ID utente
   * @param filters Oggetto con i filtri da applicare
   * @param page Pagina da recuperare (opzionale, default 1)
   * @param perPage Numero di elementi per pagina (opzionale, default 50)
   * @param sortField Campo per l'ordinamento (opzionale, default 'id')
   * @param sortDirection Direzione ordinamento (opzionale, default 'asc')
   */
  searchContratti(
    userId: any,
    filters: any,
    page: number = 1,
    perPage: number = 50,
    sortField: string = "id",
    sortDirection: string = "asc"
  ): Observable<any> {
    let headers = this.headers;
    const params = {
      user_id: userId,
      page: page.toString(),
      per_page: perPage.toString(),
      filters: filters,
      sort_field: sortField,
      sort_direction: sortDirection,
    };

    //console.log(`API - Ricerca contratti: userId=${userId}, page=${page}, per_page=${perPage}, sort=${sortField}:${sortDirection}, filters=`, filters);

    return this.http
      .post(this.global.API_URL + "searchContratti/" + userId, params, {
        headers,
      })
      .pipe(
        tap((response: any) => {
          //console.log('API - Risposta ricerca contratti ricevuta:', response);

          // Verifica struttura dati della risposta
          if (response && response.body && response.body.pagination) {
            //console.log('API - Dati paginazione ricerca:', response.body.pagination);
          } else {
            //console.warn('API - Nessun dato di paginazione nella risposta di ricerca');
          }
        }),
        takeUntil(this.destroy$)
      );
  }

  updateContratto(form: any): Observable<any> {
    //console.log(form);
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "updateContratto", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  ngOnDestroy() {
    this.destroy$.next();
    this.destroy$.complete();
  }

  getContratto(id: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "getContratto" + id, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  deleteQuestion(id: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "deleteQuestion" + id, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  deleteIMG(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "deleteIMG", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getPagamentoSystem(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getPagamentoSystem", { headers })
      .pipe(takeUntil(this.destroy$));
  }
  uploadProfileImage(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "immagineProfiloUtente", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getStatiAvanzamento(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getStatiAvanzamento", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getFilesForContract(contractId: number): Observable<any[]> {
    return this.http.get<any[]>(`${this.global.API_URL}getFiles${contractId}`); // O l'endpoint corretto
  }

  getContCodFPIva(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "getContCodFPIva", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  nuovoProdotto(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "nuovoProdotto", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getMacroProduct(id: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "getMacroProduct" + id, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  allMacroProduct(id: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "allMacroProduct" + id, { headers })
      .pipe(takeUntil(this.destroy$));
  }
  GetallMacroProduct(): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "GetallMacroProduct", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  storeNewProduct(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "storeNewProduct", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  storeNewLead(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "storeNewLead", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getLeads(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getLeads", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  controlloProdottoNeiContratti(id: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "controlloProdottoNeiContratti" + id, {
        headers,
      })
      .pipe(takeUntil(this.destroy$));
  }

  disabilitaProdotto(id: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "disabilitaProdotto" + id, { headers })
      .pipe(takeUntil(this.destroy$));
  }
  abilitaProdotto(id: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "abilitaProdotto" + id, { headers })
      .pipe(takeUntil(this.destroy$));
  }
  cancellaProdotto(id: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "cancellaProdotto" + id, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  updateProdotto(form: any): Observable<any> {
    let headers = this.headers;
    //console.log(" update prodotto ");
    //console.log(form);

    return this.http
      .post(this.global.API_URL + "updateProdotto", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  updateMacroProdotto(form: any): Observable<any> {
    let headers = this.headers;
    //console.log(form);
    return this.http
      .post(this.global.API_URL + "updateMacroProdotto", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getAppointments(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getAppointments", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  updateLead(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "updateLead", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getStatiLeads(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getStatiLeads", { headers })
      .pipe(takeUntil(this.destroy$));
  }
  appuntamentoLead(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "appuntamentoLead", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getUserForLeads(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getUserForLeads", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  updateAssegnazioneLead(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "updateAssegnazioneLead", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getLeadsDayClicked(array: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "getLeadsDayClicked", array, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /* getColorRowStatusLead(id: any): Observable<any> {
    let headers = this.headers;
    return this.http.get(this.global.API_URL + 'getColorRowStatusLead' + id, { headers }).pipe(
      takeUntil(this.destroy$)
    );
  } */
  getMessageNotification(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getMessageNotification", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  markReadMessage(id: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "markReadMessage" + id, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getAllUser(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getAllUser", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  recuperaSEU(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "recuperaSEU", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  updatePassw(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "updatePassw", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  dettagliUtente(id: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "dettagliUtente" + id, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  updateUtente(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "updateUtente", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  updateStatoMassivoContratti(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "updateStatoMassivoContratti", form, {
        headers,
      })
      .pipe(takeUntil(this.destroy$));
  }

  recuperaCategorieFornitori(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "recuperaCategorieFornitori", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  nuovoFornitore(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "nuovoFornitore", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getSupplier(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getSupplier", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getMacroStato(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getMacroStato", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  getStato(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getStato", { headers })
      .pipe(takeUntil(this.destroy$));
  }

// -------------------- TICKET MANAGEMENT API --------------------
  
/**
   * Recupera la lista dei ticket
   */
  getTickets(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getTickets", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Crea un nuovo ticket
   */
  createTicket(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "createTicket", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Recupera i messaggi di un ticket
   */
  getTicketMessages(ticketId: number): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getTicketMessages/" + ticketId, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Invia un messaggio per un ticket
   */
  sendTicketMessage(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "sendTicketMessage", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Aggiorna lo stato di un ticket (drag & drop)
   */
  updateTicketStatus(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "updateTicketStatus", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Chiude un ticket (resolved → closed)
   * Solo admin o backofficer assegnato possono chiudere
   */
  closeTicket(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "closeTicket", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Cancella multipli ticket in una volta (bulk delete)
   * Solo admin può eseguire questa operazione
   */
  bulkDeleteTickets(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "bulkDeleteTickets", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Cambia la priorità dei ticket(low, medium, high, unassigned)
   */
  updateTicketPriority(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "updateTicketPriority", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Recupera i log delle modifiche di stato e priorità di un ticket
   */
  getTicketChangeLogs(ticketId: number): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getTicketChangeLogs/" + ticketId, { headers })
      .pipe(takeUntil(this.destroy$));
  } 

  /**
  * Verifica se esiste un ticket per un contratto specifico
  * Restituisce il ticket se esiste, altrimenti null
  */
  getTicketByContractId(contractId: number): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getTicketByContractId/" + contractId, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  // -------------------- WALLET API METHODS --------------------
  
  /**
   * Get user wallet information
   */
  getWallet(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "user/wallet", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get wallet summary with active cart
   */
  getWalletSummary(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "user/wallet/summary", { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get wallet transaction history (orders)
   * @param page - Page number for pagination
   * @param perPage - Items per page (default: 10)
   */
  getWalletHistory(page: number = 1, perPage: number = 10): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + `user/wallet/history?page=${page}&per_page=${perPage}`, { headers })
      .pipe(takeUntil(this.destroy$));
  }

}