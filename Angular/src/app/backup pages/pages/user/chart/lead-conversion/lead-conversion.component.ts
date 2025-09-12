import { Component, OnInit } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';

@Component({
    selector: 'app-lead-conversion',
    templateUrl: './lead-conversion.component.html',
    styleUrl: './lead-conversion.component.scss',
    standalone: false
})
export class LeadConversionComponent implements OnInit {
  numeroClientiAmiciInvitati: number = 0;
  countContratti: number = 0;
  dataChartPie: any;
  options: any;
  amiciInvitati: any;
  convertiti: any = 0;

  constructor(private servzioAPI: ApiService) {}

  ngOnInit(): void {
    this.servzioAPI.combinedData$.subscribe((data) => {
      if (data) {
        // Evitare ricaricamenti ridondanti
        if (
          this.numeroClientiAmiciInvitati === data.leads.body.Totale_Leads &&
          this.countContratti === data.countContratti
        ) {
          return;
        }

        // Aggiorna solo quando i dati cambiano
        this.numeroClientiAmiciInvitati = data.leads.body.Totale_Leads;
        this.countContratti = data.countContratti;
        this.convertiti = data.leads.body.risposta.filter(
          (conv: any) => conv.is_converted
        ).length;

        this.initChart();
      }
    });
  }

  initChart() {
    const documentStyle = getComputedStyle(document.documentElement);
    const textColor = documentStyle.getPropertyValue('--text-color');

    this.dataChartPie = {
      labels: ['Amici Invitati', 'Amici Convertiti', 'Contratti'],
      datasets: [
        {
          data: [
            this.numeroClientiAmiciInvitati,
            this.convertiti,
            this.countContratti,
          ],
          backgroundColor: ['#ccd54f', '#6d9ebc', 'green'],
          hoverBackgroundColor: ['#ccd54f', '#6d9ebc', 'green'],
        },
      ],
    };

    this.options = {
      maintainAspectRatio: true,
            aspectRatio: 1,
      plugins: {
        legend: {
          labels: {
            usePointStyle: true,
            color: textColor,
          },
        },
      },
    };
  }
}
