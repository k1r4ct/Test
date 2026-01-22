import { Component, OnInit, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { ToastrService } from 'ngx-toastr';
import { 
  EcommerceService, 
  Cart, 
  CartItem, 
  UserBalance 
} from 'src/app/servizi/ecommerce.service';

@Component({
  selector: 'app-cart',
  templateUrl: './cart.component.html',
  styleUrls: ['./cart.component.scss'],
  standalone: false
})
export class CartComponent implements OnInit, OnDestroy {

  private destroy$ = new Subject<void>();

  cart: Cart | null = null;
  userBalance: UserBalance | null = null;
  loading: boolean = false;
  updatingItem: number | null = null;

  constructor(
    private ecommerceService: EcommerceService,
    private router: Router,
    private toastr: ToastrService
  ) {}

  ngOnInit(): void {
    this.loadCart();
    this.subscribeToUpdates();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  /**
   * Subscribe to cart and balance updates
   */
  private subscribeToUpdates(): void {
    this.ecommerceService.cart$
      .pipe(takeUntil(this.destroy$))
      .subscribe(cart => {
        if (cart) {
          this.cart = cart;
        }
      });

    this.ecommerceService.userBalance$
      .pipe(takeUntil(this.destroy$))
      .subscribe(balance => {
        this.userBalance = balance;
      });
  }

  /**
   * Load cart from API
   */
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
          }
        },
        error: (err) => {
          this.loading = false;
          console.error('Error loading cart:', err);
          this.toastr.error('Errore nel caricamento del carrello');
        }
      });
  }

  /**
   * Update item quantity
   */
  updateQuantity(item: CartItem, newQuantity: number): void {
    if (newQuantity < 0) return;
    
    this.updatingItem = item.id;

    if (newQuantity === 0) {
      this.removeItem(item);
      return;
    }

    this.ecommerceService.updateCartQuantity(item.id, newQuantity)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (res) => {
          this.updatingItem = null;
          if (res.response === 'ok') {
            this.loadCart(); // Reload to get updated totals
            this.toastr.success('Quantità aggiornata');
          } else {
            this.toastr.error(res.message || 'Errore nell\'aggiornamento');
          }
        },
        error: (err) => {
          this.updatingItem = null;
          const message = err.error?.message || 'Errore nell\'aggiornamento';
          this.toastr.error(message);
        }
      });
  }

  /**
   * Remove item from cart
   */
  removeItem(item: CartItem): void {
    this.updatingItem = item.id;

    this.ecommerceService.removeFromCart(item.id)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (res) => {
          this.updatingItem = null;
          if (res.response === 'ok') {
            this.loadCart();
            this.toastr.success(`"${item.article_name}" rimosso dal carrello`);
          } else {
            this.toastr.error(res.message || 'Errore nella rimozione');
          }
        },
        error: (err) => {
          this.updatingItem = null;
          this.toastr.error('Errore nella rimozione dal carrello');
        }
      });
  }

  /**
   * Clear entire cart
   */
  clearCart(): void {
    if (!confirm('Sei sicuro di voler svuotare il carrello?')) {
      return;
    }

    this.loading = true;

    this.ecommerceService.clearCart()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (res) => {
          this.loading = false;
          if (res.response === 'ok') {
            this.cart = null;
            this.toastr.success('Carrello svuotato');
          }
        },
        error: (err) => {
          this.loading = false;
          this.toastr.error('Errore nello svuotamento del carrello');
        }
      });
  }

  /**
   * Check if user can proceed to checkout
   */
  canCheckout(): boolean {
    if (!this.cart || this.cart.items.length === 0) return false;
    if (!this.userBalance) return false;
    return this.userBalance.pv_disponibili >= this.cart.total_pv;
  }

  /**
   * Navigate to checkout
   */
  proceedToCheckout(): void {
    if (!this.canCheckout()) {
      this.toastr.warning('PV insufficienti per procedere');
      return;
    }
    this.router.navigate(['/ecommerce/checkout']);
  }

  /**
   * Handle image error
   */
  onImageError(event: Event): void {
    const img = event.target as HTMLImageElement;
    img.src = 'assets/img/placeholder-product.png';
  }
}
