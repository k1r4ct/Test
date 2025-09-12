import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { MatIconModule } from '@angular/material/icon';
import { ApiService } from 'src/app/servizi/api.service';

@Component({
  selector: 'app-reset-password',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterModule, MatIconModule],
  templateUrl: './reset-password.component.html',
  styleUrls: ['./reset-password.component.scss']
})
export class ResetPasswordComponent {
  resetPasswordForm: FormGroup;
  isLoading = false;
  showSuccessMessage = false;
  showErrorMessage = false;
  successMessage = '';
  errorMessage = '';

  constructor(private apiService: ApiService, private fb: FormBuilder) {
    this.resetPasswordForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
    });
  }
  
  onSubmit() {
    console.log('Form submitted:', this.resetPasswordForm.value);
    
    if (this.resetPasswordForm.valid) {
      this.isLoading = true;
      this.showSuccessMessage = false;
      this.showErrorMessage = false;
      
      this.apiService.resetPwd(this.resetPasswordForm.value).subscribe(
        (response) => {
          this.isLoading = false;
          this.showSuccessMessage = true;
          this.successMessage = 'Link di reset password inviato con successo! Controlla la tua email.';
          console.log('Password reset successful', response);
          
          // Nascondi il messaggio di successo dopo 5 secondi
          setTimeout(() => {
            this.showSuccessMessage = false;
          }, 5000);
        },
        (error) => {
          this.isLoading = false;
          this.showErrorMessage = true;
          this.errorMessage = error.error?.message || 'Errore nell\'invio del link. Riprova più tardi.';
          console.error('Error resetting password', error);
        }
      );
    }
  }
}
