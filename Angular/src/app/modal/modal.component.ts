// modal.component.ts - COMPLETE FILE
import { Component, Inject, OnInit, OnDestroy } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { ApiService } from '../servizi/api.service';
import { MatSnackBar } from '@angular/material/snack-bar';
import { FormGroup, Validators, FormBuilder } from '@angular/forms';
import { Subscription, interval } from 'rxjs';

export interface StatiLeads {
  id: number;
  stato: string;
}

export interface UserForLeads {
  id: number;
  nome: string;
  cognome: string;
}

@Component({
  selector: 'app-contratto-details-dialog',
  templateUrl: './modal.component.html',
  styleUrls: ['./modal.component.scss'],
  standalone: false
})
export class ContrattoDetailsDialogComponent implements OnInit, OnDestroy {
  form: FormGroup;
  form2: FormGroup;
  statiLead: StatiLeads[] = [];
  userForLeads: UserForLeads[] = [];
  
  // Ticket creation properties
  ticketStep: 'confirm' | 'chat' | 'existing' = 'confirm';
  ticketTitle: string = '';
  ticketDescription: string = '';
  ticketPriority: 'low' | 'medium' | 'high' | 'unassigned' = 'unassigned';
  chatMessage: string = '';
  isCreatingTicket: boolean = false;
  currentUser: any;
  
  // Existing ticket properties
  existingTicket: any = null;
  ticketMessages: any[] = [];
  isLoadingMessages: boolean = false;
  isSendingMessage: boolean = false;
  pollingSubscription: Subscription | null = null;
  lastMessageId: number = 0;
  
  SetOpt: FormGroup = new FormGroup({});
  showError: boolean = false;
  showCalendar = true;
  updateTrue = true;
  ruoloUser = 0;
  ruoloUserText = "";
  attivaRiassegnazione = true;
  
  private subscriptions: Subscription[] = [];

  constructor(
    public dialogRef: MatDialogRef<ContrattoDetailsDialogComponent>,
    public apiService: ApiService,
    private snackBar: MatSnackBar,
    private fb: FormBuilder,
    @Inject(MAT_DIALOG_DATA) public data: any
  ) {
    this.form = this.fb.group({
      data_appuntamento_mat: ['', Validators.required],
      ora_appuntamento_mat: ['', Validators.required],
      id_lead: [''],
      stato_id: [''],
    });

    this.form2 = this.fb.group({
      id_user: [''],
      id_lead: [''],
    });
  }

  ngOnInit(): void {
    console.log(this.data);
    
    // Get current user
    this.apiService.PrendiUtente().subscribe((Ruolo: any) => {
      this.currentUser = Ruolo.user;
    });
    
    // Initialize ticket if reparto is ticket
    if (this.data.reparto === 'ticket') {
      // Check if we have an existing ticket
      if (this.data.existingTicket) {
        this.initializeExistingTicket();
      } else {
        this.initializeNewTicket();
      }
    }

    if (this.data.reparto === 'leads') {
      this.apiService.PrendiUtente().subscribe((Ruolo: any) => {
        this.ruoloUser = Ruolo.user.role_id;
        this.ruoloUserText = Ruolo.user.qualification.descrizione;
        if (this.ruoloUser != 3) {
          this.attivaRiassegnazione = false;
        }
      });
      
      this.apiService.getStatiLeads().subscribe((risposta: any) => {
        this.statiLead = risposta.body.risposta.map((item: any) => ({
          id: item.id,
          stato: item.micro_stato
        }));
        this.form.patchValue({ id_lead: this.data.lead.id });
        this.form.patchValue({ stato_id: { id: this.data.lead.stato, microstato: this.data.lead.microstato } });
      });

      this.apiService.getUserForLeads().subscribe((risposta: any) => {
        console.log(risposta);
        this.userForLeads = risposta.body.risposta.map((risposta: any) => ({
          id: risposta.id,
          nome: risposta.name,
          cognome: risposta.cognome,
        }));
      });
    }
  }

  ngOnDestroy(): void {
    // Clean up all subscriptions
    this.subscriptions.forEach(sub => sub.unsubscribe());
    
    // Stop polling if active
    this.stopPolling();
  }

  // ==================== TICKET METHODS ====================

  /**
   * Initialize new ticket with contract data
   */
  initializeNewTicket(): void {
    if (this.data.contractData) {
      this.ticketTitle = `Contratto #${this.data.contractData.contractCode} - ${this.data.contractData.clientName}`;
      this.ticketStep = 'confirm';
    }
  }

  /**
   * Initialize existing ticket and load messages
   */
  initializeExistingTicket(): void {
    this.existingTicket = this.data.existingTicket;
    this.ticketStep = 'existing';
    
    // Load initial messages
    this.loadTicketMessages();
    
    // Start polling for new messages
    this.startPolling();
  }

  /**
   * Load messages for existing ticket
   */
  loadTicketMessages(): void {
    if (!this.existingTicket) return;
    
    this.isLoadingMessages = true;
    
    const loadMsgSub = this.apiService.getTicketMessages(this.existingTicket.id).subscribe(
      (response: any) => {
        if (response.response === 'ok' && response.body?.risposta) {
          this.ticketMessages = response.body.risposta;
          
          // Track last message ID for detecting new messages
          if (this.ticketMessages.length > 0) {
            this.lastMessageId = Math.max(...this.ticketMessages.map(m => m.id));
          }
          
          // Scroll to bottom after loading messages
          setTimeout(() => this.scrollToBottom(), 100);
        }
        this.isLoadingMessages = false;
      },
      (error) => {
        console.error('Error loading messages:', error);
        this.isLoadingMessages = false;
        this.snackBar.open(
          'Errore nel caricamento dei messaggi',
          'Chiudi',
          {
            duration: 3000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['error-snackbar']
          }
        );
      }
    );
    
    this.subscriptions.push(loadMsgSub);
  }

  /**
   * Start polling for new messages every second
   */
  startPolling(): void {
    // Poll every 1 second
    this.pollingSubscription = interval(1000).subscribe(() => {
      this.checkForNewMessages();
    });
    
    this.subscriptions.push(this.pollingSubscription);
  }

  /**
   * Stop polling for messages
   */
  stopPolling(): void {
    if (this.pollingSubscription) {
      this.pollingSubscription.unsubscribe();
      this.pollingSubscription = null;
    }
  }

  /**
   * Check for new messages without full reload
   */
  checkForNewMessages(): void {
    if (!this.existingTicket || this.isLoadingMessages) return;
    
    const checkSub = this.apiService.getTicketMessages(this.existingTicket.id).subscribe(
      (response: any) => {
        if (response.response === 'ok' && response.body?.risposta) {
          const newMessages = response.body.risposta;
          
          // Check if there are new messages
          if (newMessages.length > this.ticketMessages.length) {
            const hasNewMessages = newMessages.some((msg: any) => msg.id > this.lastMessageId);
            
            if (hasNewMessages) {
              this.ticketMessages = newMessages;
              this.lastMessageId = Math.max(...newMessages.map((m: any) => m.id));
              
              // Auto scroll to show new message
              setTimeout(() => this.scrollToBottom(), 100);
              
              // Play notification sound (optional)
              this.playNotificationSound();
            }
          }
        }
      },
      (error) => {
        console.error('Polling error:', error);
      }
    );
    
    // Auto cleanup after execution
    setTimeout(() => checkSub.unsubscribe(), 500);
  }

  /**
   * Send message to existing ticket
   */
  sendMessageToExistingTicket(): void {
    if (!this.chatMessage.trim() || !this.existingTicket) {
      return;
    }
    
    this.isSendingMessage = true;
    
    const messageData = {
      ticket_id: this.existingTicket.id,
      message: this.chatMessage.trim()
    };
    
    const sendSub = this.apiService.sendTicketMessage(messageData).subscribe(
      (response: any) => {
        if (response.response === 'ok') {
          // Clear input
          this.chatMessage = '';
          
          // Reload messages to show the new one
          this.loadTicketMessages();
          
          this.snackBar.open(
            'Messaggio inviato',
            'Chiudi',
            {
              duration: 2000,
              horizontalPosition: 'center',
              verticalPosition: 'bottom',
              panelClass: ['success-snackbar']
            }
          );
        }
        this.isSendingMessage = false;
      },
      (error) => {
        console.error('Error sending message:', error);
        this.isSendingMessage = false;
        this.snackBar.open(
          'Errore nell\'invio del messaggio',
          'Chiudi',
          {
            duration: 3000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['error-snackbar']
          }
        );
      }
    );
    
    this.subscriptions.push(sendSub);
  }

  /**
   * Proceed to chat step
   */
  proceedToChat(): void {
    if (!this.ticketTitle || !this.ticketDescription) {
      this.snackBar.open(
        'Inserisci un titolo e una descrizione per il ticket',
        'Chiudi',
        {
          duration: 3000,
          horizontalPosition: 'center',
          verticalPosition: 'bottom',
          panelClass: ['error-snackbar']
        }
      );
      return;
    }
    
    this.ticketStep = 'chat';
  }

  /**
   * Go back to confirmation step
   */
  goBackToConfirm(): void {
    this.ticketStep = 'confirm';
  }

  /**
   * Create ticket and send first message
   */
  createTicketWithMessage(): void {
    if (!this.chatMessage.trim()) {
      this.snackBar.open(
        'Inserisci un messaggio per aprire il ticket',
        'Chiudi',
        {
          duration: 3000,
          horizontalPosition: 'center',
          verticalPosition: 'bottom',
          panelClass: ['warning-snackbar']
        }
      );
      return;
    }

    this.isCreatingTicket = true;

    const ticketData = {
      contract_id: this.data.contractData.contractId,
      title: this.ticketTitle,
      description: this.ticketDescription,
      priority: this.ticketPriority
    };

    const createSub = this.apiService.createTicket(ticketData).subscribe(
      (response: any) => {
        if (response.response === 'ok' && response.body?.risposta?.id) {
          const ticketId = response.body.risposta.id;
          this.sendFirstMessage(ticketId);
        } else {
          this.isCreatingTicket = false;
          this.snackBar.open(
            'Errore nella creazione del ticket',
            'Chiudi',
            {
              duration: 3000,
              horizontalPosition: 'center',
              verticalPosition: 'bottom',
              panelClass: ['error-snackbar']
            }
          );
        }
      },
      (error) => {
        console.error('Error creating ticket:', error);
        this.isCreatingTicket = false;
        this.snackBar.open(
          'Errore di connessione durante la creazione del ticket',
          'Chiudi',
          {
            duration: 3000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['error-snackbar']
          }
        );
      }
    );

    this.subscriptions.push(createSub);
  }

  /**
   * Send first message to ticket
   */
  private sendFirstMessage(ticketId: number): void {
    const messageData = {
      ticket_id: ticketId,
      message: this.chatMessage
    };

    const messageSub = this.apiService.sendTicketMessage(messageData).subscribe(
      (response: any) => {
        this.isCreatingTicket = false;
        
        if (response.response === 'ok') {
          this.snackBar.open(
            'Ticket creato con successo!',
            'Chiudi',
            {
              duration: 3000,
              horizontalPosition: 'center',
              verticalPosition: 'bottom',
              panelClass: ['success-snackbar']
            }
          );
          
          this.dialogRef.close({ 
            success: true, 
            ticketId: ticketId 
          });
        } else {
          this.snackBar.open(
            'Ticket creato ma errore nell\'invio del messaggio',
            'Chiudi',
            {
              duration: 3000,
              horizontalPosition: 'center',
              verticalPosition: 'bottom',
              panelClass: ['warning-snackbar']
            }
          );
        }
      },
      (error) => {
        console.error('Error sending ticket message:', error);
        this.isCreatingTicket = false;
        this.snackBar.open(
          'Ticket creato ma errore nell\'invio del messaggio',
          'Chiudi',
          {
            duration: 3000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['warning-snackbar']
          }
        );
      }
    );

    this.subscriptions.push(messageSub);
  }

  /**
   * Auto resize textarea
   */
  autoResizeTextarea(event: Event): void {
    const textarea = event.target as HTMLTextAreaElement;
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
  }

  /**
   * Scroll chat to bottom
   */
  scrollToBottom(): void {
    const element = document.getElementById('chatMessagesContainer');
    if (element) {
      element.scrollTop = element.scrollHeight;
    }
  }

  /**
   * Play notification sound for new messages
   */
  playNotificationSound(): void {
    // Optional: Add a notification sound
    // const audio = new Audio('/assets/sounds/notification.mp3');
    // audio.play().catch(e => console.log('Could not play sound'));
  }

  /**
   * Handle Enter key press in chat input
   */
  handleEnterKey(event: any): void {
    if (!event.shiftKey) {
      event.preventDefault();
      this.sendMessageToExistingTicket();
    }
    // if Shift is pressed, allow newline
  }

  /**
   * Format message time
   */
  formatMessageTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const hours = Math.floor(diff / (1000 * 60 * 60));
    
    if (hours < 24) {
      return date.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
    } else {
      return date.toLocaleDateString('it-IT', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  }

  /**
   * Get status badge color
   */
  getStatusColor(status: string): string {
    const colors: any = {
      'new': 'primary',
      'waiting': 'accent',
      'resolved': 'success',
      'closed': 'info',
      'deleted': 'warn'
    };
    return colors[status] || 'default';
  }

  /**
   * Get priority badge color
   */
  getPriorityColor(priority: string): string {
    const colors: any = {
      'high': 'warn',
      'medium': 'accent',
      'low': 'primary',
      'unassigned': 'default'
    };
    return colors[priority] || 'default';
  }

  onClose(): void {
    this.stopPolling();
    this.dialogRef.close();
  }

  // ==================== LEADS METHODS (unchanged) ====================
  
  cambiaStato(stato: any, lead: any) {
    if (stato.value.stato == 'Appuntamento Preso') {
      this.showCalendar = false;
    } else {
      this.showCalendar = true;
    }
  }

  salvaLead() {
    const dataSelezionata = this.form.get('data_appuntamento_mat')?.value;
    const oraSelezionata = this.form.get('ora_appuntamento_mat')?.value;
    const stato_id = this.form.get('stato_id')?.value;
    const id_lead = this.form.get('id_lead')?.value;
    const formData = new FormData();
    
    if (dataSelezionata && oraSelezionata) {
      const dataFormattata = new Date(dataSelezionata);
      const anno = dataFormattata.getFullYear();
      const mese = ('0' + (dataFormattata.getMonth() + 1)).slice(-2);
      const giorno = ('0' + dataFormattata.getDate()).slice(-2);
      const dataFinale = `${anno}-${mese}-${giorno}`;
      const oraFinale = `${oraSelezionata}:00`;
      
      formData.append('data_appuntamento', dataFinale);
      formData.append('ora_appuntamento', oraFinale);
      formData.append('id_lead', id_lead);
      formData.append('stato_id', stato_id.id);
      
      this.apiService.appuntamentoLead(formData).subscribe((risposta: any) => {
        if (risposta.status == 200) {
          window.location.reload();
        }
      });
    } else {
      formData.append('id_lead', id_lead);
      formData.append('stato_id', stato_id.id);
      
      this.apiService.appuntamentoLead(formData).subscribe((risposta: any) => {
        if (risposta.status == 200) {
          window.location.reload();
        }
      });
    }
  }

  cambiaAssegnazione(stato: any, lead: any) {
    console.log(this.data);
    console.log(stato.stato);
    console.log(lead);
    const formData2 = new FormData();

    formData2.append('id_user', stato.stato);
    formData2.append('id_lead', lead);
    
    this.apiService.updateAssegnazioneLead(formData2).subscribe((risposta: any) => {
      console.log(risposta);
    });
  }
}