import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';


import { MatTableModule } from '@angular/material/table';
import { MatCardModule } from '@angular/material/card';
import { MatIconModule } from '@angular/material/icon';
import { MatDividerModule } from '@angular/material/divider';
import { MatTabsModule } from '@angular/material/tabs';
import { MatSelectModule } from '@angular/material/select';
import { MatInputModule } from '@angular/material/input';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatButtonModule } from '@angular/material/button';
import { MatPaginatorModule } from '@angular/material/paginator';
import { MatSortModule } from '@angular/material/sort';
import { MatStepperModule } from '@angular/material/stepper';
import { MatListModule } from '@angular/material/list';
import { MatMenuModule } from '@angular/material/menu';


@NgModule({
  declarations: [],
  imports: [
    CommonModule,
    MatTableModule,
    MatInputModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatDividerModule,
    MatTabsModule,
    MatSelectModule,
    MatFormFieldModule,
    MatPaginatorModule,
    MatSortModule,
    MatStepperModule,
    MatListModule,
    MatMenuModule,
  ],
  exports:[
    MatTableModule,
    MatInputModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatDividerModule,
    MatTabsModule,
    MatSelectModule,
    MatFormFieldModule,
    MatPaginatorModule,
    MatSortModule,
    MatStepperModule,
    MatListModule,
    MatMenuModule,
  ]
})
export class MaterialComponentsModule { }
