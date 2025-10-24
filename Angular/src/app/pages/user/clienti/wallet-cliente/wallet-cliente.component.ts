import { Component, OnInit, OnDestroy } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';
import { Subscription } from 'rxjs';

export interface WalletData {
  pv_maturati: number;
  pv_bonus: number;
  pv_totali: number;
  pv_bloccati: number;
  pv_disponibili: number;
  pv_spesi: number;
}

@Component({
  selector: 'app-wallet-cliente',
  templateUrl: './wallet-cliente.component.html',
  styleUrls: ['./wallet-cliente.component.scss'],
  standalone: false
})
export class WalletClienteComponent implements OnInit, OnDestroy {
  
  walletData: WalletData = {
    pv_maturati: 0,
    pv_bonus: 0,
    pv_totali: 0,
    pv_bloccati: 0,
    pv_disponibili: 0,
    pv_spesi: 0
  };

  loading: boolean = true;
  error: string = '';
  
  private subscriptions: Subscription[] = [];

  constructor(private apiService: ApiService) {}

  ngOnInit(): void {
    this.loadWalletData();
  }

  ngOnDestroy(): void {
    this.subscriptions.forEach(sub => sub.unsubscribe());
  }

  /**
   * Load wallet data from API
   * Expects response in format: { response: "ok", status: "200", body: { risposta: {...} } }
   */
  loadWalletData(): void {
    this.loading = true;
    this.error = '';

    const walletSub = this.apiService.getWallet().subscribe({
      next: (response) => {
        // Handle response following TicketController pattern
        if (response && response.response === 'ok') {
          // Extract data from body.risposta
          if (response.body && response.body.risposta) {
            const data = response.body.risposta;
            
            this.walletData = {
              pv_maturati: Number(data.pv_maturati) || 0,
              pv_bonus: Number(data.pv_bonus) || 0,
              pv_totali: Number(data.pv_totali) || 0,
              pv_bloccati: Number(data.pv_bloccati) || 0,
              pv_disponibili: Number(data.pv_disponibili) || 0,
              pv_spesi: Number(data.pv_spesi) || 0
            };
          } else {
            this.error = 'Formato risposta non valido';
          }
        } else if (response && response.response === 'error') {
          // Handle error response
          this.error = response.message || 'Errore nel caricamento del salvadanaio';
        } else {
          this.error = 'Risposta non valida dal server';
        }
        
        this.loading = false;
      },
      error: (err) => {
        // Handle HTTP errors
        if (err.status === 401) {
          this.error = 'Non autorizzato. Effettua nuovamente il login.';
        } else if (err.status === 403) {
          this.error = 'Accesso negato. Il salvadanaio è disponibile solo per i clienti.';
        } else if (err.status === 404) {
          this.error = 'Endpoint non trovato. Contatta l\'amministratore.';
        } else if (err.error && err.error.message) {
          this.error = err.error.message;
        } else {
          this.error = 'Errore nel caricamento dei dati';
        }
        
        this.loading = false;
      }
    });

    this.subscriptions.push(walletSub);
  }

  /**
   * Refresh wallet data
   */
  refreshWallet(): void {
    this.loadWalletData();
  }

  /**
   * Calculate percentage of PV used
   */
  getUsagePercentage(): number {
    const total = this.walletData.pv_totali + this.walletData.pv_spesi;
    if (total === 0) return 0;
    return Math.min(100, Math.round((this.walletData.pv_spesi / total) * 100));
  }

  /**
   * Calculate percentage of available PV
   */
  getAvailablePercentage(): number {
    const total = this.walletData.pv_totali;
    if (total === 0) return 0;
    return Math.min(100, Math.round((this.walletData.pv_disponibili / total) * 100));
  }

  /**
   * Get color based on available PV
   */
  getAvailableColor(): string {
    if (this.walletData.pv_disponibili > 1000) return 'success';
    if (this.walletData.pv_disponibili > 300) return 'warning';
    return 'danger';
  }

  /**
   * Get formatted number for display
   */
  formatNumber(value: number): string {
    return value.toLocaleString('it-IT');
  }

  /**
   * Check if user has any points
   */
  hasPoints(): boolean {
    return this.walletData.pv_totali > 0 || this.walletData.pv_spesi > 0;
  }

  /**
   * Check if user has available points to spend
   */
  canSpendPoints(): boolean {
    return this.walletData.pv_disponibili > 0;
  }
}