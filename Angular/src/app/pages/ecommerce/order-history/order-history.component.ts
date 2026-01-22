import { Component, OnInit, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { ToastrService } from 'ngx-toastr';
import { EcommerceService, Order, Pagination } from 'src/app/servizi/ecommerce.service';

@Component({
  selector: 'app-order-history',
  templateUrl: './order-history.component.html',
  styleUrls: ['./order-history.component.scss'],
  standalone: false
})
export class OrderHistoryComponent implements OnInit, OnDestroy {

  private destroy$ = new Subject<void>();

  orders: Order[] = [];
  pagination: Pagination | null = null;
  loading: boolean = false;
  
  // Filters
  statusFilter: string = '';
  currentPage: number = 1;
  perPage: number = 10;

  statusOptions = [
    { value: '', label: 'Tutti gli stati' },
    { value: 'in_attesa', label: 'In attesa' },
    { value: 'in_lavorazione', label: 'In lavorazione' },
    { value: 'completato', label: 'Completato' },
    { value: 'annullato', label: 'Annullato' },
  ];

  constructor(
    private ecommerceService: EcommerceService,
    private router: Router,
    private toastr: ToastrService
  ) {}

  ngOnInit(): void {
    this.loadOrders();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  loadOrders(): void {
    this.loading = true;

    const filters: any = {
      per_page: this.perPage,
      page: this.currentPage
    };

    if (this.statusFilter) {
      filters.status = this.statusFilter;
    }

    this.ecommerceService.getOrders(filters)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (res) => {
          this.loading = false;
          if (res.response === 'ok') {
            this.orders = res.body.orders;
            this.pagination = res.body.pagination;
          }
        },
        error: (err) => {
          this.loading = false;
          this.toastr.error('Errore nel caricamento degli ordini');
        }
      });
  }

  onFilterChange(): void {
    this.currentPage = 1;
    this.loadOrders();
  }

  goToPage(page: number): void {
    this.currentPage = page;
    this.loadOrders();
  }

  viewOrder(order: Order): void {
    this.router.navigate(['/ecommerce/orders', order.id]);
  }

  getStatusClass(status: string): string {
    return this.ecommerceService.getStatusClass(status);
  }
}
