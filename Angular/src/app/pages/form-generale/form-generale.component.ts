import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from 'src/app/servizi/api.service';
@Component({
  selector: 'app-form-generale',
  imports: [FormsModule, CommonModule],
  templateUrl: './form-generale.component.html',
  styleUrl: './form-generale.component.scss'
})
export class FormGeneraleComponent implements OnInit {
  // Parametro dalla rotta
  userId: string = '';
  
  // Dati del form
  nome: string = '';
  cognome: string = '';
  telefono: string = '';
  email: string = '';
  privacy: boolean = false;
  
  // Stati del form
  isSubmitting: boolean = false;
  isSubmitted: boolean = false;
  errorMessage: string = '';

  constructor(private route: ActivatedRoute, private apiService: ApiService) {}

  ngOnInit(): void {
    // Recupera l'userId dai parametri della rotta
    this.route.params.subscribe(params => {
      this.userId = params['userId'];
      console.log('User ID ricevuto:', this.userId);
      
      // Validazione dell'userId
      if (!this.userId || this.userId.trim() === '') {
        this.errorMessage = 'Errore: ID utente mancante nell\'URL';
        console.error('Form Generale: userId non trovato nei parametri della rotta');
      }
    });
  }

  onSubmit(): void {
    // Validazione che l'userId sia presente
    if (!this.userId || this.userId.trim() === '') {
      this.errorMessage = 'Errore: ID utente non valido. Contatta il supporto.';
      return;
    }

    // Validazione base
    if (!this.nome.trim() || !this.cognome.trim() || !this.telefono.trim() || !this.email.trim()) {
      this.errorMessage = 'Tutti i campi sono obbligatori';
      return;
    }

    if (!this.privacy) {
      this.errorMessage = 'È necessario accettare i termini per la privacy';
      return;
    }

    // Validazione email semplice
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(this.email)) {
      this.errorMessage = 'Inserisci un indirizzo email valido';
      return;
    }

    // Validazione telefono (controllo base)
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{8,}$/;
    if (!phoneRegex.test(this.telefono.trim())) {
      this.errorMessage = 'Inserisci un numero di telefono valido';
      return;
    }

    this.errorMessage = '';
    this.isSubmitting = true;

    // Prepara i dati da inviare includendo l'userId
    const formData = {
      userId: this.userId,
      nome: this.nome,
      cognome: this.cognome,
      telefono: this.telefono,
      email: this.email,
      privacy: this.privacy,
      timestamp: new Date().toISOString()
    };

    this.apiService.storeLeadExternal(formData).subscribe({
      next: (response) => {
        console.log('Dati inviati con successo:', response);
      },
      error: (error) => {
        console.error('Errore durante l\'invio dei dati:', error);
      }
    });
    // Simula invio (sostituire con chiamata API reale)
    setTimeout(() => {
      console.log('Dati inviati:', formData);
      
      // Qui dovresti fare la chiamata HTTP al tuo backend
      // this.httpClient.post('/api/form-generale', formData).subscribe({
      //   next: (response) => this.handleSuccess(response),
      //   error: (error) => this.handleError(error)
      // });
      
      this.isSubmitting = false;
      this.isSubmitted = true;
      
      // Reset form dopo successo
      setTimeout(() => {
        this.resetForm();
      }, 3000);
    }, 2000);
  }

  resetForm(): void {
    this.nome = '';
    this.cognome = '';
    this.telefono = '';
    this.email = '';
    this.privacy = false;
    this.isSubmitted = false;
    this.errorMessage = '';
  }
}
