// ticket-management.component.ts
import { Component, OnInit, OnDestroy } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';
import { MatSnackBar } from '@angular/material/snack-bar';
import { Subscription } from 'rxjs';

export interface Ticket {
  id: number;
  ticket_number: string;
  title: string;
  description: string;
  status: 'new' | 'waiting' | 'resolved';
  priority: 'low' | 'medium' | 'high';
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
}

export interface TicketMessage {
  id: number;
  ticket_id: number;
  user_id: number;
  user_name: string;
  user_role: string;
  message: string;
  message_type: 'text' | 'attachment';
  attachment_path?: string;
  attachment_name?: string;
  created_at: string;
}

export interface TicketFilters {
  contractId: string;
  contractCode: string;
  product: string;
  priority: string;
  status: string[];
  assignedTo: string;
  customer: string;
  seu: string;
  generatedBy: string;
  openingDate: string;
}

@Component({
  selector: 'app-ticket-management',
  templateUrl: './ticket-management.component.html',
  styleUrls: ['./ticket-management.component.css'],
  standalone: false,
})
export class TicketManagementComponent implements OnInit, OnDestroy {
  private subscriptions: Subscription[] = [];
  
  tickets: Ticket[] = [];
  filteredTickets: Ticket[] = [];
  currentUser: any;
  userRole: number = 0;
  
  filters: TicketFilters = {
    contractId: '',
    contractCode: '',
    product: '',
    priority: '',
    status: ['new', 'waiting', 'resolved'], // All selected by default
    assignedTo: '',
    customer: '',
    seu: '',
    generatedBy: '',
    openingDate: ''
  };

  previousStatusSelection: string[] = ['new', 'waiting', 'resolved'];


  showFilters: boolean = true;
  selectedTicket: Ticket | null = null;
  showTicketModal: boolean = false;
  showNewTicketModal: boolean = false;
  showValidationError: boolean = false;
  isShaking: boolean = false;
  
  newTicket = {
    title: '',
    description: '',
    priority: 'medium',
    contract_id: null
  };

  newMessage: string = '';
  isLoadingMessages: boolean = false;

  columns = [
    {
      id: 'new',
      title: 'Nuovo',
      icon: 'fas fa-plus-circle',
      color: '#2196F3',
      count: 0
    },
    {
      id: 'waiting',
      title: 'In Attesa',
      icon: 'fas fa-clock',
      color: '#9c27b0',
      count: 0
    },
    {
      id: 'resolved',
      title: 'Risolto',
      icon: 'fas fa-check-circle',
      color: '#4caf50',
      count: 0
    }
  ];

  products: string[] = [];
  seuList: any[] = [];
  generatorsList: any[] = [];
  
  priorities = [
    { value: 'low', label: 'Bassa', color: '#28a745' },
    { value: 'medium', label: 'Media', color: '#ffc107' },
    { value: 'high', label: 'Alta', color: '#dc3545' }
  ];

  contracts: any[] = [];
  users: any[] = [];

  constructor(
    private apiService: ApiService,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit() {
    this.previousStatusSelection = [...this.filters.status];

    this.loadCurrentUser();
    this.adjustForMobile();
  }

  ngOnDestroy() {
    this.subscriptions.forEach(sub => sub.unsubscribe());
  }

  adjustForMobile() {
    // Adjust filters visibility on mobile
    if (window.innerWidth < 768) {
      this.showFilters = false;
    }
  }

  loadCurrentUser() {
    const userSub = this.apiService.PrendiUtente().subscribe((userData: any) => {
      this.currentUser = userData.user;
      this.userRole = userData.user.role.id;
      this.loadInitialData();
      this.loadTickets();
    });
    
    this.subscriptions.push(userSub);
  }

  loadInitialData() {
    // Load contracts - Fix per Admin
    if (this.userRole === 1) {
      const contractsSub = this.apiService.getContratti(null).subscribe((response: any) => {
        if (response.body && response.body.risposta) {
          this.contracts = response.body.risposta.data || response.body.risposta;
        }
      }, error => {
        this.apiService.getContratti(0).subscribe((response: any) => {
          if (response.body && response.body.risposta) {
            this.contracts = response.body.risposta.data || response.body.risposta;
          }
        }, error2 => {
          this.apiService.getContratti(this.currentUser.id).subscribe((response: any) => {
            if (response.body && response.body.risposta && response.body.risposta.data) {
              this.contracts = response.body.risposta.data;
            }
          });
        });
      });
      this.subscriptions.push(contractsSub);
    } else {
      const contractsSub = this.apiService.getContratti(this.currentUser.id).subscribe((response: any) => {
        if (response.body && response.body.risposta && response.body.risposta.data) {
          this.contracts = response.body.risposta.data;
        }
      });
      this.subscriptions.push(contractsSub);
    }

    // Load products
    const productsSub = this.apiService.ListaProdotti().subscribe((response: any) => {
      if (response.body && response.body.prodotti) {
        this.products = response.body.prodotti.map((p: any) => p.descrizione);
      }
    });
    this.subscriptions.push(productsSub);

    // Load users e SEU
    const usersSub = this.apiService.getAllUser().subscribe((response: any) => {
      if (response.body && response.body.risposta) {
        this.users = response.body.risposta.filter((user: any) => 
          user.role.id === 1 || user.role.id === 5
        );
        
        const seuUsers = response.body.risposta.filter((user: any) => 
          user.role.id === 2 || user.role.id === 4
        );
        this.seuList = seuUsers.map((user: any) => ({
          id: user.id,
          name: `${user.name || ''} ${user.cognome || ''}`.trim() || user.email
        }));
        
        this.generatorsList = response.body.risposta.map((user: any) => ({
          id: user.id,
          name: `${user.name || ''} ${user.cognome || ''}`.trim() || user.email
        }));
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
    return ticketsData.map(ticket => ({
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
      seu_name: ticket.contract?.user_seu ? 
        `${ticket.contract.user_seu.name || ''} ${ticket.contract.user_seu.cognome || ''}`.trim() : 'N/A',
      messages: ticket.messages || []
    }));
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

  toggleStatusFilter(statusId: string) {
    const index = this.filters.status.indexOf(statusId);
    
    // Don't allow deselecting all statuses
    if (index > -1 && this.filters.status.length > 1) {
      this.filters.status.splice(index, 1);
    }

  onStatusDropdownChange(_event: Event) {
    // Ensure at least one status remains selected
    if (!this.filters.status || this.filters.status.length === 0) {
      // Restore previous non-empty selection
      this.filters.status = [...this.previousStatusSelection];
    } else {
      // Update previous selection snapshot
      this.previousStatusSelection = [...this.filters.status];
    }
    this.applyFilters();
  }
 else if (index === -1) {
      this.filters.status.push(statusId);
    }
    
    this.applyFilters();
  }

  isColumnVisible(columnId: string): boolean {
    return this.filters.status.includes(columnId);
  }

  getVisibleColumnsCount(): number {
    return this.filters.status.length;
  }

  getColumnFlex(columnId: string): string {
    if (!this.isColumnVisible(columnId)) {
      return '0';
    }
    
    const visibleCount = this.getVisibleColumnsCount();
    if (visibleCount === 1) {
      return '1 1 100%';
    } else if (visibleCount === 2) {
      return '1 1 50%';
    }
    return '1';
  }

  getColumnPosition(columnId: string): number {
    const visibleColumns = this.columns.filter(c => this.isColumnVisible(c.id));
    return visibleColumns.findIndex(c => c.id === columnId);
  }

  applyFilters() {
    this.filteredTickets = this.tickets.filter(ticket => {
      // Contract ID filter
      if (this.filters.contractId) {
        const ticketIdStr = ticket.contract_id.toString();
        const filterIdStr = this.filters.contractId.toString();
        if (!ticketIdStr.startsWith(filterIdStr)) {
          return false;
        }
      }
      
      // Contract Code filter
      if (this.filters.contractCode && 
          !ticket.contract_code.toLowerCase().includes(this.filters.contractCode.toLowerCase())) {
        return false;
      }
      
      // Product filter
      if (this.filters.product && ticket.product_name !== this.filters.product) {
        return false;
      }
      
      // Priority filter
      if (this.filters.priority && ticket.priority !== this.filters.priority) {
        return false;
      }
      
      // Status filter - multi-select
      if (this.filters.status.length > 0 && !this.filters.status.includes(ticket.status)) {
        return false;
      }
      
      // Customer filter
      if (this.filters.customer && 
          !ticket.customer_name.toLowerCase().includes(this.filters.customer.toLowerCase())) {
        return false;
      }
      
      // SEU filter
      if (this.filters.seu) {
        const seuUser = this.seuList.find(s => s.id.toString() === this.filters.seu);
        if (seuUser && ticket.seu_name && !ticket.seu_name.toLowerCase().includes(seuUser.name.toLowerCase())) {
          return false;
        }
      }
      
      // Generated by filter
      if (this.filters.generatedBy && 
          ticket.created_by_user_id?.toString() !== this.filters.generatedBy) {
        return false;
      }
      
      // Assigned to filter
      if (this.filters.assignedTo) {
        if (this.filters.assignedTo === '0') {
          if (ticket.status !== 'new' && ticket.assigned_to_user_id) {
            return false;
          }
        } else {
          if (ticket.status === 'new') {
            return false;
          }
          if (ticket.assigned_to_user_id?.toString() !== this.filters.assignedTo) {
            return false;
          }
        }
      }
      
      // Opening date filter
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
      product: '',
      priority: '',
      status: ['new', 'waiting', 'resolved'], // Reset to all selected
      assignedTo: '',
      customer: '',
      seu: '',
      generatedBy: '',
      openingDate: ''
    };
    this.applyFilters();
  }

  createNewTicket() {
    this.showNewTicketModal = true;
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
      if (response.response === 'ok') {
        this.snackBar.open(
          'Ticket creato con successo',
          'Chiudi',
          { 
            duration: 3000,
            horizontalPosition: 'center',
            verticalPosition: 'bottom',
            panelClass: ['success-snackbar']
          }
        );
        this.closeNewTicketModal();
        this.loadTickets();
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

  openTicketModal(ticket: Ticket) {
    this.selectedTicket = ticket;
    this.showTicketModal = true;
    this.loadTicketMessages(ticket.id);
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
    if (!this.newMessage.trim() || !this.selectedTicket) return;

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

  closeTicketModal() {
    this.showTicketModal = false;
    this.selectedTicket = null;
    this.newMessage = '';
  }

  closeNewTicketModal() {
    this.showNewTicketModal = false;
    this.showValidationError = false;
    this.isShaking = false;
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
    
    if (ticket && ticket.status !== newStatus) {
      this.updateTicketStatus(ticket, newStatus);
    }
  }

  onTicketDragStart(event: any, ticket: Ticket) {
    event.dataTransfer.setData('text/plain', ticket.id.toString());
  }

  onDragOver(event: any) {
    event.preventDefault();
  }

  updateTicketStatus(ticket: Ticket, newStatus: string) {
    const updateData = {
      ticket_id: ticket.id,
      status: newStatus,
      assigned_to_user_id: this.currentUser.id
    };

    const updateSub = this.apiService.updateTicketStatus(updateData).subscribe((response: any) => {
      if (response.response === 'ok') {
        ticket.status = newStatus as any;
        ticket.assigned_to_user_id = this.currentUser.id;
        ticket.assigned_to_user_name = `${this.currentUser.name || ''} ${this.currentUser.cognome || ''}`.trim() || this.currentUser.email;
        
        this.updateColumnCounts();
        
        this.snackBar.open(
          `Ticket ${ticket.ticket_number} spostato in ${this.getStatusLabel(newStatus)} e assegnato a te`,
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
          'Errore nell\'aggiornamento dello stato',
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
        'Errore nell\'aggiornamento dello stato',
        'Chiudi',
        { 
          duration: 3000,
          horizontalPosition: 'center',
          verticalPosition: 'bottom',
          panelClass: ['error-snackbar']
        }
      );
    });
    this.subscriptions.push(updateSub);
  }

  getAssignedUserName(ticket: Ticket): string {
    if (ticket.status === 'new') {
      return 'Non assegnato';
    }
    return ticket.assigned_to_user_name || 'Non assegnato';
  }

  getStatusLabel(status: string): string {
    const column = this.columns.find(c => c.id === status);
    return column ? column.title : status;
  }

  getPriorityColor(priority: string): string {
    const priorityObj = this.priorities.find(p => p.value === priority);
    return priorityObj ? priorityObj.color : '#666';
  }

  getPriorityLabel(priority: string): string {
    const priorityObj = this.priorities.find(p => p.value === priority);
    return priorityObj ? priorityObj.label : priority;
  }

  getTimeAgo(date: string): string {
    const now = new Date();
    const ticketDate = new Date(date);
    const diffInMs = now.getTime() - ticketDate.getTime();
    const diffInHours = diffInMs / (1000 * 60 * 60);
    const diffInDays = diffInMs / (1000 * 60 * 60 * 24);

    if (diffInHours < 1) {
      return 'Adesso';
    } else if (diffInHours < 24) {
      return `${Math.floor(diffInHours)} ore fa`;
    } else if (diffInDays < 7) {
      return `${Math.floor(diffInDays)} giorni fa`;
    } else {
      return `${Math.floor(diffInDays / 7)} settimane fa`;
    }
  }

  canManageTickets(): boolean {
    return this.userRole === 1 || this.userRole === 5;
  }
}