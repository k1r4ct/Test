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

  // Metodo per emettere i dati filtrati per data
  emitFilteredData(
    selectedDate: Date,
    filteredContratti: any[],
    filteredLeads?: any[]
  ) {
    const currentData = this.combinedDataSubject.value || {};

    const updatedData = {
      ...currentData,
      selectedDate: selectedDate,
      filteredContratti: filteredContratti,
      filteredLeads: filteredLeads,
      isFiltered: true,
      filterType: "date",
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
      filterType: null,
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

    return this.http
      .post(
        this.global.API_URL + "getDomandeMacro/" + idMacroProdotto,
        { domande: domande },
        { headers }
      )
      .pipe(takeUntil(this.destroy$));
  }

  getRisposteSelect(idDomanda: any, risposta: any): Observable<any> {
    let headers = this.headers;

    return this.http
      .post(
        this.global.API_URL + "getRisposteSelect/" + idDomanda,
        { rispostafornita: risposta },
        { headers }
      )
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

    return this.http
      .post(this.global.API_URL + "getContratti" + id, params, { headers })
      .pipe(
        tap((response: any) => {
          if (response && response.body && response.body.pagination) {
            // Pagination data available
          } else {
            // No pagination data
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

    return this.http
      .post(this.global.API_URL + "searchContratti/" + userId, params, {
        headers,
      })
      .pipe(
        tap((response: any) => {
          if (response && response.body && response.body.pagination) {
            // Pagination data available
          } else {
            // No pagination data
          }
        }),
        takeUntil(this.destroy$)
      );
  }

  updateContratto(form: any): Observable<any> {
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
    return this.http.get<any[]>(`${this.global.API_URL}getFiles${contractId}`);
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
    return this.http
      .post(this.global.API_URL + "updateProdotto", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  updateMacroProdotto(form: any): Observable<any> {
    let headers = this.headers;
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

  creaNuovoMacroProdotto(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "creaNuovoMacroProdotto", form, { headers })
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
   * @param filters - Optional filters including category (supports comma-separated for multiselect)
   */
  getTickets(filters: { category?: string } = {}): Observable<any> {
    let headers = this.headers;
    let params = new HttpParams();
    
    // Category filter (supports multiselect: 'ordinary,extraordinary')
    if (filters.category && filters.category !== 'all') {
      params = params.set('category', filters.category);
    }
    
    return this.http
      .get(this.global.API_URL + "getTickets", { headers, params })
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
   * Cambia la categoria del ticket (ordinary, extraordinary)
   * Solo admin o backoffice assegnato possono cambiare
   */
  updateTicketCategory(form: { ticket_id: number; category: 'ordinary' | 'extraordinary' }): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "updateTicketCategory", form, { headers })
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
      .get(this.global.API_URL + "getTicketByContractId/" + contractId, {
        headers,
      })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get all tickets for a specific contract (admin only)
   */
  getAllTicketsByContractId(contractId: number): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + "getAllTicketsByContractId/" + contractId, {
        headers,
      })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Delete active ticket for a contract (admin only)
   */
  deleteTicketByContractId(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "deleteTicketByContractId", form, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Restore last closed/deleted ticket for a contract (admin only)
   */
  restoreLastTicketByContractId(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "restoreLastTicketByContractId", form, {
        headers,
      })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Restore a specific ticket (admin only)
   */
  restoreTicket(form: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + "restoreTicket", form, { headers })
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
      .get(
        this.global.API_URL +
          `user/wallet/history?page=${page}&per_page=${perPage}`,
        { headers }
      )
      .pipe(takeUntil(this.destroy$));
  }

  // -------------------- TICKET ATTACHMENTS API --------------------

  /**
   * Upload attachments for a ticket
   * Supports multiple files up to 10MB each
   */
  uploadTicketAttachments(formData: FormData): Observable<any> {
    let headers = this.getAuthHeaders();
    // Remove Content-Type to let browser set it with boundary for multipart/form-data
    headers = headers.delete("Content-Type");

    return this.http
      .post(this.global.API_URL + "tickets/attachments/upload", formData, {
        headers,
      })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get all attachments for a specific ticket
   */
  getTicketAttachments(ticketId: number): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + `tickets/${ticketId}/attachments`, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Download a specific attachment
   * Returns blob for file download
   */
  downloadTicketAttachment(attachmentId: number): Observable<Blob> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + `attachments/${attachmentId}/download`, {
        headers,
        responseType: "blob",
      })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Delete a specific attachment
   * Only admin or uploader can delete
   */
  deleteTicketAttachment(attachmentId: number): Observable<any> {
    let headers = this.headers;
    return this.http
      .delete(this.global.API_URL + `attachments/${attachmentId}`, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  // -------------------- SYSTEM LOGS API --------------------

  /**
   * Get paginated logs with filters including audit trail and device tracking fields
   * @param filters - Optional filters (source, level, search, date_from, date_to, entity_type, contract_id, device tracking, etc.)
   */
  getLogs(filters: {
    source?: string;
    level?: string;
    search?: string;
    user_id?: number;
    date_from?: string;
    date_to?: string;
    per_page?: number;
    page?: number;
    sort_by?: string;
    sort_dir?: 'asc' | 'desc';
    // Audit trail filters
    entity_type?: string;
    entity_id?: number;
    contract_id?: number;
    contract_code?: string;
    with_changes?: boolean;
    with_entity_tracking?: boolean;
    // Device tracking filters
    device_fingerprint?: string;
    geo_country?: string;
    geo_city?: string;
    geo_isp?: string;
    device_type?: string;
    device_browser?: string;
    device_os?: string;
    screen_resolution?: string;
    timezone?: string;
  } = {}): Observable<any> {
    let headers = this.headers;
    let params = new HttpParams();
    
    // Basic filters
    if (filters.source) params = params.set('source', filters.source);
    if (filters.level) params = params.set('level', filters.level);
    if (filters.search) params = params.set('search', filters.search);
    if (filters.user_id) params = params.set('user_id', filters.user_id.toString());
    if (filters.date_from) params = params.set('date_from', filters.date_from);
    if (filters.date_to) params = params.set('date_to', filters.date_to);
    if (filters.per_page) params = params.set('per_page', filters.per_page.toString());
    if (filters.page) params = params.set('page', filters.page.toString());
    if (filters.sort_by) params = params.set('sort_by', filters.sort_by);
    if (filters.sort_dir) params = params.set('sort_dir', filters.sort_dir);
    
    // Audit trail filters
    if (filters.entity_type) params = params.set('entity_type', filters.entity_type);
    if (filters.entity_id) params = params.set('entity_id', filters.entity_id.toString());
    if (filters.contract_id) params = params.set('contract_id', filters.contract_id.toString());
    if (filters.contract_code) params = params.set('contract_code', filters.contract_code);
    if (filters.with_changes !== undefined) params = params.set('with_changes', filters.with_changes ? '1' : '0');
    if (filters.with_entity_tracking !== undefined) params = params.set('with_entity_tracking', filters.with_entity_tracking ? '1' : '0');
    
    // Device tracking filters - FIXED: All filters now included
    if (filters.device_fingerprint) params = params.set('device_fingerprint', filters.device_fingerprint);
    if (filters.geo_country) params = params.set('geo_country', filters.geo_country);
    if (filters.geo_city) params = params.set('geo_city', filters.geo_city);
    if (filters.geo_isp) params = params.set('geo_isp', filters.geo_isp);
    if (filters.device_type) params = params.set('device_type', filters.device_type);
    if (filters.device_browser) params = params.set('device_browser', filters.device_browser);
    if (filters.device_os) params = params.set('device_os', filters.device_os);
    if (filters.screen_resolution) params = params.set('screen_resolution', filters.screen_resolution);
    if (filters.timezone) params = params.set('timezone', filters.timezone);

    return this.http
      .get(this.global.API_URL + 'logs', { headers, params })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get single log details
   */
  getLog(id: number): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + `logs/${id}`, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get log statistics for dashboard
   */
  getLogStats(source?: string): Observable<any> {
    let headers = this.headers;
    let params = new HttpParams();
    if (source) params = params.set('source', source);

    return this.http
      .get(this.global.API_URL + 'logs/stats', { headers, params })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get log volume data for chart (last 24h by default)
   */
  getLogVolume(source?: string, hours: number = 24): Observable<any> {
    let headers = this.headers;
    let params = new HttpParams().set('hours', hours.toString());
    if (source) params = params.set('source', source);

    return this.http
      .get(this.global.API_URL + 'logs/volume', { headers, params })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get available log sources with counts
   */
  getLogSources(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + 'logs/sources', { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get available filters for log dropdown menus
   * Returns: entity_types, sources, levels, users, device tracking options with counts
   */
  getLogFilters(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + 'logs/filters', { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get complete audit history for a specific contract
   * @param contractId - Contract ID
   * @param limit - Maximum results (default 100, max 500)
   */
  getContractHistory(contractId: number, limit: number = 100): Observable<any> {
    let headers = this.headers;
    let params = new HttpParams().set('limit', limit.toString());
    
    return this.http
      .get(this.global.API_URL + `logs/contract/${contractId}`, { headers, params })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get log files list
   */
  getLogFiles(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + 'logs/files', { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get log file content
   */
  getLogFileContent(source: string = 'all', fromDb: boolean = true, limit: number = 500): Observable<any> {
    let headers = this.headers;
    let params = new HttpParams()
      .set('source', source)
      .set('from_db', fromDb.toString())
      .set('limit', limit.toString());

    return this.http
      .get(this.global.API_URL + 'logs/file', { headers, params })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Export logs in various formats with audit trail filters
   */
  exportLogs(format: 'csv' | 'json' | 'txt', filters: {
    source?: string;
    level?: string;
    search?: string;
    date_from?: string;
    date_to?: string;
    // Audit trail filters
    entity_type?: string;
    contract_id?: number;
  } = {}): Observable<Blob> {
    let headers = this.headers;
    let params = new HttpParams().set('format', format);
    
    if (filters.source) params = params.set('source', filters.source);
    if (filters.level) params = params.set('level', filters.level);
    if (filters.search) params = params.set('search', filters.search);
    if (filters.date_from) params = params.set('date_from', filters.date_from);
    if (filters.date_to) params = params.set('date_to', filters.date_to);
    // Audit trail filters
    if (filters.entity_type) params = params.set('entity_type', filters.entity_type);
    if (filters.contract_id) params = params.set('contract_id', filters.contract_id.toString());

    return this.http
      .get(this.global.API_URL + 'logs/export', { 
        headers, 
        params, 
        responseType: 'blob' 
      })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Delete a single log entry (Admin only)
   */
  deleteLog(id: number): Observable<any> {
    let headers = this.headers;
    return this.http
      .delete(this.global.API_URL + `logs/${id}`, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Clear logs by source (Admin only)
   */
  clearLogs(source?: string): Observable<any> {
    let headers = this.headers;
    let params = new HttpParams();
    if (source) params = params.set('source', source);

    return this.http
      .delete(this.global.API_URL + 'logs/clear', { headers, params })
      .pipe(takeUntil(this.destroy$));
  }

  // -------------------- LOG SETTINGS API --------------------

  /**
   * Get all log settings grouped by category
   */
  getLogSettings(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + 'log-settings', { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get a single log setting
   */
  getLogSetting(key: string): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + `log-settings/${key}`, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Update a single log setting
   */
  updateLogSetting(key: string, value: any): Observable<any> {
    let headers = this.headers;
    return this.http
      .put(this.global.API_URL + `log-settings/${key}`, { value }, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Bulk update multiple log settings
   */
  bulkUpdateLogSettings(settings: { key: string; value: any }[]): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + 'log-settings/bulk-update', { settings }, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Reset all log settings to defaults (Admin only)
   */
  resetLogSettings(): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + 'log-settings/reset', {}, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Run manual cleanup (Admin only)
   */
  runLogCleanup(): Observable<any> {
    let headers = this.headers;
    return this.http
      .post(this.global.API_URL + 'log-settings/run-cleanup', {}, { headers })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get cleanup statistics
   */
  getLogCleanupStats(): Observable<any> {
    let headers = this.headers;
    return this.http
      .get(this.global.API_URL + 'log-settings/cleanup-stats', { headers })
      .pipe(takeUntil(this.destroy$));
  }
}