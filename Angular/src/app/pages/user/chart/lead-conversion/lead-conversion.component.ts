import { Component, OnInit, computed, signal, inject, DestroyRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ChartModule } from 'primeng/chart';
import { ApiService } from 'src/app/servizi/api.service';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

interface ChartData {
  labels: string[];
  datasets: {
    data: number[];
    backgroundColor: string[];
    hoverBackgroundColor: string[];
    borderWidth?: number;
    borderColor?: string[];
  }[];
}

interface ChartOptions {
  maintainAspectRatio: boolean;
  aspectRatio: number;
  responsive: boolean;
  plugins: {
    legend: {
      position: string;
      labels: {
        usePointStyle: boolean;
        color: string;
        font: {
          size: number;
          family: string;
        };
        padding: number;
      };
    };
    tooltip: {
      backgroundColor: string;
      titleColor: string;
      bodyColor: string;
      borderColor: string;
      borderWidth: number;
      cornerRadius: number;
      callbacks: {
        label: (context: any) => string;
      };
    };
  };
  animation: {
    animateRotate: boolean;
    animateScale: boolean;
    duration: number;
  };
}

@Component({
    selector: 'app-lead-conversion',
    templateUrl: './lead-conversion.component.html',
    styleUrl: './lead-conversion.component.scss',
    standalone: true,
    imports: [CommonModule, ChartModule]
})
export class LeadConversionComponent implements OnInit {
  private readonly apiService = inject(ApiService);
  private readonly destroyRef = inject(DestroyRef);

  // Signals per lo stato del componente
  private readonly leadData = signal<any>(null);
  private readonly contractCount = signal<number>(0);
  protected readonly isLoading = signal<boolean>(true);
  protected readonly hasError = signal<boolean>(false);

  // Computed signals per i dati derivati
  protected readonly totalLeads = computed(() => {
    const data = this.leadData();
    const combinedData = this.apiService.combinedData$;
    
    // Se ci sono dati filtrati, usa quelli
    if (data?.isFiltered && data?.filteredLeads) {
      return data.filteredLeads.length;
    }
    
    // Altrimenti usa i dati originali
    return data?.Totale_Leads || 0;
  });

  protected readonly convertedLeads = computed(() => {
    const data = this.leadData();
    
    // Se ci sono dati filtrati, usa quelli
    if (data?.isFiltered && data?.filteredLeads) {
      return data.filteredLeads.filter((lead: any) => lead.is_converted).length;
    }
    
    // Altrimenti usa i dati originali
    if (!data?.risposta) return 0;
    return data.risposta.filter((lead: any) => lead.is_converted).length;
  });

  protected readonly conversionRate = computed(() => {
    const total = this.totalLeads();
    const converted = this.convertedLeads();
    return total > 0 ? Math.round((converted / total) * 100) : 0;
  });

  protected readonly currentContractCount = computed(() => {
    const data = this.leadData();
    
    // Se ci sono contratti filtrati, conta quelli
    if (data?.isFiltered && data?.filteredContratti) {
      return data.filteredContratti.length;
    }
    
    // Altrimenti usa il conteggio originale del signal
    return this.contractCount();
  });

  protected readonly chartData = computed<ChartData>(() => {
    const total = this.totalLeads();
    const converted = this.convertedLeads();
    const contracts = this.currentContractCount();

    return {
      labels: ['Lead Totali', 'Lead Convertiti', 'Contratti Attivi'],
      datasets: [{
        data: [total, converted, contracts],
        backgroundColor: [
          'rgba(204, 213, 79, 0.8)',
          'rgba(109, 158, 188, 0.8)',
          'rgba(34, 197, 94, 0.8)'
        ],
        hoverBackgroundColor: [
          'rgba(204, 213, 79, 1)',
          'rgba(109, 158, 188, 1)',
          'rgba(34, 197, 94, 1)'
        ],
        borderWidth: 2,
        borderColor: [
          'rgba(204, 213, 79, 1)',
          'rgba(109, 158, 188, 1)',
          'rgba(34, 197, 94, 1)'
        ]
      }]
    };
  });

  protected readonly chartOptions = computed<ChartOptions>(() => {
    const documentStyle = getComputedStyle(document.documentElement);
    const textColor = documentStyle.getPropertyValue('--text-color') || '#6b7280';
    const surfaceBorder = documentStyle.getPropertyValue('--surface-border') || '#e5e7eb';

    return {
      maintainAspectRatio: true,
      aspectRatio: 1.2,
      responsive: true,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            usePointStyle: true,
            color: textColor,
            font: {
              size: 12,
              family: 'Inter, system-ui, -apple-system, sans-serif'
            },
            padding: 20
          }
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          titleColor: '#ffffff',
          bodyColor: '#ffffff',
          borderColor: surfaceBorder,
          borderWidth: 1,
          cornerRadius: 8,
          callbacks: {
            label: (context: any) => {
              const label = context.label || '';
              const value = context.parsed || 0;
              const percentage = this.calculatePercentage(value, context.dataset.data);
              return `${label}: ${value} (${percentage}%)`;
            }
          }
        }
      },
      animation: {
        animateRotate: true,
        animateScale: true,
        duration: 1000
      }
    };
  });

  protected readonly chartSummary = computed(() => {
    const total = this.totalLeads();
    const converted = this.convertedLeads();
    const rate = this.conversionRate();
    return `${total} lead totali, ${converted} convertiti, tasso di conversione ${rate}%`;
  });

  ngOnInit(): void {
    this.loadData();
  }

  private loadData(): void {
    this.isLoading.set(true);
    this.hasError.set(false);

    this.apiService.combinedData$
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (data) => {
          if (data) {
            // Aggiorna sempre il leadData con i dati completi
            if (data.leads?.body) {
              this.leadData.set({
                ...data.leads.body,
                isFiltered: data.isFiltered || false,
                filteredLeads: data.filteredLeads || null,
                filteredContratti: data.filteredContratti || null
              });
            }
            
            // Aggiorna il conteggio contratti se disponibile
            if (data.countContratti !== undefined) {
              this.contractCount.set(data.countContratti);
            }
            
            this.isLoading.set(false);
          }
        },
        error: (error) => {
          console.error('Errore nel caricamento dei dati:', error);
          this.hasError.set(true);
          this.isLoading.set(false);
        }
      });
  }

  private calculatePercentage(value: number, dataset: number[]): number {
    const total = dataset.reduce((sum, val) => sum + val, 0);
    return total > 0 ? Math.round((value / total) * 100) : 0;
  }
}
