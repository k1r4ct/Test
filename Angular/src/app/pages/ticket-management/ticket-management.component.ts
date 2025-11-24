import { Component, OnInit, OnDestroy, AfterViewChecked, HostListener, ElementRef, ViewChild } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatDialog } from '@angular/material/dialog';
import { Subscription, Subject, of, Observable } from 'rxjs';
import { debounceTime, distinctUntilChanged, switchMap, tap, catchError, map } from 'rxjs/operators';

export interface Ticket {
  id: number;
  ticket_number: string;
  title: string;
  description: string;
  status: 'new' | 'waiting' | 'resolved' | 'closed' | 'deleted';
  previous_status?: string;
  priority: 'low' | 'medium' | 'high' | 'unassigned';
  contract_id: number;
  contract_code: string;
  created_by_user_id: number;
  created_by_user_name?: string;
  assigned_to_user_id?: number;
  assigned_to_user_name?: string;
  customer_name: string;
  customer_initials: string;
  avatar_color: string;
  created_at: string;
  updated_at: string;
  product_name?: string;
  seu_name?: string;
  messages?: TicketMessage[];
  attachments_count?: number;
  has_attachments?: boolean;
}

export interface TicketMessage {
  id: number;
  ticket_id: number;
  user_id: number;
  user_name: string;
  user_role: string;
  role_letter?: string; 
  message: string;
  message_type: 'text' | 'attachment' | 'status_change';  
  attachment_path?: string;
  attachment_name?: string;
  old_status?: string;  
  new_status?: string;  
  created_at: string;
  has_attachments?: boolean;
  attachments?: TicketAttachment[];
  user?: any;
}

export interface TicketAttachment {
  id: number;
  ticket_id: number;
  ticket_message_id?: number;
  user_id: number;
  file_name: string;
  original_name: string;
  file_path: string;
  file_size: number;
  mime_type: string;
  hash: string;
  created_at: string;
  user_name?: string;
  formatted_size?: string;
  is_image?: boolean;
  is_pdf?: boolean;
  is_document?: boolean;
}

export interface TicketFilters {
  contractId: string;
  contractCode: string;
  product: string[];
  priority: string[];
  status: string[];
  assignedTo: string[];
  customer: string;
  seu: string[];
  generatedBy: string[];
  openingDate: string;
  contract: string[];
}

@Component({
  selector: 'app-ticket-management',
  templateUrl: './ticket-management.component.html',
  styleUrls: ['./ticket-management.component.css'],
  standalone: false,
})
export class TicketManagementComponent implements OnInit, OnDestroy, AfterViewChecked {
  private subscriptions: Subscription[] = [];
  
  tickets: Ticket[] = [];
  filteredTickets: Ticket[] = [];
  currentUser: any;
  userRole: number = 0;
  isAdmin: boolean = false;
  
  filters: TicketFilters = {
    contractId: '',
    contractCode: '',
    product: [],
    priority: [],
    status: ['new', 'waiting', 'resolved'],
    assignedTo: [],
    customer: '',
    seu: [],
    generatedBy: [],
    openingDate: '',
    contract: []
  };

  statusColorMap: Record<string, string> = {
    'new': '#2196F3',
    'waiting': '#9C27B0',
    'resolved': '#4CAF50',
    'closed': '#00bcd4',
    'deleted': '#f44336'
  };

  showFilters: boolean = true;
  showStatusDropdown: boolean = false;
  showProductDropdown: boolean = false;
  showPriorityDropdown: boolean = false;
  showSeuDropdown: boolean = false;
  showGeneratedByDropdown: boolean = false;
  showAssignedToDropdown: boolean = false;
  showPriorityDropdownForTicket: number | null = null;
  showContractDropdown: boolean = false;
  showClosedColumn: boolean = false;
  showDeletedColumn: boolean = false;
  
  selectedTicketsForDeletion: Set<number> = new Set<number>();
  
  selectedTicket: Ticket | null = null;
  showTicketModal: boolean = false;
  showNewTicketModal: boolean = false;
  showValidationError: boolean = false;
  isShaking: boolean = false;
  minimizedTickets: Set<number> = new Set<number>();

  isDragging: boolean = false;
  currentDraggingTicket: Ticket | null = null;

  productSearchQuery: string = '';
  seuSearchQuery: string = '';
  generatedBySearchQuery: string = '';
  assignedToSearchQuery: string = '';

  @ViewChild('contractSearchInput') contractSearchInput?: ElementRef<HTMLInputElement>;

  filteredProducts: string[] = [];
  filteredSeuList: any[] = [];
  filteredGeneratorsList: any[] = [];
  filteredUsers: any[] = [];
  filteredContracts: any[] = [];
  contractSearchQuery: string = '';
  isLoadingContractOptions: boolean = false;
  private contractSearch$ = new Subject<string>();
  private contractSearchSub?: Subscription;
  private contractSearchInitialized = false;
  
  newTicket = {
    title: '',
    description: '',
    priority: 'medium',
    contract_id: null
  };

  newMessage: string = '';
  isLoadingMessages: boolean = false;
  
  newTicketAttachments: File[] = [];
  isUploadingNewTicketAttachments: boolean = false;
  
  replyAttachments: File[] = [];
  isUploadingReplyAttachments: boolean = false;
  
  ticketAttachments: TicketAttachment[] = [];
  isLoadingAttachments: boolean = false;
  
  maxFiles: number = 5;
  maxFileSize: number = 10 * 1024 * 1024; // 10MB

  columns = [
    {
      id: 'new',
      title: 'Nuovo',
      icon: 'fas fa-plus-circle',
      color: '#2196F3',
      count: 0,
      hidden: false
    },
    {
      id: 'waiting',
      title: 'In Lavorazione',
      icon: 'fas fa-clock',
      color: '#9c27b0',
      count: 0,
      hidden: false
    },
    {
      id: 'resolved',
      title: 'Risolto',
      icon: 'fas fa-check-circle',
      color: '#4caf50',
      count: 0,
      hidden: false
    },
    {
      id: 'closed',
      title: 'Chiuso',
      icon: 'fas fa-archive',
      color: '#00bcd4',
      count: 0,
      hidden: true
    },
    {
      id: 'deleted',
      title: 'Cancellato',
      icon: 'fas fa-trash',
      color: '#f44336',
      count: 0,
      hidden: true
    }
  ];

  products: string[] = [];
  seuList: any[] = [];
  generatorsList: any[] = [];
  
  priorities = [
    { value: 'high', label: 'Alta', color: '#dc3545' },
    { value: 'medium', label: 'Media', color: '#ffc107' },
    { value: 'low', label: 'Bassa', color: '#28a745' },
    { value: 'unassigned', label: 'N/A', color: '#9e9e9e' }
  ];

  contracts: any[] = [];
  users: any[] = [];

  constructor(
    private apiService: ApiService,
    private snackBar: MatSnackBar,
    private dialog: MatDialog
  ) {}

  ngOnInit() {
    this.loadCurrentUser();
    this.loadMinimizedTicketsFromStorage();
  }

  ngOnDestroy() {
    this.subscriptions.forEach(sub => sub.unsubscribe());
    this.contractSearch$.complete();
  }

  ngAfterViewChecked() {
    // Removed automatic positioning to let CSS handle it
  }

  positionDropdowns() {
    // Positioning is handled by CSS with position: absolute
    // This avoids conflicts and positioning issues
  }

  @HostListener('window:resize', ['$event'])
  onResize(event: any) {
    this.positionDropdowns();
  }

  @HostListener('window:scroll', ['$event'])
  onScroll(event: any) {
    this.closeAllDropdowns();
    this.closePriorityDropdown();
  }

  isStatusChangeMessage(message: TicketMessage): boolean {
    return message.message_type === 'status_change';
  }

  getMessageAuthor(message: TicketMessage): string {
    let displayName = '';

    if (message.user_name && message.user_name.trim().length > 0) {
      displayName = message.user_name.trim();
    } else {
      const anyMsg: any = message as any;
      const user = anyMsg.user;
      if (user) {
        const fullName = `${user.name || ''} ${user.cognome || ''}`.trim();
        if (fullName) {
          displayName = fullName;
        } else if (user.ragione_sociale) {
          displayName = user.ragione_sociale;
        } else if (user.email) {
          displayName = user.email;
        }
      }
    }

    if (!displayName) {
      displayName = 'Utente';
    }

    if (this.currentUser && message.user_id === this.currentUser.id) {
      return `${displayName} (tu)`;
    }

    return displayName;
  }

  getColumnTitle(status: string): string {
    const column = this.columns.find(c => c.id === status);
    return column ? column.title : '';
  }

  getColumnByStatus(status: string): any {
    return this.columns.find(c => c.id === status);
  }

  getStatusChangeGradient(message: TicketMessage): string {
    if (!this.selectedTicket) {
      return `linear-gradient(135deg, #3a3939ff 0%, #ffffffff 100%)`;
    }
    
    const currentStatus = this.selectedTicket.status || 'waiting';
    const statusColor = this.statusColorMap[currentStatus] || this.statusColorMap['waiting'];
    
    return `linear-gradient(180deg, ${statusColor} 0%, rgb(20 20 20) 130%)`;
  }

  canManageTickets(): boolean {
    return this.userRole === 1 || this.userRole === 5;
  }

  canDragTicket(ticket: Ticket): boolean {
    if (this.userRole === 1) {
      return true;
    }

    if (this.userRole === 4 || this.userRole === 5) {
      return ticket.assigned_to_user_id === this.currentUser.id || ticket.status === 'new';
    }

    return false;
  }

  canDropOnColumn(ticket: Ticket, targetStatus: string): boolean {
    if (this.userRole === 1) {
      return true;
    }

    if (this.userRole === 5) {
      if (ticket.assigned_to_user_id === this.currentUser.id && 
          ticket.status !== 'new' && 
          targetStatus === 'new') {
        return false;
      }
    }

    return this.canDragTicket(ticket);
  }

  canReplyToTicket(ticket: Ticket | null): boolean {
    if (!ticket) {
      return false;
    }

    if (this.userRole === 1) {
      return true;
    }

    if (this.userRole === 4 || this.userRole === 5) {
      return ticket.assigned_to_user_id === this.currentUser.id;
    }

    return false;
  }

  shouldShowBanIcon(ticket: Ticket): boolean {
    return this.isDragging && this.currentDraggingTicket?.id === ticket.id && !this.canDragTicket(ticket);
  }

  // ==================== ATTACHMENT METHODS ====================

  canAttachToTicket(ticket: Ticket | null): boolean {
    if (!ticket) return false;
    
    if (this.isAdmin) return true;
    
    if (ticket.status === 'new' || !ticket.assigned_to_user_id) {
      return true;
    }
    
    return ticket.assigned_to_user_id === this.currentUser?.id;
  }

  canDeleteAttachment(attachment: TicketAttachment): boolean {
    if (!attachment || !this.currentUser) return false;
    
    if (this.isAdmin) return true;
    
    return attachment.user_id === this.currentUser.id;
  }

  loadTicketAttachments(): void {
    if (!this.selectedTicket) return;
    
    this.isLoadingAttachments = true;
    
    const loadSub = this.apiService.getTicketAttachments(this.selectedTicket.id).subscribe(
      (response: any) => {
        if (response.response === 'ok' && response.body?.attachments) {
          this.ticketAttachments = response.body.attachments;
        }
        this.isLoadingAttachments = false;
      },
      (error) => {
        console.error('Error loading attachments:', error);
        this.isLoadingAttachments = false;
      }
    );
    
    this.subscriptions.push(loadSub);
  }

  onNewTicketFilesSelected(event: any): void {
    const files: FileList = event.target.files;
    if (!files || files.length === 0) return;
    
    const filesArray = Array.from(files);
    
    if (this.newTicketAttachments.length + filesArray.length > this.maxFiles) {
      this.snackBar.open(
        `Puoi caricare massimo ${this.maxFiles} file in totale`,
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
    
    const oversizedFiles = filesArray.filter(f => f.size > this.maxFileSize);
    if (oversizedFiles.length > 0) {
      this.snackBar.open(
        `Alcuni file superano la dimensione massima di ${this.formatFileSize(this.maxFileSize)}`,
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
    
    this.newTicketAttachments = [...this.newTicketAttachments, ...filesArray];
  }

  onReplyFilesSelected(event: any): void {
    const files: FileList = event.target.files;
    if (!files || files.length === 0) return;
    
    const filesArray = Array.from(files);
    
    if (this.replyAttachments.length + filesArray.length > this.maxFiles) {
      this.snackBar.open(
        `Puoi caricare massimo ${this.maxFiles} file in totale`,
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
    
    const oversizedFiles = filesArray.filter(f => f.size > this.maxFileSize);
    if (oversizedFiles.length > 0) {
      this.snackBar.open(
        `Alcuni file superano la dimensione massima di ${this.formatFileSize(this.maxFileSize)}`,
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
    
    this.replyAttachments = [...this.replyAttachments, ...filesArray];
  }

  getAttachmentIcon(fileName: string): string {
    if (!fileName) return 'fa-file';
    
    const extension = fileName.split('.').pop()?.toLowerCase() || '';
    
    const iconMap: { [key: string]: string } = {
      'jpg': 'fa-file-image', 'jpeg': 'fa-file-image', 'png': 'fa-file-image', 'gif': 'fa-file-image',
      'pdf': 'fa-file-pdf',
      'doc': 'fa-file-word', 'docx': 'fa-file-word',
      'xls': 'fa-file-excel', 'xlsx': 'fa-file-excel',
      'ppt': 'fa-file-powerpoint', 'pptx': 'fa-file-powerpoint',
      'txt': 'fa-file-alt',
      'zip': 'fa-file-archive', 'rar': 'fa-file-archive',
    };
    
    return iconMap[extension] || 'fa-file';
  }

  formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  }

  /**
   * Open attachment preview modal
   */
  openAttachmentPreview(attachment: any, isPending: boolean = false): void {
    import('../../attachment-preview-modal/attachment-preview-modal.component').then(m => {
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
      if (!isPending && attachment.id) {
        this.downloadAttachment(attachment);
      }
    });
  }

  /**
   * Open attachment preview for pending files (before upload)
   */
  openPendingAttachmentPreview(file: File, index: number): void {
    const filePreview = {
      file: file,
      name: file.name,
      size: file.size,
      type: file.type,
      preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : undefined
    };
    
    this.openAttachmentPreview(filePreview, true);
  }

  downloadAttachment(attachment: TicketAttachment): void {
    this.apiService.downloadTicketAttachment(attachment.id).subscribe(
      (blob: Blob) => {
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = attachment.original_name;
        link.click();
        window.URL.revokeObjectURL(url);
        
        this.snackBar.open('Download avviato', 'Chiudi', {
          duration: 2000,
          horizontalPosition: 'center',
          verticalPosition: 'bottom',
          panelClass: ['success-snackbar']
        });
      },
      (error) => {
        this.snackBar.open('Errore durante il download', 'Chiudi', {
          duration: 3000,
          horizontalPosition: 'center',
          verticalPosition: 'bottom',
          panelClass: ['error-snackbar']
        });
      }
    );
  }

  deleteAttachment(attachment: TicketAttachment): void {
    if (!this.canDeleteAttachment(attachment)) {
      this.snackBar.open('Non hai i permessi per eliminare questo allegato', 'Chiudi', {
        duration: 3000,
        horizontalPosition: 'center',
        verticalPosition: 'bottom',
        panelClass: ['error-snackbar']
      });
      return;
    }
    
    if (!confirm(`Eliminare "${attachment.original_name}"?`)) {
      return;
    }
    
    this.apiService.deleteTicketAttachment(attachment.id).subscribe(
      (response: any) => {
        if (response.response === 'ok') {
          this.snackBar.open('Allegato eliminato', 'Chiudi', {
            duration: 2000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['success-snackbar']
          });
          
          this.ticketAttachments = this.ticketAttachments.filter(a => a.id !== attachment.id);
          
          if (this.selectedTicket) {
            this.loadTicketMessages(this.selectedTicket.id);
          }
        }
      },
      (error) => {
        this.snackBar.open('Errore durante l\'eliminazione', 'Chiudi', {
          duration: 3000,
          horizontalPosition: 'center',
          verticalPosition: 'bottom',
          panelClass: ['error-snackbar']
        });
      }
    );
  }

  // ==================== END ATTACHMENT METHODS ====================

  loadCurrentUser() {
    const userSub = this.apiService.PrendiUtente().subscribe((userData: any) => {
      this.currentUser = userData.user;
      this.userRole = userData.user.role.id;
      this.isAdmin = this.userRole === 1;
      this.setupContractSearchPipeline();
      this.loadInitialData();
      this.loadTickets();
    });
    
    this.subscriptions.push(userSub);
  }

  loadInitialData() {
    const productsSub = this.apiService.ListaProdotti().subscribe((response: any) => {
      if (response.body && response.body.prodotti) {
        this.products = response.body.prodotti.map((p: any) => p.descrizione);
        this.filteredProducts = [...this.products];
      }
    });
    this.subscriptions.push(productsSub);

    const usersSub = this.apiService.getAllUser().subscribe((response: any) => {
      if (response.body && response.body.risposta) {
        this.users = response.body.risposta.filter((user: any) => 
          user.role.id === 1 || user.role.id === 5
        );
        this.filteredUsers = [...this.users];
        
        const seuUsers = response.body.risposta.filter((user: any) => 
          user.role.id === 2 || user.role.id === 4
        );
        this.seuList = seuUsers.map((user: any) => ({
          id: user.id,
          name: `${user.name || ''} ${user.cognome || ''}`.trim() || user.email
        }));
        this.filteredSeuList = [...this.seuList];
        
        this.generatorsList = response.body.risposta.map((user: any) => ({
          id: user.id,
          name: `${user.name || ''} ${user.cognome || ''}`.trim() || user.email
        }));
        this.filteredGeneratorsList = [...this.generatorsList];
      }
    });
    this.subscriptions.push(usersSub);
  }

  loadTickets() {
    const ticketsSub = this.apiService.getTickets().subscribe((response: any) => {
      if (response && response.body && response.body.risposta) {
        this.tickets = this.processTicketsData(response.body.risposta);
        this.applyFilters();
      }
    }, error => {
      this.snackBar.open(
        'Errore nel caricamento dei ticket',
        'Chiudi',
        { 
          duration: 3000,
          horizontalPosition: 'center',
          verticalPosition: 'bottom',
          panelClass: ['error-snackbar']
        }
      );
    });
    this.subscriptions.push(ticketsSub);
  }

  processTicketsData(ticketsData: any[]): Ticket[] {
    return ticketsData.map(ticket => {
      let seuName = 'N/A';
      if (ticket.contract?.inserito_da_user_id) {
        const seuUser = this.seuList.find(seu => seu.id === ticket.contract.inserito_da_user_id);
        if (seuUser) {
          seuName = seuUser.name;
        } else {
          const allUser = this.generatorsList.find(user => user.id === ticket.contract.inserito_da_user_id);
          if (allUser) {
            seuName = allUser.name;
          } else {
            seuName = `User ID: ${ticket.contract.inserito_da_user_id}`;
          }
        }
      }
      
      return {      
        id: ticket.id,
        ticket_number: ticket.ticket_number || `TK-${ticket.id.toString().padStart(3, '0')}`,
        title: ticket.title,
        description: ticket.description,
        status: ticket.status,
        priority: ticket.priority,
        contract_id: ticket.contract_id,
        contract_code: ticket.contract?.codice_contratto || 'N/A',
        created_by_user_id: ticket.created_by_user_id,
        created_by_user_name: ticket.created_by?.name ? 
          `${ticket.created_by.name} ${ticket.created_by.cognome || ''}`.trim() : 
          ticket.created_by?.email || 'N/A',
        assigned_to_user_id: ticket.assigned_to_user_id,
        assigned_to_user_name: ticket.assigned_to?.name ? 
          `${ticket.assigned_to.name} ${ticket.assigned_to.cognome || ''}`.trim() : 
          ticket.assigned_to?.email || null,
        customer_name: ticket.contract?.customer_data?.nome && ticket.contract?.customer_data?.cognome ?
          `${ticket.contract.customer_data.nome} ${ticket.contract.customer_data.cognome}` :
          ticket.contract?.customer_data?.ragione_sociale || 'N/A',
        customer_initials: this.getInitials(
          ticket.contract?.customer_data?.nome && ticket.contract?.customer_data?.cognome ?
          `${ticket.contract.customer_data.nome} ${ticket.contract.customer_data.cognome}` :
          ticket.contract?.customer_data?.ragione_sociale || 'N/A'
        ),
        avatar_color: this.getRandomColor(),
        created_at: ticket.created_at,
        updated_at: ticket.updated_at,
        product_name: ticket.contract?.product?.descrizione || 'N/A',
        seu_name: seuName,
        messages: ticket.messages || [],
        attachments_count: ticket.attachments_count || ticket.attachment_count || 0,
        has_attachments: (ticket.attachments_count || ticket.attachment_count || 0) > 0
      };
    });
  }

  getInitials(fullName: string): string {
    if (!fullName || fullName === 'N/A') {
      return '??';
    }
    return fullName.split(' ')
      .map(name => name.charAt(0).toUpperCase())
      .join('')
      .substring(0, 2);
  }

  getRandomColor(): string {
    const colors = ['#ff6b6b', '#4ecdc4', '#95afc0', '#f368e0', '#feca57', '#00d2d3'];
    return colors[Math.floor(Math.random() * colors.length)];
  }

  getUserColor(userId: number): string {
    const colors = ['#ff6b6b', '#4ecdc4', '#95afc0', '#f368e0', '#feca57', '#00d2d3'];
    return colors[userId % colors.length];
  }

  applyFilters() {
    this.filteredTickets = this.tickets.filter(ticket => {
      if (this.filters.contractId) {
        const ticketIdStr = ticket.contract_id.toString();
        const filterIdStr = this.filters.contractId.toString();
        if (!ticketIdStr.startsWith(filterIdStr)) {
          return false;
        }
      }
      
      if (this.filters.contractCode && 
          !ticket.contract_code.toLowerCase().includes(this.filters.contractCode.toLowerCase())) {
        return false;
      }
      
      if (this.filters.product.length > 0 && !this.filters.product.includes(ticket.product_name || '')) {
        return false;
      }
      
      if (this.filters.priority.length > 0 && !this.filters.priority.includes(ticket.priority)) {
        return false;
      }
      
      if (this.filters.status.length > 0 && !this.filters.status.includes(ticket.status)) {
        return false;
      }
      
      if (this.filters.customer && 
          !ticket.customer_name.toLowerCase().includes(this.filters.customer.toLowerCase())) {
        return false;
      }
      
      if (this.filters.seu.length > 0) {
        const seuIds = this.filters.seu.map(id => parseInt(id));
        const ticketSeuUser = this.seuList.find(s => 
          ticket.seu_name && ticket.seu_name.toLowerCase().includes(s.name.toLowerCase())
        );
        if (!ticketSeuUser || !seuIds.includes(ticketSeuUser.id)) {
          return false;
        }
      }
      
      if (this.filters.generatedBy.length > 0) {
        const generatorIds = this.filters.generatedBy.map(id => parseInt(id));
        if (!ticket.created_by_user_id || !generatorIds.includes(ticket.created_by_user_id)) {
          return false;
        }
      }
      
      if (this.filters.assignedTo.length > 0) {
        if (this.filters.assignedTo.includes('0')) {
          if (ticket.status !== 'new' && ticket.assigned_to_user_id) {
            return false;
          }
        } else {
          const assignedIds = this.filters.assignedTo.map(id => parseInt(id));
          if (ticket.status === 'new' || !ticket.assigned_to_user_id || !assignedIds.includes(ticket.assigned_to_user_id)) {
            return false;
          }
        }
      }
      
      if (this.filters.openingDate) {
        const filterDate = new Date(this.filters.openingDate);
        const ticketDate = new Date(ticket.created_at);
        
        const filterDateString = filterDate.toISOString().split('T')[0];
        const ticketDateString = ticketDate.toISOString().split('T')[0];
        
        if (filterDateString !== ticketDateString) {
          return false;
        }
      }
      
      return true;
    });
    
    this.sortTicketsByPriority();
    this.updateColumnCounts();
  }

  updateColumnCounts() {
    this.columns.forEach(column => {
      column.count = this.filteredTickets.filter(ticket => ticket.status === column.id).length;
    });
  }

  getTicketsForColumn(columnId: string): Ticket[] {
    return this.filteredTickets.filter(ticket => ticket.status === columnId);
  }

  toggleFilters() {
    this.showFilters = !this.showFilters;
  }

  clearFilters() {
    this.filters = {
      contractId: '',
      contractCode: '',
      product: [],
      priority: [],
      status: [],
      assignedTo: [],
      customer: '',
      seu: [],
      generatedBy: [],
      openingDate: '',
      contract: []
    };
    this.applyFilters();
  }

  // ==================== PRIORITY CHANGE METHODS ====================

  togglePriorityDropdownForTicket(ticketId: number, event: Event): void {
    event.stopPropagation();
    
    if (this.showPriorityDropdownForTicket === ticketId) {
      this.showPriorityDropdownForTicket = null;
    } else {
      this.showPriorityDropdownForTicket = ticketId;
    }
  }

  isPriorityDropdownOpen(ticketId: number): boolean {
    return this.showPriorityDropdownForTicket === ticketId;
  }

  closePriorityDropdown(): void {
    this.showPriorityDropdownForTicket = null;
  }

  changeTicketPriority(ticket: Ticket, newPriority: string, event: Event): void {
    event.stopPropagation();
    
    if (ticket.priority === newPriority) {
      this.closePriorityDropdown();
      return;
    }
    
    const oldPriority = ticket.priority;
    const oldLabel = this.getPriorityLabel(oldPriority);
    const newLabel = this.getPriorityLabel(newPriority);
    
    const updateData = {
      ticket_id: ticket.id,
      priority: newPriority
    };
    
    const prioritySub = this.apiService.updateTicketPriority(updateData).subscribe(
      (response: any) => {
        ticket.priority = newPriority as any;
        this.sortTicketsByPriority();
        this.closePriorityDropdown();
        
        this.snackBar.open(
          `Priorità ticket ${ticket.ticket_number} cambiata da ${oldLabel} a ${newLabel}`,
          'Chiudi',
          { 
            duration: 3000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['success-snackbar']
          }
        );
      },
      error => {
        if (error.status === 204 || error.status === 200) {
          ticket.priority = newPriority as any;
          this.sortTicketsByPriority();
          this.closePriorityDropdown();
          
          this.snackBar.open(
            `Priorità ticket ${ticket.ticket_number} cambiata da ${oldLabel} a ${newLabel}`,
            'Chiudi',
            { 
              duration: 3000,
              horizontalPosition: 'center',
              verticalPosition: 'bottom',
              panelClass: ['success-snackbar']
            }
          );
        } else {
          this.snackBar.open(
            'Errore nell\'aggiornamento della priorità',
            'Chiudi',
            { 
              duration: 3000,
              horizontalPosition: 'center',
              verticalPosition: 'bottom',
              panelClass: ['error-snackbar']
            }
          );
        }
      }
    );
    
    this.subscriptions.push(prioritySub);
  }

  // ==================== COLUMN VISIBILITY METHODS ====================

  toggleClosedColumn(): void {
    this.showClosedColumn = !this.showClosedColumn;
    
    const closedColumn = this.columns.find(c => c.id === 'closed');
    if (closedColumn) {
      closedColumn.hidden = !this.showClosedColumn;
    }
    
    if (this.showClosedColumn) {
      if (!this.filters.status.includes('closed')) {
        this.filters.status.push('closed');
      }
    } else {
      const index = this.filters.status.indexOf('closed');
      if (index > -1) {
        this.filters.status.splice(index, 1);
      }
    }
    
    this.applyFilters();
  }

  toggleDeletedColumn(): void {
    if (!this.isAdmin) return;
    
    this.showDeletedColumn = !this.showDeletedColumn;
    
    const deletedColumn = this.columns.find(c => c.id === 'deleted');
    if (deletedColumn) {
      deletedColumn.hidden = !this.showDeletedColumn;
    }
    
    if (this.showDeletedColumn) {
      if (!this.filters.status.includes('deleted')) {
        this.filters.status.push('deleted');
      }
    } else {
      const index = this.filters.status.indexOf('deleted');
      if (index > -1) {
        this.filters.status.splice(index, 1);
      }
    }
    
    this.applyFilters();
  }

  getDeletedButtonText(): string {
    return this.showDeletedColumn ? 'Nascondi Cancellati' : 'Mostra Cancellati';
  }

  canCloseTicket(ticket: Ticket): boolean {
    if (this.isAdmin) return true;
    return ticket.assigned_to_user_id === this.currentUser.id;
  }

  closeTicket(ticket: Ticket, event?: Event): void {
    if (event) {
      event.stopPropagation();
    }
    
    if (!this.canCloseTicket(ticket)) {
      this.snackBar.open(
        'Solo gli amministratori o gli assegnatari possono chiudere i ticket',
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
    
    if (ticket.status !== 'resolved') {
      this.snackBar.open(
        'Solo i ticket risolti possono essere chiusi',
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
    
    const closeSub = this.apiService.closeTicket({ ticket_id: ticket.id }).subscribe(
      (response: any) => {
        ticket.status = 'closed';
        this.applyFilters();
        this.updateColumnCounts();
        
        this.snackBar.open(
          `Ticket ${ticket.ticket_number} archiviato e chiuso`,
          'Chiudi',
          { 
            duration: 3000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['success-snackbar']
          }
        );
      },
      error => {
        if (error.status === 204 || error.status === 200) {
          ticket.status = 'closed';
          this.applyFilters();
          this.updateColumnCounts();
          
          this.snackBar.open(
            `Ticket ${ticket.ticket_number} archiviato e chiuso`,
            'Chiudi',
            { 
              duration: 3000,
              horizontalPosition: 'center',
              verticalPosition: 'bottom',
              panelClass: ['success-snackbar']
            }
          );
        } else {
          this.snackBar.open(
            'Errore nella chiusura del ticket',
            'Chiudi',
            { 
              duration: 3000,
              horizontalPosition: 'center',
              verticalPosition: 'bottom',
              panelClass: ['error-snackbar']
            }
          );
        }
      }
    );
    this.subscriptions.push(closeSub);
  }

  selectTicketForDeletion(ticketId: number, event: Event): void {
    event.stopPropagation();
    
    if (this.selectedTicketsForDeletion.has(ticketId)) {
      this.selectedTicketsForDeletion.delete(ticketId);
    } else {
      this.selectedTicketsForDeletion.add(ticketId);
    }
  }

  bulkDeleteTickets(): void {
    if (!this.isAdmin) return;
    
    if (this.selectedTicketsForDeletion.size === 0) {
      this.snackBar.open(
        'Seleziona almeno un ticket da cancellare',
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
    
    if (!confirm(`Sei sicuro di voler cancellare ${this.selectedTicketsForDeletion.size} ticket?`)) {
      return;
    }
    
    const ticketIds = Array.from(this.selectedTicketsForDeletion);
    
    const deleteSub = this.apiService.bulkDeleteTickets({ ticket_ids: ticketIds }).subscribe(
      (response: any) => {
        this.tickets.forEach(ticket => {
          if (ticketIds.includes(ticket.id)) {
            ticket.status = 'deleted';
          }
        });
        
        this.applyFilters();
        this.updateColumnCounts();
        this.selectedTicketsForDeletion.clear();
        
        const deletedCount = response?.deleted_count || ticketIds.length;
        this.snackBar.open(
          `${deletedCount} ticket cancellati con successo`,
          'Chiudi',
          { 
            duration: 3000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['success-snackbar']
          }
        );
      },
      error => {
        if (error.status === 204 || error.status === 200) {
          this.tickets.forEach(ticket => {
            if (ticketIds.includes(ticket.id)) {
              ticket.status = 'deleted';
            }
          });
          
          this.applyFilters();
          this.updateColumnCounts();
          this.selectedTicketsForDeletion.clear();
          
          this.snackBar.open(
            `${ticketIds.length} ticket cancellati con successo`,
            'Chiudi',
            { 
              duration: 3000,
              horizontalPosition: 'center',
              verticalPosition: 'bottom',
              panelClass: ['success-snackbar']
            }
          );
        } else {
          this.snackBar.open(
            'Errore nella cancellazione dei ticket',
            'Chiudi',
            { 
              duration: 3000,
              horizontalPosition: 'center',
              verticalPosition: 'bottom',
              panelClass: ['error-snackbar']
            }
          );
        }
      }
    );
    
    this.subscriptions.push(deleteSub);
  }

  // ==================== STATUS MULTI-SELECT METHODS ====================

  toggleStatusDropdown() {
    const wasOpen = this.showStatusDropdown;
    this.closeAllDropdowns();
    this.showStatusDropdown = !wasOpen;
    this.updateDropdownClasses();
  }

  toggleStatusFilter(statusId: string) {
    const index = this.filters.status.indexOf(statusId);
    
    if (index > -1) {
      this.filters.status.splice(index, 1);
      
      if (statusId === 'closed' && this.showClosedColumn) {
        this.showClosedColumn = false;
        const closedColumn = this.columns.find(c => c.id === 'closed');
        if (closedColumn) {
          closedColumn.hidden = true;
        }
      } else if (statusId === 'deleted' && this.showDeletedColumn) {
        this.showDeletedColumn = false;
        const deletedColumn = this.columns.find(c => c.id === 'deleted');
        if (deletedColumn) {
          deletedColumn.hidden = true;
        }
      }
    } else {
      this.filters.status.push(statusId);
      
      if (statusId === 'closed' && !this.showClosedColumn) {
        this.showClosedColumn = true;
        const closedColumn = this.columns.find(c => c.id === 'closed');
        if (closedColumn) {
          closedColumn.hidden = false;
        }
      } else if (statusId === 'deleted' && !this.showDeletedColumn && this.isAdmin) {
        this.showDeletedColumn = true;
        const deletedColumn = this.columns.find(c => c.id === 'deleted');
        if (deletedColumn) {
          deletedColumn.hidden = false;
        }
      }
    }
    
    this.applyFilters();
  }

  isStatusSelected(statusId: string): boolean {
    return this.filters.status.includes(statusId);
  }

  getSelectedStatusLabels(): string {
    if (this.filters.status.length === 0) {
      return 'Tutti gli stati';
    }
    const visibleColumns = this.columns.filter(c => !c.hidden);
    if (this.filters.status.length === visibleColumns.length) {
      return 'Tutti gli stati';
    }
    return this.filters.status
      .map(statusId => {
        const column = this.columns.find(c => c.id === statusId);
        return column ? column.title : '';
      })
      .filter(label => label)
      .join(', ');
  }

  // ==================== PRODUCT MULTI-SELECT METHODS ====================

  toggleProductDropdown() {
    const wasOpen = this.showProductDropdown;
    this.closeAllDropdowns();
    this.showProductDropdown = !wasOpen;
    if (this.showProductDropdown) {
      this.productSearchQuery = '';
      this.filteredProducts = [...this.products];
    }
    this.updateDropdownClasses();
  }

  toggleContractDropdown() {
    const wasOpen = this.showAssignedToDropdown;
    this.closeAllDropdowns();
    this.showAssignedToDropdown = !wasOpen;
    if (this.showAssignedToDropdown) {
      this.assignedToSearchQuery = '';
      this.filteredUsers = [...this.users];
    }
    this.updateDropdownClasses();
  }

  toggleProductFilter(product: string) {
    const index = this.filters.product.indexOf(product);
    if (index > -1) {
      this.filters.product.splice(index, 1);
    } else {
      this.filters.product.push(product);
    }
    this.applyFilters();
  }

  isProductSelected(product: string): boolean {
    return this.filters.product.includes(product);
  }

  filterProducts() {
    const query = this.productSearchQuery.toLowerCase();
    this.filteredProducts = this.products.filter(product =>
      product.toLowerCase().includes(query)
    );
  }

  getSelectedProductLabels(): string {
    if (this.filters.product.length === 0) {
      return 'Tutti i prodotti';
    }
    if (this.filters.product.length === 1) {
      return this.filters.product[0];
    }
    return `${this.filters.product[0]} (+${this.filters.product.length - 1})`;
  }

  getSelectedContractLabels(): string {
    if (!this.filters.contractId) {
      return 'Tutti i contratti';
    }
    return `#${this.filters.contractId}`;
  }

  // ========================================
  // PRIORITY MULTI-SELECT METHODS
  // ========================================

  togglePriorityDropdown() {
    const wasOpen = this.showPriorityDropdown;
    this.closeAllDropdowns();
    this.showPriorityDropdown = !wasOpen;
    this.updateDropdownClasses();
  }

  togglePriorityFilter(priorityValue: string) {
    const index = this.filters.priority.indexOf(priorityValue);
    if (index > -1) {
      this.filters.priority.splice(index, 1);
    } else {
      this.filters.priority.push(priorityValue);
    }
    this.applyFilters();
  }

  isPrioritySelected(priorityValue: string): boolean {
    return this.filters.priority.includes(priorityValue);
  }

  getSelectedPriorityLabels(): string {
    if (this.filters.priority.length === 0) {
      return 'Tutte le Priorità';
    }
    if (this.filters.priority.length === 1) {
      const priority = this.priorities.find(p => p.value === this.filters.priority[0]);
      return priority ? priority.label : 'Priorità';
    }
    if (this.filters.priority.length === 4) {
      return 'Tutte le Priorità';
    }
    const firstPriority = this.priorities.find(p => p.value === this.filters.priority[0]);
    return `${firstPriority?.label || 'Priorità'} (+${this.filters.priority.length - 1})`;
  }

  // ==================== SEU MULTI-SELECT METHODS ====================

  toggleSeuDropdown() {
    const wasOpen = this.showSeuDropdown;
    this.closeAllDropdowns();
    this.showSeuDropdown = !wasOpen;
    if (this.showSeuDropdown) {
      this.seuSearchQuery = '';
      this.filteredSeuList = [...this.seuList];
    }
    this.updateDropdownClasses();
  }

  toggleSeuFilter(seuId: string) {
    const index = this.filters.seu.indexOf(seuId);
    if (index > -1) {
      this.filters.seu.splice(index, 1);
    } else {
      this.filters.seu.push(seuId);
    }
    this.applyFilters();
  }

  isSeuSelected(seuId: string): boolean {
    return this.filters.seu.includes(seuId);
  }

  filterSeu() {
    const query = this.seuSearchQuery.toLowerCase();
    this.filteredSeuList = this.seuList.filter(seu =>
      seu.name.toLowerCase().includes(query)
    );
  }

  getSelectedSeuLabels(): string {
    if (this.filters.seu.length === 0) {
      return 'Tutti i SEU';
    }
    if (this.filters.seu.length === 1) {
      const seu = this.seuList.find(s => s.id.toString() === this.filters.seu[0]);
      return seu ? seu.name : 'SEU';
    }
    const firstSeu = this.seuList.find(s => s.id.toString() === this.filters.seu[0]);
    return `${firstSeu?.name || 'SEU'} (+${this.filters.seu.length - 1})`;
  }

  // ==================== GENERATED BY MULTI-SELECT METHODS ====================

  toggleGeneratedByDropdown() {
    const wasOpen = this.showGeneratedByDropdown;
    this.closeAllDropdowns();
    this.showGeneratedByDropdown = !wasOpen;
    if (this.showGeneratedByDropdown) {
      this.generatedBySearchQuery = '';
      this.filteredGeneratorsList = [...this.generatorsList];
    }
    this.updateDropdownClasses();
  }

  toggleGeneratedByFilter(userId: string) {
    const index = this.filters.generatedBy.indexOf(userId);
    if (index > -1) {
      this.filters.generatedBy.splice(index, 1);
    } else {
      this.filters.generatedBy.push(userId);
    }
    this.applyFilters();
  }

  isGeneratedBySelected(userId: string): boolean {
    return this.filters.generatedBy.includes(userId);
  }

  filterGenerators() {
    const query = this.generatedBySearchQuery.toLowerCase();
    this.filteredGeneratorsList = this.generatorsList.filter(gen =>
      gen.name.toLowerCase().includes(query)
    );
  }

  getSelectedGeneratedByLabels(): string {
    if (this.filters.generatedBy.length === 0) {
      return 'Tutti gli utenti';
    }
    if (this.filters.generatedBy.length === 1) {
      const user = this.generatorsList.find(g => g.id.toString() === this.filters.generatedBy[0]);
      return user ? user.name : 'Utente';
    }
    const firstUser = this.generatorsList.find(g => g.id.toString() === this.filters.generatedBy[0]);
    return `${firstUser?.name || 'Utente'} (+${this.filters.generatedBy.length - 1})`;
  }

  // ==================== ASSIGNED TO MULTI-SELECT METHODS ====================

  toggleAssignedToDropdown() {
    const wasOpen = this.showAssignedToDropdown;
    this.closeAllDropdowns();
    this.showAssignedToDropdown = !wasOpen;
    if (this.showAssignedToDropdown) {
      this.assignedToSearchQuery = '';
      this.filteredUsers = [...this.users];
    }
    this.updateDropdownClasses();
  }

  toggleAssignedToFilter(userId: string) {
    const index = this.filters.assignedTo.indexOf(userId);
    if (index > -1) {
      this.filters.assignedTo.splice(index, 1);
    } else {
      this.filters.assignedTo.push(userId);
    }
    this.applyFilters();
  }

  isAssignedToSelected(userId: string): boolean {
    return this.filters.assignedTo.includes(userId);
  }

  filterAssignedTo() {
    const query = this.assignedToSearchQuery.toLowerCase();
    this.filteredUsers = this.users.filter(user =>
      `${user.name} ${user.cognome}`.toLowerCase().includes(query)
    );
  }

  getSelectedAssignedToLabels(): string {
    if (this.filters.assignedTo.length === 0) {
      return 'Tutti gli utenti';
    }
    if (this.filters.assignedTo.includes('0')) {
      if (this.filters.assignedTo.length === 1) {
        return 'Non assegnato';
      }
      return `Non assegnato (+${this.filters.assignedTo.length - 1})`;
    }
    if (this.filters.assignedTo.length === 1) {
      const user = this.users.find(u => u.id.toString() === this.filters.assignedTo[0]);
      return user ? `${user.name} ${user.cognome}` : 'Utente';
    }
    const firstUser = this.users.find(u => u.id.toString() === this.filters.assignedTo[0]);
    return `${firstUser?.name || 'Utente'} (+${this.filters.assignedTo.length - 1})`;
  }

  // ==================== DROPDOWN MANAGEMENT ====================

  closeAllDropdowns() {
    this.showStatusDropdown = false;
    this.showProductDropdown = false;
    this.showPriorityDropdown = false;
    this.showSeuDropdown = false;
    this.showGeneratedByDropdown = false;
    this.showAssignedToDropdown = false;
    this.showContractDropdown = false;
    this.updateDropdownClasses();
  }

  updateDropdownClasses() {
    setTimeout(() => {
      const allFilterGroups = document.querySelectorAll('.filter-group');
      allFilterGroups.forEach(group => {
        group.classList.remove('dropdown-open');
      });

      if (this.showStatusDropdown || this.showProductDropdown || this.showPriorityDropdown ||
          this.showSeuDropdown || this.showGeneratedByDropdown || this.showAssignedToDropdown) {
        
        const activeDropdown = document.querySelector('.multi-select-dropdown');
        if (activeDropdown) {
          const parentFilterGroup = activeDropdown.closest('.filter-group');
          if (parentFilterGroup) {
            parentFilterGroup.classList.add('dropdown-open');
          }
        }
      }
    }, 0);
  }

  closeStatusDropdown(event: MouseEvent) {
    const target = event.target as HTMLElement;
    if (!target.closest('.multi-select-container')) {
      this.closeAllDropdowns();
    }
  }

  isColumnVisible(columnId: string): boolean {
    const column = this.columns.find(c => c.id === columnId);
    if (!column) return false;
    return !column.hidden && this.filters.status.includes(columnId);
  }

  getVisibleColumnsCount(): number {
    return this.columns.filter(c => 
      !c.hidden && this.filters.status.includes(c.id)
    ).length;
  }

  getColumnPosition(columnId: string): number {
    const visibleColumns = this.columns.filter(c => !c.hidden);
    return visibleColumns.findIndex(c => c.id === columnId);
  }

  getPriorityLabel(priority: string): string {
    const priorityObj = this.priorities.find(p => p.value === priority);
    return priorityObj ? priorityObj.label : priority;
  }

  getPriorityColor(priority: string): string {
    const priorityObj = this.priorities.find(p => p.value === priority);
    return priorityObj ? priorityObj.color : '#666';
  }

  getTimeAgo(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMs = now.getTime() - date.getTime();
    const diffInMinutes = Math.floor(diffInMs / 60000);
    const diffInHours = Math.floor(diffInMinutes / 60);
    const diffInDays = Math.floor(diffInHours / 24);

    if (diffInMinutes < 60) {
      return `${diffInMinutes} min fa`;
    } else if (diffInHours < 24) {
      return `${diffInHours} ore fa`;
    } else {
      return `${diffInDays} giorni fa`;
    }
  }

  getAssignedUserName(ticket: Ticket): string {
    if (ticket.status === 'new') {
      return 'Non assegnato';
    }
    return ticket.assigned_to_user_name || 'Non assegnato';
  }

  createNewTicket() {
    this.showNewTicketModal = true;
    this.newTicketAttachments = [];
    if (!this.contractSearchInitialized) {
      this.setupContractSearchPipeline();
    }
    this.onContractSearch('');
  }

  saveNewTicket() {
    if (!this.newTicket.title || !this.newTicket.description || !this.newTicket.contract_id) {
      this.showValidationError = true;
      this.isShaking = true;
      
      setTimeout(() => {
        this.isShaking = false;
      }, 300);
      
      setTimeout(() => {
        this.showValidationError = false;
      }, 2000);
      
      return;
    }

    const ticketData = {
      title: this.newTicket.title,
      description: this.newTicket.description,
      priority: this.newTicket.priority,
      contract_id: this.newTicket.contract_id,
      created_by_user_id: this.currentUser.id,
      status: 'new'
    };

    const createSub = this.apiService.createTicket(ticketData).subscribe((response: any) => {
      if (response.response === 'ok' && response.body?.risposta) {
        const createdTicket = response.body.risposta;

        if (this.newTicketAttachments.length > 0) {
          this.uploadNewTicketAttachments(createdTicket.id);
        } else {
          this.closeNewTicketModal();
          this.loadTickets();

          this.snackBar.open(
            `Ticket ${createdTicket.ticket_number} creato con successo`,
            'Chiudi',
            { 
              duration: 3000,
              horizontalPosition: 'center',
              verticalPosition: 'bottom',
              panelClass: ['success-snackbar']
            }
          );
        }
      } else {
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
    }, error => {
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
    });
    this.subscriptions.push(createSub);
  }

  private uploadNewTicketAttachments(ticketId: number): void {
    this.isUploadingNewTicketAttachments = true;

    const formData = new FormData();
    formData.append('ticket_id', ticketId.toString());

    this.newTicketAttachments.forEach((file) => {
      formData.append('attachments[]', file);
    });

    this.apiService.uploadTicketAttachments(formData).subscribe(
      (response: any) => {
        this.isUploadingNewTicketAttachments = false;

        if (response.response === 'ok') {
          this.closeNewTicketModal();
          this.loadTickets();

          this.snackBar.open(
            'Ticket creato con allegati',
            'Chiudi',
            { 
              duration: 3000,
              horizontalPosition: 'center',
              verticalPosition: 'bottom',
              panelClass: ['success-snackbar']
            }
          );
        }
      },
      (error) => {
        this.isUploadingNewTicketAttachments = false;
        this.closeNewTicketModal();
        this.loadTickets();

        this.snackBar.open(
          'Ticket creato ma errore nel caricamento degli allegati',
          'Chiudi',
          { 
            duration: 4000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['warning-snackbar']
          }
        );
      }
    );
  }

  openTicketModal(ticket: Ticket) {
    this.selectedTicket = ticket;
    this.showTicketModal = true;
    this.loadTicketMessages(ticket.id);
    this.loadTicketAttachments();
  }

  loadTicketMessages(ticketId: number) {
    this.isLoadingMessages = true;
    const messagesSub = this.apiService.getTicketMessages(ticketId).subscribe((response: any) => {
      if (response && response.body && response.body.risposta && this.selectedTicket) {
        this.selectedTicket.messages = response.body.risposta;
      }
      this.isLoadingMessages = false;
    }, error => {
      this.isLoadingMessages = false;
    });
    this.subscriptions.push(messagesSub);
  }

  sendMessage() {
    if (!this.selectedTicket) return;
    
    if (!this.newMessage.trim() && this.replyAttachments.length === 0) {
      return;
    }

    if (!this.canReplyToTicket(this.selectedTicket)) {
      this.snackBar.open(
        'Solo il backoffice assegnato può rispondere a questo ticket',
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

    if (this.replyAttachments.length > 0) {
      this.uploadReplyAttachmentsAndSendMessage();
    } else {
      this.sendMessageWithoutAttachments();
    }
  }

  private sendMessageWithoutAttachments(): void {
    if (!this.selectedTicket || !this.newMessage.trim()) return;

    const messageData = {
      ticket_id: this.selectedTicket.id,
      user_id: this.currentUser.id,
      message: this.newMessage,
      message_type: 'text'
    };

    const messageSub = this.apiService.sendTicketMessage(messageData).subscribe((response: any) => {
      if (response.response === 'ok') {
        this.newMessage = '';
        this.loadTicketMessages(this.selectedTicket!.id);
      } else {
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
    }, error => {
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
    });
    this.subscriptions.push(messageSub);
  }

  private uploadReplyAttachmentsAndSendMessage(): void {
    if (!this.selectedTicket) return;

    this.isUploadingReplyAttachments = true;

    const formData = new FormData();
    formData.append('ticket_id', this.selectedTicket.id.toString());

    if (this.newMessage.trim()) {
      const messageData = {
        ticket_id: this.selectedTicket.id,
        user_id: this.currentUser.id,
        message: this.newMessage,
        message_type: 'text'
      };

      this.apiService.sendTicketMessage(messageData).subscribe(
        (response: any) => {
          if (response.response === 'ok' && response.body?.message?.id) {
            const messageId = response.body.message.id;
            formData.append('message_id', messageId.toString());

            this.replyAttachments.forEach((file) => {
              formData.append('attachments[]', file);
            });

            this.uploadAttachments(formData);
          }
        },
        (error) => {
          this.isUploadingReplyAttachments = false;
          this.snackBar.open('Errore durante l\'invio del messaggio', 'Chiudi', {
            duration: 3000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['error-snackbar']
          });
        }
      );
    } else {
      this.replyAttachments.forEach((file) => {
        formData.append('attachments[]', file);
      });

      this.uploadAttachments(formData);
    }
  }

  private uploadAttachments(formData: FormData): void {
    this.apiService.uploadTicketAttachments(formData).subscribe(
      (response: any) => {
        this.isUploadingReplyAttachments = false;

        if (response.response === 'ok') {
          this.newMessage = '';
          this.replyAttachments = [];

          this.snackBar.open('Messaggio e allegati inviati', 'Chiudi', {
            duration: 2000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['success-snackbar']
          });

          if (this.selectedTicket) {
            this.loadTicketMessages(this.selectedTicket.id);
            this.loadTicketAttachments();
          }
        }
      },
      (error) => {
        this.isUploadingReplyAttachments = false;
        this.snackBar.open('Errore durante il caricamento degli allegati', 'Chiudi', {
          duration: 3000,
          horizontalPosition: 'center',
          verticalPosition: 'bottom',
          panelClass: ['error-snackbar']
        });
      }
    );
  }

  closeTicketModal() {
    this.showTicketModal = false;
    this.selectedTicket = null;
    this.newMessage = '';
    this.ticketAttachments = [];
    this.replyAttachments = [];
  }

  closeNewTicketModal() {
    this.showNewTicketModal = false;
    this.showValidationError = false;
    this.isShaking = false;
    this.contractSearchQuery = '';
    this.newTicketAttachments = [];
    this.newTicket = {
      title: '',
      description: '',
      priority: 'medium',
      contract_id: null
    };
  }

  onTicketDrop(event: any, newStatus: string) {
    event.preventDefault();
    const ticketId = event.dataTransfer.getData('text/plain');
    const ticket = this.tickets.find(t => t.id.toString() === ticketId);
    
    if (!ticket) {
        this.isDragging = false;
        this.currentDraggingTicket = null;
        return;
    }
    
    if (!this.canDropOnColumn(ticket, newStatus)) {
        this.snackBar.open(
        'Non puoi spostare un ticket assegnato su "Nuovo"',
        'Chiudi',
        { 
            duration: 3000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['error-snackbar']
        }
        );
        this.isDragging = false;
        this.currentDraggingTicket = null;
        return;
    }
    
    if (!this.canDragTicket(ticket)) {
        this.snackBar.open(
        'Non hai i permessi per spostare questo ticket',
        'Chiudi',
        { 
            duration: 3000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['error-snackbar']
        }
        );
        this.isDragging = false;
        this.currentDraggingTicket = null;
        return;
    }
    
    if (ticket.status !== newStatus) {
        this.updateTicketStatus(ticket, newStatus);
    }
    
    this.isDragging = false;
    this.currentDraggingTicket = null;
  }

  onTicketDragStart(event: any, ticket: Ticket) {
    if (!this.canDragTicket(ticket)) {
        event.preventDefault();
        event.stopPropagation();

        this.isDragging = true;
        this.currentDraggingTicket = ticket;
        
        setTimeout(() => {
          this.isDragging = false;
          this.currentDraggingTicket = null;
        }, 800);

        return;
    }
    
    this.isDragging = true;
    this.currentDraggingTicket = ticket;
    
    event.dataTransfer.setData('text/plain', ticket.id.toString());
  }

  onDragOver(event: any) {
    event.preventDefault();
  }
  
  onTicketDragEnd() {
    this.isDragging = false;
    this.currentDraggingTicket = null;
  }

  updateTicketStatus(ticket: Ticket, newStatus: string) {
    const updateData = {
      ticket_id: ticket.id,
      status: newStatus,
      assigned_to_user_id: this.currentUser.id
    };

    const updateSub = this.apiService.updateTicketStatus(updateData).subscribe((response: any) => {
      ticket.status = newStatus as any;
      ticket.assigned_to_user_id = this.currentUser.id;
      ticket.assigned_to_user_name = `${this.currentUser.name || ''} ${this.currentUser.cognome || ''}`.trim() || this.currentUser.email;
      
      this.updateColumnCounts();
      
      this.snackBar.open(
        `Ticket ${ticket.ticket_number} spostato in ${this.getStatusLabel(newStatus)}`,
        'Chiudi',
        { 
          duration: 3000,
          horizontalPosition: 'center',
          verticalPosition: 'bottom',
          panelClass: ['success-snackbar']
        }
      );
    }, error => {
      if (error.status === 204 || error.status === 200) {
        ticket.status = newStatus as any;
        ticket.assigned_to_user_id = this.currentUser.id;
        ticket.assigned_to_user_name = `${this.currentUser.name || ''} ${this.currentUser.cognome || ''}`.trim() || this.currentUser.email;
        
        this.updateColumnCounts();
        
        this.snackBar.open(
          `Ticket ${ticket.ticket_number} spostato in ${this.getStatusLabel(newStatus)}`,
          'Chiudi',
          { 
            duration: 3000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['success-snackbar']
          }
        );
      } else {
        this.snackBar.open(
          error.error?.message || 'Errore nell\'aggiornamento dello stato',
          'Chiudi',
          { 
            duration: 3000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['error-snackbar']
          }
        );
      }
    });
    this.subscriptions.push(updateSub);
  }

  sortTicketsByPriority() {
    const priorityWeight = {
      'high': 3,
      'medium': 2,
      'low': 1,
      'unassigned': 0
    };
    
    this.filteredTickets.sort((a, b) => {
      const weightA = priorityWeight[a.priority as keyof typeof priorityWeight] || 0;
      const weightB = priorityWeight[b.priority as keyof typeof priorityWeight] || 0;
      return weightB - weightA;
    });
  }

  getStatusLabel(status: string): string {
    const column = this.columns.find(c => c.id === status);
    return column ? column.title : status;
  }

  loadMinimizedTicketsFromStorage() {
    try {
      const saved = localStorage.getItem('minimizedTickets');
      if (saved) {
        const ticketIds = JSON.parse(saved);
        this.minimizedTickets = new Set(ticketIds);
      }
    } catch (error) {
      console.error('Error loading minimized tickets:', error);
    }
  }

  saveMinimizedTicketsToStorage() {
    try {
      const ticketIds = Array.from(this.minimizedTickets);
      localStorage.setItem('minimizedTickets', JSON.stringify(ticketIds));
    } catch (error) {
      console.error('Error saving minimized tickets:', error);
    }
  }

  private setupContractSearchPipeline(): void {
    if (this.contractSearchInitialized || !this.currentUser) {
      return;
    }

    this.contractSearchInitialized = true;

    const searchSub = this.contractSearch$
      .pipe(
        debounceTime(300),
        distinctUntilChanged(),
        tap(() => (this.isLoadingContractOptions = true)),
        switchMap((term: string) =>
          this.queryContracts(term).pipe(
            catchError(() => {
              this.isLoadingContractOptions = false;
              return of([]);
            })
          )
        )
      )
      .subscribe((contracts: any[]) => {
        this.contracts = contracts;
        this.filteredContracts = contracts;
        this.isLoadingContractOptions = false;
      });

    this.contractSearchSub = searchSub;
    this.subscriptions.push(searchSub);
    this.contractSearch$.next('');
  }

  private queryContracts(term: string): Observable<any[]> {
    if (!this.currentUser) {
      return of([]);
    }

    const filters: any[] = [];
    const trimmed = term.trim();

    if (trimmed) {
      filters.push(['ricerca', trimmed]);
    }

    return this.apiService
      .searchContratti(
        this.currentUser.id,
        JSON.stringify(filters),
        1,
        20,
        'id',
        'desc'
      )
      .pipe(
        map((response: any) => {
          let data = response?.body?.risposta;
          if (data?.data) {
            data = data.data;
          }
          return Array.isArray(data) ? data : [];
        })
      );
  }

  onContractSearch(term: string): void {
    this.contractSearchQuery = term;
    this.contractSearch$.next(term);
  }

  onContractPanelToggle(open: boolean): void {
    if (open) {
      if (!this.contractSearchInitialized) {
        this.setupContractSearchPipeline();
      }
      setTimeout(() => this.contractSearchInput?.nativeElement.focus(), 0);
    } else {
      this.contractSearchQuery = '';
      this.onContractSearch('');
    }
  }

  toggleMinimizeTicket(ticketId: number, event: Event) {
    event.stopPropagation();
    
    if (this.minimizedTickets.has(ticketId)) {
      this.minimizedTickets.delete(ticketId);
    } else {
      this.minimizedTickets.add(ticketId);
    }
    
    this.saveMinimizedTicketsToStorage();
  }

  isTicketMinimized(ticketId: number): boolean {
    return this.minimizedTickets.has(ticketId);
  }

  truncateText(text: string, maxLength: number = 30): string {
    if (text.length <= maxLength) {
      return text;
    }
    return text.substring(0, maxLength) + '...';
  }

  isDraggingFile: boolean = false;

  getUserRoleInitial(userRole: string | number): string {
    const roleStr = typeof userRole === 'number' ? userRole.toString() : userRole;
    
    switch(roleStr) {
      case '1':
      case 'admin':
        return 'A';
      case '2':
      case 'backoffice':
        return 'B';
      case '3':
      case 'operatore_web':
        return 'O';
      case '4':
      case 'seu':
        return 'S';
      default:
        if (this.isAdmin) return 'A';
        if (this.userRole === 2) return 'B';
        if (this.userRole === 3) return 'O';
        if (this.userRole === 4) return 'S';
        return 'U';
    }
  }

  formatMessageTime(dateString: string): string {
    const date = new Date(dateString);
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    return `${hours}:${minutes}`;
  }

  onFileDragOver(event: DragEvent): void {
    event.preventDefault();
    event.stopPropagation();
    this.isDraggingFile = true;
  }

  onFileDragLeave(event: DragEvent): void {
    event.preventDefault();
    event.stopPropagation();
    this.isDraggingFile = false;
  }

  onFileDrop(event: DragEvent): void {
    event.preventDefault();
    event.stopPropagation();
    this.isDraggingFile = false;

    if (!this.canAttachToTicket(this.selectedTicket)) {
      return;
    }

    const files = event.dataTransfer?.files;
    if (files && files.length > 0) {
      const fileArray = Array.from(files);
      
      if (this.replyAttachments.length + fileArray.length > this.maxFiles) {
        this.snackBar.open(
          `Puoi allegare massimo ${this.maxFiles} file`,
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

      fileArray.forEach(file => {
        if (file.size > this.maxFileSize) {
          this.snackBar.open(
            `Il file ${file.name} supera la dimensione massima di ${this.formatFileSize(this.maxFileSize)}`,
            'Chiudi',
            { 
              duration: 3000,
              horizontalPosition: 'center',
              verticalPosition: 'bottom',
              panelClass: ['error-snackbar']
            }
          );
        } else {
          this.replyAttachments.push(file);
        }
      });
    }
  }
}