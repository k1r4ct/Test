import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { ApiService } from 'src/app/servizi/api.service';
import { MessageService } from 'primeng/api';

export interface ProfileModalData {
  userId: number;
  userName: string;
  userEmail: string;
  userRole: string;
}

@Component({
  selector: 'app-profile-settings-modal',
  templateUrl: './profile-settings-modal.component.html',
  styleUrls: ['./profile-settings-modal.component.scss'],
  standalone: false,
  providers: [MessageService]
})
export class ProfileSettingsModalComponent implements OnInit {

  // Loading states
  isLoadingProfile: boolean = false;
  isLoadingPassword: boolean = false;
  isLoadingData: boolean = true;

  // User data
  userData: any = null;

  constructor(
    private apiService: ApiService,
    private messageService: MessageService,
    public dialogRef: MatDialogRef<ProfileSettingsModalComponent>,
    @Inject(MAT_DIALOG_DATA) public data: ProfileModalData
  ) {}

  ngOnInit(): void {
    this.loadUserData();
  }

  /**
   * Load current user data from API
   */
  private loadUserData(): void {
    this.isLoadingData = true;

    this.apiService.PrendiUtente().subscribe({
      next: (response: any) => {
        if (response?.user) {
          this.userData = response.user;
        }
        this.isLoadingData = false;
      },
      error: (err) => {
        console.error('Error loading user data:', err);
        this.showToast('error', 'Errore', 'Impossibile caricare i dati utente');
        this.isLoadingData = false;
      }
    });
  }

  /**
   * Save profile changes (all fields except password)
   * Uses document.querySelector like original user.component.ts
   */
  saveProfile(): void {
    this.isLoadingProfile = true;

    const formData = new FormData();
    const email = (document.querySelector('.modal-email') as HTMLInputElement)?.value;
    const nome = (document.querySelector('.modal-nome') as HTMLInputElement)?.value;
    const cognome = (document.querySelector('.modal-cognome') as HTMLInputElement)?.value;
    const indirizzo = (document.querySelector('.modal-indirizzo') as HTMLInputElement)?.value;
    const citta = (document.querySelector('.modal-citta') as HTMLInputElement)?.value;
    const stato = (document.querySelector('.modal-stato') as HTMLInputElement)?.value;
    const cap = (document.querySelector('.modal-cap') as HTMLInputElement)?.value;

    formData.append('idUtente', this.data.userId.toString());
    formData.append('emailUtente', email || '');
    formData.append('nomeutente', nome || '');
    formData.append('cognomeUtente', cognome || '');
    formData.append('indirizzo', indirizzo || '');
    formData.append('citta', citta || '');
    formData.append('stato', stato || '');
    formData.append('cap', cap || '');

    this.apiService.updateUtente(formData).subscribe({
      next: (response: any) => {
        this.isLoadingProfile = false;
        
        // Check response.response === "ok" OR status === "200" (string)
        if (response.response === 'ok' || response.status === '200' || response.status === 200) {
          this.showToast('success', 'Successo', 'Dati aggiornati correttamente');
          
          // Close dialog and emit updated data
          this.dialogRef.close({
            updated: true,
            userName: (nome || '') + ' ' + (cognome || ''),
            userEmail: email || ''
          });
        } else {
          this.showToast('error', 'Errore', 'Errore durante l\'aggiornamento dei dati');
        }
      },
      error: (err) => {
        this.isLoadingProfile = false;
        console.error('Error updating profile:', err);
        this.showToast('error', 'Errore', 'Errore durante l\'aggiornamento del profilo');
      }
    });
  }

  /**
   * Change password
   * Uses document.querySelector like original user.component.ts
   */
  changePassword(): void {
    const oldPassw = (document.querySelector('.modal-oldpassword') as HTMLInputElement)?.value;
    const newPassword = (document.querySelector('.modal-newPassword') as HTMLInputElement)?.value;
    const repeatNewPassword = (document.querySelector('.modal-repeatNewPassword') as HTMLInputElement)?.value;

    // Validate passwords match
    if (newPassword !== repeatNewPassword) {
      this.showToast('error', 'Errore', 'Le password nuove non coincidono');
      return;
    }

    // Validate fields are not empty
    if (!oldPassw || !newPassword || !repeatNewPassword) {
      this.showToast('error', 'Errore', 'Compila tutti i campi password');
      return;
    }

    this.isLoadingPassword = true;

    const formData = new FormData();
    formData.append('idUser', this.data.userId.toString());
    formData.append('oldPw', oldPassw);
    formData.append('newPw', newPassword);
    formData.append('rNewPw', repeatNewPassword);

    this.apiService.updatePassw(formData).subscribe({
      next: (response: any) => {
        this.isLoadingPassword = false;

        // Check response.response === "ok" OR status === "200" (string)
        if (response.response === 'ok' || response.status === '200' || response.status === 200) {
          this.showToast('success', 'Successo', 'Password modificata correttamente');
          
          // Clear password fields
          const oldPwInput = document.querySelector('.modal-oldpassword') as HTMLInputElement;
          const newPwInput = document.querySelector('.modal-newPassword') as HTMLInputElement;
          const repeatPwInput = document.querySelector('.modal-repeatNewPassword') as HTMLInputElement;
          
          if (oldPwInput) oldPwInput.value = '';
          if (newPwInput) newPwInput.value = '';
          if (repeatPwInput) repeatPwInput.value = '';
        } else {
          // Show error message from API or default
          const errorMsg = response.body?.risposta || 'La password vecchia è errata';
          this.showToast('error', 'Errore', errorMsg);
        }
      },
      error: (err) => {
        this.isLoadingPassword = false;
        console.error('Error changing password:', err);
        this.showToast('error', 'Errore', 'Errore durante il cambio password');
      }
    });
  }

  /**
   * Close modal
   */
  closeModal(): void {
    this.dialogRef.close();
  }

  /**
   * Show toast notification
   */
  private showToast(severity: string, summary: string, detail: string): void {
    this.messageService.add({
      severity: severity,
      summary: summary,
      detail: detail,
      life: 5000
    });
  }
}