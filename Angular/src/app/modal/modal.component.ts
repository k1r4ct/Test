import { Component, Inject, OnInit, OnDestroy, ViewChild, ElementRef } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialog } from '@angular/material/dialog';
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

// Interface for file preview
interface FilePreview {
  file: File;
  name: string;
  size: number;
  type: string;
  preview?: string;
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
  
  // Attachment handling - Local buffer approach
  pendingAttachments: FilePreview[] = [];  // Files waiting to be uploaded
  isUploadingAttachments: boolean = false;
  maxFileSize: number = 10 * 1024 * 1024; // 10MB
  maxFiles: number = 5;
  
  // Blocked file extensions for security
  blockedExtensions: string[] = [
    'exe', 'bat', 'cmd', 'sh', 'php', 'js',
    'jar', 'app', 'deb', 'rpm', 'dmg', 'pkg',
    'com', 'scr', 'vbs', 'msi', 'dll'
  ];
  
  // File input reference
  @ViewChild('fileInput') fileInput!: ElementRef<HTMLInputElement>;
  
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
    @Inject(MAT_DIALOG_DATA) public data: any,
    private dialog: MatDialog // ADDED for attachment preview
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
    
    // Revoke object URLs for previews
    this.pendingAttachments.forEach(file => {
      if (file.preview) {
        URL.revokeObjectURL(file.preview);
      }
    });
  }

  // ==================== TICKET METHODS ====================

  /**
   * Initialize new ticket with contract data
   */
  initializeNewTicket(): void {
    if (this.data.contractData) {
      this.ticketTitle = `Contratto #${this.data.contractData.contractCode} - ${this.data.contractData.clientName}`;
      this.ticketStep = 'confirm';
      // Priority always 'unassigned' for SEU users
      this.ticketPriority = 'unassigned';
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
    // Poll every 5 seconds
    this.pollingSubscription = interval(5000).subscribe(() => {
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
    
    // Step 1: Send message first
    const messageData = {
      ticket_id: this.existingTicket.id,
      message: this.chatMessage.trim()
    };
    
    const sendSub = this.apiService.sendTicketMessage(messageData).subscribe(
      (response: any) => {
        if (response.response === 'ok' && response.body?.risposta) {
          const messageId = response.body.risposta.id;
          
          // Step 2: Upload attachments if any with the message_id
          if (this.pendingAttachments.length > 0) {
            this.uploadAttachmentsForMessage(this.existingTicket.id, messageId);
          } else {
            // No attachments, complete the process
            this.completeMessageSending();
          }
        } else {
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
      },
      (error) => {
        console.error('Error sending message:', error);
        this.isSendingMessage = false;
        this.snackBar.open(
          'Errore di connessione nell\'invio del messaggio',
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
   * Upload attachments for a specific message
   */
  private uploadAttachmentsForMessage(ticketId: number, messageId: number): void {
    const formData = new FormData();
    formData.append('ticket_id', ticketId.toString());
    formData.append('message_id', messageId.toString());
    
    // Add all files to FormData with array notation
    this.pendingAttachments.forEach(filePreview => {
      formData.append('attachments[]', filePreview.file);
    });
    
    this.isUploadingAttachments = true;
    
    this.apiService.uploadTicketAttachments(formData).subscribe(
      (response: any) => {
        this.isUploadingAttachments = false;
        
        if (response.response === 'ok') {
          console.log('Attachments uploaded successfully:', response.body?.attachments);
          this.completeMessageSending();
        } else {
          // Attachments failed but message was sent
          this.snackBar.open(
            'Messaggio inviato ma errore nel caricamento allegati',
            'Chiudi',
            {
              duration: 3000,
              horizontalPosition: 'center',
              verticalPosition: 'bottom',
              panelClass: ['warning-snackbar']
            }
          );
          this.completeMessageSending();
        }
      },
      (error) => {
        console.error('Error uploading attachments:', error);
        this.isUploadingAttachments = false;
        // Message sent but attachments failed
        this.snackBar.open(
          'Messaggio inviato ma errore nel caricamento allegati',
          'Chiudi',
          {
            duration: 3000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['warning-snackbar']
          }
        );
        this.completeMessageSending();
      }
    );
  }

  /**
   * Complete message sending process
   */
  private completeMessageSending(): void {
    // Clear message and attachments
    this.chatMessage = '';
    this.clearAttachments();
    this.isSendingMessage = false;
    
    // Reload messages to show new one
    this.loadTicketMessages();
    
    this.snackBar.open(
      'Messaggio inviato con successo!',
      'Chiudi',
      {
        duration: 2000,
        horizontalPosition: 'center',
        verticalPosition: 'bottom',
        panelClass: ['success-snackbar']
      }
    );
  }

  /**
   * Proceed to chat step
   */
  proceedToChat(): void {
    this.ticketStep = 'chat';
  }

  /**
   * Go back to confirmation step
   */
  goBackToConfirm(): void {
    this.ticketStep = 'confirm';
    // Clear attachments when going back
    this.clearAttachments();
  }

  /**
   * Create ticket with message
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

    // Step 1: Create ticket
    const createSub = this.apiService.createTicket(ticketData).subscribe(
      (response: any) => {
        if (response.response === 'ok' && response.body?.risposta?.id) {
          const ticketId = response.body.risposta.id;
          
          // Step 2: Send first message
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
        if (response.response === 'ok' && response.body?.risposta) {
          const messageId = response.body.risposta.id;
          
          // Step 3: Upload attachments if any
          if (this.pendingAttachments.length > 0) {
            this.uploadAttachmentsForNewTicket(ticketId, messageId);
          } else {
            // No attachments, complete the process
            this.completeTicketCreation(ticketId);
          }
        } else {
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
   * Upload attachments for new ticket first message
   */
  private uploadAttachmentsForNewTicket(ticketId: number, messageId: number): void {
    const formData = new FormData();
    formData.append('ticket_id', ticketId.toString());
    formData.append('message_id', messageId.toString());
    
    // Add all files with array notation
    this.pendingAttachments.forEach(filePreview => {
      formData.append('attachments[]', filePreview.file);
    });
    
    this.isUploadingAttachments = true;
    
    this.apiService.uploadTicketAttachments(formData).subscribe(
      (response: any) => {
        this.isUploadingAttachments = false;
        
        if (response.response === 'ok') {
          console.log('Attachments uploaded for new ticket:', response.body?.attachments);
        } else {
          console.error('Attachments upload failed but ticket was created');
        }
        
        // Complete regardless of attachment upload result
        this.completeTicketCreation(ticketId);
      },
      (error) => {
        console.error('Error uploading attachments:', error);
        this.isUploadingAttachments = false;
        // Complete even if attachments failed
        this.completeTicketCreation(ticketId);
      }
    );
  }

  /**
   * Complete ticket creation process
   */
  private completeTicketCreation(ticketId: number): void {
    this.isCreatingTicket = false;
    
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
  }

  // ==================== ATTACHMENT METHODS ====================

  /**
   * Trigger file input click
   */
  openFileSelector(): void {
    if (this.fileInput) {
      this.fileInput.nativeElement.click();
    }
  }

  /**
   * Handle file selection
   */
  onFilesSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
      this.addFiles(Array.from(input.files));
    }
    // Reset input value to allow selecting the same file again
    input.value = '';
  }

  /**
   * Add files to pending attachments
   */
  private addFiles(files: File[]): void {
    const availableSlots = this.maxFiles - this.pendingAttachments.length;
    
    if (availableSlots <= 0) {
      this.snackBar.open(
        `Massimo ${this.maxFiles} allegati consentiti`,
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
    
    const filesToAdd = files.slice(0, availableSlots);
    
    for (const file of filesToAdd) {
      // Validate file
      const validation = this.validateFile(file);
      if (!validation.valid) {
        this.snackBar.open(
          validation.message,
          'Chiudi',
          {
            duration: 3000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['warning-snackbar']
          }
        );
        continue;
      }
      
      // Create preview
      const filePreview: FilePreview = {
        file: file,
        name: file.name,
        size: file.size,
        type: file.type
      };
      
      // Generate preview for images
      if (this.isImage(file)) {
        const reader = new FileReader();
        reader.onload = (e: any) => {
          filePreview.preview = e.target.result;
        };
        reader.readAsDataURL(file);
      }
      
      this.pendingAttachments.push(filePreview);
    }
  }

  /**
   * Validate file
   */
  private validateFile(file: File): { valid: boolean; message: string } {
    // Check file size
    if (file.size > this.maxFileSize) {
      return {
        valid: false,
        message: `File "${file.name}" supera il limite di 10MB`
      };
    }
    
    // Check file extension
    const extension = file.name.split('.').pop()?.toLowerCase();
    if (extension && this.blockedExtensions.includes(extension)) {
      return {
        valid: false,
        message: `Tipo di file non consentito: .${extension}`
      };
    }
    
    return { valid: true, message: '' };
  }

  /**
   * Remove attachment from pending list
   */
  removeAttachment(index: number): void {
    const removed = this.pendingAttachments.splice(index, 1)[0];
    if (removed.preview) {
      URL.revokeObjectURL(removed.preview);
    }
  }

  /**
   * Clear all attachments
   */
  clearAttachments(): void {
    this.pendingAttachments.forEach(file => {
      if (file.preview) {
        URL.revokeObjectURL(file.preview);
      }
    });
    this.pendingAttachments = [];
  }

  /**
   * Check if file is an image
   */
  private isImage(file: File): boolean {
    return file.type.startsWith('image/');
  }

  /**
   * Get file icon based on type
   */
  getFileIcon(file: FilePreview): string {
    if (file.type.startsWith('image/')) return 'image';
    if (file.type === 'application/pdf') return 'picture_as_pdf';
    if (file.type.includes('word')) return 'description';
    if (file.type.includes('excel') || file.type.includes('spreadsheet')) return 'table_chart';
    if (file.type.includes('powerpoint') || file.type.includes('presentation')) return 'slideshow';
    if (file.type.includes('zip') || file.type.includes('rar')) return 'folder_zip';
    return 'insert_drive_file';
  }

  /**
   * Format file size
   */
  formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  }

  /**
   * Get attachment icon based on file name/extension
   * Used in template for displaying attachment icons in messages
   */
  getAttachmentIcon(fileName: string): string {
    if (!fileName) return 'insert_drive_file';
    
    const extension = fileName.split('.').pop()?.toLowerCase() || '';
    
    // Map extensions to Material icons
    const iconMap: { [key: string]: string } = {
      // Images
      'jpg': 'image',
      'jpeg': 'image', 
      'png': 'image',
      'gif': 'image',
      'bmp': 'image',
      'svg': 'image',
      'webp': 'image',
      
      // Documents
      'pdf': 'picture_as_pdf',
      'doc': 'description',
      'docx': 'description',
      'txt': 'text_snippet',
      'rtf': 'description',
      'odt': 'description',
      
      // Spreadsheets
      'xls': 'table_chart',
      'xlsx': 'table_chart',
      'csv': 'table_chart',
      'ods': 'table_chart',
      
      // Presentations
      'ppt': 'slideshow',
      'pptx': 'slideshow',
      'odp': 'slideshow',
      
      // Archives
      'zip': 'folder_zip',
      'rar': 'folder_zip',
      '7z': 'folder_zip',
      'tar': 'folder_zip',
      'gz': 'folder_zip',
      
      // Code
      'html': 'code',
      'css': 'code',
      'js': 'code',
      'ts': 'code',
      'json': 'code',
      'xml': 'code',
      
      // Default
      'default': 'insert_drive_file'
    };
    
    return iconMap[extension] || iconMap['default'];
  }

  /**
   * Handle drag over
   */
  onDragOver(event: DragEvent): void {
    event.preventDefault();
    event.stopPropagation();
  }

  /**
   * Handle drop
   */
  onDrop(event: DragEvent): void {
    event.preventDefault();
    event.stopPropagation();
    
    const files = event.dataTransfer?.files;
    if (files && files.length > 0) {
      this.addFiles(Array.from(files));
    }
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
   * Get message author name (nome + cognome) without email
   * NEW METHOD - Replaces inline logic in template
   */
  getMessageAuthor(message: any): string {
    if (message.user) {
      // First check if we have nome and cognome
      if (message.user.nome && message.user.cognome) {
        return `${message.user.nome} ${message.user.cognome}`;
      }
      // Check for name property (sometimes used instead of nome)
      if (message.user.name && message.user.cognome) {
        return `${message.user.name} ${message.user.cognome}`;
      }
      // Check for ragione_sociale (business name)
      if (message.user.ragione_sociale) {
        return message.user.ragione_sociale;
      }
    }
    
    // Fallback to user_name if available
    if (message.user_name && message.user_name !== 'Utente Sconosciuto') {
      return message.user_name;
    }
    
    // Last resort fallback
    return 'Utente';
  }

  /**
   * Open attachment preview modal
   * NEW METHOD - Opens preview modal for attachments
   */
  openAttachmentPreview(attachment: any, isPending: boolean = false): void {
    // Import the preview component dynamically
    import('../attachment-preview-modal/attachment-preview-modal.component').then(m => {
      const dialogRef = this.dialog.open(m.AttachmentPreviewModalComponent, {
        width: '90vw',
        maxWidth: '1200px',
        height: '90vh',
        data: {
          attachment: attachment,
          isPending: isPending
        },
        panelClass: 'attachment-preview-dialog'
      });
    }).catch(err => {
      console.error('Error loading preview component:', err);
      // Fallback: just download the file if it's not pending
      if (!isPending && attachment.id) {
        window.open(`/api/attachments/${attachment.id}/download`, '_blank');
      }
    });
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
    this.clearAttachments();
    this.dialogRef.close();
  }

  // ==================== LEADS METHODS ====================
  
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