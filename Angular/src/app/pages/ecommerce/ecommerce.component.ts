import { Component, OnInit, OnDestroy, HostListener, ViewChild, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { trigger, transition, style, animate, state } from '@angular/animations';
import { Subscription, Subject } from 'rxjs';
import { debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { 
  EcommerceService, 
  Store, 
  Category, 
  Article, 
  Cart, 
  CartItem, 
  UserBalance,
  Order,
  OrderItem,
  Pagination 
} from 'src/app/servizi/ecommerce.service';
import { ApiService } from 'src/app/servizi/api.service';
import { ToastrService } from 'ngx-toastr';
import { MaterialComponentsModule } from 'src/app/material-components/material-components/material-components.component';

// ==================== INTERFACES ====================

interface CarouselSlide {
  id: number;
  title: string;
  description: string;
  badge: string;
  badgeIcon?: string;
  ctaText: string;
  ctaAction: string;
  ctaDisabled?: boolean;
  imageUrl: string;
  gradient: string;
}

interface WalletTransaction {
  id: number;
  type: 'credit' | 'debit' | 'pending';
  title: string;
  amount: number;
  date: string;
}

/**
 * ProductRow interface for dynamic product row sections.
 * Supports featured, new arrivals, category-based, and manual selections.
 * Prepared for future backend configuration via page builder.
 */
interface ProductRow {
  row_key: string;
  title: string;
  icon: string;
  row_type: 'featured' | 'new_arrivals' | 'bestsellers' | 'category' | 'manual';
  display_style: 'grid' | 'carousel' | 'compact';
  items_per_row: number;
  max_items: number;
  category_id?: number;
  category_name?: string;
  category_slug?: string;
  articles: Article[];
  is_auto_generated?: boolean;
  custom_css?: string;
}

/**
 * Configuration for product rows - will be loaded from backend in future.
 * For now, defined statically but structured for API compatibility.
 */
interface ProductRowConfig {
  row_key: string;
  title: string | null; // null = auto-generate
  row_type: 'featured' | 'new_arrivals' | 'bestsellers' | 'category' | 'manual';
  icon: string;
  max_items: number;
  items_per_row: number;
  display_style: 'grid' | 'carousel' | 'compact';
  category_id?: number;
  is_system: boolean;
  sort_order: number;
}

type ViewState = 'catalog' | 'product-detail' | 'checkout' | 'order-success' | 'orders' | 'order-detail' | 'wallet';

// ==================== COMPONENT ====================

@Component({
  selector: 'app-ecommerce',
  standalone: false,
  templateUrl: './ecommerce.component.html',
  styleUrls: ['./ecommerce.component.scss'],
  animations: [
    // Page transition animation
    trigger('pageTransition', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateY(20px)' }),
        animate('300ms ease-out', style({ opacity: 1, transform: 'translateY(0)' }))
      ]),
      transition(':leave', [
        animate('200ms ease-in', style({ opacity: 0, transform: 'translateY(-20px)' }))
      ])
    ]),
    // Cart sidebar animation
    trigger('cartSlide', [
      state('closed', style({ transform: 'translateX(100%)' })),
      state('open', style({ transform: 'translateX(0)' })),
      transition('closed <=> open', animate('300ms cubic-bezier(0.4, 0, 0.2, 1)'))
    ]),
    // Toast animation
    trigger('toastAnimation', [
      transition(':enter', [
        style({ transform: 'translateX(100%)', opacity: 0 }),
        animate('300ms ease-out', style({ transform: 'translateX(0)', opacity: 1 }))
      ]),
      transition(':leave', [
        animate('200ms ease-in', style({ transform: 'translateX(100%)', opacity: 0 }))
      ])
    ]),
    // Fade animation
    trigger('fadeIn', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('200ms ease-out', style({ opacity: 1 }))
      ])
    ]),
    // Scale animation for modals
    trigger('scaleIn', [
      transition(':enter', [
        style({ transform: 'scale(0.9)', opacity: 0 }),
        animate('200ms ease-out', style({ transform: 'scale(1)', opacity: 1 }))
      ])
    ])
  ]
})
export class EcommerceComponent implements OnInit, OnDestroy {

  // ==================== VIEW STATE ====================
  currentView: ViewState = 'catalog';
  isLoading: boolean = true;
  isLoadingMore: boolean = false;

  // ==================== USER DATA ====================
  currentUser: any;
  userRole: number = 0;
  userBalance: UserBalance | null = null;

  // ==================== CATALOG DATA ====================
  stores: Store[] = [];
  categories: Category[] = [];
  articles: Article[] = [];
  featuredArticles: Article[] = [];
  pagination: Pagination | null = null;
  
  // Selected store/category
  selectedStore: Store | null = null;
  selectedCategory: Category | null = null;
  selectedArticle: Article | null = null;

  // ==================== FILTERS ====================
  searchQuery: string = '';
  searchSubject = new Subject<string>();
  sortOption: string = 'default';

  // ==================== CART ====================
  cart: Cart | null = null;
  cartItems: CartItem[] = [];
  isCartOpen: boolean = false;
  cartItemsCount: number = 0;
  cartTotalPv: number = 0;

  // ==================== CHECKOUT ====================
  customerMessage: string = '';
  isProcessingCheckout: boolean = false;
  lastOrderNumber: string = '';
  lastOrderTotal: number = 0;
  lastOrderDate: string = '';

  // ==================== ORDERS ====================
  orders: Order[] = [];
  selectedOrder: Order | null = null;
  selectedOrderItems: OrderItem[] = [];
  ordersFilter: string = '';
  ordersPagination: Pagination | null = null;
  isLoadingOrders: boolean = false;

  // ==================== WALLET ====================
  walletTransactions: WalletTransaction[] = [];

  // ==================== CAROUSEL ====================
  carouselSlides: CarouselSlide[] = [];
  currentSlideIndex: number = 0;
  previousSlideIndex: number = 0;
  carouselInterval: any;
  carouselIntervalDuration: number = 5000;
  isSlideAnimating: boolean = false;

  // ==================== PRODUCT ROWS ====================
  /**
   * Dynamic product rows for the catalog.
   * Currently built from frontend logic, prepared for future backend configuration.
   * Rows are rebuilt after each article load to reflect current data.
   */
  productRows: ProductRow[] = [];
  
  /**
   * Configuration for system rows (In Evidenza, Novità).
   * In future, this will be loaded from backend API.
   * BO will be able to modify titles, icons, and add custom rows.
   */
  private productRowsConfig: ProductRowConfig[] = [
    {
      row_key: 'featured',
      title: 'In Evidenza',
      row_type: 'featured',
      icon: 'local_fire_department',
      max_items: 8,
      items_per_row: 4,
      display_style: 'grid',
      is_system: true,
      sort_order: 1
    },
    {
      row_key: 'new_arrivals',
      title: 'Novità',
      row_type: 'new_arrivals',
      icon: 'new_releases',
      max_items: 8,
      items_per_row: 4,
      display_style: 'grid',
      is_system: true,
      sort_order: 2
    }
  ];

  // ==================== MOBILE ====================
  isMobile: boolean = false;

  // ==================== QUANTITY SELECTOR ====================
  selectedQuantity: number = 1;
  maxQuantity: number = 10;

  // ==================== TOAST NOTIFICATIONS ====================
  toasts: { id: number; type: 'success' | 'error' | 'info'; title: string; message: string }[] = [];
  toastCounter: number = 0;

  // ==================== SUBSCRIPTIONS ====================
  private subscriptions: Subscription[] = [];

  // ==================== VIEW CHILDREN ====================
  @ViewChild('searchInput') searchInput!: ElementRef;
  @ViewChild('catalogSection') catalogSection!: ElementRef;

  constructor(
    private ecommerceService: EcommerceService,
    private apiService: ApiService,
    private toastr: ToastrService
  ) {}

  // ==================== LIFECYCLE ====================

  ngOnInit(): void {
    this.checkMobile();
    this.loadCurrentUser();
    this.initializeCarousel();
    this.initializeWalletTransactions();
    this.loadInitialData();
    this.setupSearchDebounce();
    this.subscribeToCartUpdates();
    this.startCarouselAutoplay();
  }

  ngOnDestroy(): void {
    this.subscriptions.forEach(sub => sub.unsubscribe());
    if (this.carouselInterval) {
      clearInterval(this.carouselInterval);
    }
  }

  @HostListener('window:resize', ['$event'])
  onResize(): void {
    this.checkMobile();
  }

  // ==================== INITIALIZATION ====================

  private checkMobile(): void {
    this.isMobile = window.innerWidth < 768;
  }

  private loadCurrentUser(): void {
    const userId = localStorage.getItem('userLogin');
    if (userId) {
      this.apiService.PrendiUtente().subscribe({
        next: (response: any) => {
          if (response && response.user) {
            this.currentUser = response.user;
            this.userRole = response.user.role_id || response.user.role?.id || 0;
          }
        },
        error: (err) => {
          console.error('Error loading current user:', err);
        }
      });
    }
  }

  private initializeCarousel(): void {
    this.carouselSlides = [
      {
        id: 1,
        title: 'Buoni Amazon Disponibili!',
        description: 'Utilizza i tuoi Punti Valore per ottenere buoni regalo Amazon da 15€, 30€ e 50€. Consegna immediata!',
        badge: '🎉 Disponibile Ora',
        ctaText: 'Scopri i Buoni',
        ctaAction: 'scroll-catalog',
        imageUrl: 'https://m.media-amazon.com/images/G/01/gc/designs/livepreview/amazon_dkblue_noto_email_v2016_us-main._CB468775337_.png',
        gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
      },
      {
        id: 2,
        title: 'Premia la Tua Fedeltà!',
        description: 'Ogni abbonamento ti fa guadagnare Punti Valore. Accumula e spendi nel nostro store per ottenere fantastici premi!',
        badge: '💰 Sistema Cashback',
        ctaText: 'Vai al Wallet',
        ctaAction: 'open-wallet',
        imageUrl: 'https://cdn-icons-png.flaticon.com/512/2331/2331966.png',
        gradient: 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)'
      },
      {
        id: 3,
        title: 'Buoni Carburante in Arrivo!',
        description: 'Presto potrai utilizzare i tuoi PV anche per ottenere buoni carburante. Risparmia su ogni rifornimento!',
        badge: '🚀 Prossimamente',
        ctaText: 'Disponibile Presto',
        ctaAction: 'coming-soon',
        ctaDisabled: true,
        imageUrl: 'https://cdn-icons-png.flaticon.com/512/2917/2917995.png',
        gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
      },
      {
        id: 4,
        title: 'Vendi i Tuoi Prodotti!',
        description: 'Presto i clienti potranno caricare e vendere prodotti delle proprie attività commerciali direttamente su Semprechiaro Store. Espandi il tuo business!',
        badge: '🏪 Novità in Arrivo',
        ctaText: 'Coming Soon',
        ctaAction: 'coming-soon',
        ctaDisabled: true,
        imageUrl: 'https://cdn-icons-png.flaticon.com/512/3081/3081559.png',
        gradient: 'linear-gradient(135deg, #4776E6 0%, #8E54E9 100%)'
      }
    ];
  }

  private initializeWalletTransactions(): void {
    // This will be populated from API in real implementation
    this.walletTransactions = [
      { id: 1, type: 'credit', title: 'Bonus Benvenuto', amount: 500, date: '15/01/2026' },
      { id: 2, type: 'credit', title: 'Punti Maturati - Contratto Luce', amount: 400, date: '10/01/2026' },
      { id: 3, type: 'credit', title: 'Punti Maturati - Contratto Gas', amount: 300, date: '05/01/2026' }
    ];
  }

  private loadInitialData(): void {
    this.isLoading = true;

    // Load stores
    const storesSub = this.ecommerceService.getStores().subscribe({
      next: (res) => {
        if (res.response === 'ok') {
          this.stores = res.body.stores;
        }
      },
      error: (err) => console.error('Error loading stores:', err)
    });
    this.subscriptions.push(storesSub);

    // Load categories
    const categoriesSub = this.ecommerceService.getCategories().subscribe({
      next: (res) => {
        if (res.response === 'ok') {
          this.categories = res.body.categories;
        }
      },
      error: (err) => console.error('Error loading categories:', err)
    });
    this.subscriptions.push(categoriesSub);

    // Load articles
    this.loadArticles(true);

    // Load cart
    this.loadCart();
  }

  private setupSearchDebounce(): void {
    const searchSub = this.searchSubject.pipe(
      debounceTime(400),
      distinctUntilChanged()
    ).subscribe(query => {
      this.searchQuery = query;
      this.loadArticles(true);
    });
    this.subscriptions.push(searchSub);
  }

  private subscribeToCartUpdates(): void {
    const cartSub = this.ecommerceService.cart$.subscribe(cart => {
      if (cart) {
        this.cart = cart;
        this.cartItems = cart.items;
        this.cartItemsCount = cart.items_count;
        this.cartTotalPv = cart.total_pv;
      }
    });
    this.subscriptions.push(cartSub);

    const balanceSub = this.ecommerceService.userBalance$.subscribe(balance => {
      if (balance) {
        this.userBalance = balance;
      }
    });
    this.subscriptions.push(balanceSub);

    const summarySub = this.ecommerceService.cartSummary$.subscribe(summary => {
      this.cartItemsCount = summary.items_count;
      this.cartTotalPv = summary.total_pv;
    });
    this.subscriptions.push(summarySub);
  }

  // ==================== CAROUSEL ====================

  private startCarouselAutoplay(): void {
    this.carouselInterval = setInterval(() => {
      this.nextSlide();
    }, this.carouselIntervalDuration);
  }

  nextSlide(): void {
    this.triggerSlideChange((this.currentSlideIndex + 1) % this.carouselSlides.length);
  }

  prevSlide(): void {
    this.triggerSlideChange(
      (this.currentSlideIndex - 1 + this.carouselSlides.length) % this.carouselSlides.length
    );
  }

  goToSlide(index: number): void {
    if (index === this.currentSlideIndex) return;
    
    this.triggerSlideChange(index);
    
    // Reset autoplay timer
    if (this.carouselInterval) {
      clearInterval(this.carouselInterval);
      this.startCarouselAutoplay();
    }
  }

  /**
   * Handle slide transition with animation
   */
  private triggerSlideChange(newIndex: number): void {
    // Set animation flag and track previous slide
    this.isSlideAnimating = true;
    this.previousSlideIndex = this.currentSlideIndex;
    this.currentSlideIndex = newIndex;

    // Reset animation flag after animation completes (600ms matches CSS)
    setTimeout(() => {
      this.isSlideAnimating = false;
    }, 600);
  }

  onCarouselCtaClick(action: string): void {
    switch (action) {
      case 'scroll-catalog':
        this.scrollToCatalog();
        break;
      case 'open-wallet':
        this.navigateTo('wallet');
        break;
      case 'coming-soon':
        // Do nothing for disabled CTAs
        break;
    }
  }

  private scrollToCatalog(): void {
    if (this.catalogSection) {
      this.catalogSection.nativeElement.scrollIntoView({ behavior: 'smooth' });
    }
  }

  // ==================== PRODUCT ROWS ====================

  /**
   * Builds the dynamic product rows based on loaded articles.
   * Creates system rows (featured, new arrivals) and auto-generates category rows.
   * 
   * Future: This will load configuration from backend API,
   * allowing BO to customize rows via page builder.
   */
  private buildProductRows(): void {
    if (this.articles.length === 0) {
      this.productRows = [];
      return;
    }

    const rows: ProductRow[] = [];

    // 1. Build system rows from config
    for (const config of this.productRowsConfig) {
      const row = this.buildRowFromConfig(config);
      if (row && row.articles.length > 0) {
        rows.push(row);
      }
    }

    // 2. Auto-generate category rows for categories not in system rows
    const categoryRows = this.buildCategoryRows();
    rows.push(...categoryRows);

    // 3. If no rows were generated, create a fallback "All Products" row
    if (rows.length === 0 && this.articles.length > 0) {
      rows.push({
        row_key: 'all_products',
        title: 'Prodotti Disponibili',
        icon: 'shopping_bag',
        row_type: 'manual',
        display_style: 'grid',
        items_per_row: 4,
        max_items: 12,
        articles: this.articles.slice(0, 12),
        is_auto_generated: true
      });
    }

    this.productRows = rows;
    
    // Debug log (remove in production)
    console.log('Product rows built:', this.productRows.length, 'rows');
    this.productRows.forEach(r => console.log(`- ${r.title}: ${r.articles.length} articles`));
  }

  /**
   * Builds a single product row based on configuration.
   */
  private buildRowFromConfig(config: ProductRowConfig): ProductRow | null {
    let articles: Article[] = [];

    switch (config.row_type) {
      case 'featured':
        // Articles with is_featured = true
        articles = this.articles
          .filter(a => a.is_featured)
          .slice(0, config.max_items);
        
        // Fallback: if no featured articles, use first N articles
        if (articles.length === 0) {
          articles = this.articles.slice(0, config.max_items);
        }
        break;

      case 'new_arrivals':
        // Articles sorted by ID (higher ID = more recent)
        // Using ID as proxy since created_at is not in API response
        // TODO: Add created_at to transformArticle in backend for accurate sorting
        articles = [...this.articles]
          .sort((a, b) => b.id - a.id)
          .slice(0, config.max_items);
        break;

      case 'bestsellers':
        // Future: sort by sales count
        // For now, use featured articles
        articles = this.articles
          .filter(a => a.is_featured)
          .slice(0, config.max_items);
        break;

      case 'category':
        // Filter by category_name since category_id is not in API response
        if (config.category_id) {
          // If we have category info from manual config
          articles = this.articles
            .filter(a => a.category?.id === config.category_id)
            .slice(0, config.max_items);
        }
        break;

      case 'manual':
        // Future: load specific article IDs from config
        break;
    }

    if (articles.length === 0) {
      return null;
    }

    return {
      row_key: config.row_key,
      title: config.title || this.getDefaultRowTitle(config),
      icon: config.icon,
      row_type: config.row_type,
      display_style: config.display_style,
      items_per_row: config.items_per_row,
      max_items: config.max_items,
      category_id: config.category_id,
      articles: articles
    };
  }

  /**
   * Auto-generates category rows for categories with articles.
   * Creates one row per category, with articles from that category.
   * Uses category_name for grouping since category_id is not in API response.
   */
  private buildCategoryRows(): ProductRow[] {
    const categoryRows: ProductRow[] = [];

    // Group articles by category_name (since category_id is not in transform response)
    const articlesByCategory = new Map<string, Article[]>();
    
    for (const article of this.articles) {
      const categoryName = article.category_name || article.category?.name;
      if (categoryName) {
        const existing = articlesByCategory.get(categoryName) || [];
        existing.push(article);
        articlesByCategory.set(categoryName, existing);
      }
    }

    // Create a row for each category
    let categoryIndex = 0;
    
    articlesByCategory.forEach((articles, categoryName) => {
      // Get category info from first article
      const firstArticle = articles[0];
      const categoryId = firstArticle.category?.id;

      categoryRows.push({
        row_key: `category_${categoryId || categoryIndex}`,
        title: `Nella categoria ${categoryName}`,
        icon: 'category',
        row_type: 'category',
        display_style: 'grid',
        items_per_row: 4,
        max_items: 8,
        category_id: categoryId,
        category_name: categoryName,
        articles: articles.slice(0, 8),
        is_auto_generated: true
      });

      categoryIndex++;
    });

    return categoryRows;
  }

  /**
   * Gets default title for a row based on its type.
   */
  private getDefaultRowTitle(config: ProductRowConfig): string {
    switch (config.row_type) {
      case 'featured':
        return 'In Evidenza';
      case 'new_arrivals':
        return 'Novità';
      case 'bestsellers':
        return 'I Più Venduti';
      case 'category':
        return 'Prodotti';
      case 'manual':
        return 'Selezionati per Te';
      default:
        return 'Prodotti';
    }
  }

  /**
   * TrackBy function for product rows.
   */
  trackByRowKey(index: number, row: ProductRow): string {
    return row.row_key;
  }

  // ==================== DATA LOADING ====================

  loadArticles(reset: boolean = false): void {
    if (reset) {
      this.articles = [];
      this.productRows = []; // Reset product rows
      this.isLoading = true;
    } else {
      this.isLoadingMore = true;
    }

    const filters: any = {
      // Request more items on initial load to populate multiple rows
      per_page: reset ? 50 : 12,
      page: reset ? 1 : (this.pagination?.current_page || 0) + 1
    };

    if (this.selectedStore) {
      filters.store_id = this.selectedStore.id;
    }
    if (this.selectedCategory) {
      filters.category_id = this.selectedCategory.id;
    }
    if (this.searchQuery.trim()) {
      filters.search = this.searchQuery.trim();
    }
    if (this.sortOption !== 'default') {
      filters.sort = this.sortOption;
    }

    const articlesSub = this.ecommerceService.getArticles(filters).subscribe({
      next: (res) => {
        console.log('API Response:', res); // DEBUG
        if (res.response === 'ok') {
          if (reset) {
            this.articles = res.body.articles;
          } else {
            this.articles = [...this.articles, ...res.body.articles];
          }
          this.pagination = res.body.pagination;
          
          console.log('Articles loaded:', this.articles.length); // DEBUG

          // Set featured articles (legacy, kept for compatibility)
          if (reset && this.articles.length > 0) {
            this.featuredArticles = this.articles.filter(a => a.is_featured).slice(0, 4);
            if (this.featuredArticles.length === 0) {
              this.featuredArticles = this.articles.slice(0, 4);
            }
          }

          // Build dynamic product rows
          if (reset) {
            console.log('Calling buildProductRows...'); // DEBUG
            this.buildProductRows();
          }
        } else {
          console.error('API returned non-ok response:', res); // DEBUG
        }
        this.isLoading = false;
        this.isLoadingMore = false;
      },
      error: (err) => {
        console.error('Error loading articles:', err);
        this.isLoading = false;
        this.isLoadingMore = false;
        this.showToast('error', 'Errore', 'Impossibile caricare i prodotti');
      }
    });
    this.subscriptions.push(articlesSub);
  }

  loadCart(): void {
    const cartSub = this.ecommerceService.getCart().subscribe({
      next: (res) => {
        if (res.response === 'ok') {
          this.cart = res.body.cart;
          this.cartItems = res.body.cart.items;
          this.cartItemsCount = res.body.cart.items_count;
          this.cartTotalPv = res.body.cart.total_pv;
          this.userBalance = res.body.user_balance;
        }
      },
      error: (err) => console.error('Error loading cart:', err)
    });
    this.subscriptions.push(cartSub);
  }

  loadOrders(): void {
    this.isLoadingOrders = true;

    const filters: any = { per_page: 20 };
    if (this.ordersFilter) {
      filters.status = this.ordersFilter;
    }

    const ordersSub = this.ecommerceService.getOrders(filters).subscribe({
      next: (res) => {
        if (res.response === 'ok') {
          this.orders = res.body.orders;
          this.ordersPagination = res.body.pagination;
        }
        this.isLoadingOrders = false;
      },
      error: (err) => {
        console.error('Error loading orders:', err);
        this.isLoadingOrders = false;
        this.showToast('error', 'Errore', 'Impossibile caricare gli ordini');
      }
    });
    this.subscriptions.push(ordersSub);
  }

  loadOrderDetail(orderId: number): void {
    this.isLoading = true;

    const orderSub = this.ecommerceService.getOrderDetail(orderId).subscribe({
      next: (res) => {
        if (res.response === 'ok') {
          this.selectedOrder = res.body.order;
          this.selectedOrderItems = res.body.items;
          this.currentView = 'order-detail';
        }
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error loading order detail:', err);
        this.isLoading = false;
        this.showToast('error', 'Errore', 'Impossibile caricare i dettagli dell\'ordine');
      }
    });
    this.subscriptions.push(orderSub);
  }

  // ==================== FILTERS ====================

  onSearchInput(event: Event): void {
    const query = (event.target as HTMLInputElement).value;
    this.searchSubject.next(query);
  }

  clearSearch(): void {
    this.searchQuery = '';
    this.searchSubject.next('');
    if (this.searchInput) {
      this.searchInput.nativeElement.value = '';
    }
  }

  filterByStore(storeSlug: string | null): void {
    if (storeSlug) {
      this.selectedStore = this.stores.find(s => s.slug === storeSlug) || null;
    } else {
      this.selectedStore = null;
    }
    this.loadArticles(true);
  }

  filterByCategory(categoryId: number | null): void {
    if (categoryId) {
      this.selectedCategory = this.categories.find(c => c.id === categoryId) || null;
    } else {
      this.selectedCategory = null;
    }
    this.loadArticles(true);
  }

  clearFilters(): void {
    this.selectedStore = null;
    this.selectedCategory = null;
    this.searchQuery = '';
    this.sortOption = 'default';
    this.loadArticles(true);
  }

  hasActiveFilters(): boolean {
    return !!(this.selectedStore || this.selectedCategory || this.searchQuery.trim() || this.sortOption !== 'default');
  }

  // ==================== PRODUCT DETAIL ====================

  openProductDetail(article: Article): void {
    this.isLoading = true;
    this.selectedQuantity = 1;

    const articleSub = this.ecommerceService.getArticle(article.id).subscribe({
      next: (res) => {
        if (res.response === 'ok') {
          this.selectedArticle = res.body.article;
          this.currentView = 'product-detail';
        }
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error loading article detail:', err);
        this.isLoading = false;
        this.showToast('error', 'Errore', 'Impossibile caricare il prodotto');
      }
    });
    this.subscriptions.push(articleSub);
  }

  closeProductDetail(): void {
    this.selectedArticle = null;
    this.currentView = 'catalog';
  }

  incrementQuantity(): void {
    if (this.selectedQuantity < this.maxQuantity) {
      this.selectedQuantity++;
    }
  }

  decrementQuantity(): void {
    if (this.selectedQuantity > 1) {
      this.selectedQuantity--;
    }
  }

  getTotalPvForQuantity(): number {
    if (!this.selectedArticle) return 0;
    return this.selectedArticle.pv_price * this.selectedQuantity;
  }

  canAffordProduct(article: Article | null): boolean {
    if (!article || !this.userBalance) return false;
    return this.userBalance.pv_disponibili >= (article.pv_price * this.selectedQuantity);
  }

  // ==================== CART ====================

  toggleCart(): void {
    this.isCartOpen = !this.isCartOpen;
    if (this.isCartOpen) {
      this.loadCart();
    }
  }

  closeCart(): void {
    this.isCartOpen = false;
  }

  addToCart(article: Article, quantity: number = 1): void {
    const addSub = this.ecommerceService.addToCart(article.id, quantity).subscribe({
      next: (res) => {
        if (res.response === 'ok') {
          this.showToast('success', 'Aggiunto al carrello', `${article.article_name} x${quantity}`);
          this.loadCart();
        } else {
          this.showToast('error', 'Errore', res.message || 'Impossibile aggiungere al carrello');
        }
      },
      error: (err) => {
        console.error('Error adding to cart:', err);
        const message = err.error?.message || 'Impossibile aggiungere al carrello';
        this.showToast('error', 'Errore', message);
      }
    });
    this.subscriptions.push(addSub);
  }

  addToCartFromDetail(): void {
    if (this.selectedArticle) {
      this.addToCart(this.selectedArticle, this.selectedQuantity);
    }
  }

  updateCartItemQuantity(cartItemId: number, newQuantity: number): void {
    if (newQuantity < 0) return;

    if (newQuantity === 0) {
      this.removeFromCart(cartItemId);
      return;
    }

    const updateSub = this.ecommerceService.updateCartQuantity(cartItemId, newQuantity).subscribe({
      next: (res) => {
        if (res.response === 'ok') {
          this.loadCart();
        } else {
          this.showToast('error', 'Errore', res.message || 'Impossibile aggiornare la quantità');
        }
      },
      error: (err) => {
        console.error('Error updating cart:', err);
        this.showToast('error', 'Errore', 'Impossibile aggiornare la quantità');
      }
    });
    this.subscriptions.push(updateSub);
  }

  removeFromCart(cartItemId: number): void {
    const removeSub = this.ecommerceService.removeFromCart(cartItemId).subscribe({
      next: (res) => {
        if (res.response === 'ok') {
          this.showToast('info', 'Rimosso', 'Prodotto rimosso dal carrello');
          this.loadCart();
        } else {
          this.showToast('error', 'Errore', res.message || 'Impossibile rimuovere dal carrello');
        }
      },
      error: (err) => {
        console.error('Error removing from cart:', err);
        this.showToast('error', 'Errore', 'Impossibile rimuovere dal carrello');
      }
    });
    this.subscriptions.push(removeSub);
  }

  clearCart(): void {
    const clearSub = this.ecommerceService.clearCart().subscribe({
      next: (res) => {
        if (res.response === 'ok') {
          this.showToast('info', 'Carrello Svuotato', 'Tutti i prodotti sono stati rimossi');
          this.loadCart();
        }
      },
      error: (err) => {
        console.error('Error clearing cart:', err);
        this.showToast('error', 'Errore', 'Impossibile svuotare il carrello');
      }
    });
    this.subscriptions.push(clearSub);
  }

  // ==================== CHECKOUT ====================

  goToCheckout(): void {
    if (this.cartItems.length === 0) {
      this.showToast('error', 'Carrello Vuoto', 'Aggiungi prodotti al carrello prima di procedere');
      return;
    }

    if (!this.canAffordCart()) {
      this.showToast('error', 'PV Insufficienti', 'Non hai abbastanza Punti Valore per questo ordine');
      return;
    }

    this.closeCart();
    this.currentView = 'checkout';
  }

  canAffordCart(): boolean {
    if (!this.userBalance) return false;
    return this.userBalance.pv_disponibili >= this.cartTotalPv;
  }

  getRemainingPvAfterCheckout(): number {
    if (!this.userBalance) return 0;
    return this.userBalance.pv_disponibili - this.cartTotalPv;
  }

  cancelCheckout(): void {
    this.currentView = 'catalog';
    this.customerMessage = '';
  }

  placeOrder(): void {
    if (this.isProcessingCheckout) return;

    this.isProcessingCheckout = true;

    const checkoutSub = this.ecommerceService.checkout(this.customerMessage).subscribe({
      next: (res) => {
        if (res.response === 'ok') {
          this.lastOrderNumber = res.body.order.order_number;
          this.lastOrderTotal = res.body.order.total_pv;
          this.lastOrderDate = res.body.order.created_at;
          
          // Add transaction to wallet
          this.walletTransactions.unshift({
            id: Date.now(),
            type: 'debit',
            title: `Ordine ${this.lastOrderNumber}`,
            amount: this.lastOrderTotal,
            date: new Date().toLocaleDateString('it-IT')
          });
          
          // Reset cart
          this.cartItems = [];
          this.cartItemsCount = 0;
          this.cartTotalPv = 0;
          this.customerMessage = '';
          
          // Show success page
          this.currentView = 'order-success';
          this.showToast('success', 'Ordine Completato!', `Ordine ${this.lastOrderNumber} creato con successo`);
        } else {
          this.showToast('error', 'Errore', res.message || 'Impossibile completare l\'ordine');
        }
        this.isProcessingCheckout = false;
      },
      error: (err) => {
        console.error('Error during checkout:', err);
        const message = err.error?.message || 'Impossibile completare l\'ordine';
        this.showToast('error', 'Errore', message);
        this.isProcessingCheckout = false;
      }
    });
    this.subscriptions.push(checkoutSub);
  }

  // ==================== ORDERS ====================

  goToOrders(): void {
    this.loadOrders();
    this.currentView = 'orders';
  }

  filterOrders(status: string): void {
    this.ordersFilter = status;
    this.loadOrders();
  }

  viewOrderDetail(order: Order): void {
    this.loadOrderDetail(order.id);
  }

  backToOrders(): void {
    this.selectedOrder = null;
    this.selectedOrderItems = [];
    this.currentView = 'orders';
  }

  // ==================== NAVIGATION ====================

  navigateTo(view: ViewState): void {
    this.currentView = view;
    
    if (view === 'catalog') {
      this.selectedArticle = null;
    } else if (view === 'orders') {
      this.loadOrders();
    } else if (view === 'wallet') {
      // Wallet transactions are already loaded
    }

    window.scrollTo(0, 0);
  }

  backToCatalog(): void {
    this.currentView = 'catalog';
    this.selectedArticle = null;
  }

  continueShopping(): void {
    this.currentView = 'catalog';
  }

  // ==================== TOAST NOTIFICATIONS ====================

  showToast(type: 'success' | 'error' | 'info', title: string, message: string): void {
    const toast = {
      id: ++this.toastCounter,
      type,
      title,
      message
    };
    this.toasts.push(toast);

    // Auto remove after 4 seconds
    setTimeout(() => {
      this.removeToast(toast.id);
    }, 4000);
  }

  removeToast(id: number): void {
    this.toasts = this.toasts.filter(t => t.id !== id);
  }

  // ==================== HELPER METHODS ====================

  formatPv(pv: number): string {
    return new Intl.NumberFormat('it-IT').format(pv) + ' PV';
  }

  formatPvNumber(pv: number): string {
    return new Intl.NumberFormat('it-IT').format(pv);
  }

  formatEuro(euro: number | null): string {
    if (euro === null) return '';
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(euro);
  }

  getStatusClass(status: string): string {
    const classes: { [key: string]: string } = {
      'in_attesa': 'status-pending',
      'in_lavorazione': 'status-processing',
      'completato': 'status-completed',
      'annullato': 'status-cancelled',
      'rimborsato': 'status-refunded'
    };
    return classes[status] || 'status-default';
  }

  getStatusLabel(status: string): string {
    const labels: { [key: string]: string } = {
      'in_attesa': 'In Attesa',
      'in_lavorazione': 'In Elaborazione',
      'completato': 'Completato',
      'annullato': 'Annullato',
      'rimborsato': 'Rimborsato'
    };
    return labels[status] || status;
  }

  getItemStatusClass(status: string): string {
    const classes: { [key: string]: string } = {
      'pending': 'item-pending',
      'processing': 'item-processing',
      'fulfilled': 'item-fulfilled',
      'cancelled': 'item-cancelled'
    };
    return classes[status] || 'item-default';
  }

  trackByArticleId(index: number, article: Article): number {
    return article.id;
  }

  trackByCartItemId(index: number, item: CartItem): number {
    return item.id;
  }

  trackByOrderId(index: number, order: Order): number {
    return order.id;
  }

  trackByTransactionId(index: number, transaction: WalletTransaction): number {
    return transaction.id;
  }

  // Load more articles (infinite scroll)
  loadMoreArticles(): void {
    if (this.pagination && this.pagination.current_page < this.pagination.last_page && !this.isLoadingMore) {
      this.loadArticles(false);
    }
  }

  hasMoreArticles(): boolean {
    return this.pagination ? this.pagination.current_page < this.pagination.last_page : false;
  }

  // Copy redemption code to clipboard
  copyToClipboard(text: string): void {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(() => {
        this.showToast('success', 'Copiato!', 'Codice copiato negli appunti');
      }).catch(() => {
        this.fallbackCopyToClipboard(text);
      });
    } else {
      this.fallbackCopyToClipboard(text);
    }
  }

  private fallbackCopyToClipboard(text: string): void {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    textArea.select();
    try {
      document.execCommand('copy');
      this.showToast('success', 'Copiato!', 'Codice copiato negli appunti');
    } catch (err) {
      this.showToast('error', 'Errore', 'Impossibile copiare il codice');
    }
    document.body.removeChild(textArea);
  }
}