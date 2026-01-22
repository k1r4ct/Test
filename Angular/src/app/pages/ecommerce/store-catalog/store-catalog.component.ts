import { Component, OnInit, OnDestroy } from '@angular/core';
import { Router } from '@angular/router';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { ToastrService } from 'ngx-toastr';
import { 
  EcommerceService, 
  Store, 
  Category, 
  Article, 
  UserBalance,
  Pagination 
} from 'src/app/servizi/ecommerce.service';

@Component({
  selector: 'app-store-catalog',
  templateUrl: './store-catalog.component.html',
  styleUrls: ['./store-catalog.component.scss'],
  standalone: false
})
export class StoreCatalogComponent implements OnInit, OnDestroy {

  private destroy$ = new Subject<void>();

  // Data
  stores: Store[] = [];
  categories: Category[] = [];
  articles: Article[] = [];
  userBalance: UserBalance | null = null;
  pagination: Pagination | null = null;

  // Filters
  selectedStoreId: number | null = null;
  selectedCategoryId: number | null = null;
  searchQuery: string = '';
  sortBy: string = 'default';
  showFeaturedOnly: boolean = false;
  currentPage: number = 1;
  perPage: number = 12;

  // UI State
  loading: boolean = false;
  addingToCart: number | null = null;

  constructor(
    private ecommerceService: EcommerceService,
    private router: Router,
    private toastr: ToastrService
  ) {}

  ngOnInit(): void {
    this.loadStores();
    this.loadArticles();
    this.subscribeToUserBalance();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  /**
   * Subscribe to user balance updates
   */
  private subscribeToUserBalance(): void {
    this.ecommerceService.userBalance$
      .pipe(takeUntil(this.destroy$))
      .subscribe(balance => {
        this.userBalance = balance;
      });

    // Initial load
    this.ecommerceService.getCartSummary().subscribe();
  }

  /**
   * Load available stores
   */
  loadStores(): void {
    this.ecommerceService.getStores()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (res) => {
          if (res.response === 'ok') {
            this.stores = res.body.stores;
          }
        },
        error: (err) => {
          console.error('Error loading stores:', err);
          this.toastr.error('Errore nel caricamento degli store');
        }
      });
  }

  /**
   * Load categories (filtered by store if selected)
   */
  loadCategories(): void {
    this.ecommerceService.getCategories(this.selectedStoreId || undefined)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (res) => {
          if (res.response === 'ok') {
            this.categories = res.body.categories;
          }
        },
        error: (err) => {
          console.error('Error loading categories:', err);
        }
      });
  }

  /**
   * Load articles with current filters
   */
  loadArticles(): void {
    this.loading = true;

    const filters: any = {
      per_page: this.perPage,
      page: this.currentPage
    };

    if (this.selectedStoreId) filters.store_id = this.selectedStoreId;
    if (this.selectedCategoryId) filters.category_id = this.selectedCategoryId;
    if (this.searchQuery) filters.search = this.searchQuery;
    if (this.sortBy !== 'default') filters.sort = this.sortBy;
    if (this.showFeaturedOnly) filters.featured = true;

    this.ecommerceService.getArticles(filters)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (res) => {
          this.loading = false;
          if (res.response === 'ok') {
            this.articles = res.body.articles;
            this.pagination = res.body.pagination;
            
            // Update user balance if provided
            if (res.body.user_pv) {
              this.userBalance = {
                pv_totali: 0,
                pv_bloccati: 0,
                pv_disponibili: res.body.user_pv.pv_disponibili
              };
            }
          }
        },
        error: (err) => {
          this.loading = false;
          console.error('Error loading articles:', err);
          this.toastr.error('Errore nel caricamento dei prodotti');
        }
      });
  }

  /**
   * Handle store change
   */
  onStoreChange(): void {
    this.selectedCategoryId = null;
    this.loadCategories();
    this.currentPage = 1;
    this.loadArticles();
  }

  /**
   * Handle filter change
   */
  onFilterChange(): void {
    this.currentPage = 1;
    this.loadArticles();
  }

  /**
   * Reset all filters
   */
  resetFilters(): void {
    this.selectedStoreId = null;
    this.selectedCategoryId = null;
    this.searchQuery = '';
    this.sortBy = 'default';
    this.showFeaturedOnly = false;
    this.currentPage = 1;
    this.loadCategories();
    this.loadArticles();
  }

  /**
   * Go to specific page
   */
  goToPage(page: number): void {
    this.currentPage = page;
    this.loadArticles();
  }

  /**
   * Check if user can afford article
   */
  canAfford(article: Article): boolean {
    if (!this.userBalance) return false;
    return this.userBalance.pv_disponibili >= article.pv_price;
  }

  /**
   * Add article to cart
   */
  addToCart(article: Article, event: Event): void {
    event.stopPropagation();
    
    if (!this.canAfford(article)) {
      this.toastr.warning('PV insufficienti per questo prodotto');
      return;
    }

    this.addingToCart = article.id;

    this.ecommerceService.addToCart(article.id, 1)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (res) => {
          this.addingToCart = null;
          if (res.response === 'ok') {
            this.toastr.success(`"${article.article_name}" aggiunto al carrello!`);
          } else {
            this.toastr.error(res.message || 'Errore nell\'aggiunta al carrello');
          }
        },
        error: (err) => {
          this.addingToCart = null;
          const message = err.error?.message || 'Errore nell\'aggiunta al carrello';
          this.toastr.error(message);
        }
      });
  }

  /**
   * Navigate to product detail
   */
  viewProductDetail(article: Article): void {
    this.router.navigate(['/ecommerce/product', article.id]);
  }

  /**
   * Handle image error
   */
  onImageError(event: Event): void {
    const img = event.target as HTMLImageElement;
    img.src = 'assets/img/placeholder-product.png';
  }
}
