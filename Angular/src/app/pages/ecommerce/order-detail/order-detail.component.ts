import { Component, OnInit, OnDestroy } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { ToastrService } from 'ngx-toastr';
import { EcommerceService, Order, OrderItem } from 'src/app/servizi/ecommerce.service';

@Component({
  selector: 'app-order-detail',
  templateUrl: './order-detail.component.html',
  styleUrls: ['./order-detail.component.scss'],
  standalone: false
})
export class OrderDetailComponent implements OnInit, OnDestroy {

  private destroy$ = new Subject<void>();

  order: Order | null = null;
  items: OrderItem[] = [];
  loading: boolean = false;
  orderId: number = 0;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private ecommerceService: EcommerceService,
    private toastr: ToastrService
  ) {}

  ngOnInit(): void {
    this.orderId = +this.route.snapshot.paramMap.get('id')!;
    if (this.orderId) {
      this.loadOrder();
    } else {
      this.router.navigate(['/ecommerce/orders']);
    }
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  loadOrder(): void {
    this.loading = true;

    this.ecommerceService.getOrderDetail(this.orderId)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (res) => {
          this.loading = false;
          if (res.response === 'ok') {
            this.order = res.body.order;
            this.items = res.body.items;
          } else {
            this.toastr.error('Ordine non trovato');
            this.router.navigate(['/ecommerce/orders']);
          }
        },
        error: (err) => {
          this.loading = false;
          this.toastr.error('Errore nel caricamento dell\'ordine');
          this.router.navigate(['/ecommerce/orders']);
        }
      });
  }

  copyCode(code: string): void {
    navigator.clipboard.writeText(code).then(() => {
      this.toastr.success('Codice copiato!');
    });
  }

  getStatusClass(status: string): string {
    return this.ecommerceService.getStatusClass(status);
  }

  goBack(): void {
    this.router.navigate(['/ecommerce/orders']);
  }
}
