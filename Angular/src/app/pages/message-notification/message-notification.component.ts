import { 
  Component, 
  OnInit, 
  OnDestroy, 
  inject, 
  computed, 
  signal, 
  effect 
} from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';
import { Subject, takeUntil, interval, switchMap, startWith } from 'rxjs';
import { MatSnackBar } from '@angular/material/snack-bar';

export interface NotificationMessage {
  id: number;
  notifica_html: SafeHtml;
  visualizzato: boolean;
  created_at: string;
  tipo?: 'info' | 'warning' | 'error' | 'success';
  priorita?: 'alta' | 'media' | 'bassa';
  titolo?: string;
  descrizione?: string;
}

@Component({
  selector: 'app-message-notification',
  templateUrl: './message-notification.component.html',
  styleUrls: ['./message-notification.component.scss'],
  standalone: false
  // Rimosse le animazioni per evitare interferenze
})
export class MessageNotificationComponent implements OnInit, OnDestroy {
  private readonly destroy$ = new Subject<void>();
  private readonly apiService = inject(ApiService);
  private readonly sanitizer = inject(DomSanitizer);
  private readonly snackBar = inject(MatSnackBar);
  
  // Signals per gestire lo stato reattivo
  protected readonly messages = signal<NotificationMessage[]>([]);
  protected readonly isLoading = signal(false);
  protected readonly isPanelOpen = signal(false);
  protected readonly selectedMessageId = signal<number | null>(null);
  protected readonly autoRefresh = signal(true);
  
  // Computed signals
  protected readonly counter = computed(() => this.messages().length);
  protected readonly hasUnreadMessages = computed(() => this.messages().some(msg => !msg.visualizzato));
  protected readonly sortedMessages = computed(() => 
    [...this.messages()].sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
  );
  
  constructor() {
    // Effect per auto-refresh delle notifiche
    effect(() => {
      if (this.autoRefresh()) {
        this.startAutoRefresh();
      }
    });
  }

  ngOnInit(): void {
    //console.log('🔔 MessageNotificationComponent initialized');
    
    // Test con dati mock per debugging
    this.messages.set([
      {
        id: 1,
        notifica_html: this.sanitizer.bypassSecurityTrustHtml('<p>Nuova notifica importante</p>'),
        visualizzato: false,
        created_at: new Date().toISOString(),
        tipo: 'error',
        priorita: 'alta',
        titolo: 'Errore Sistema',
        descrizione: 'Si è verificato un errore nel sistema che richiede la tua attenzione'
      },
      {
        id: 2,
        notifica_html: this.sanitizer.bypassSecurityTrustHtml('<p>Aggiornamento disponibile</p>'),
        visualizzato: false,
        created_at: new Date(Date.now() - 1000 * 60 * 30).toISOString(),
        tipo: 'warning',
        priorita: 'media',
        titolo: 'Aggiornamento',
        descrizione: 'È disponibile un nuovo aggiornamento dell\'applicazione'
      },
      {
        id: 3,
        notifica_html: this.sanitizer.bypassSecurityTrustHtml('<p>Operazione completata</p>'),
        visualizzato: true,
        created_at: new Date(Date.now() - 1000 * 60 * 60).toISOString(),
        tipo: 'success',
        priorita: 'bassa',
        titolo: 'Successo',
        descrizione: 'L\'operazione è stata completata con successo'
      },
      {
        id: 4,
        notifica_html: this.sanitizer.bypassSecurityTrustHtml('<p>Informazione generale</p>'),
        visualizzato: false,
        created_at: new Date(Date.now() - 1000 * 60 * 60 * 2).toISOString(),
        tipo: 'info',
        priorita: 'bassa',
        titolo: 'Informazione',
        descrizione: 'Questa è una notifica informativa di esempio'
      }
    ]);
    
    this.loadMessages();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private startAutoRefresh(): void {
    interval(30000) // Refresh ogni 30 secondi
      .pipe(
        startWith(0),
        switchMap(() => this.apiService.getMessageNotification()),
        takeUntil(this.destroy$)
      )
      .subscribe({
        next: (response: any) => this.processMessagesResponse(response),
        error: (error) => this.handleError('Errore nel caricamento delle notifiche', error)
      });
  }

  protected loadMessages(): void {
    //console.log('📥 Loading messages...');
    this.isLoading.set(true);
    
    this.apiService.getMessageNotification()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response: any) => {
          //console.log('📥 Messages loaded:', response);
          this.processMessagesResponse(response);
          this.isLoading.set(false);
        },
        error: (error) => {
          console.error('📥 Error loading messages:', error);
          this.handleError('Errore nel caricamento delle notifiche', error);
          this.isLoading.set(false);
        }
      });
  }

  private processMessagesResponse(response: any): void {
    if (response?.body?.risposta) {
      const processedMessages = response.body.risposta.map((message: any) => ({
        ...message,
        notifica_html: this.sanitizer.bypassSecurityTrustHtml(message.notifica_html),
        tipo: this.determineMessageType(message),
        priorita: this.determineMessagePriority(message)
      }));
      
      this.messages.set(processedMessages);
    }
  }

  private determineMessageType(message: any): 'info' | 'warning' | 'error' | 'success' {
    // Logica per determinare il tipo di messaggio basata sul contenuto
    const content = message.notifica_html?.toLowerCase() || '';
    if (content.includes('error') || content.includes('errore')) return 'error';
    if (content.includes('warning') || content.includes('attenzione')) return 'warning';
    if (content.includes('success') || content.includes('completato')) return 'success';
    return 'info';
  }

  private determineMessagePriority(message: any): 'alta' | 'media' | 'bassa' {
    // Logica per determinare la priorità
    const content = message.notifica_html?.toLowerCase() || '';
    if (content.includes('urgente') || content.includes('importante')) return 'alta';
    if (content.includes('scadenza') || content.includes('deadline')) return 'media';
    return 'bassa';
  }

  protected togglePanel(): void {
    //console.log('🔔 Toggle panel clicked! Current state:', this.isPanelOpen());
    this.isPanelOpen.update(isOpen => !isOpen);
    //console.log('🔔 New panel state:', this.isPanelOpen());
    
    if (this.isPanelOpen()) {
      this.loadMessages();
      //console.log('🔔 Loading messages...');
    }
  }

  protected markAsRead(message: NotificationMessage): void {
    this.apiService.markReadMessage(message.id)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response: any) => {
          this.messages.update(messages => 
            messages.filter(msg => msg.id !== message.id)
          );
          
          this.snackBar.open('Notifica contrassegnata come letta', 'Chiudi', {
            duration: 3000,
            horizontalPosition: 'end',
            verticalPosition: 'top'
          });
        },
        error: (error) => this.handleError('Errore nell\'aggiornamento della notifica', error)
      });
  }

  protected markAllAsRead(): void {
    const unreadMessages = this.messages().filter(msg => !msg.visualizzato);
    
    if (unreadMessages.length === 0) {
      this.snackBar.open('Nessuna notifica da leggere', 'Chiudi', { duration: 2000 });
      return;
    }

    // Simula l'operazione di batch (da implementare nel backend)
    unreadMessages.forEach(message => this.markAsRead(message));
  }

  protected selectMessage(messageId: number): void {
    this.selectedMessageId.set(
      this.selectedMessageId() === messageId ? null : messageId
    );
  }

  protected closePanel(): void {
    this.isPanelOpen.set(false);
  }

  protected toggleAutoRefresh(): void {
    this.autoRefresh.update(current => !current);
  }

  private handleError(message: string, error: any): void {
    console.error(message, error);
    this.snackBar.open(message, 'Chiudi', {
      duration: 5000,
      panelClass: ['error-snackbar']
    });
  }

  protected getMessageIcon(message: NotificationMessage): string {
    switch (message.tipo) {
      case 'error': return 'error';
      case 'warning': return 'warning';
      case 'success': return 'check_circle';
      default: return 'info';
    }
  }

  protected getMessageColor(message: NotificationMessage): string {
    switch (message.tipo) {
      case 'error': return 'warn';
      case 'warning': return 'accent';
      case 'success': return 'primary';
      default: return '';
    }
  }

  protected formatDate(dateString: string): string {
    return new Date(dateString).toLocaleString('it-IT', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  protected trackByMessageId(index: number, message: NotificationMessage): number {
    return message.id;
  }
}