import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Injectable, OnDestroy } from '@angular/core';
import { BehaviorSubject, Observable, Subject } from 'rxjs';
import { takeUntil, tap } from 'rxjs/operators';
import { environment } from 'src/environments/environment';
import { AuthService } from './auth.service';

// ==================== INTERFACES ====================

export interface Store {
  id: number;
  store_name: string;
  slug: string;
  store_type: string;
  description: string | null;
  logo_url: string | null;
  banner_url: string | null;
  articles_count: number;
}

export interface Category {
  id: number;
  category_name: string;
  slug: string;
  description: string | null;
  icon: string | null;
  parent_id: number | null;
  articles_count: number;
}

export interface Article {
  id: number;
  sku: string;
  article_name: string;
  description: string;
  pv_price: number;
  euro_price: number | null;
  formatted_pv_price: string;
  formatted_euro_price?: string;
  is_digital: boolean;
  is_featured: boolean;
  is_bestseller: boolean;
  available?: boolean;
  thumbnail_url: string | null;
  category_name?: string;
  store_name?: string;
  category?: { id: number; name: string; slug: string };
  store?: { id: number; name: string; slug: string };
  stock?: { quantity: number; in_stock: boolean; low_stock: boolean };
  gallery?: { id: number; url: string; type: string }[];
  attributes?: { code: string; label: string; value: any }[];
}

export interface CartItem {
  id: number;
  article_id: number;
  article_name: string;
  article_sku: string;
  thumbnail_url: string | null;
  is_digital: boolean;
  pv_unit_price: number;
  euro_unit_price: number | null;
  quantity: number;
  pv_total: number;
  category_name: string;
  added_at: string;
  expires_at: string;
}

export interface Cart {
  items: CartItem[];
  total_pv: number;
  total_items: number;
  items_count: number;
}

export interface UserBalance {
  pv_totali: number;
  pv_bloccati: number;
  pv_disponibili: number;
  punti_bonus?: number;
  punti_maturati?: number;
}

export interface Order {
  id: number;
  order_number: string;
  total_pv: number;
  formatted_total_pv: string;
  status: string;
  status_label: string;
  priority?: string;
  priority_label?: string;
  priority_class?: string;
  items_count: number;
  fulfilled_count?: number;
  fulfillment_progress: number;
  customer_message?: string;
  created_at: string;
  processing_started_at?: string;
  processed_at?: string;
  cancelled_at?: string;
  customer?: { id: number; name: string; email: string };
  assigned_to?: { id: number; name: string };
}

export interface OrderItem {
  id: number;
  article_id?: number;
  article_name: string;
  article_sku: string;
  quantity: number;
  pv_unit_price: number;
  pv_total_price: number;
  formatted_unit_price: string;
  formatted_total_price: string;
  status: string;
  status_label: string;
  redemption_code?: string;
  fulfilled_at?: string;
  fulfilled_by?: { id: number; name: string };
  customer_note?: string;
  internal_note?: string;
}

export interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

// ==================== SERVICE ====================

@Injectable({
  providedIn: 'root'
})
export class EcommerceService implements OnDestroy {

  private destroy$ = new Subject<void>();
  private apiUrl: string;
  private headers: HttpHeaders;

  // Cart state (reactive)
  private cartSubject = new BehaviorSubject<Cart | null>(null);
  private cartSummarySubject = new BehaviorSubject<{ items_count: number; total_pv: number }>({ items_count: 0, total_pv: 0 });
  private userBalanceSubject = new BehaviorSubject<UserBalance | null>(null);

  public cart$ = this.cartSubject.asObservable();
  public cartSummary$ = this.cartSummarySubject.asObservable();
  public userBalance$ = this.userBalanceSubject.asObservable();

  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) {
    this.apiUrl = environment.apiUrl + 'ecommerce/';
    this.headers = new HttpHeaders({
      'Authorization': 'Bearer ' + this.authService.getToken()
    });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  // Refresh headers (in case token changed)
  private getHeaders(): HttpHeaders {
    return new HttpHeaders({
      'Authorization': 'Bearer ' + this.authService.getToken()
    });
  }

  // ==================== CATALOG ====================

  /**
   * Get all stores visible to current user
   */
  getStores(): Observable<any> {
    return this.http.get(this.apiUrl + 'stores', { headers: this.getHeaders() })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get single store by slug
   */
  getStore(slug: string): Observable<any> {
    return this.http.get(this.apiUrl + 'stores/' + slug, { headers: this.getHeaders() })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get categories, optionally filtered by store
   */
  getCategories(storeId?: number): Observable<any> {
    let params = new HttpParams();
    if (storeId) {
      params = params.set('store_id', storeId.toString());
    }
    return this.http.get(this.apiUrl + 'categories', { headers: this.getHeaders(), params })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get articles with filters
   */
  getArticles(filters?: {
    store_id?: number;
    category_id?: number;
    featured?: boolean;
    bestseller?: boolean;
    min_pv?: number;
    max_pv?: number;
    search?: string;
    sort?: string;
    per_page?: number;
    page?: number;
  }): Observable<any> {
    let params = new HttpParams();
    
    if (filters) {
      if (filters.store_id) params = params.set('store_id', filters.store_id.toString());
      if (filters.category_id) params = params.set('category_id', filters.category_id.toString());
      if (filters.featured) params = params.set('featured', '1');
      if (filters.bestseller) params = params.set('bestseller', '1');
      if (filters.min_pv) params = params.set('min_pv', filters.min_pv.toString());
      if (filters.max_pv) params = params.set('max_pv', filters.max_pv.toString());
      if (filters.search) params = params.set('search', filters.search);
      if (filters.sort) params = params.set('sort', filters.sort);
      if (filters.per_page) params = params.set('per_page', filters.per_page.toString());
      if (filters.page) params = params.set('page', filters.page.toString());
    }

    return this.http.get(this.apiUrl + 'articles', { headers: this.getHeaders(), params })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get single article detail
   */
  getArticle(id: number): Observable<any> {
    return this.http.get(this.apiUrl + 'articles/' + id, { headers: this.getHeaders() })
      .pipe(takeUntil(this.destroy$));
  }

  // ==================== CART ====================

  /**
   * Get current cart
   */
  getCart(): Observable<any> {
    return this.http.get(this.apiUrl + 'cart', { headers: this.getHeaders() })
      .pipe(
        takeUntil(this.destroy$),
        tap((res: any) => {
          if (res.response === 'ok') {
            this.cartSubject.next(res.body.cart);
            this.userBalanceSubject.next(res.body.user_balance);
          }
        })
      );
  }

  /**
   * Get cart summary (for header badge)
   */
  getCartSummary(): Observable<any> {
    return this.http.get(this.apiUrl + 'cart/summary', { headers: this.getHeaders() })
      .pipe(
        takeUntil(this.destroy$),
        tap((res: any) => {
          if (res.response === 'ok') {
            this.cartSummarySubject.next({
              items_count: res.body.items_count,
              total_pv: res.body.total_pv
            });
          }
        })
      );
  }

  /**
   * Add item to cart
   */
  addToCart(articleId: number, quantity: number = 1): Observable<any> {
    return this.http.post(this.apiUrl + 'cart/add', { article_id: articleId, quantity }, { headers: this.getHeaders() })
      .pipe(
        takeUntil(this.destroy$),
        tap((res: any) => {
          if (res.response === 'ok') {
            this.userBalanceSubject.next(res.body.user_balance);
            this.refreshCartSummary();
          }
        })
      );
  }

  /**
   * Update cart item quantity
   */
  updateCartQuantity(cartItemId: number, quantity: number): Observable<any> {
    return this.http.put(this.apiUrl + 'cart/update/' + cartItemId, { quantity }, { headers: this.getHeaders() })
      .pipe(
        takeUntil(this.destroy$),
        tap((res: any) => {
          if (res.response === 'ok') {
            this.userBalanceSubject.next(res.body.user_balance);
            this.refreshCartSummary();
          }
        })
      );
  }

  /**
   * Remove item from cart
   */
  removeFromCart(cartItemId: number): Observable<any> {
    return this.http.delete(this.apiUrl + 'cart/remove/' + cartItemId, { headers: this.getHeaders() })
      .pipe(
        takeUntil(this.destroy$),
        tap((res: any) => {
          if (res.response === 'ok') {
            this.userBalanceSubject.next(res.body.user_balance);
            this.refreshCartSummary();
          }
        })
      );
  }

  /**
   * Clear entire cart
   */
  clearCart(): Observable<any> {
    return this.http.delete(this.apiUrl + 'cart/clear', { headers: this.getHeaders() })
      .pipe(
        takeUntil(this.destroy$),
        tap((res: any) => {
          if (res.response === 'ok') {
            this.cartSubject.next(null);
            this.userBalanceSubject.next(res.body.user_balance);
            this.cartSummarySubject.next({ items_count: 0, total_pv: 0 });
          }
        })
      );
  }

  /**
   * Refresh cart summary (call after cart changes)
   */
  refreshCartSummary(): void {
    this.getCartSummary().subscribe();
  }

  // ==================== ORDERS (Customer) ====================

  /**
   * Checkout - create order from cart
   */
  checkout(customerMessage?: string): Observable<any> {
    const body = customerMessage ? { customer_message: customerMessage } : {};
    return this.http.post(this.apiUrl + 'checkout', body, { headers: this.getHeaders() })
      .pipe(
        takeUntil(this.destroy$),
        tap((res: any) => {
          if (res.response === 'ok') {
            // Clear cart after successful checkout
            this.cartSubject.next(null);
            this.cartSummarySubject.next({ items_count: 0, total_pv: 0 });
          }
        })
      );
  }

  /**
   * Get user's order history
   */
  getOrders(filters?: { status?: string; per_page?: number; page?: number }): Observable<any> {
    let params = new HttpParams();
    if (filters) {
      if (filters.status) params = params.set('status', filters.status);
      if (filters.per_page) params = params.set('per_page', filters.per_page.toString());
      if (filters.page) params = params.set('page', filters.page.toString());
    }
    return this.http.get(this.apiUrl + 'orders', { headers: this.getHeaders(), params })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get order detail
   */
  getOrderDetail(orderId: number): Observable<any> {
    return this.http.get(this.apiUrl + 'orders/' + orderId, { headers: this.getHeaders() })
      .pipe(takeUntil(this.destroy$));
  }

  // ==================== ORDERS (Backoffice) ====================

  /**
   * Get all orders for backoffice processing
   */
  getAllOrders(filters?: {
    status?: string;
    priority?: string;
    assigned_to_me?: boolean;
    all?: boolean;
    per_page?: number;
    page?: number;
  }): Observable<any> {
    let params = new HttpParams();
    if (filters) {
      if (filters.status) params = params.set('status', filters.status);
      if (filters.priority) params = params.set('priority', filters.priority);
      if (filters.assigned_to_me) params = params.set('assigned_to_me', '1');
      if (filters.all) params = params.set('all', '1');
      if (filters.per_page) params = params.set('per_page', filters.per_page.toString());
      if (filters.page) params = params.set('page', filters.page.toString());
    }
    return this.http.get(this.apiUrl + 'admin/orders', { headers: this.getHeaders(), params })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Get order detail for backoffice
   */
  getAdminOrderDetail(orderId: number): Observable<any> {
    return this.http.get(this.apiUrl + 'admin/orders/' + orderId, { headers: this.getHeaders() })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Take order in charge (start processing)
   */
  startProcessingOrder(orderId: number): Observable<any> {
    return this.http.post(this.apiUrl + 'admin/orders/' + orderId + '/process', {}, { headers: this.getHeaders() })
      .pipe(takeUntil(this.destroy$));
  }

  /**
   * Fulfill order item (add redemption code)
   */
  fulfillOrderItem(orderId: number, itemId: number, redemptionCode: string, customerNote?: string): Observable<any> {
    const body: any = { redemption_code: redemptionCode };
    if (customerNote) body.customer_note = customerNote;
    
    return this.http.post(
      this.apiUrl + 'admin/orders/' + orderId + '/items/' + itemId + '/fulfill',
      body,
      { headers: this.getHeaders() }
    ).pipe(takeUntil(this.destroy$));
  }

  /**
   * Cancel order
   */
  cancelOrder(orderId: number, reason: string): Observable<any> {
    return this.http.post(
      this.apiUrl + 'admin/orders/' + orderId + '/cancel',
      { reason },
      { headers: this.getHeaders() }
    ).pipe(takeUntil(this.destroy$));
  }

  /**
   * Add admin note to order
   */
  addAdminNote(orderId: number, note: string): Observable<any> {
    return this.http.post(
      this.apiUrl + 'admin/orders/' + orderId + '/note',
      { note },
      { headers: this.getHeaders() }
    ).pipe(takeUntil(this.destroy$));
  }

  // ==================== HELPERS ====================

  /**
   * Format PV price
   */
  formatPv(pv: number): string {
    return new Intl.NumberFormat('it-IT').format(pv) + ' PV';
  }

  /**
   * Format Euro price
   */
  formatEuro(euro: number): string {
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(euro);
  }

  /**
   * Get status color class
   */
  getStatusClass(status: string): string {
    const statusClasses: { [key: string]: string } = {
      'in_attesa': 'badge-warning',
      'in_lavorazione': 'badge-info',
      'completato': 'badge-success',
      'annullato': 'badge-danger',
      'rimborsato': 'badge-secondary',
      'pending': 'badge-warning',
      'processing': 'badge-info',
      'fulfilled': 'badge-success',
      'cancelled': 'badge-danger',
      'refunded': 'badge-secondary',
    };
    return statusClasses[status] || 'badge-secondary';
  }

  /**
   * Get priority color class
   */
  getPriorityClass(priority: string): string {
    const priorityClasses: { [key: string]: string } = {
      'low': 'text-muted',
      'normal': 'text-primary',
      'high': 'text-warning',
      'urgent': 'text-danger',
    };
    return priorityClasses[priority] || 'text-primary';
  }
}