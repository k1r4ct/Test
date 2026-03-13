# CLAUDE.md - Project Intelligence

## 1. Project Context

This is a **CRM/ERP platform** for **Semprechiaro**, an Italian energy consulting company. A white-label clone called **Visinnova** shares the same codebase. The system manages energy contracts, leads, customers, suppliers, products, tickets, and an e-commerce loyalty/rewards store.

## 2. Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 10+ (PHP 8.1+) |
| Frontend | Angular 17+ (standalone components, Creative Tim Paper Dashboard Pro) |
| Database | MySQL / MariaDB |
| Auth | JWT (`tymon/jwt-auth`) |
| Hosting | Plesk (production deployment) |
| CSS Framework | Bootstrap 4 via Paper Dashboard Pro |
| State/HTTP | Angular HttpClient + RxJS |

## 3. Role Structure (FIXED - NEVER CHANGE)

| role_id | Name | Description |
|---------|------|-------------|
| **1** | **Admin** | Full access, all modules, all data |
| **2** | **Advisor / SEU** | Sales agent, manages own leads/clients/contracts |
| **3** | **Cliente** | End customer, limited personal dashboard |
| **4** | **Operatore Web** | Web operator, lead/client/contract access (no admin) |
| **5** | **BackOffice** | Back-office staff, client/contract/ticket management |

> These IDs are hardcoded throughout the entire codebase (backend guards, frontend sidebar, route guards). NEVER reassign, rename, or add roles without understanding all downstream effects.

## 4. API Response Format

**ALL API endpoints return this envelope:**

```json
{
  "response": "ok",
  "status": "200",
  "body": {
    "risposta": { ... }
  }
}
```

Error responses use the same shape with `"response": "error"` and appropriate status codes.

## 5. Coding Conventions

### Modification Philosophy
- **Surgical modifications ONLY** - never rewrite existing code wholesale
- **Reuse existing methods and patterns** (DRY) - look for similar implementations before writing new code
- **Never conflate roles** - each role_id has distinct permissions; do not blur boundaries

### Naming Conventions
- **UI labels**: Italian (`Gestione Contratti`, `Nuovo Cliente`, `Salva`)
- **Code comments**: English
- **Variable/method names**: Mixed Italian/English (follows existing patterns)
  - Models: `contract`, `customer_data`, `lead`, `supplier`
  - Routes: `nuovoContratto`, `getContratti`, `storeNewLead`
  - Services folder: `servizi/`
  - Components folder: `componenti/` (auth), `pages/` (features)

### Angular Conventions
- Components use standalone pattern (Angular 17+)
- Services live in `Angular/src/app/servizi/`
- Route guard uses functional pattern (`activateUsersFn`)
- Sidebar defines per-role route arrays: `ROUTES`, `ROUTES_ADMIN`, `ROUTES_BKOFF`, `ROUTES_ADVISOR`, `ROUTES_CLI`

### Laravel Conventions
- Controllers return JSON via the standard envelope (see section 4)
- Models use both PascalCase (`User.php`, `Ticket.php`) and snake_case (`contract.php`, `lead.php`)
- JWT middleware stack: `api` group + `JwtSessionExpiryMiddleware` + `DeviceTrackingMiddleware`
- System logging via `SystemLogService` (singleton with fluent API: `SystemLogService::auth()->warning(...)`)

---

## 6. Complete File Index

**Base URL:** `https://raw.githubusercontent.com/k1r4ct/Test/main/`

---

### 6.1 Laravel Controllers

| File | Raw URL |
|------|---------|
| Controller.php (base) | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/Controller.php) |
| ApiTokenController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/ApiTokenController.php) |
| AssetController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/AssetController.php) |
| AuthController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/AuthController.php) |
| BackofficeNoteController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/BackofficeNoteController.php) |
| CartController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/CartController.php) |
| ContractController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/ContractController.php) |
| ContractDataOverviewController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/ContractDataOverviewController.php) |
| ContractTypeInformationController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/ContractTypeInformationController.php) |
| CustomerDataController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/CustomerDataController.php) |
| DocumentDataController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/DocumentDataController.php) |
| EcommerceHomepageAdminController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/EcommerceHomepageAdminController.php) |
| EcommerceHomepageConfigController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/EcommerceHomepageConfigController.php) |
| IndirectController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/IndirectController.php) |
| LeadController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/LeadController.php) |
| LeadStatusController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/LeadStatusController.php) |
| LogController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/LogController.php) |
| LogSettingsController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/LogSettingsController.php) |
| MacroProductController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/MacroProductController.php) |
| NotificationController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/NotificationController.php) |
| OptionStatusContractController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/OptionStatusContractController.php) |
| OrderController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/OrderController.php) |
| PaymentModeController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/PaymentModeController.php) |
| ProductController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/ProductController.php) |
| ProfileController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/ProfileController.php) |
| QualificationController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/QualificationController.php) |
| RelationshipController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/RelationshipController.php) |
| RoleController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/RoleController.php) |
| SpecificDataController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/SpecificDataController.php) |
| StatusContractController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/StatusContractController.php) |
| StoreController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/StoreController.php) |
| SupplierCategoryController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/SupplierCategoryController.php) |
| SupplierController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/SupplierController.php) |
| SurveyController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/SurveyController.php) |
| SurveyTypeInformationController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/SurveyTypeInformationController.php) |
| TicketController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/TicketController.php) |
| UserDataOverviewController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/UserDataOverviewController.php) |
| WalletController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/WalletController.php) |

#### Auth Controllers

| File | Raw URL |
|------|---------|
| AuthenticatedSessionController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/Auth/AuthenticatedSessionController.php) |
| ConfirmablePasswordController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/Auth/ConfirmablePasswordController.php) |
| EmailVerificationNotificationController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/Auth/EmailVerificationNotificationController.php) |
| EmailVerificationPromptController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/Auth/EmailVerificationPromptController.php) |
| NewPasswordController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/Auth/NewPasswordController.php) |
| PasswordController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/Auth/PasswordController.php) |
| PasswordResetLinkController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/Auth/PasswordResetLinkController.php) |
| RegisteredUserController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/Auth/RegisteredUserController.php) |
| VerifyEmailController.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Controllers/Auth/VerifyEmailController.php) |

---

### 6.2 Laravel Models

| File | Raw URL |
|------|---------|
| api_token.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/api_token.php) |
| Article.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/Article.php) |
| ArticleAsset.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/ArticleAsset.php) |
| ArticleAttributeValue.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/ArticleAttributeValue.php) |
| Asset.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/Asset.php) |
| Attribute.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/Attribute.php) |
| AttributeSet.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/AttributeSet.php) |
| AttributeSetAttribute.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/AttributeSetAttribute.php) |
| backoffice_note.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/backoffice_note.php) |
| CartItem.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/CartItem.php) |
| CartStatus.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/CartStatus.php) |
| Category.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/Category.php) |
| contract.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/contract.php) |
| contract_management.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/contract_management.php) |
| contract_type_information.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/contract_type_information.php) |
| customer_data.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/customer_data.php) |
| DetailQuestion.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/DetailQuestion.php) |
| document_data.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/document_data.php) |
| EcommerceHomepageProductRow.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/EcommerceHomepageProductRow.php) |
| EcommerceHomepageRowArticle.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/EcommerceHomepageRowArticle.php) |
| EcommerceHomepageSlide.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/EcommerceHomepageSlide.php) |
| Filter.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/Filter.php) |
| indirect.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/indirect.php) |
| lead.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/lead.php) |
| lead_status.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/lead_status.php) |
| leadConverted.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/leadConverted.php) |
| log.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/log.php) |
| LogSetting.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/LogSetting.php) |
| macro_product.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/macro_product.php) |
| notification.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/notification.php) |
| option_status_contract.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/option_status_contract.php) |
| Order.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/Order.php) |
| OrderItem.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/OrderItem.php) |
| OrderStatus.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/OrderStatus.php) |
| payment_mode.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/payment_mode.php) |
| product.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/product.php) |
| qualification.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/qualification.php) |
| relationship.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/relationship.php) |
| Role.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/Role.php) |
| specific_data.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/specific_data.php) |
| status_contract.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/status_contract.php) |
| Stock.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/Stock.php) |
| Store.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/Store.php) |
| supplier.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/supplier.php) |
| supplier_category.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/supplier_category.php) |
| survey.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/survey.php) |
| survey_type_information.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/survey_type_information.php) |
| TableColor.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/TableColor.php) |
| Ticket.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/Ticket.php) |
| TicketAttachment.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/TicketAttachment.php) |
| TicketChangeLog.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/TicketChangeLog.php) |
| TicketMessage.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/TicketMessage.php) |
| User.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Models/User.php) |

---

### 6.3 Laravel Middleware

| File | Raw URL |
|------|---------|
| Authenticate.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Middleware/Authenticate.php) |
| DeviceTrackingMiddleware.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Middleware/DeviceTrackingMiddleware.php) |
| EncryptCookies.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Middleware/EncryptCookies.php) |
| EnsureEmailIsVerified.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Middleware/EnsureEmailIsVerified.php) |
| JwtSessionExpiryMiddleware.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Middleware/JwtSessionExpiryMiddleware.php) |
| PreventRequestsDuringMaintenance.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Middleware/PreventRequestsDuringMaintenance.php) |
| RedirectIfAuthenticated.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Middleware/RedirectIfAuthenticated.php) |
| TrimStrings.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Middleware/TrimStrings.php) |
| TrustHosts.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Middleware/TrustHosts.php) |
| TrustProxies.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Middleware/TrustProxies.php) |
| ValidateSignature.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Middleware/ValidateSignature.php) |
| VerifyCsrfToken.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Http/Middleware/VerifyCsrfToken.php) |

---

### 6.4 Laravel Services, Traits, Jobs, Providers

| File | Raw URL |
|------|---------|
| SystemLogService.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Services/SystemLogService.php) |
| GeoLocationService.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Services/GeoLocationService.php) |
| LogsDatabaseOperations.php (trait) | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Traits/LogsDatabaseOperations.php) |
| CartCleanupJob.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Jobs/CartCleanupJob.php) |
| AppServiceProvider.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Providers/AppServiceProvider.php) |
| AuthServiceProvider.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Providers/AuthServiceProvider.php) |
| BroadcastServiceProvider.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Providers/BroadcastServiceProvider.php) |
| EventServiceProvider.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Providers/EventServiceProvider.php) |
| RouteServiceProvider.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Providers/RouteServiceProvider.php) |

---

### 6.5 Laravel Mail Classes

| File | Raw URL |
|------|---------|
| CambioStatoContratto.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Mail/CambioStatoContratto.php) |
| CambioStatoTicket.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Mail/CambioStatoTicket.php) |
| CriticalErrorNotification.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Mail/CriticalErrorNotification.php) |
| LeadMail.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Mail/LeadMail.php) |
| LeadMailInvitante.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Mail/LeadMailInvitante.php) |
| MailNewMessageTicket.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Mail/MailNewMessageTicket.php) |
| NuovaPassword.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Mail/NuovaPassword.php) |
| NuovoTicketCreato.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/app/Mail/NuovoTicketCreato.php) |

---

### 6.6 Laravel Routes

| File | Raw URL |
|------|---------|
| api.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/routes/api.php) |
| web.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/routes/web.php) |
| auth.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/routes/auth.php) |
| channels.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/routes/channels.php) |
| console.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/routes/console.php) |

---

### 6.7 Laravel Migrations

| File | Raw URL |
|------|---------|
| 2014_10_12_100000_create_password_reset_tokens_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2014_10_12_100000_create_password_reset_tokens_table.php) |
| 2019_08_19_000000_create_failed_jobs_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2019_08_19_000000_create_failed_jobs_table.php) |
| 2019_12_14_000001_create_personal_access_tokens_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php) |
| 2023_11_21_000000_create_roles_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_21_000000_create_roles_table.php) |
| 2023_11_21_000002_create_qualifications_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_21_000002_create_qualifications_table.php) |
| 2023_11_21_000003_create_users_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_21_000003_create_users_table.php) |
| 2023_11_21_000004_create_relationships_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_21_000004_create_relationships_table.php) |
| 2023_11_22_104134_create_indirects_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104134_create_indirects_table.php) |
| 2023_11_22_104150_create_surveys_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104150_create_surveys_table.php) |
| 2023_11_22_104158_create_customer_datas_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104158_create_customer_datas_table.php) |
| 2023_11_22_104205_create_status_contracts_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104205_create_status_contracts_table.php) |
| 2023_11_22_104212_create_option_status_contracts_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104212_create_option_status_contracts_table.php) |
| 2023_11_22_104228_create_supplier_categories_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104228_create_supplier_categories_table.php) |
| 2023_11_22_104235_create_suppliers_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104235_create_suppliers_table.php) |
| 2023_11_22_104242_create_macro_products_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104242_create_macro_products_table.php) |
| 2023_11_22_104248_create_products_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104248_create_products_table.php) |
| 2023_11_22_104255_create_payment_modes_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104255_create_payment_modes_table.php) |
| 2023_11_22_104256_create_contracts_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104256_create_contracts_table.php) |
| 2023_11_22_104257_create_specific_datas_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104257_create_specific_datas_table.php) |
| 2023_11_22_104306_create_contract_type_informations_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104306_create_contract_type_informations_table.php) |
| 2023_11_22_104319_create_document_datas_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104319_create_document_datas_table.php) |
| 2023_11_22_104341_create_logs_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104341_create_logs_table.php) |
| 2023_11_22_104355_create_survey_type_informations_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_104355_create_survey_type_informations_table.php) |
| 2023_11_22_154232_create_api_tokens_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2023_11_22_154232_create_api_tokens_table.php) |
| 2024_03_25_151824_create_detail_questions_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2024_03_25_151824_create_detail_questions_table.php) |
| 2024_06_06_101631_create_contract_managements_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2024_06_06_101631_create_contract_managements_table.php) |
| 2024_10_04_130906_create_lead_converteds_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2024_10_04_130906_create_lead_converteds_table.php) |
| 2025_09_22_151834_create_tickets_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_09_22_151834_create_tickets_table.php) |
| 2025_09_22_151835_create_ticket_messages_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_09_22_151835_create_ticket_messages_table.php) |
| 2025_10_02_131305_add_soft_deletes_to_tickets_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_02_131305_add_soft_deletes_to_tickets_table.php) |
| 2025_10_02_132827_update_message_type_enum_in_ticket_messages.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_02_132827_update_message_type_enum_in_ticket_messages.php) |
| 2025_10_14_080224_add_deleted_status_to_tickets_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_14_080224_add_deleted_status_to_tickets_table.php) |
| 2025_10_14_090223_add_closed_status_to_tickets_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_14_090223_add_closed_status_to_tickets_table.php) |
| 2025_10_15_143630_add_previous_status_to_tickets_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_15_143630_add_previous_status_to_tickets_table.php) |
| 2025_10_16_120838_add_unassigned_to_priority_in_tickets_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_16_120838_add_unassigned_to_priority_in_tickets_table.php) |
| 2025_10_16_123349_create_ticket_changes_log_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_16_123349_create_ticket_changes_log_table.php) |
| 2025_10_20_161247_create_stores_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_20_161247_create_stores_table.php) |
| 2025_10_20_161248_create_categories_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_20_161248_create_categories_table.php) |
| 2025_10_20_161249_create_assets_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_20_161249_create_assets_table.php) |
| 2025_10_20_161250_create_filters_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_20_161250_create_filters_table.php) |
| 2025_10_20_161251_create_articles_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_20_161251_create_articles_table.php) |
| 2025_10_20_161252_create_article_assets_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_20_161252_create_article_assets_table.php) |
| 2025_10_20_161253_create_stock_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_20_161253_create_stock_table.php) |
| 2025_10_20_161255_create_order_statuses_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_20_161255_create_order_statuses_table.php) |
| 2025_10_20_161256_create_cart_statuses_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_20_161256_create_cart_statuses_table.php) |
| 2025_10_20_161257_create_orders_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_20_161257_create_orders_table.php) |
| 2025_10_20_161258_create_order_items_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_20_161258_create_order_items_table.php) |
| 2025_10_20_161259_create_cart_items_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_20_161259_create_cart_items_table.php) |
| 2025_10_20_161925_add_punti_bonus_to_users_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_20_161925_add_punti_bonus_to_users_table.php) |
| 2025_10_20_164404_add_punti_spesi_to_users_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_20_164404_add_punti_spesi_to_users_table.php) |
| 2025_10_22_161630_update_payment_modes_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_22_161630_update_payment_modes_table.php) |
| 2025_10_22_161636_update_filters_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_22_161636_update_filters_table.php) |
| 2025_10_22_161643_add_filter_to_categories_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_22_161643_add_filter_to_categories_table.php) |
| 2025_10_22_161648_update_stores_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_22_161648_update_stores_table.php) |
| 2025_10_22_161652_update_articles_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_22_161652_update_articles_table.php) |
| 2025_10_22_165316_add_status_names_to_cart_statuses_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_22_165316_add_status_names_to_cart_statuses_table.php) |
| 2025_10_24_000000_rename_pv_temporanei_to_pv_bloccati_in_cart_items.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_24_000000_rename_pv_temporanei_to_pv_bloccati_in_cart_items.php) |
| 2025_10_30_165908_create_ticket_attachments_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_30_165908_create_ticket_attachments_table.php) |
| 2025_10_30_165909_modify_ticket_messages_remove_attachment_columns.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_10_30_165909_modify_ticket_messages_remove_attachment_columns.php) |
| 2025_11_20_130502_create_jobs_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_11_20_130502_create_jobs_table.php) |
| 2025_11_24_110901_add_status_timestamps_to_tickets_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_11_24_110901_add_status_timestamps_to_tickets_table.php) |
| 2025_11_24_110902_backfill_closed_at_for_existing_tickets.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_11_24_110902_backfill_closed_at_for_existing_tickets.php) |
| 2025_11_24_110903_add_removed_status_to_ticket_changes_log.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_11_24_110903_add_removed_status_to_ticket_changes_log.php) |
| 2025_12_09_100001_add_resolved_at_to_tickets_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_12_09_100001_add_resolved_at_to_tickets_table.php) |
| 2025_12_09_100002_backfill_resolved_at_for_existing_tickets.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_12_09_100002_backfill_resolved_at_for_existing_tickets.php) |
| 2025_12_15_103314_create_attributes_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_12_15_103314_create_attributes_table.php) |
| 2025_12_15_103315_create_attribute_sets_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_12_15_103315_create_attribute_sets_table.php) |
| 2025_12_15_103316_create_attribute_set_attributes_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_12_15_103316_create_attribute_set_attributes_table.php) |
| 2025_12_15_103317_create_article_attribute_values_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_12_15_103317_create_article_attribute_values_table.php) |
| 2025_12_15_103318_add_attribute_set_to_articles_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_12_15_103318_add_attribute_set_to_articles_table.php) |
| 2025_12_15_103319_add_hierarchy_to_categories_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_12_15_103319_add_hierarchy_to_categories_table.php) |
| 2025_12_15_103320_add_details_to_stores_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_12_15_103320_add_details_to_stores_table.php) |
| 2025_12_15_103321_add_processing_fields_to_orders_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_12_15_103321_add_processing_fields_to_orders_table.php) |
| 2025_12_15_103322_add_redemption_fields_to_order_items_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_12_15_103322_add_redemption_fields_to_order_items_table.php) |
| 2025_12_15_103331_add_metadata_to_assets_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2025_12_15_103331_add_metadata_to_assets_table.php) |
| 2026_01_01_212806_modify_logs_table_for_system_logs.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_01_01_212806_modify_logs_table_for_system_logs.php) |
| 2026_01_02_001905_add_external_api_to_logs_source.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_01_02_001905_add_external_api_to_logs_source.php) |
| 2026_01_02_085030_create_log_settings_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_01_02_085030_create_log_settings_table.php) |
| 2026_01_05_155510_add_audit_columns_to_logs_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_01_05_155510_add_audit_columns_to_logs_table.php) |
| 2026_01_06_131755_add_device_tracking_to_logs_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_01_06_131755_add_device_tracking_to_logs_table.php) |
| 2026_01_07_162427_add_category_to_tickets_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_01_07_162427_add_category_to_tickets_table.php) |
| 2026_01_07_163119_add_category_to_ticket_changes_log_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_01_07_163119_add_category_to_ticket_changes_log_table.php) |
| 2026_01_09_114312_add_session_timeout_settings_to_log_settings_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_01_09_114312_add_session_timeout_settings_to_log_settings_table.php) |
| 2026_01_09_150755_add_read_tracking_to_tickets_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_01_09_150755_add_read_tracking_to_tickets_table.php) |
| 2026_01_09_150759_add_entity_fields_to_notifications_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_01_09_150759_add_entity_fields_to_notifications_table.php) |
| 2026_01_20_101218_add_category_to_change_type_enum_in_ticket_changes_log.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_01_20_101218_add_category_to_change_type_enum_in_ticket_changes_log.php) |
| 2026_01_27_101153_add_is_besteller_to_article_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_01_27_101153_add_is_besteller_to_article_table.php) |
| 2026_02_16_211336_fix_leads_note_column_type.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_02_16_211336_fix_leads_note_column_type.php) |
| 2026_02_16_211417_add_converted_by_to_lead_converteds.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_02_16_211417_add_converted_by_to_lead_converteds.php) |
| 2026_03_03_153011_add_usage_context_to_assets_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_03_03_153011_add_usage_context_to_assets_table.php) |
| 2026_03_03_153023_create_ecommerce_homepage_slides_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_03_03_153023_create_ecommerce_homepage_slides_table.php) |
| 2026_03_03_153023_create_homepage_slides_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_03_03_153023_create_homepage_slides_table.php) |
| 2026_03_03_153030_create_ecommerce_homepage_product_rows_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_03_03_153030_create_ecommerce_homepage_product_rows_table.php) |
| 2026_03_03_153030_create_homepage_product_rows_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_03_03_153030_create_homepage_product_rows_table.php) |
| 2026_03_03_153039_create_ecommerce_homepage_row_articles_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_03_03_153039_create_ecommerce_homepage_row_articles_table.php) |
| 2026_03_03_153039_create_homepage_row_articles_table.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/migrations/2026_03_03_153039_create_homepage_row_articles_table.php) |

---

### 6.8 Laravel Seeders

| File | Raw URL |
|------|---------|
| DatabaseSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/DatabaseSeeder.php) |
| RoleSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/RoleSeeder.php) |
| UserSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/UserSeeder.php) |
| QualificationSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/QualificationSeeder.php) |
| ContractSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/ContractSeeder.php) |
| ContractTypeSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/ContractTypeSeeder.php) |
| CustomerDataSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/CustomerDataSeeder.php) |
| DocumentsDataSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/DocumentsDataSeeder.php) |
| IndirectsSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/IndirectsSeeder.php) |
| LeadSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/LeadSeeder.php) |
| LeadStatusesSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/LeadStatusesSeeder.php) |
| MacroProductSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/MacroProductSeeder.php) |
| OptionStatusContractSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/OptionStatusContractSeeder.php) |
| PaymentModeSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/PaymentModeSeeder.php) |
| ProductSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/ProductSeeder.php) |
| SpecificDataSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/SpecificDataSeeder.php) |
| StatusContractSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/StatusContractSeeder.php) |
| SupplierCategoriesSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/SupplierCategoriesSeeder.php) |
| SupplierSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/SupplierSeeder.php) |
| SurveySeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/SurveySeeder.php) |
| SurveyTypeSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/SurveyTypeSeeder.php) |
| DetailQuestion.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/DetailQuestion.php) |
| contract_management.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/contract_management.php) |
| TableColor.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/TableColor.php) |
| ArticleSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/ArticleSeeder.php) |
| CartStatusSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/CartStatusSeeder.php) |
| CategorySeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/CategorySeeder.php) |
| EcommerceHomepageSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/EcommerceHomepageSeeder.php) |
| EcommerceSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/EcommerceSeeder.php) |
| FilterSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/FilterSeeder.php) |
| OrderStatusSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/OrderStatusSeeder.php) |
| StockSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/StockSeeder.php) |
| StoreSeeder.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/database/seeders/StoreSeeder.php) |

---

### 6.9 Laravel Config

| File | Raw URL |
|------|---------|
| app.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/app.php) |
| auth.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/auth.php) |
| broadcasting.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/broadcasting.php) |
| cache.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/cache.php) |
| cors.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/cors.php) |
| database.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/database.php) |
| ecommerce.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/ecommerce.php) |
| filesystems.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/filesystems.php) |
| jwt.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/jwt.php) |
| logging.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/logging.php) |
| mail.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/mail.php) |
| queue.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/queue.php) |
| sanctum.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/sanctum.php) |
| services.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/services.php) |
| session.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/session.php) |
| view.php | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Laravel/config/view.php) |

---

### 6.10 Angular Components

#### Auth & Layout

| File | Raw URL |
|------|---------|
| app.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/app.component.ts) |
| admin-layout.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/layouts/admin-layout/admin-layout.component.ts) |
| sidebar.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/sidebar/sidebar.component.ts) |
| navbar.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/shared/navbar/navbar.component.ts) |
| login.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/componenti/login/login.component.ts) |
| registrazione.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/componenti/registrazione/registrazione.component.ts) |

#### Shared Components

| File | Raw URL |
|------|---------|
| confirm-dialog.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/confirm-dialog/confirm-dialog.component.ts) |
| modal.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/modal/modal.component.ts) |
| attachment-preview-modal.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/attachment-preview-modal/attachment-preview-modal.component.ts) |
| profile-settings-modal.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/shared/components/profile-settings-modal/profile-settings-modal.component.ts) |
| notifications-modal.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/shared/notifications-modal/notifications-modal.component.ts) |
| dropzone.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/dropzone/dropzone.component.ts) |
| filepond-uploader.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/filepond-uploader/filepond-uploader.component.ts) |
| snack-bar.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/snack-bar/snack-bar.component.ts) |

#### Page Components

| File | Raw URL |
|------|---------|
| dashboard.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/dashboard/dashboard.component.ts) |
| user.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/user/user.component.ts) |
| leads.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/leads/leads.component.ts) |
| converti-lead.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/converti-lead/converti-lead.component.ts) |
| clienti.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/clienti/clienti.component.ts) |
| nuovocliente.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/nuovocliente/nuovocliente.component.ts) |
| nuovocontraente.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/nuovocontraente/nuovocontraente.component.ts) |
| ricercaclienti.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/ricercaclienti/ricercaclienti.component.ts) |
| listaContratti.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/listacontratti/listaContratti.component.ts) |
| contratti-ricerca.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/contratti-ricerca/contratti-ricerca.component.ts) |
| nuovocontratto.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/nuovocontratto/nuovocontratto.component.ts) |
| dettagli-contratto-prodotto.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/dettagli-contratto-prodotto/dettagli-contratto-prodotto.component.ts) |
| prodotti.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/prodotto/prodotti.component.ts) |
| gestione-prodotti.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/gestione-prodotti/gestione-prodotti.component.ts) |
| gestione-macroprodotti.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/gestione-macroprodotti/gestione-macroprodotti.component.ts) |
| nuovoprodotto.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/nuovoprodotto/nuovoprodotto.component.ts) |
| gestione-utenti.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/gestione-utenti/gestione-utenti.component.ts) |
| scheda-utente.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/scheda-utente/scheda-utente.component.ts) |
| domande.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/domande/domande.component.ts) |
| nuovofornitore.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/nuovofornitore/nuovofornitore.component.ts) |
| pagamento.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/pagamento/pagamento.component.ts) |
| ticket-management.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/ticket-management/ticket-management.component.ts) |
| system-logs.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/system-logs/system-logs.component.ts) |
| ecommerce.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/ecommerce/ecommerce.component.ts) |
| calendar.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/calendar/calendar.component.ts) |
| home.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/home/home.component.ts) |
| maps.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/maps/maps.component.ts) |
| qrcode-generator.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/qrcode-generator/qrcode-generator.component.ts) |
| reset-password.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/reset-password/reset-password.component.ts) |
| password-reset-success.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/components/password-reset-success/password-reset-success.component.ts) |
| form-generale.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/form-generale/form-generale.component.ts) |

#### User Dashboard Sub-components

| File | Raw URL |
|------|---------|
| lead-conversion.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/user/chart/lead-conversion/lead-conversion.component.ts) |
| prevision-pvbar.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/user/chart/prevision-pvbar/prevision-pvbar.component.ts) |
| card-statistic.component.ts (clienti) | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/user/clienti/card/card-statistic/card-statistic.component.ts) |
| tabellacontatti.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/user/clienti/tabellacontatti/tabellacontatti.component.ts) |
| tabellacontratti.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/user/clienti/tabellacontratti/tabellacontratti.component.ts) |
| wallet-cliente.component.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/user/clienti/wallet-cliente/wallet-cliente.component.ts) |
| card-statistic.component.ts (seu) | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/user/seu/card/card-statistic/card-statistic.component.ts) |

---

### 6.11 Angular Services

| File | Raw URL |
|------|---------|
| api.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/api.service.ts) |
| auth.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/auth.service.ts) |
| chat.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/chat.service.ts) |
| contract.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/contract.service.ts) |
| contratto.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/contratto.service.ts) |
| contract-status-guard.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/contract-status-guard.service.ts) |
| device-fingerprint.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/device-fingerprint.service.ts) |
| device-tracking.interceptor.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/device-tracking.interceptor.ts) |
| dropzone.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/dropzone.service.ts) |
| dropzone-deactivate-guard.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/dropzone-deactivate-guard.service.ts) |
| ecommerce.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/ecommerce.service.ts) |
| inactivity.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/inactivity.service.ts) |
| interceptor.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/interceptor.service.ts) |
| layout-scroll.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/layout-scroll.service.ts) |
| notification.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/notification.service.ts) |
| ricercaclienti.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/ricercaclienti.service.ts) |
| route-guard.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/route-guard.service.ts) |
| shared.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/shared.service.ts) |
| theme.service.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/servizi/theme.service.ts) |

---

### 6.12 Angular Modules & Routing

| File | Raw URL |
|------|---------|
| app.module.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/app.module.ts) |
| app.routing.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/app.routing.ts) |
| sidebar.module.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/sidebar/sidebar.module.ts) |
| navbar.module.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/shared/navbar/navbar.module.ts) |
| chart.module.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/pages/user/chart/chart.module.ts) |
| safe-html.pipe.module.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/app/safe-html.pipe.module.ts) |

---

### 6.13 Angular Environment & Config

| File | Raw URL |
|------|---------|
| environment.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/environments/environment.ts) |
| environment.prod.ts | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/src/environments/environment.prod.ts) |
| angular.json | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/angular.json) |
| tsconfig.json | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/tsconfig.json) |
| package.json | [Link](https://raw.githubusercontent.com/k1r4ct/Test/main/Angular/package.json) |

---

## 7. Angular Route Map (app.routing.ts)

| Path | Component | Access |
|------|-----------|--------|
| `/login` | LoginComponent | Public |
| `/registrazione` | RegistrazioneComponent | Public |
| `/reset-password` | ResetPasswordComponent | Public |
| `/password-reset-success` | PasswordResetSuccessComponent | Public |
| `/form-generale/:userId` | FormGeneraleComponent | Public |
| `/dashboard` | DashboardComponent | Authenticated (guarded) |
| `/user` | UserComponent | Authenticated |
| `/leads` | LeadsComponent | Authenticated |
| `/clienti` | ClientiComponent | Authenticated |
| `/contratti` | ListaContrattiComponent | Authenticated |
| `/table` | GestioneProdottiComponent | Authenticated |
| `/macroprodotti` | GestioneMacroprodottiComponent | Authenticated |
| `/gestionedomande` | DomandeComponent | Authenticated |
| `/utenti` | GestioneUtentiComponent | Authenticated |
| `/schedapr` | SchedaUtenteComponent | Authenticated |
| `/ticket` | TicketManagementComponent | Authenticated |
| `/logs` | SystemLogsComponent | Authenticated |
| `/ecommerce` | EcommerceComponent | Authenticated |
| `**` | Redirects to `/dashboard` | - |

## 8. Sidebar Route Visibility by Role

| Route | Admin (1) | Advisor/SEU (2) | Cliente (3) | Operatore Web (4) | BackOffice (5) |
|-------|-----------|-----------------|-------------|-------------------|----------------|
| /dashboard | Yes | Yes | - | Yes | Yes |
| /user | - | - | Yes | - | - |
| /gestionedomande | Yes | - | - | - | - |
| /leads | Yes | Yes | Yes* | Yes | - |
| /clienti | Yes | Yes | - | Yes | Yes |
| /contratti | Yes | Yes | - | Yes | Yes |
| /table | Yes | Yes | - | Yes | Yes |
| /macroprodotti | Yes | - | - | - | - |
| /utenti | Yes | - | - | - | - |
| /schedapr | - | - | Yes | - | - |
| /ecommerce | Yes | - | - | - | - |
| /ticket | Yes | - | - | - | Yes |
| /logs | Yes | - | - | - | - |

*Cliente sees "Amici Invitati" (referral leads) instead of full leads management.

## 9. Key Domain Concepts

- **Contratto** = Energy contract (gas/electricity supply agreement)
- **Lead** = Sales lead, can be converted to Cliente
- **MacroProdotto** = Product category (e.g., "Energia Elettrica", "Gas")
- **Prodotto** = Specific product under a MacroProdotto
- **Fornitore** = Energy supplier (e.g., Enel, Eni)
- **Sopralluogo/Survey** = On-site inspection data
- **PV (Punti Vendita)** = Points / loyalty credits for e-commerce
- **Ticket** = Support ticket with status lifecycle (open -> in_progress -> resolved -> closed)
- **Wallet** = Customer loyalty point balance (punti_bonus, punti_spesi)
