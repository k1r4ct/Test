// ticket-management.component.ts
import { Component, OnInit, OnDestroy } from '@angular/core';
import { ApiService } from 'src/app/servizi/api.service';
import { ToastrService } from 'ngx-toastr';
import { MatSnackBar } from '@angular/material/snack-bar';
import { Subscription } from 'rxjs';

export interface Ticket {
  id: number;
  ticket_number: string;
  title: string;
  description: string;
  status: 'new' | 'in-progress' | 'waiting' | 'resolved';
  priority: 'low' | 'medium' | 'high';
  contract_id: number;
  contract_code: string;
  created_by_user_id: number;
  assigned_to_user_id?: number;
  customer_name: string;
  customer_initials: string;
  avatar_color: string;
  created_at: string;
  updated_at: string;
  product_name?: string;
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
  product: string;
  priority: string;
  status: string;
  assignedTo: string;
  customer: string;
  dateFrom: string;
  dateTo: string;
}

@Component({
  selector: 'app-ticket-management',
  templateUrl: './ticket-management.component.html',
  styleUrls: ['./ticket-management.component.css']
})
export class TicketManagementComponent implements OnInit, OnDestroy {
  private subscriptions: Subscription[] = [];
  
  tickets: Ticket[] = [];
  filteredTickets: Ticket[] = [];
  currentUser: any;
  userRole: number = 0;
  
  filters: TicketFilters = {
    contractId: '',
    product: '',
    priority: '',
    status: '',
    assignedTo: '',
    customer: '',
    dateFrom: '',
    dateTo: ''
  };

  showFilters: boolean = true;
  selectedTicket: Ticket | null = null;
  showTicketModal: boolean = false;
  showNewTicketModal: boolean = false;
  
  newTicket = {
    title: '',
    description: '',
    priority: 'medium',
    contract_id: null
  };

  newMessage: string = '';
  isLoadingMessages: boolean = false;

  // Kanban columns configuration
  columns = [
    {
      id: 'new',
      title: 'Nuovo',
      icon: 'fas fa-plus-circle',
      color: '#2196F3',
      count: 0
    },
    {
      id: 'in-progress', 
      title: 'In Lavorazione',
      icon: 'fas fa-spinner',
      color: '#ff9800',
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
  priorities = [
    { value: 'low', label: 'Bassa', color: '#28a745' },
    { value: 'medium', label: 'Media', color: '#ffc107' },
    { value: 'high', label: 'Alta', color: '#dc3545' }
  ];

  contracts: any[] = [];
  users: any[] = [];

  constructor(
    private apiService: ApiService,
    private toastr: ToastrService,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit() {
    this.loadCurrentUser();
    this.loadInitialData();
  }

  ngOnDestroy() {
    this.subscriptions.forEach(sub => sub.unsubscribe());
  }

  loadCurrentUser() {
    const userSub = this.apiService.PrendiUtente().subscribe((userData: any) => {
      this.currentUser = userData.user;
      this.userRole = userData.user.role.id;
      this.loadTickets();
    });
    this.subscriptions.push(userSub);
  }

  loadInitialData() {
    // Load contracts
    const contractsSub = this.apiService.getContratti().subscribe((response: any) => {
      if (response.body && response.body.risposta && response.body.risposta.data) {
        this.contracts = response.body.risposta.data;
      }
    });
    this.subscriptions.push(contractsSub);

    // Load products
    const productsSub = this.apiService.PrendiProdotti().subscribe((response: any) => {
      if (response.body && response.body.risposta) {
        this.products = response.body.risposta.map((p: any) => p.descrizione);
      }
    });
    this.subscriptions.push(productsSub);

    // Load users for assignment (only backoffice and admins)
    const usersSub = this.apiService.getAllUser().subscribe((response: any) => {
      if (response.body && response.body.risposta) {
        this.users = response.body.risposta.filter((user: any) => 
          user.role.id === 1 || user.role.id === 5 // Admin or BackOffice
        );
      }
    });
    this.subscriptions.push(usersSub);
  }

  loadTickets() {
    // Extended ApiService call for tickets
    const ticketsSub = this.apiService.getTickets().subscribe((response: any) => {
      if (response && response.body && response.body.risposta) {
        this.tickets = this.processTicketsData(response.body.risposta);
        this.applyFilters();
      }
    }, error => {
      this.toastr.error('Errore nel caricamento dei ticket', 'Errore');
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
      assigned_to_user_id: ticket.assigned_to_user_id,
      customer_name: ticket.contract?.customer_data?.nome_cognome || 'N/A',
      customer_initials: this.getInitials(ticket.contract?.customer_data?.nome_cognome || 'N/A'),
      avatar_color: this.getRandomColor(),
      created_at: ticket.created_at,
      updated_at: ticket.updated_at,
      product_name: ticket.contract?.product?.descrizione || 'N/A',
      messages: ticket.messages || []
    }));
  }

  getInitials(fullName: string): string {
    return fullName.split(' ')
      .map(name => name.charAt(0).toUpperCase())
      .join('')
      .substring(0, 2);
  }

  getRandomColor(): string {
    const colors = ['#ff6b6b', '#4ecdc4', '#95afc0', '#f368e0', '#feca57', '#00d2d3'];
    return colors[Math.floor(Math.random() * colors.length)];
  }

  applyFilters() {
    this.filteredTickets = this.tickets.filter(ticket => {
      // Contract ID filter
      if (this.filters.contractId && 
          !ticket.contract_code.toLowerCase().includes(this.filters.contractId.toLowerCase())) {
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
      
      // Status filter
      if (this.filters.status && ticket.status !== this.filters.status) {
        return false;
      }
      
      // Customer filter
      if (this.filters.customer && 
          !ticket.customer_name.toLowerCase().includes(this.filters.customer.toLowerCase())) {
        return false;
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
      product: '',
      priority: '',
      status: '',
      assignedTo: '',
      customer: '',
      dateFrom: '',
      dateTo: ''
    };
    this.applyFilters();
  }

  createNewTicket() {
    this.showNewTicketModal = true;
  }

  saveNewTicket() {
    if (!this.newTicket.title || !this.newTicket.description || !this.newTicket.contract_id) {
      this.toastr.error('Compilare tutti i campi obbligatori', 'Errore');
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
        this.toastr.success('Ticket creato con successo', 'Successo');
        this.closeNewTicketModal();
        this.loadTickets();
      } else {
        this.toastr.error('Errore nella creazione del ticket', 'Errore');
      }
    }, error => {
      this.toastr.error('Errore nella creazione del ticket', 'Errore');
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
        this.toastr.error('Errore nell\'invio del messaggio', 'Errore');
      }
    }, error => {
      this.toastr.error('Errore nell\'invio del messaggio', 'Errore');
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
      status: newStatus
    };

    const updateSub = this.apiService.updateTicketStatus(updateData).subscribe((response: any) => {
      if (response.response === 'ok') {
        ticket.status = newStatus as any;
        this.updateColumnCounts();
        
        this.snackBar.open(
          `Ticket ${ticket.ticket_number} spostato in ${this.getStatusLabel(newStatus)}`,
          'Chiudi',
          { duration: 3000 }
        );
      } else {
        this.toastr.error('Errore nell\'aggiornamento dello stato', 'Errore');
      }
    }, error => {
      this.toastr.error('Errore nell\'aggiornamento dello stato', 'Errore');
    });
    this.subscriptions.push(updateSub);
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
    // Only Admin (1) and BackOffice (5) can manage tickets
    return this.userRole === 1 || this.userRole === 5;
  }
}