import { Component, OnInit, OnDestroy, ViewChild, TemplateRef } from '@angular/core';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { ApiService } from 'src/app/servizi/api.service';
import { ToastrService } from 'ngx-toastr';
import { MatDialog } from '@angular/material/dialog';

// ============================================================================
// INTERFACES - Updated with Device Tracking fields
// ============================================================================

interface ParsedUserAgent {
  browser: string;
  browser_version: string;
  os: string;
  os_version: string;
  device: string;
  raw: string;
}

interface LogChange {
  field: string;
  field_label: string;
  old_value: any;
  new_value: any;
}

interface DeviceInfo {
  fingerprint: string | null;
  type: string | null;
  os: string | null;
  browser: string | null;
  screen_resolution: string | null;
  cpu_cores: number | null;
  ram_gb: number | null;
  timezone: string | null;
  language: string | null;
  touch_support: boolean | null;
}

interface GeoInfo {
  country: string | null;
  country_code: string | null;
  region: string | null;
  city: string | null;
  isp: string | null;
  timezone: string | null;
}

interface LogEntry {
  id: number;
  level: string;
  level_label: string;
  source: string;
  source_label: string;
  message: string;
  datetime: string;
  formatted_datetime: string;
  user: {
    id: number;
    name: string;
    email: string;
  } | null;
  has_stack_trace: boolean;
  context?: any;
  ip_address?: string;
  user_agent?: string;
  request_url?: string;
  request_method?: string;
  stack_trace?: string;
  // Audit trail fields
  entity_type?: string;
  entity_type_label?: string;
  entity_id?: number;
  contract_id?: number;
  contract_code?: string;
  has_tracked_changes?: boolean;
  changes?: LogChange[];
  // Device tracking fields (summary view)
  device_fingerprint?: string;
  device_type?: string;
  geo_city?: string;
  geo_country?: string;
  // Device tracking fields (full detail view)
  device_info?: DeviceInfo;
  geo_info?: GeoInfo;
}

interface LogSource {
  key: string;
  label: string;
  count: number;
  icon: string;
}

interface EntityTypeOption {
  key: string;
  label: string;
  count: number;
}

interface UserOption {
  id: number;
  name: string;
  email: string;
  count: number;
}

interface FilterOption {
  value: string;
  label?: string;
  count: number;
}

interface LogStats {
  total: number;
  by_level: {
    debug: number;
    info: number;
    warning: number;
    error: number;
    critical: number;
  };
  by_source: { [key: string]: number };
  errors_today: number;
  logs_today: number;
  logs_last_week: number;
  // Audit trail stats
  by_entity_type?: { [key: string]: number };
  audit_logs_today?: number;
  logs_with_changes_today?: number;
}

interface LogVolume {
  hour: string;
  hour_formatted: string;
  date_formatted: string;
  count: number;
  errors: number;
  warnings: number;
}

interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

@Component({
  selector: 'app-system-logs',
  templateUrl: './system-logs.component.html',
  styleUrls: ['./system-logs.component.scss'],
  standalone: false
})
export class SystemLogsComponent implements OnInit, OnDestroy {

  @ViewChild('logDetailModal') logDetailModal!: TemplateRef<any>;
  @ViewChild('clearConfirmModal') clearConfirmModal!: TemplateRef<any>;
  @ViewChild('settingsModal') settingsModal!: TemplateRef<any>;
  @ViewChild('contractHistoryModal') contractHistoryModal!: TemplateRef<any>;

  private destroy$ = new Subject<void>();

  // Data
  logs: LogEntry[] = [];
  sources: LogSource[] = [];
  stats: LogStats | null = null;
  volumeData: LogVolume[] = [];
  selectedLog: LogEntry | null = null;

  // Pagination
  pagination: Pagination = {
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0
  };

  // Basic Filters
  currentSource: string = 'all';
  selectedLevels: string[] = [];
  searchQuery: string = '';
  dateFrom: string = '';
  dateTo: string = '';
  sortBy: string = 'datetime';
  sortDir: 'asc' | 'desc' = 'desc';

  // Advanced Audit Trail Filters
  filterUserId: number | null = null;
  filterEntityType: string = '';
  filterContractId: number | null = null;
  filterContractCode: string = '';
  filterWithChanges: boolean = false;
  filterWithEntityTracking: boolean = false;
  showAdvancedFilters: boolean = true;

  // Device Tracking Filters
  filterFingerprint: string = '';
  filterCountry: string = '';
  filterCity: string = '';
  filterIsp: string = '';
  filterDeviceType: string = '';
  filterBrowser: string = '';
  filterOS: string = '';
  filterScreenResolution: string = '';
  filterTimezone: string = '';

  // Filter options from API
  availableEntityTypes: EntityTypeOption[] = [];
  availableUsers: UserOption[] = [];

  // Device tracking filter options from API
  availableCountries: FilterOption[] = [];
  availableCities: FilterOption[] = [];
  availableIsps: FilterOption[] = [];
  availableDeviceTypes: FilterOption[] = [];
  availableBrowsers: FilterOption[] = [];
  availableOperatingSystems: FilterOption[] = [];
  availableScreenResolutions: FilterOption[] = [];
  availableTimezones: FilterOption[] = [];

  // UI State
  isLoading: boolean = false;
  isLoadingStats: boolean = false;
  isLoadingVolume: boolean = false;
  isLoadingFilters: boolean = false;
  currentView: 'table' | 'file' = 'table';
  autoRefreshEnabled: boolean = false;
  autoRefreshInterval: any = null;
  refreshCountdown: number = 10;
  showStatsBar: boolean = true;

  // File view
  fileContent: any[] = [];
  fileSearchQuery: string = '';

  // Settings
  logSettings: any = null;
  isLoadingSettings: boolean = false;
  isRunningCleanup: boolean = false;

  // Contract History Modal
  contractHistory: any = null;
  isLoadingContractHistory: boolean = false;
  
  // Retention sources mapping
  retentionSources = [
    { key: 'auth', label: 'Autenticazione', settingKey: 'retention_auth' },
    { key: 'api', label: 'API', settingKey: 'retention_api' },
    { key: 'database', label: 'Database', settingKey: 'retention_database' },
    { key: 'scheduler', label: 'Scheduler', settingKey: 'retention_scheduler' },
    { key: 'email', label: 'Email', settingKey: 'retention_email' },
    { key: 'system', label: 'Sistema', settingKey: 'retention_system' },
    { key: 'user_activity', label: 'Attività Utente', settingKey: 'retention_user_activity' },
    { key: 'external_api', label: 'API Esterne', settingKey: 'retention_external_api' },
    { key: 'ecommerce', label: 'E-commerce', settingKey: 'retention_ecommerce' },
  ];

  // Source icons mapping
  sourceIcons: { [key: string]: string } = {
    'all': 'fa-server',
    'auth': 'fa-shield-alt',
    'api': 'fa-code',
    'database': 'fa-database',
    'scheduler': 'fa-clock',
    'email': 'fa-envelope',
    'system': 'fa-cog',
    'user_activity': 'fa-user-clock',
    'external_api': 'fa-plug',
    'ecommerce': 'fa-shopping-cart'
  };

  // Level icons mapping
  levelIcons: { [key: string]: string } = {
    'debug': 'fa-bug',
    'info': 'fa-info-circle',
    'warning': 'fa-exclamation-triangle',
    'error': 'fa-times-circle',
    'critical': 'fa-skull-crossbones'
  };

  // Entity type icons mapping
  entityTypeIcons: { [key: string]: string } = {
    'contract': 'fa-file-contract',
    'user': 'fa-user',
    'customer_data': 'fa-address-card',
    'specific_data': 'fa-list-alt',
    'product': 'fa-box',
    'article': 'fa-newspaper',
    'lead': 'fa-user-plus',
    'lead_converted': 'fa-user-check',
    'backoffice_note': 'fa-sticky-note',
    'ticket': 'fa-ticket-alt',
    'order': 'fa-shopping-cart'
  };

  // User role
  userRole: number = 0;
  isAdmin: boolean = false;

  constructor(
    private apiService: ApiService,
    private toastr: ToastrService,
    public dialog: MatDialog
  ) {}

  ngOnInit(): void {
    this.loadUserRole();
    this.loadSources();
    this.loadStats();
    this.loadLogs();
    this.loadVolume();
    this.loadAvailableFilters();
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
    this.stopAutoRefresh();
  }

  // ==================== DATA LOADING ====================

  loadUserRole(): void {
    this.apiService.PrendiUtente()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response: any) => {
          if (response && response.user && response.user.role) {
            this.userRole = response.user.role.id;
            this.isAdmin = this.userRole === 1;
          }
        }
      });
  }

  loadSources(): void {
    this.apiService.getLogSources()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          if (response.response === 'ok' && response.body?.sources) {
            this.sources = response.body.sources.map((s: any) => ({
              ...s,
              label: s.key === 'all' ? 'Tutti i Log' : s.label,
              icon: this.sourceIcons[s.key] || 'fa-file'
            }));
          }
        },
        error: (err) => {
          console.error('Error loading sources:', err);
          this.toastr.error('Errore nel caricamento delle sorgenti');
        }
      });
  }

  loadStats(): void {
    this.isLoadingStats = true;
    const source = this.currentSource === 'all' ? undefined : this.currentSource;

    this.apiService.getLogStats(source)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          if (response.response === 'ok' && response.body) {
            this.stats = response.body;
          }
          this.isLoadingStats = false;
        },
        error: (err) => {
          console.error('Error loading stats:', err);
          this.isLoadingStats = false;
        }
      });
  }

  // Load available filter options including device tracking
  loadAvailableFilters(): void {
    this.isLoadingFilters = true;
    
    this.apiService.getLogFilters()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          if (response.response === 'ok' && response.body) {
            // Entity types
            if (response.body.entity_types) {
              this.availableEntityTypes = response.body.entity_types
                .filter((et: any) => et.key && et.key !== 'all')
                .map((et: any) => ({
                  key: et.key,
                  label: et.label,
                  count: et.count
                }));
            }
            // Users
            if (response.body.users) {
              this.availableUsers = response.body.users;
            }

            // Device tracking filter options
            if (response.body.countries) {
              this.availableCountries = response.body.countries.map((c: any) => ({
                value: c.geo_country,
                label: c.geo_country,
                count: c.count
              }));
            }
            if (response.body.cities) {
              this.availableCities = response.body.cities.map((c: any) => ({
                value: c.geo_city,
                label: c.geo_city,
                count: c.count
              }));
            }
            if (response.body.isps) {
              this.availableIsps = response.body.isps.map((i: any) => ({
                value: i.geo_isp,
                label: i.geo_isp,
                count: i.count
              }));
            }
            if (response.body.device_types) {
              this.availableDeviceTypes = response.body.device_types.map((d: any) => ({
                value: d.device_type,
                label: d.device_type,
                count: d.count
              }));
            }
            if (response.body.browsers) {
              this.availableBrowsers = response.body.browsers.map((b: any) => ({
                value: b.device_browser,
                label: b.device_browser,
                count: b.count
              }));
            }
            if (response.body.operating_systems) {
              this.availableOperatingSystems = response.body.operating_systems.map((o: any) => ({
                value: o.device_os,
                label: o.device_os,
                count: o.count
              }));
            }
            if (response.body.screen_resolutions) {
              this.availableScreenResolutions = response.body.screen_resolutions.map((s: any) => ({
                value: s.screen_resolution,
                label: s.screen_resolution,
                count: s.count
              }));
            }
            if (response.body.timezones) {
              this.availableTimezones = response.body.timezones.map((t: any) => ({
                value: t.timezone_client,
                label: t.timezone_client,
                count: t.count
              }));
            }
          }
          this.isLoadingFilters = false;
        },
        error: (err) => {
          console.warn('Could not load filter options:', err);
          this.isLoadingFilters = false;
        }
      });
  }

  loadLogs(): void {
    this.isLoading = true;

    const filters: any = {
      page: this.pagination.current_page,
      per_page: this.pagination.per_page,
      sort_by: this.sortBy,
      sort_dir: this.sortDir
    };

    // Basic filters
    if (this.currentSource !== 'all') {
      filters.source = this.currentSource;
    }

    if (this.selectedLevels.length > 0) {
      filters.level = this.selectedLevels.join(',');
    }

    if (this.searchQuery.trim()) {
      filters.search = this.searchQuery.trim();
    }

    if (this.dateFrom) {
      filters.date_from = this.dateFrom;
    }

    if (this.dateTo) {
      filters.date_to = this.dateTo;
    }

    // Advanced audit trail filters
    if (this.filterUserId) {
      filters.user_id = this.filterUserId;
    }

    if (this.filterEntityType) {
      filters.entity_type = this.filterEntityType;
    }

    if (this.filterContractId) {
      filters.contract_id = this.filterContractId;
    }

    if (this.filterContractCode.trim()) {
      filters.contract_code = this.filterContractCode.trim();
    }

    if (this.filterWithChanges) {
      filters.with_changes = true;
    }

    if (this.filterWithEntityTracking) {
      filters.with_entity_tracking = true;
    }

    // Device tracking filters
    if (this.filterFingerprint.trim()) {
      filters.device_fingerprint = this.filterFingerprint.trim();
    }

    if (this.filterCountry) {
      filters.geo_country = this.filterCountry;
    }

    if (this.filterCity) {
      filters.geo_city = this.filterCity;
    }

    if (this.filterIsp) {
      filters.geo_isp = this.filterIsp;
    }

    if (this.filterDeviceType) {
      filters.device_type = this.filterDeviceType;
    }

    if (this.filterBrowser) {
      filters.device_browser = this.filterBrowser;
    }

    if (this.filterOS) {
      filters.device_os = this.filterOS;
    }

    if (this.filterScreenResolution) {
      filters.screen_resolution = this.filterScreenResolution;
    }

    if (this.filterTimezone) {
      filters.timezone = this.filterTimezone;
    }

    this.apiService.getLogs(filters)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          if (response.response === 'ok' && response.body) {
            this.logs = response.body.data || [];
            this.pagination = response.body.pagination || this.pagination;
          }
          this.isLoading = false;
        },
        error: (err) => {
          console.error('Error loading logs:', err);
          this.toastr.error('Errore nel caricamento dei log');
          this.isLoading = false;
        }
      });
  }

  loadVolume(): void {
    this.isLoadingVolume = true;
    const source = this.currentSource === 'all' ? undefined : this.currentSource;

    this.apiService.getLogVolume(source, 24)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          if (response.response === 'ok' && response.body?.volume) {
            this.volumeData = response.body.volume;
          }
          this.isLoadingVolume = false;
        },
        error: (err) => {
          console.error('Error loading volume:', err);
          this.isLoadingVolume = false;
        }
      });
  }

  loadFileContent(): void {
    this.isLoading = true;
    const source = this.currentSource === 'all' ? 'all' : this.currentSource;

    this.apiService.getLogFileContent(source, true, 500)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          if (response.response === 'ok' && response.body?.lines) {
            this.fileContent = response.body.lines;
          }
          this.isLoading = false;
        },
        error: (err) => {
          console.error('Error loading file content:', err);
          this.isLoading = false;
        }
      });
  }

  // ==================== FILTERING ====================

  filterBySource(source: string): void {
    this.currentSource = source;
    this.pagination.current_page = 1;
    this.loadLogs();
    this.loadStats();
    this.loadVolume();
    
    if (this.currentView === 'file') {
      this.loadFileContent();
    }
  }

  toggleLevelFilter(level: string): void {
    const index = this.selectedLevels.indexOf(level);
    if (index > -1) {
      this.selectedLevels = this.selectedLevels.filter(l => l !== level);
    } else {
      this.selectedLevels = [...this.selectedLevels, level];
    }
    
    if (this.currentView === 'table') {
      this.pagination.current_page = 1;
      this.loadLogs();
    }
  }

  isLevelSelected(level: string): boolean {
    return this.selectedLevels.includes(level);
  }

  clearLevelFilters(): void {
    this.selectedLevels = [];
    
    if (this.currentView === 'table') {
      this.pagination.current_page = 1;
      this.loadLogs();
    }
  }

  onSearch(): void {
    this.pagination.current_page = 1;
    this.loadLogs();
  }

  onDateFilterChange(): void {
    this.pagination.current_page = 1;
    this.loadLogs();
  }

  // Advanced filter change handlers
  onAdvancedFilterChange(): void {
    this.pagination.current_page = 1;
    this.loadLogs();
  }

  onUserFilterChange(): void {
    this.pagination.current_page = 1;
    this.loadLogs();
  }

  onEntityTypeFilterChange(): void {
    this.pagination.current_page = 1;
    this.loadLogs();
  }

  onContractFilterChange(): void {
    this.pagination.current_page = 1;
    this.loadLogs();
  }

  onDeviceFilterChange(): void {
    this.pagination.current_page = 1;
    this.loadLogs();
  }

  toggleAdvancedFilters(): void {
    this.showAdvancedFilters = !this.showAdvancedFilters;
  }

  clearAllFilters(): void {
    // Basic filters
    this.selectedLevels = [];
    this.searchQuery = '';
    this.dateFrom = '';
    this.dateTo = '';
    // Advanced filters
    this.filterUserId = null;
    this.filterEntityType = '';
    this.filterContractId = null;
    this.filterContractCode = '';
    this.filterWithChanges = false;
    this.filterWithEntityTracking = false;
    // Device tracking filters
    this.filterFingerprint = '';
    this.filterCountry = '';
    this.filterCity = '';
    this.filterIsp = '';
    this.filterDeviceType = '';
    this.filterBrowser = '';
    this.filterOS = '';
    this.filterScreenResolution = '';
    this.filterTimezone = '';
    
    this.pagination.current_page = 1;
    this.loadLogs();
  }

  hasActiveFilters(): boolean {
    return this.selectedLevels.length > 0 ||
           this.searchQuery.trim() !== '' ||
           this.dateFrom !== '' ||
           this.dateTo !== '' ||
           this.filterUserId !== null ||
           this.filterEntityType !== '' ||
           this.filterContractId !== null ||
           this.filterContractCode.trim() !== '' ||
           this.filterWithChanges ||
           this.filterWithEntityTracking ||
           // Device tracking filters
           this.filterFingerprint.trim() !== '' ||
           this.filterCountry !== '' ||
           this.filterCity !== '' ||
           this.filterIsp !== '' ||
           this.filterDeviceType !== '' ||
           this.filterBrowser !== '' ||
           this.filterOS !== '' ||
           this.filterScreenResolution !== '' ||
           this.filterTimezone !== '';
  }

  getActiveFiltersCount(): number {
    let count = 0;
    if (this.selectedLevels.length > 0) count++;
    if (this.searchQuery.trim()) count++;
    if (this.dateFrom) count++;
    if (this.dateTo) count++;
    if (this.filterUserId) count++;
    if (this.filterEntityType) count++;
    if (this.filterContractId || this.filterContractCode.trim()) count++;
    if (this.filterWithChanges) count++;
    if (this.filterWithEntityTracking) count++;
    // Device tracking filters
    if (this.filterFingerprint.trim()) count++;
    if (this.filterCountry) count++;
    if (this.filterCity) count++;
    if (this.filterIsp) count++;
    if (this.filterDeviceType) count++;
    if (this.filterBrowser) count++;
    if (this.filterOS) count++;
    if (this.filterScreenResolution) count++;
    if (this.filterTimezone) count++;
    return count;
  }

  // Quick filter from modal (click on user/entity/fingerprint)
  filterByUser(userId: number): void {
    this.filterUserId = userId;
    this.showAdvancedFilters = true;
    this.dialog.closeAll();
    this.pagination.current_page = 1;
    this.loadLogs();
    this.toastr.info('Filtrato per utente');
  }

  filterByEntityType(entityType: string): void {
    this.filterEntityType = entityType;
    this.showAdvancedFilters = true;
    this.dialog.closeAll();
    this.pagination.current_page = 1;
    this.loadLogs();
    this.toastr.info(`Filtrato per tipo: ${this.getEntityTypeLabel(entityType)}`);
  }

  filterByContract(contractId: number): void {
    this.filterContractId = contractId;
    this.showAdvancedFilters = true;
    this.dialog.closeAll();
    this.pagination.current_page = 1;
    this.loadLogs();
    this.toastr.info(`Filtrato per contratto #${contractId}`);
  }

  // Filter by specific entity (type + id) - shows all logs for that specific entity
  filterBySpecificEntity(entityType: string, entityId: number, contractId?: number): void {
    // If it's a contract, filter by contract_id
    if (entityType === 'contract') {
      this.filterContractId = entityId;
      this.filterEntityType = '';
      this.toastr.info(`Filtrato per contratto #${entityId}`);
    } else if (contractId) {
      // For other entities linked to a contract, filter by that contract
      this.filterContractId = contractId;
      this.filterEntityType = '';
      this.toastr.info(`Filtrato per contratto #${contractId}`);
    } else {
      // For entities without contract, filter by entity type + search for entity id
      this.filterEntityType = entityType;
      this.searchQuery = `#${entityId}`;
      this.toastr.info(`Filtrato per ${this.getEntityTypeLabel(entityType)} #${entityId}`);
    }
    this.showAdvancedFilters = true;
    this.dialog.closeAll();
    this.pagination.current_page = 1;
    this.loadLogs();
  }

  filterByFingerprint(fingerprint: string): void {
    this.filterFingerprint = fingerprint;
    this.showAdvancedFilters = true;
    this.dialog.closeAll();
    this.pagination.current_page = 1;
    this.loadLogs();
    this.toastr.info('Filtrato per dispositivo');
  }

  // Device tracking quick filters from modal
  filterByLocation(city: string | null, country: string | null): void {
    if (city) this.filterCity = city;
    if (country) this.filterCountry = country;
    this.showAdvancedFilters = true;
    this.dialog.closeAll();
    this.pagination.current_page = 1;
    this.loadLogs();
    this.toastr.info('Filtrato per posizione');
  }

  filterByIspClick(isp: string): void {
    this.filterIsp = isp;
    this.showAdvancedFilters = true;
    this.dialog.closeAll();
    this.pagination.current_page = 1;
    this.loadLogs();
    this.toastr.info('Filtrato per ISP');
  }

  filterByDeviceTypeClick(deviceType: string): void {
    this.filterDeviceType = deviceType;
    this.showAdvancedFilters = true;
    this.dialog.closeAll();
    this.pagination.current_page = 1;
    this.loadLogs();
    this.toastr.info('Filtrato per tipo dispositivo');
  }

  filterByBrowserClick(browser: string): void {
    this.filterBrowser = browser;
    this.showAdvancedFilters = true;
    this.dialog.closeAll();
    this.pagination.current_page = 1;
    this.loadLogs();
    this.toastr.info('Filtrato per browser');
  }

  filterByOSClick(os: string): void {
    this.filterOS = os;
    this.showAdvancedFilters = true;
    this.dialog.closeAll();
    this.pagination.current_page = 1;
    this.loadLogs();
    this.toastr.info('Filtrato per sistema operativo');
  }

  // ==================== SORTING ====================

  sortTable(column: string): void {
    if (this.sortBy === column) {
      this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortBy = column;
      this.sortDir = 'desc';
    }
    this.loadLogs();
  }

  getSortIcon(column: string): string {
    if (this.sortBy !== column) return 'fa-sort';
    return this.sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
  }

  // ==================== PAGINATION ====================

  goToPage(page: number): void {
    if (page >= 1 && page <= this.pagination.last_page) {
      this.pagination.current_page = page;
      this.loadLogs();
    }
  }

  getPageNumbers(): number[] {
    const pages: number[] = [];
    const total = this.pagination.last_page;
    const current = this.pagination.current_page;
    
    let start = Math.max(1, current - 2);
    let end = Math.min(total, current + 2);
    
    if (current <= 3) {
      end = Math.min(5, total);
    }
    if (current >= total - 2) {
      start = Math.max(1, total - 4);
    }
    
    for (let i = start; i <= end; i++) {
      pages.push(i);
    }
    
    return pages;
  }

  onPerPageChange(): void {
    this.pagination.current_page = 1;
    this.loadLogs();
  }

  // ==================== VIEW SWITCHING ====================

  isViewTransitioning: boolean = false;

  setView(view: 'table' | 'file'): void {
    if (this.currentView === view) return;
    
    this.isViewTransitioning = true;
    
    setTimeout(() => {
      this.currentView = view;
      
      if (view === 'file' && this.fileContent.length === 0) {
        this.loadFileContent();
      }
      
      setTimeout(() => {
        this.isViewTransitioning = false;
      }, 50);
    }, 150);
  }

  // ==================== AUTO REFRESH ====================

  toggleAutoRefresh(): void {
    this.autoRefreshEnabled = !this.autoRefreshEnabled;
    
    if (this.autoRefreshEnabled) {
      this.startAutoRefresh();
      this.toastr.info('Auto-refresh attivato');
    } else {
      this.stopAutoRefresh();
      this.toastr.info('Auto-refresh disattivato');
    }
  }

  startAutoRefresh(): void {
    this.refreshCountdown = 10;
    this.autoRefreshInterval = setInterval(() => {
      this.refreshCountdown--;
      if (this.refreshCountdown <= 0) {
        this.refreshLogs(true);
        this.refreshCountdown = 10;
      }
    }, 1000);
  }

  stopAutoRefresh(): void {
    if (this.autoRefreshInterval) {
      clearInterval(this.autoRefreshInterval);
      this.autoRefreshInterval = null;
    }
  }

  refreshLogs(silent: boolean = false): void {
    this.loadLogs();
    this.loadStats();
    this.loadSources();
    
    if (this.currentView === 'file') {
      this.loadFileContent();
    }
    
    if (!silent) {
      this.toastr.success('Log aggiornati');
    }
  }

  // ==================== LOG ACTIONS ====================

  viewLogDetail(log: LogEntry): void {
    this.selectedLog = null;
    
    this.apiService.getLog(log.id)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          if (response.response === 'ok' && response.body?.log) {
            this.selectedLog = response.body.log;
            this.dialog.open(this.logDetailModal, {
              width: '900px',
              maxHeight: '90vh'
            });
          }
        },
        error: (err) => {
          console.error('Error loading log detail:', err);
          this.toastr.error('Errore nel caricamento dei dettagli');
        }
      });
  }

  copyLog(log: LogEntry): void {
    const text = `[${log.datetime}] ${log.source.toUpperCase()}.${log.level.toUpperCase()}: ${log.message}`;
    navigator.clipboard.writeText(text).then(() => {
      this.toastr.success('Log copiato negli appunti');
    });
  }

  copyLogDetail(): void {
    if (!this.selectedLog) return;
    
    let text = `ID: #${this.selectedLog.id}\n`;
    text += `Data: ${this.selectedLog.datetime}\n`;
    text += `Livello: ${this.selectedLog.level}\n`;
    text += `Sorgente: ${this.selectedLog.source}\n`;
    text += `Messaggio: ${this.selectedLog.message}\n`;
    
    if (this.selectedLog.user) {
      text += `Utente: ${this.selectedLog.user.name} (${this.selectedLog.user.email})\n`;
    }
    
    if (this.selectedLog.ip_address) {
      text += `IP: ${this.selectedLog.ip_address}\n`;
    }

    // Include audit trail info
    if (this.selectedLog.entity_type) {
      text += `Entità: ${this.selectedLog.entity_type_label || this.selectedLog.entity_type}`;
      if (this.selectedLog.entity_id) {
        text += ` #${this.selectedLog.entity_id}`;
      }
      text += '\n';
    }

    if (this.selectedLog.contract_id) {
      text += `Contratto: #${this.selectedLog.contract_id}`;
      if (this.selectedLog.contract_code) {
        text += ` (${this.selectedLog.contract_code})`;
      }
      text += '\n';
    }

    // Include device info
    if (this.selectedLog.device_info) {
      const di = this.selectedLog.device_info;
      text += `\nDispositivo:\n`;
      if (di.type) text += `  Tipo: ${di.type}\n`;
      if (di.os) text += `  OS: ${di.os}\n`;
      if (di.browser) text += `  Browser: ${di.browser}\n`;
      if (di.screen_resolution) text += `  Schermo: ${di.screen_resolution}\n`;
      if (di.cpu_cores) text += `  CPU Cores: ${di.cpu_cores}\n`;
      if (di.ram_gb) text += `  RAM: ${di.ram_gb} GB\n`;
      if (di.fingerprint) text += `  Fingerprint: ${di.fingerprint}\n`;
    }

    // Include geo info
    if (this.selectedLog.geo_info) {
      const gi = this.selectedLog.geo_info;
      text += `\nPosizione:\n`;
      if (gi.city) text += `  Città: ${gi.city}\n`;
      if (gi.region) text += `  Regione: ${gi.region}\n`;
      if (gi.country) text += `  Paese: ${gi.country}\n`;
      if (gi.isp) text += `  ISP: ${gi.isp}\n`;
    }

    // Include changes
    if (this.selectedLog.has_tracked_changes && this.selectedLog.changes) {
      text += '\nModifiche:\n';
      this.selectedLog.changes.forEach(change => {
        text += `  ${change.field_label || change.field}: "${change.old_value || '-'}" → "${change.new_value || '-'}"\n`;
      });
    }
    
    if (this.selectedLog.stack_trace) {
      text += `\nStack Trace:\n${this.selectedLog.stack_trace}`;
    }
    
    navigator.clipboard.writeText(text).then(() => {
      this.toastr.success('Dettaglio copiato negli appunti');
    });
  }

  deleteLog(log: LogEntry): void {
    if (!this.isAdmin) {
      this.toastr.error('Solo gli amministratori possono eliminare i log');
      return;
    }

    if (confirm(`Eliminare il log #${log.id}?`)) {
      this.apiService.deleteLog(log.id)
        .pipe(takeUntil(this.destroy$))
        .subscribe({
          next: (response) => {
            if (response.response === 'ok') {
              this.toastr.success('Log eliminato');
              this.loadLogs();
              this.loadStats();
            }
          },
          error: (err) => {
            console.error('Error deleting log:', err);
            this.toastr.error('Errore nell\'eliminazione del log');
          }
        });
    }
  }

  // ==================== CONTRACT HISTORY ====================

  viewContractHistory(contractId: number): void {
    this.isLoadingContractHistory = true;
    this.contractHistory = null;

    this.apiService.getContractHistory(contractId, 200)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          if (response.response === 'ok' && response.body) {
            this.contractHistory = response.body;
            this.dialog.open(this.contractHistoryModal, {
              width: '900px',
              maxHeight: '90vh'
            });
          }
          this.isLoadingContractHistory = false;
        },
        error: (err) => {
          console.error('Error loading contract history:', err);
          this.toastr.error('Errore nel caricamento della cronologia');
          this.isLoadingContractHistory = false;
        }
      });
  }

  // ==================== CLEAR LOGS ====================

  showClearConfirm(): void {
    if (!this.isAdmin) {
      this.toastr.error('Solo gli amministratori possono svuotare i log');
      return;
    }
    
    this.dialog.open(this.clearConfirmModal, {
      width: '450px'
    });
  }

  clearLogs(): void {
    const source = this.currentSource === 'all' ? undefined : this.currentSource;
    
    this.apiService.clearLogs(source)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          if (response.response === 'ok') {
            this.toastr.success(response.message || 'Log eliminati');
            this.dialog.closeAll();
            this.loadLogs();
            this.loadStats();
            this.loadSources();
          }
        },
        error: (err) => {
          console.error('Error clearing logs:', err);
          this.toastr.error('Errore nella pulizia dei log');
        }
      });
  }

  // ==================== EXPORT ====================

  exportLogs(format: 'csv' | 'json' | 'txt'): void {
    const filters: any = {};
    
    if (this.currentSource !== 'all') {
      filters.source = this.currentSource;
    }
    
    if (this.selectedLevels.length > 0) {
      filters.level = this.selectedLevels.join(',');
    }
    
    if (this.searchQuery.trim()) {
      filters.search = this.searchQuery.trim();
    }
    
    if (this.dateFrom) {
      filters.date_from = this.dateFrom;
    }
    
    if (this.dateTo) {
      filters.date_to = this.dateTo;
    }

    if (this.filterEntityType) {
      filters.entity_type = this.filterEntityType;
    }

    if (this.filterContractId) {
      filters.contract_id = this.filterContractId;
    }

    this.apiService.exportLogs(format, filters)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (blob) => {
          const fileName = this.currentSource === 'all' ? 'laravel' : this.currentSource;
          const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
          const fullFileName = `${fileName}_${timestamp}.${format}`;
          
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = fullFileName;
          a.click();
          window.URL.revokeObjectURL(url);
          
          this.toastr.success(`Esportato ${fullFileName}`);
        },
        error: (err) => {
          console.error('Error exporting logs:', err);
          this.toastr.error('Errore nell\'esportazione');
        }
      });
  }

  // ==================== FILE VIEW ====================

  searchInFile(): void {
    // File search is handled in template with highlighting
  }

  scrollToBottom(): void {
    const container = document.querySelector('.log-file-content');
    if (container) {
      container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
    }
  }

  downloadLogFile(): void {
    this.exportLogs('txt');
  }

  copyLogFile(): void {
    const content = this.fileContent
      .map(line => line.content)
      .join('\n');
    
    navigator.clipboard.writeText(content).then(() => {
      this.toastr.success('File copiato negli appunti');
    });
  }

  // ==================== HELPERS ====================

  getLevelIcon(level: string): string {
    return this.levelIcons[level] || 'fa-circle';
  }

  getSourceIcon(source: string): string {
    return this.sourceIcons[source] || 'fa-file';
  }

  getEntityTypeIcon(entityType: string): string {
    return this.entityTypeIcons[entityType] || 'fa-cube';
  }

  getEntityTypeLabel(entityType: string): string {
    const labels: { [key: string]: string } = {
      'contract': 'Contratto',
      'user': 'Utente',
      'customer_data': 'Dati Cliente',
      'specific_data': 'Dati Specifici',
      'product': 'Prodotto',
      'article': 'Articolo',
      'lead': 'Lead',
      'lead_converted': 'Lead Convertito',
      'backoffice_note': 'Nota Backoffice',
      'ticket': 'Ticket',
      'order': 'Ordine'
    };
    return labels[entityType] || entityType;
  }

  getFileName(): string {
    return this.currentSource === 'all' ? 'laravel.log' : `${this.currentSource}.log`;
  }

  getSourceLabel(source: string): string {
    const found = this.sources.find(s => s.key === source);
    return found ? found.label : source;
  }

  formatTimelineDate(dateString: string): string {
    if (!dateString) return dateString;
    
    try {
      const date = new Date(dateString);
      const options: Intl.DateTimeFormatOptions = { 
        day: '2-digit', 
        month: 'long', 
        year: 'numeric' 
      };
      return date.toLocaleDateString('it-IT', options);
    } catch {
      return dateString;
    }
  }

  // Parse user agent string into readable components
  parseUserAgent(userAgent: string): ParsedUserAgent {
    const result: ParsedUserAgent = {
      browser: 'Sconosciuto',
      browser_version: '',
      os: 'Sconosciuto',
      os_version: '',
      device: 'Desktop',
      raw: userAgent
    };

    if (!userAgent) return result;

    // Detect browser
    if (/Edg\/(\d+)/i.test(userAgent)) {
      result.browser = 'Edge';
      result.browser_version = userAgent.match(/Edg\/(\d+)/i)?.[1] || '';
    } else if (/Edge\/(\d+)/i.test(userAgent)) {
      result.browser = 'Edge';
      result.browser_version = userAgent.match(/Edge\/(\d+)/i)?.[1] || '';
    } else if (/Chrome\/(\d+)/i.test(userAgent)) {
      result.browser = 'Chrome';
      result.browser_version = userAgent.match(/Chrome\/(\d+)/i)?.[1] || '';
    } else if (/Firefox\/(\d+)/i.test(userAgent)) {
      result.browser = 'Firefox';
      result.browser_version = userAgent.match(/Firefox\/(\d+)/i)?.[1] || '';
    } else if (/Safari\/(\d+)/i.test(userAgent) && !/Chrome/i.test(userAgent)) {
      result.browser = 'Safari';
      const vMatch = userAgent.match(/Version\/(\d+)/i);
      result.browser_version = vMatch?.[1] || '';
    } else if (/MSIE (\d+)/i.test(userAgent) || /Trident.*rv:(\d+)/i.test(userAgent)) {
      result.browser = 'Internet Explorer';
      result.browser_version = userAgent.match(/(?:MSIE |rv:)(\d+)/i)?.[1] || '';
    }

    // Detect OS
    if (/Windows NT 10/i.test(userAgent)) {
      result.os = 'Windows';
      result.os_version = '10/11';
    } else if (/Windows NT 6\.3/i.test(userAgent)) {
      result.os = 'Windows';
      result.os_version = '8.1';
    } else if (/Windows NT 6\.2/i.test(userAgent)) {
      result.os = 'Windows';
      result.os_version = '8';
    } else if (/Windows NT 6\.1/i.test(userAgent)) {
      result.os = 'Windows';
      result.os_version = '7';
    } else if (/Mac OS X (\d+)[._](\d+)/i.test(userAgent)) {
      result.os = 'macOS';
      const match = userAgent.match(/Mac OS X (\d+)[._](\d+)/i);
      result.os_version = match ? `${match[1]}.${match[2]}` : '';
    } else if (/Linux/i.test(userAgent)) {
      result.os = 'Linux';
      if (/Ubuntu/i.test(userAgent)) result.os = 'Ubuntu';
    } else if (/Android (\d+)/i.test(userAgent)) {
      result.os = 'Android';
      result.os_version = userAgent.match(/Android (\d+)/i)?.[1] || '';
      result.device = 'Mobile';
    } else if (/iPhone|iPad/i.test(userAgent)) {
      result.os = 'iOS';
      const match = userAgent.match(/OS (\d+)_(\d+)/i);
      result.os_version = match ? `${match[1]}.${match[2]}` : '';
      result.device = /iPad/i.test(userAgent) ? 'Tablet' : 'Mobile';
    }

    // Detect device type
    if (/Mobile|Android.*Mobile|iPhone/i.test(userAgent)) {
      result.device = 'Mobile';
    } else if (/iPad|Android(?!.*Mobile)|Tablet/i.test(userAgent)) {
      result.device = 'Tablet';
    }

    return result;
  }

  getDeviceIcon(device: string): string {
    const icons: { [key: string]: string } = {
      'Desktop': 'fa-desktop',
      'Mobile': 'fa-mobile-alt',
      'Tablet': 'fa-tablet-alt'
    };
    return icons[device] || 'fa-question';
  }

  getBrowserIcon(browser: string): string {
    const icons: { [key: string]: string } = {
      'Chrome': 'fa-chrome',
      'Firefox': 'fa-firefox',
      'Safari': 'fa-safari',
      'Edge': 'fa-edge',
      'Internet Explorer': 'fa-internet-explorer'
    };
    return icons[browser] || 'fa-globe';
  }

  getOsIcon(os: string): string {
    const icons: { [key: string]: string } = {
      'Windows': 'fa-windows',
      'macOS': 'fa-apple',
      'Linux': 'fa-linux',
      'Ubuntu': 'fa-linux',
      'Android': 'fa-android',
      'iOS': 'fa-apple'
    };
    return icons[os] || 'fa-desktop';
  }

  // Check if device info is available
  hasDeviceInfo(log: LogEntry): boolean {
    return !!(log.device_info && (log.device_info.type || log.device_info.os || log.device_info.browser));
  }

  // Check if geo info is available
  hasGeoInfo(log: LogEntry): boolean {
    return !!(log.geo_info && (log.geo_info.country || log.geo_info.city));
  }

  // Get country flag emoji from country code
  getCountryFlag(countryCode: string | null): string {
    if (!countryCode || countryCode.length !== 2) return '🌍';
    const codePoints = countryCode
      .toUpperCase()
      .split('')
      .map(char => 127397 + char.charCodeAt(0));
    return String.fromCodePoint(...codePoints);
  }

  // ==================== SETTINGS ====================

  openSettings(): void {
    this.loadSettings();
    this.dialog.open(this.settingsModal, {
      width: '700px',
      maxHeight: '90vh'
    });
  }

  loadSettings(): void {
    this.isLoadingSettings = true;
    
    this.apiService.getLogSettings()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          console.log('Settings response:', response);
          if (response.success && response.data) {
            this.logSettings = this.transformSettingsFromApi(response.data);
            console.log('Transformed settings:', this.logSettings);
          } else {
            this.logSettings = this.getDefaultSettings();
          }
          this.isLoadingSettings = false;
        },
        error: (err) => {
          console.error('Error loading settings:', err);
          this.logSettings = this.getDefaultSettings();
          this.toastr.warning('Caricamento impostazioni predefinite');
          this.isLoadingSettings = false;
        }
      });
  }

  private transformSettingsFromApi(data: any): any {
    const settings: any = {
      options: {},
      retention: {},
      cleanup: {},
      notifications: {}
    };

    Object.keys(data).forEach(group => {
      const groupSettings = data[group];
      
      if (Array.isArray(groupSettings)) {
        groupSettings.forEach((setting: any) => {
          if (setting.key && setting.value !== undefined) {
            settings[group] = settings[group] || {};
            settings[group][setting.key] = setting.value;
          }
        });
      } else if (typeof groupSettings === 'object') {
        settings[group] = groupSettings;
      }
    });

    return settings;
  }

  private getDefaultSettings(): any {
    return {
      options: {
        log_to_database: true,
        log_to_file: true,
        log_emails_auto: true,
        log_slow_queries: false,
        slow_query_threshold: 1000
      },
      retention: {
        retention_auth: 30,
        retention_api: 14,
        retention_database: 7,
        retention_scheduler: 14,
        retention_email: 30,
        retention_system: 30,
        retention_user_activity: 60,
        retention_external_api: 14,
        retention_ecommerce: 90
      },
      cleanup: {
        cleanup_enabled: true,
        cleanup_frequency: 'daily',
        cleanup_time: '03:00',
        cleanup_last_run: null
      },
      notifications: {
        notify_critical_errors: false,
        notify_email: ''
      }
    };
  }

  updateSetting(key: string, value: any): void {
    if (['slow_query_threshold', 'retention_auth', 'retention_api', 'retention_database', 
         'retention_scheduler', 'retention_email', 'retention_system', 'retention_user_activity',
         'retention_external_api', 'retention_ecommerce', 'retention_errors'].includes(key)) {
      value = parseInt(value, 10);
    }

    this.apiService.updateLogSetting(key, value)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          if (response.success) {
            this.toastr.success('Impostazione aggiornata');
            this.updateLocalSetting(key, value);
          }
        },
        error: (err) => {
          console.error('Error updating setting:', err);
          this.toastr.error('Errore nell\'aggiornamento');
        }
      });
  }

  private updateLocalSetting(key: string, value: any): void {
    if (!this.logSettings) return;
    
    if (key.startsWith('retention_')) {
      if (!this.logSettings.retention) this.logSettings.retention = {};
      this.logSettings.retention[key] = value;
    } else if (key.startsWith('cleanup_')) {
      if (!this.logSettings.cleanup) this.logSettings.cleanup = {};
      this.logSettings.cleanup[key] = value;
    } else if (key.startsWith('notify_')) {
      if (!this.logSettings.notifications) this.logSettings.notifications = {};
      this.logSettings.notifications[key] = value;
    } else {
      if (!this.logSettings.options) this.logSettings.options = {};
      this.logSettings.options[key] = value;
    }
  }

  resetSettings(): void {
    if (confirm('Ripristinare tutte le impostazioni ai valori predefiniti?')) {
      this.apiService.resetLogSettings()
        .pipe(takeUntil(this.destroy$))
        .subscribe({
          next: (response) => {
            if (response.success) {
              this.toastr.success('Impostazioni ripristinate');
              this.loadSettings();
            }
          },
          error: (err) => {
            console.error('Error resetting settings:', err);
            this.toastr.error('Errore nel ripristino');
          }
        });
    }
  }

  runManualCleanup(): void {
    this.isRunningCleanup = true;
    
    this.apiService.runLogCleanup()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          if (response.success) {
            this.toastr.success(response.message || 'Pulizia completata');
            this.loadSettings();
            this.loadLogs();
            this.loadStats();
            this.loadSources();
          }
          this.isRunningCleanup = false;
        },
        error: (err) => {
          console.error('Error running cleanup:', err);
          this.toastr.error('Errore durante la pulizia');
          this.isRunningCleanup = false;
        }
      });
  }

  // ==================== TRACK BY FUNCTIONS ====================

  trackByLogId(index: number, log: LogEntry): number {
    return log.id;
  }

  trackBySourceKey(index: number, source: LogSource): string {
    return source.key;
  }

  trackByFileLine(index: number, line: any): number {
    return line.id || line.line_number;
  }

  trackByChangeField(index: number, change: LogChange): string {
    return change.field;
  }

  trackByFilterOption(index: number, option: FilterOption): string {
    return option.value;
  }
}