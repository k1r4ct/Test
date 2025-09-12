import { isPlatformBrowser } from "@angular/common";
import {
  ChangeDetectorRef,
  Component,
  inject,
  OnInit,
  PLATFORM_ID,
} from "@angular/core";
import { ApiService } from "src/app/servizi/api.service";

export interface Labels {
  labels: string; // Nome del mese
  pvReali: number; // Somma dei PV reali del mese
  pvPotenziali: number; // Somma dei PV potenziali del mese
}

@Component({
    selector: "app-prevision-pvbar",
    templateUrl: "./prevision-pvbar.component.html",
    styleUrl: "./prevision-pvbar.component.scss",
    standalone: false
})
export class PrevisionPVbarComponent implements OnInit {
  data: any;
  Labels: Labels[] = [];
  options: any;

  mesiOrdinati: string[] = [
    "gennaio",
    "febbraio",
    "marzo",
    "aprile",
    "maggio",
    "giugno",
    "luglio",
    "agosto",
    "settembre",
    "ottobre",
    "novembre",
    "dicembre",
  ];

  platformId = inject(PLATFORM_ID);

  constructor(private cd: ChangeDetectorRef, private servzioAPI: ApiService) {}

  ngOnInit(): void {
    this.servzioAPI.combinedData$.subscribe((data) => {
      //console.log(data);
      
      if (data && Array.isArray(data.contrattiUtente)) {
        // Usa una mappa per aggregare i dati
        const meseAggregato = new Map<string, { pvReali: number; pvPotenziali: number }>();
    
        data.contrattiUtente.forEach((contratto: any) => {
          if (contratto.data_inserimento) {
            const dataStringa = contratto.data_inserimento;
            const partiData = dataStringa.split("/");
            const giorno = parseInt(partiData[0], 10);
            const mese = parseInt(partiData[1], 10) - 1; // I mesi in JavaScript vanno da 0 a 11
            const anno = parseInt(partiData[2], 10);
            const dataNew = new Date(anno, mese, giorno);
            const nomeMese = dataNew.toLocaleDateString("it-IT", { month: "long", year: "numeric" });
    
            // Controlla se il mese è già presente nella mappa
            if (!meseAggregato.has(nomeMese)) {
              meseAggregato.set(nomeMese, { pvReali: 0, pvPotenziali: 0 });
            }
    
            // Aggiorna i valori aggregati per il mese
            const meseData = meseAggregato.get(nomeMese)!;
            if (contratto.status_contract_id === 15) {
              meseData.pvReali += contratto.product.macro_product.punti_valore;
            }
            if (contratto.status_contract_id != 3||contratto.status_contract_id != 5||contratto.status_contract_id != 8||contratto.status_contract_id != 9||contratto.status_contract_id != 12||contratto.status_contract_id != 16) {
              
              meseData.pvPotenziali += contratto.product.macro_product.punti_valore;
            }
          }
        });
    
        // Converti la mappa in un array ordinato
        this.Labels = Array.from(meseAggregato, ([mese, valori]) => ({
          labels: mese,
          pvReali: valori.pvReali,
          pvPotenziali: valori.pvPotenziali,
        })).sort((a, b) => {
          return this.mesiOrdinati.indexOf(a.labels) - this.mesiOrdinati.indexOf(b.labels);
        });
    
        //console.log("Dati aggregati (Labels):", this.Labels);
        this.initChart();
      } else {
        //console.warn("Dati mancanti o non validi:", data);
      }
    });
  }

  initChart() {
    if (isPlatformBrowser(this.platformId)) {
      const documentStyle = getComputedStyle(document.documentElement);
      const textColor = documentStyle.getPropertyValue("--p-text-color");
      const textColorSecondary = documentStyle.getPropertyValue("--p-text-muted-color");
      const surfaceBorder = documentStyle.getPropertyValue("--p-content-border-color");

      const labels = this.Labels.map((item) => item.labels);
      const pvReali = this.Labels.map((item) => item.pvReali);
      const pvPotenziali = this.Labels.map((item) => item.pvPotenziali);

      this.data = {
        labels: labels,
        datasets: [
          {
            label: "PV reali",
            backgroundColor: "#ccd54f",
            borderColor: "green",
            data: pvReali,
          },
          {
            label: "PV potenziali",
            backgroundColor: "#6d9ebc",
            borderColor: "blue",
            data: pvPotenziali,
          },
        ],
      };

      this.options = {
        maintainAspectRatio: false,
        aspectRatio: 0.75,
        plugins: {
          legend: {
            labels: {
              color: textColor,
            },
          },
        },
        scales: {
          x: {
            ticks: {
              color: textColorSecondary,
              font: {
                weight: 500,
              },
            },
            grid: {
              color: surfaceBorder,
              drawBorder: false,
            },
          },
          y: {
            ticks: {
              color: textColorSecondary,
            },
            grid: {
              color: surfaceBorder,
              drawBorder: false,
            },
          },
        },
      };

      this.cd.markForCheck();
    }
  }
}
