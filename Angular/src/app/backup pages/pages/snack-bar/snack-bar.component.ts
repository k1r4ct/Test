/* import { Component, Inject } from '@angular/core';
import { MAT_SNACK_BAR_DATA ,MatSnackBarRef } from '@angular/material/snack-bar';
import { trigger, transition, style, animate } from '@angular/animations';
@Component({
  selector: 'app-snack-bar',
  template: `<div class="custom-snackbar">
      {{ data.message }}
      <button mat-button (click)="dismiss()">Chiudi</button>
    </div>`,
  styleUrl:'./snack-bar.component.scss',
  animations: [
    trigger('slideInFromLeft', [
      transition(':enter', [
        style({ transform: 'translateX(-100%)' }),
        animate('300ms ease-out', style({ transform: 'translateX(0)' }))
      ])
    ])
  ]
})
export class SnackBarComponent {
  constructor(@Inject(MAT_SNACK_BAR_DATA) public data: any,
  private snackBarRef: MatSnackBarRef<SnackBarComponent>) {}

  dismiss() {
    this.snackBarRef.dismiss(); // Chiudi lo snackbar
  }
} */