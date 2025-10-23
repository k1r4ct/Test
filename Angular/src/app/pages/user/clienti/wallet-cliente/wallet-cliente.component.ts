import { Component, OnInit, OnDestroy } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';
import { Subscription } from 'rxjs';
import { trigger, style, animate, transition } from '@angular/animations';

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
  animations: [
    trigger('cardAnimation', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateY(20px)' }),
        animate('400ms ease-out', style({ opacity: 1, transform: 'translateY(0)' }))
      ])
    ])
  ],
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
   */
  loadWalletData(): void {
    this.loading = true;
    this.error = '';

    const walletSub = this.apiService.getWallet().subscribe({
      next: (response) => {
        if (response.success && response.data) {
          this.walletData = response.data;
        } else {
          this.error = 'Impossibile caricare i dati del salvadanaio';
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading wallet data:', err);
        this.error = 'Errore nel caricamento dei dati';
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
    if (this.walletData.pv_totali === 0) return 0;
    return Math.round((this.walletData.pv_spesi / (this.walletData.pv_totali + this.walletData.pv_spesi)) * 100);
  }

  /**
   * Get color based on available PV
   */
  getAvailableColor(): string {
    if (this.walletData.pv_disponibili > 1000) return 'success';
    if (this.walletData.pv_disponibili > 300) return 'warning';
    return 'danger';
  }
}