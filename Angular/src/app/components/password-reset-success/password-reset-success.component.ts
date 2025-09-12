import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-password-reset-success',
  standalone: true,
  imports: [CommonModule, RouterModule, MatIconModule],
  templateUrl: './password-reset-success.component.html',
  styleUrls: ['./password-reset-success.component.scss']
})
export class PasswordResetSuccessComponent {
  
}
