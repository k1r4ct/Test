// ticket-management.component.ts
import { Component, OnInit, OnDestroy, AfterViewChecked, HostListener } from '@angular/core';
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
  message_type: 'text' | 'attachment' | 'status_change';  
  attachment_path?: string;
  attachment_name?: string;
  old_status?: string;  
  new_status?: string;  
  created_at: string;
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
    openingDate: ''
  };

  statusColorMap: Record<string, string> = {
    'new': '#2196F3',
    'waiting': '#9C27B0',
    'resolved': '#4CAF50'
  };

  showFilters: boolean = true;
  showStatusDropdown: boolean = false;
  showProductDropdown: boolean = false;
  showPriorityDropdown: boolean = false;
  showSeuDropdown: boolean = false;
  showGeneratedByDropdown: boolean = false;
  showAssignedToDropdown: boolean = false;
  
  selectedTicket: Ticket | null = null;
  showTicketModal: boolean = false;
  showNewTicketModal: boolean = false;
  showValidationError: boolean = false;
  isShaking: boolean = false;
  minimizedTickets: Set<number> = new Set<number>();

  // Search queries for filtering dropdown options
  productSearchQuery: string = '';
  seuSearchQuery: string = '';
  generatedBySearchQuery: string = '';
  assignedToSearchQuery: string = '';

  // Filtered lists for dropdowns
  filteredProducts: string[] = [];
  filteredSeuList: any[] = [];
  filteredGeneratorsList: any[] = [];
  filteredUsers: any[] = [];
  
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
    this.loadCurrentUser();
    this.loadMinimizedTicketsFromStorage();
  }

  ngOnDestroy() {
    this.subscriptions.forEach(sub => sub.unsubscribe());
  }

  ngAfterViewChecked() {
    // Removed automatic positioning to let CSS handle it
  }


  positionDropdowns() {
    // Positioning is handled by CSS with position: absolute
    // This avoids conflicts and positioning issues
  }

  // Handle window resize to reposition dropdowns
  @HostListener('window:resize', ['$event'])
  onResize(event: any) {
    this.positionDropdowns();
  }

  // Handle window scroll to reposition or close dropdowns
  @HostListener('window:scroll', ['$event'])
  onScroll(event: any) {
    // Close all dropdowns on scroll to prevent positioning issues
    this.closeAllDropdowns();
  }

  // Checks if a message is a status change message
  isStatusChangeMessage(message: TicketMessage): boolean {
    return message.message_type === 'status_change';
  }

  // Gets the column title for a given status
  getColumnTitle(status: string): string {
    const column = this.columns.find(c => c.id === status);
    return column ? column.title : '';
  }

  // Gets the column by status id
  getColumnByStatus(status: string): any {
    return this.columns.find(c => c.id === status);
  }

  // Extracts old and new status from a status change message
  getStatusFromMessage(message: TicketMessage): { oldStatus: string, newStatus: string } {
    if (message.old_status && message.new_status) {
      return {
        oldStatus: message.old_status,
        newStatus: message.new_status
      };
    }

    const regex = /da ['"](\w+)['"] a ['"](\w+)['"]/i;
    const match = message.message.match(regex);
    
    if (match) {
      return {
        oldStatus: match[1],
        newStatus: match[2]
      };
    }

    return {
      oldStatus: 'new',
      newStatus: 'waiting'
    };
  }

  // Generates the gradient style for a status change message
  getStatusChangeGradient(message: TicketMessage): string {
    const { oldStatus, newStatus } = this.getStatusFromMessage(message);
    const oldColor = this.statusColorMap[oldStatus] || this.statusColorMap['new'];
    const newColor = this.statusColorMap[newStatus] || this.statusColorMap['waiting'];
    
    return `linear-gradient(135deg, ${oldColor} 0%, ${newColor} 100%)`;
  }

  canManageTickets(): boolean {
    return this.userRole === 1 || this.userRole === 5;
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
    // Load contracts
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
        this.filteredProducts = [...this.products];
      }
    });
    this.subscriptions.push(productsSub);

    // Load users and SEU
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
    // Debug: log the first ticket to see the structure
    if (ticketsData.length > 0) {
      console.log('Sample ticket data from API:', ticketsData[0]);
      if (ticketsData[0].contract) {
        console.log('Contract data:', ticketsData[0].contract);
        console.log('User SEU in contract:', ticketsData[0].contract.user_seu);
      }
    }
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
       // Check different possible field names for SEU
      seu_name: ticket.contract?.user_seu ? 
        `${ticket.contract.user_seu.name || ''} ${ticket.contract.user_seu.cognome || ''}`.trim() : 
         (ticket.contract?.seu ? 
            `${ticket.contract.seu.name || ''} ${ticket.contract.seu.cognome || ''}`.trim() :
            (ticket.contract?.seu_user ?
                `${ticket.contract.seu_user.name || ''} ${ticket.contract.seu_user.cognome || ''}`.trim() : 
                'N/A')),
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

  //Get consistent color for user based on user ID
  getUserColor(userId: number): string {
    const colors = ['#ff6b6b', '#4ecdc4', '#95afc0', '#f368e0', '#feca57', '#00d2d3'];
    return colors[userId % colors.length];
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
      
      // Product filter - Multi-select
      if (this.filters.product.length > 0 && !this.filters.product.includes(ticket.product_name || '')) {
        return false;
      }
      
      // Priority filter - Multi-select
      if (this.filters.priority.length > 0 && !this.filters.priority.includes(ticket.priority)) {
        return false;
      }
      
      // Status filter - Multi-select
      if (this.filters.status.length > 0 && !this.filters.status.includes(ticket.status)) {
        return false;
      }
      
      // Customer filter
      if (this.filters.customer && 
          !ticket.customer_name.toLowerCase().includes(this.filters.customer.toLowerCase())) {
        return false;
      }
      
      // SEU filter - Multi-select
      if (this.filters.seu.length > 0) {
        const seuIds = this.filters.seu.map(id => parseInt(id));
        const ticketSeuUser = this.seuList.find(s => 
          ticket.seu_name && ticket.seu_name.toLowerCase().includes(s.name.toLowerCase())
        );
        if (!ticketSeuUser || !seuIds.includes(ticketSeuUser.id)) {
          return false;
        }
      }
      
      // Generated by filter - Multi-select
      if (this.filters.generatedBy.length > 0) {
        const generatorIds = this.filters.generatedBy.map(id => parseInt(id));
        if (!ticket.created_by_user_id || !generatorIds.includes(ticket.created_by_user_id)) {
          return false;
        }
      }
      
      // Assigned to filter - Multi-select
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
      openingDate: ''
    };
    this.applyFilters();
  }

  // ========================================
  // STATUS MULTI-SELECT METHODS
  // ========================================

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
    } else {
      this.filters.status.push(statusId);
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
    if (this.filters.status.length === 3) {
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

  // ========================================
  // PRODUCT MULTI-SELECT METHODS
  // ========================================

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
    if (this.filters.priority.length === 3) {
      return 'Tutte le Priorità';
    }
    const firstPriority = this.priorities.find(p => p.value === this.filters.priority[0]);
    return `${firstPriority?.label || 'Priorità'} (+${this.filters.priority.length - 1})`;
  }

  // ========================================
  // SEU MULTI-SELECT METHODS
  // ========================================

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

  // ========================================
  // GENERATED BY MULTI-SELECT METHODS
  // ========================================

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

  // ========================================
  // ASSIGNED TO MULTI-SELECT METHODS
  // ========================================

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

  // ========================================
  // CLOSE DROPDOWNS
  // ========================================

  closeAllDropdowns() {
    this.showStatusDropdown = false;
    this.showProductDropdown = false;
    this.showPriorityDropdown = false;
    this.showSeuDropdown = false;
    this.showGeneratedByDropdown = false;
    this.showAssignedToDropdown = false;
    this.updateDropdownClasses();
  }

  /**
   * Updates CSS classes for filter groups based on dropdown state
   */
  updateDropdownClasses() {
    // Use setTimeout to ensure DOM is updated
    setTimeout(() => {
      // Remove all dropdown-open classes first
      const allFilterGroups = document.querySelectorAll('.filter-group');
      allFilterGroups.forEach(group => {
        group.classList.remove('dropdown-open');
      });

      // Add dropdown-open class to the active dropdown's parent
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

  // Column visibility methods
  isColumnVisible(columnId: string): boolean {
    if (this.filters.status.length === 0 || this.filters.status.length === 3) {
      return true;
    }
    return this.filters.status.includes(columnId);
  }

  getVisibleColumnsCount(): number {
    if (this.filters.status.length === 0 || this.filters.status.length === 3) {
      return 3;
    }
    return this.filters.status.length;
  }

  getColumnPosition(columnId: string): number {
    const visibleColumns = this.columns.filter(c => this.isColumnVisible(c.id));
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

  sortTicketsByPriority() {
    const priorityWeight = {
      'high': 3,
      'medium': 2,
      'low': 1
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
}