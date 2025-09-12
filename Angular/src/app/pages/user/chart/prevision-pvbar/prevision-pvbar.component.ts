import { isPlatformBrowser, CommonModule } from "@angular/common";
import {
  Component,
  OnInit,
  inject,
  PLATFORM_ID,
  DestroyRef,
  computed,
  signal
} from "@angular/core";
import { ChartModule } from 'primeng/chart';
import { ApiService } from "src/app/servizi/api.service";
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

export interface MonthData {
  labels: string;
  pvReali: number;
  pvPotenziali: number;
}

interface ChartDataset {
  label: string;
  backgroundColor: string | string[];
  borderColor: string | string[];
  borderWidth?: number;
  data: number[];
  borderRadius?: number;
  borderSkipped?: boolean;
}

interface ChartData {
  labels: string[];
  datasets: ChartDataset[];
}

interface ChartOptions {
  maintainAspectRatio: boolean;
  aspectRatio: number;
  responsive: boolean;
  interaction: {
    mode: string;
    intersect: boolean;
  };
  plugins: {
    legend: {
      position: string;
      labels: {
        color: string;
        font: {
          size: number;
          family: string;
          weight: string;
        };
        padding: number;
        usePointStyle: boolean;
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
        afterLabel: (context: any) => string;
      };
    };
  };
  scales: {
    x: {
      beginAtZero: boolean;
      ticks: {
        color: string;
        font: {
          weight: string;
          size: number;
        };
        maxRotation: number;
      };
      grid: {
        color: string;
        drawBorder: boolean;
        lineWidth: number;
      };
    };
    y: {
      beginAtZero: boolean;
      ticks: {
        color: string;
        font: {
          size: number;
        };
        callback: (value: any) => string;
      };
      grid: {
        color: string;
        drawBorder: boolean;
        lineWidth: number;
      };
    };
  };
  animation: {
    duration: number;
    easing: string;
  };
}

@Component({
    selector: "app-prevision-pvbar",
    templateUrl: "./prevision-pvbar.component.html",
    styleUrl: "./prevision-pvbar.component.scss",
    standalone: true,
    imports: [CommonModule, ChartModule]
})
export class PrevisionPVbarComponent implements OnInit {
  private readonly apiService = inject(ApiService);
  private readonly platformId = inject(PLATFORM_ID);
  private readonly destroyRef = inject(DestroyRef);

  // Signals per lo stato del componente
  private readonly monthlyData = signal<MonthData[]>([]);
  protected readonly isLoading = signal<boolean>(true);
  protected readonly hasError = signal<boolean>(false);

  private readonly mesiOrdinati: string[] = [
    "gennaio", "febbraio", "marzo", "aprile", "maggio", "giugno",
    "luglio", "agosto", "settembre", "ottobre", "novembre", "dicembre"
  ];

  // Computed signals per i dati derivati
  protected readonly isEmpty = computed(() => {
    return this.monthlyData().length === 0 && !this.isLoading();
  });

  protected readonly totalPvReali = computed(() => {
    return this.monthlyData().reduce((sum, month) => sum + month.pvReali, 0);
  });

  protected readonly totalPvPotenziali = computed(() => {
    return this.monthlyData().reduce((sum, month) => sum + month.pvPotenziali, 0);
  });

  protected readonly achievementRate = computed(() => {
    const reali = this.totalPvReali();
    const potenziali = this.totalPvPotenziali();
    return potenziali > 0 ? Math.round((reali / potenziali) * 100) : 0;
  });

  protected readonly activeMonths = computed(() => {
    return this.monthlyData().filter(month => month.pvReali > 0 || month.pvPotenziali > 0).length;
  });

  protected readonly chartData = computed<ChartData>(() => {
    const data = this.monthlyData();
    const labels = data.map(item => item.labels);
    const pvReali = data.map(item => item.pvReali);
    const pvPotenziali = data.map(item => item.pvPotenziali);

    return {
      labels,
      datasets: [
        {
          label: "PV Reali",
          backgroundColor: "rgba(34, 197, 94, 0.8)",
          borderColor: "rgba(34, 197, 94, 1)",
          borderWidth: 2,
          data: pvReali,
          borderRadius: 6,
          borderSkipped: false
        },
        {
          label: "PV Potenziali",
          backgroundColor: "rgba(59, 130, 246, 0.8)",
          borderColor: "rgba(59, 130, 246, 1)",
          borderWidth: 2,
          data: pvPotenziali,
          borderRadius: 6,
          borderSkipped: false
        }
      ]
    };
  });

  protected readonly chartOptions = computed<ChartOptions>(() => {
    if (!isPlatformBrowser(this.platformId)) {
      return {} as ChartOptions;
    }

    const documentStyle = getComputedStyle(document.documentElement);
    const textColor = documentStyle.getPropertyValue("--p-text-color") || "#374151";
    const textColorSecondary = documentStyle.getPropertyValue("--p-text-muted-color") || "#6b7280";
    const surfaceBorder = documentStyle.getPropertyValue("--p-content-border-color") || "#e5e7eb";

    return {
      maintainAspectRatio: false,
      aspectRatio: 0.8,
      responsive: true,
      interaction: {
        mode: 'index',
        intersect: false
      },
      plugins: {
        legend: {
          position: 'top',
          labels: {
            color: textColor,
            font: {
              size: 12,
              family: 'Inter, system-ui, -apple-system, sans-serif',
              weight: '500'
            },
            padding: 20,
            usePointStyle: true
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
              const label = context.dataset.label || '';
              const value = context.parsed.y || 0;
              return `${label}: ${value.toLocaleString()} PV`;
            },
            afterLabel: (context: any) => {
              const dataIndex = context.dataIndex;
              const datasets = context.chart.data.datasets;
              const reali = datasets[0].data[dataIndex] || 0;
              const potenziali = datasets[1].data[dataIndex] || 0;
              if (potenziali > 0) {
                const percentage = Math.round((reali / potenziali) * 100);
                return `Realizzazione: ${percentage}%`;
              }
              return '';
            }
          }
        }
      },
      scales: {
        x: {
          beginAtZero: true,
          ticks: {
            color: textColorSecondary,
            font: {
              weight: '500',
              size: 11
            },
            maxRotation: 45
          },
          grid: {
            color: surfaceBorder,
            drawBorder: false,
            lineWidth: 1
          }
        },
        y: {
          beginAtZero: true,
          ticks: {
            color: textColorSecondary,
            font: {
              size: 11
            },
            callback: (value: any) => {
              return value.toLocaleString() + ' PV';
            }
          },
          grid: {
            color: surfaceBorder,
            drawBorder: false,
            lineWidth: 1
          }
        }
      },
      animation: {
        duration: 1000,
        easing: 'easeInOutQuart'
      }
    };
  });

  protected readonly chartSummary = computed(() => {
    const reali = this.totalPvReali();
    const potenziali = this.totalPvPotenziali();
    const rate = this.achievementRate();
    const months = this.activeMonths();
    return `${reali} PV reali su ${potenziali} potenziali in ${months} mesi, tasso realizzazione ${rate}%`;
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
            let contractsToProcess: any[] = [];
            
            // Se ci sono dati filtrati, usa quelli
            if (data.isFiltered && data.filteredContratti) {
              contractsToProcess = data.filteredContratti;
            } 
            // Altrimenti usa i dati originali
            else if (data.contrattiUtente && Array.isArray(data.contrattiUtente)) {
              contractsToProcess = data.contrattiUtente;
            }
            
            if (contractsToProcess.length > 0) {
              this.processContractData(contractsToProcess);
            } else {
              // Se non ci sono contratti, resetta i dati
              this.monthlyData.set([]);
            }
            
            this.isLoading.set(false);
          }
        },
        error: (error) => {
          console.error('Errore nel caricamento dei dati PV:', error);
          this.hasError.set(true);
          this.isLoading.set(false);
        }
      });
  }

  private processContractData(contratti: any[]): void {
    const meseAggregato = new Map<string, { pvReali: number; pvPotenziali: number }>();

    contratti.forEach((contratto: any) => {
      if (contratto.data_inserimento && contratto.product?.macro_product?.punti_valore) {
        const nomeMese = this.extractMonthFromDate(contratto.data_inserimento);
        
        if (!meseAggregato.has(nomeMese)) {
          meseAggregato.set(nomeMese, { pvReali: 0, pvPotenziali: 0 });
        }

        const meseData = meseAggregato.get(nomeMese)!;
        const puntiValore = contratto.product.macro_product.punti_valore;

        // PV Reali: solo contratti con status_contract_id === 15
        if (contratto.status_contract_id === 15) {
          meseData.pvReali += puntiValore;
        }

        // PV Potenziali: tutti tranne stati specifici
        const excludedStatuses = [3, 5, 8, 9, 12, 16];
        if (!excludedStatuses.includes(contratto.status_contract_id)) {
          meseData.pvPotenziali += puntiValore;
        }
      }
    });

    // Converti la mappa in array e ordina per mese
    const monthlyData = Array.from(meseAggregato, ([mese, valori]) => ({
      labels: mese,
      pvReali: valori.pvReali,
      pvPotenziali: valori.pvPotenziali
    })).sort((a, b) => {
      const indexA = this.getMonthIndex(a.labels);
      const indexB = this.getMonthIndex(b.labels);
      return indexA - indexB;
    });

    this.monthlyData.set(monthlyData);
  }

  private extractMonthFromDate(dataStringa: string): string {
    try {
      const partiData = dataStringa.split("/");
      const giorno = parseInt(partiData[0], 10);
      const mese = parseInt(partiData[1], 10) - 1; // JavaScript: mesi da 0 a 11
      const anno = parseInt(partiData[2], 10);
      const dataNew = new Date(anno, mese, giorno);
      return dataNew.toLocaleDateString("it-IT", { month: "long", year: "numeric" });
    } catch (error) {
      console.warn('Errore nel parsing della data:', dataStringa, error);
      return 'Data non valida';
    }
  }

  private getMonthIndex(nomeMese: string): number {
    const meseBase = nomeMese.split(' ')[0].toLowerCase();
    const index = this.mesiOrdinati.indexOf(meseBase);
    return index !== -1 ? index : 999; // Mette alla fine i mesi non riconosciuti
  }
}
