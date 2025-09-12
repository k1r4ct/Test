import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ChartModule as PrimeChartModule } from 'primeng/chart';

import { LeadConversionComponent } from './lead-conversion/lead-conversion.component';
import { PrevisionPVbarComponent } from './prevision-pvbar/prevision-pvbar.component';

@NgModule({
  imports: [
    CommonModule,
    PrimeChartModule,
    // Import dei componenti standalone
    LeadConversionComponent,
    PrevisionPVbarComponent
  ],
  exports: [
    LeadConversionComponent,
    PrevisionPVbarComponent
  ]
})
export class ChartsModule { }
