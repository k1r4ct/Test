import { Component, OnInit, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { ToastrService } from 'ngx-toastr';
import { 
  EcommerceService, 
  Cart, 
  UserBalance 
} from 'src/app/servizi/ecommerce.service';

@Component({
  selector: 'app-checkout',
  templateUrl: './checkout.component.html',
  styleUrls: ['./checkout.component.scss'],
  standalone: false
})
export class CheckoutComponent implements OnInit, OnDestroy {

  private destroy$ = new Subject<void>();

  cart: Cart | null = null;
  userBalance: UserBalance | null = null;
  customerMessage: string = '';
  loading: boolean = false;
  processing: boolean = false;

  constructor(
    private ecommerceService: EcommerceService,
    private router: Router,
    private toastr: ToastrService
  ) {}

  ngOnInit(): void {
    this.loadCart();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  loadCart(): void {
    this.loading = true;
    
    this.ecommerceService.getCart()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (res) => {
          this.loading = false;
          if (res.response === 'ok') {
            this.cart = res.body.cart;
            this.userBalance = res.body.user_balance;
            
            // Redirect if cart is empty
            if (!this.cart || this.cart.items.length === 0) {
              this.toastr.info('Il carrello è vuoto');
              this.router.navigate(['/ecommerce/catalog']);
            }
          }
        },
        error: (err) => {
          this.loading = false;
          this.toastr.error('Errore nel caricamento');
          this.router.navigate(['/ecommerce/cart']);
        }
      });
  }

  canCheckout(): boolean {
    if (!this.cart || this.cart.items.length === 0) return false;
    if (!this.userBalance) return false;
    return this.userBalance.pv_disponibili >= this.cart.total_pv;
  }

  confirmOrder(): void {
    if (!this.canCheckout()) {
      this.toastr.error('Impossibile procedere con l\'ordine');
      return;
    }

    this.processing = true;

    this.ecommerceService.checkout(this.customerMessage || undefined)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (res) => {
          this.processing = false;
          if (res.response === 'ok') {
            this.toastr.success('Ordine creato con successo!');
            // Navigate to order detail
            this.router.navigate(['/ecommerce/orders', res.body.order.id]);
          } else {
            this.toastr.error(res.message || 'Errore nella creazione dell\'ordine');
          }
        },
        error: (err) => {
          this.processing = false;
          const message = err.error?.message || 'Errore nella creazione dell\'ordine';
          this.toastr.error(message);
        }
      });
  }

  goBack(): void {
    this.router.navigate(['/ecommerce/cart']);
  }
}
