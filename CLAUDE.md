# CLAUDE.md - AI Assistant Guide for Semprechiaro CRM

## Repository Overview

**Project Name:** Semprechiaro
**Type:** Full-stack B2B/Enterprise CRM & E-Commerce Platform
**Architecture:** Monorepo (Laravel Backend + Angular Frontend)
**Primary Language:** Italian (UI, database fields, comments)

### Technology Stack

**Backend:**
- Laravel 11 (PHP 8.x)
- MySQL database
- JWT Authentication (`tymon/jwt-auth`)
- Eloquent ORM
- Pusher (real-time broadcasting)

**Frontend:**
- Angular 17+
- TypeScript
- RxJS (reactive programming)
- Angular Material + PrimeNG
- Bootstrap (ng-bootstrap)

### Project Structure

```
/home/user/Test/
├── Laravel/           # Backend API (Laravel 11)
│   ├── app/
│   │   ├── Models/           # 46 Eloquent models
│   │   ├── Http/Controllers/ # 28+ API controllers
│   │   ├── Mail/
│   │   ├── Notifications/
│   │   └── Console/
│   ├── routes/
│   │   ├── api.php           # 97 API routes (main)
│   │   ├── web.php           # Web routes (file downloads, password reset)
│   │   └── channels.php      # Broadcasting channels
│   ├── database/
│   │   ├── migrations/       # 50+ migrations
│   │   └── seeders/          # 36 seeders
│   ├── config/               # 17 config files
│   └── .env.example          # Environment template
│
└── Angular/           # Frontend SPA (Angular 17+)
    ├── src/app/
    │   ├── pages/            # 39+ feature pages
    │   ├── servizi/          # 14 services
    │   ├── components/       # Shared components
    │   ├── modal/            # Modal dialogs
    │   └── app.module.ts     # Main module
    └── src/environments/     # Environment configs
```

---

## Core Business Domains

### 1. Contract Management (`contract.php`, `ContractController.php`)
- Complete contract lifecycle management
- Custom contract types with dynamic questions
- Status workflow with state machine
- Supplier and payment mode associations
- PV (Punti Valore) bonus system (50% referral bonus)

**Key Routes:**
- `POST /api/nuovoContratto` - Create contract
- `POST /api/updateContratto` - Update contract
- `POST /api/getContratti/{id}` - List contracts
- `POST /api/searchContratti/{id}` - Advanced search

### 2. Support Ticket System (`Ticket.php`, `TicketController.php`)
**Recently enhanced with attachment system**

**Features:**
- Multi-message threading
- File attachments with preview modal
- Status tracking (open, in_progress, closed, deleted)
- Priority levels (unassigned, low, medium, high)
- Change log audit trail
- Soft deletes
- Role-based visibility

**Key Models:**
- `Ticket` - Main ticket entity
- `TicketMessage` - Message threads
- `TicketAttachment` - File attachments (normalized from messages)
- `TicketChangeLog` - Audit trail

**Key Routes:**
- `GET /api/getTickets` - List with role filtering
- `POST /api/createTicket` - Create ticket
- `POST /api/sendTicketMessage` - Post message
- `POST /api/tickets/attachments/upload` - Upload files
- `GET /api/attachments/{attachment}/download` - Download file
- `DELETE /api/attachments/{attachment}` - Delete attachment

### 3. Points/Rewards System (PV - Punti Valore)
**Integrated throughout the application**

**User Points Fields:**
- `punti_valore_maturati` - Earned PV from contracts
- `punti_carriera_maturati` - Career progression points
- `punti_bonus` - Referral bonuses (50% of invited user's contract PV)
- `punti_spesi` - Spent points

**Key Routes:**
- `GET /api/user/wallet` - User balance
- `GET /api/user/wallet/summary` - Summary stats
- `GET /api/user/wallet/history` - Transaction history
- `POST /api/admin/wallet/add-bonus` - Admin bonus points

**Business Logic:**
```php
// Contract model - Referral bonus calculation
const BONUS_COEFFICIENT = 0.5; // 50% bonus to inviter
```

### 4. E-Commerce Module (NEW)
**Point-based purchasing system**

**Key Models:**
- `Article` - Products with PV pricing
- `Order` / `OrderItem` - Order management
- `CartItem` - Shopping cart
- `CartStatus` - Cart states (attivo, in_attesa_di_pagamento, completato, annullato)
- `OrderStatus` - Order states
- `Stock` - Inventory management
- `Asset` / `ArticleAsset` - Media gallery

**Features:**
- PV-based pricing (no monetary currency)
- Cart with point blocking (`pv_bloccati`)
- Digital and physical products
- Multi-location stock management
- Order status workflow

### 5. Lead Management (`lead.php`, `LeadController.php`)
**Lead generation and conversion system**

**Features:**
- Lead capture with status tracking
- Assignment to sales team (SEU users)
- Appointment scheduling
- Lead-to-customer conversion
- Color-coded status indicators

**Key Routes:**
- `POST /api/storeNewLead` - Create lead
- `GET /api/getLeads` - List leads
- `POST /api/updateLead` - Update lead
- `POST /api/nuovoClienteLead` - Convert to customer

---

## Authentication & Authorization

### JWT Authentication Flow

**Login Process:**
```
1. POST /api/login (email, password)
2. AuthController validates credentials
3. Returns JWT token + user data
4. Frontend stores token in localStorage['jwt']
5. All subsequent requests include: Authorization: Bearer {token}
```

**Implementation:**
- **Backend:** `config/auth.php` - Guard: `api` with JWT driver
- **Frontend:** `auth.service.ts` + `interceptor.service.ts`
- **Middleware:** `auth:api` protects routes
- **Token Refresh:** `POST /api/refresh`
- **Logout:** `GET /api/logout` + clear localStorage

### Role-Based Access Control

**Roles (stored in `roles` table):**

| Role ID | Description | Access Level |
|---------|-------------|--------------|
| 1, 6 | Admin | Full access |
| 5, 10 | BackOffice | Management access |
| 4, 9 | Web Operators | Support & operations |
| 2 | SEU (Sales Engineers) | Sales & leads |
| 3 | Users | Basic user |

**Authorization Pattern:**
```php
// Example from TicketController
$userRole = Auth::user()->role->id;

// Only Admin, BackOffice, Web Operators can access
if (!in_array($userRole, [1, 4, 5, 6, 9, 10])) {
    return response()->json([
        "response" => "error",
        "status" => "403"
    ]);
}
```

**Important:** Authorization is implemented via manual role checks in controllers, not through middleware policies.

---

## API Standards & Conventions

### Response Format

**Standard API Response:**
```json
{
  "response": "ok" | "error",
  "status": "200" | "401" | "403" | "500",
  "body": {
    "risposta": [...]  // Data payload
  },
  "message": "Error message if applicable"
}
```

**Success Example:**
```json
{
  "response": "ok",
  "status": "200",
  "body": {
    "risposta": [
      {"id": 1, "nome": "Test"}
    ]
  }
}
```

**Error Example:**
```json
{
  "response": "error",
  "status": "500",
  "message": "Database connection failed"
}
```

### Request Patterns

**GET Requests:**
- Use query parameters for filters: `/api/endpoint?filter=value`
- Path parameters for IDs: `/api/getProdotto/{id}`

**POST Requests:**
- JSON body for data
- Example: `Content-Type: application/json`
- Many update operations use POST (not PUT)

**File Uploads:**
- Use `multipart/form-data`
- Files stored in `storage/` with pattern: `storage/{userId}/{filename}`
- Access via: `/public/storage/{fileId}/{fileName}`

### Middleware Chain

```php
// All API routes wrapped in 'api' middleware
Route::group(['middleware' => 'api'], function() {
    // Public routes
    Route::post('/login', [AuthController::class, 'login']);

    // Protected routes (require JWT)
    Route::middleware('auth:api')->group(function() {
        Route::get('/me', [AuthController::class, 'me']);
        // ... other protected routes
    });
});
```

---

## Development Workflows

### Making Changes to Existing Features

**1. Read Before Modifying**
```bash
# ALWAYS read existing code first
# Example: Adding a field to tickets
1. Read: Laravel/app/Models/Ticket.php
2. Read: Laravel/app/Http/Controllers/TicketController.php
3. Read: Laravel/database/migrations/*_create_tickets_table.php
4. Make changes
5. Test thoroughly
```

**2. Backend Changes (Laravel)**

**Adding a new API endpoint:**
```php
// Step 1: Add route in routes/api.php
Route::post('/nuovoEndpoint', [YourController::class, 'method']);

// Step 2: Implement in controller
public function method(Request $request) {
    try {
        $data = $request->all();
        // Validation
        $validator = Validator::make($data, [
            'field' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "response" => "error",
                "status" => "400",
                "message" => $validator->errors()
            ]);
        }

        // Business logic
        $result = Model::create($data);

        return response()->json([
            "response" => "ok",
            "status" => "200",
            "body" => ["risposta" => $result]
        ]);
    } catch (\Exception $e) {
        Log::error('Error in method: ' . $e->getMessage());
        return response()->json([
            "response" => "error",
            "status" => "500",
            "message" => $e->getMessage()
        ]);
    }
}
```

**Adding a database field:**
```php
// Step 1: Create migration
php artisan make:migration add_field_to_table --table=table_name

// Step 2: Edit migration
public function up() {
    Schema::table('table_name', function (Blueprint $table) {
        $table->string('new_field')->nullable();
    });
}

public function down() {
    Schema::table('table_name', function (Blueprint $table) {
        $table->dropColumn('new_field');
    });
}

// Step 3: Add to model's $fillable array
protected $fillable = ['existing_fields', 'new_field'];

// Step 4: Run migration
php artisan migrate
```

**3. Frontend Changes (Angular)**

**Adding API call to service:**
```typescript
// In servizi/api.service.ts
nuovoEndpoint(data: any): Observable<any> {
  return this.http.post(`${this.url}nuovoEndpoint`, data, {
    headers: new HttpHeaders({
      'Content-Type': 'application/json'
    })
  });
}
```

**Using in component:**
```typescript
// In component
constructor(private apiService: ApiService) {}

ngOnInit() {
  this.apiService.nuovoEndpoint({field: 'value'}).subscribe({
    next: (response) => {
      if (response.response === 'ok') {
        // Handle success
        this.data = response.body.risposta;
      }
    },
    error: (error) => {
      console.error('Error:', error);
    }
  });
}
```

### Creating New Features

**Complete Feature Workflow:**

1. **Database Schema**
   ```bash
   cd Laravel
   php artisan make:model FeatureName -m  # Creates model + migration
   # Edit migration file
   php artisan migrate
   ```

2. **Backend Model** (`app/Models/FeatureName.php`)
   ```php
   class FeatureName extends Model {
       protected $fillable = ['field1', 'field2'];

       // Relationships
       public function user() {
           return $this->belongsTo(User::class);
       }
   }
   ```

3. **Backend Controller** (`app/Http/Controllers/FeatureController.php`)
   ```bash
   php artisan make:controller FeatureController
   ```
   Implement CRUD methods following existing patterns

4. **Routes** (`routes/api.php`)
   ```php
   Route::middleware('auth:api')->group(function() {
       Route::get('/features', [FeatureController::class, 'index']);
       Route::post('/createFeature', [FeatureController::class, 'store']);
   });
   ```

5. **Frontend Service** (`servizi/feature.service.ts`)
   ```bash
   cd Angular
   ng generate service servizi/feature
   ```

6. **Frontend Component** (`pages/feature/`)
   ```bash
   ng generate component pages/feature
   ```

7. **Routing** (`app.routing.ts`)
   ```typescript
   {
     path: 'feature',
     component: FeatureComponent,
     canActivate: [activateUsersFn]
   }
   ```

---

## Key Conventions & Patterns

### Naming Conventions

**Laravel (Backend):**
- **Models:** PascalCase, singular (`User`, `Contract`, `TicketMessage`)
- **Controllers:** PascalCase + `Controller` suffix (`AuthController`)
- **Methods:** camelCase (`nuovoContratto`, `getTickets`)
- **Routes:** snake_case or camelCase (`/nuovoContratto`, `/getTickets`)
- **Database Tables:** snake_case, plural (`users`, `contracts`, `ticket_messages`)
- **Database Columns:** snake_case (`created_at`, `user_id`, `punti_valore_maturati`)
- **Config Files:** snake_case (`auth.php`, `jwt.php`)

**Angular (Frontend):**
- **Components:** kebab-case folders, PascalCase classes (`ticket-management/`)
- **Services:** kebab-case + `.service.ts` (`api.service.ts`)
- **Methods:** camelCase (`getTickets()`, `nuovoContratto()`)
- **Variables:** camelCase (`userData`, `ticketList`)
- **Constants:** UPPER_SNAKE_CASE (`BONUS_COEFFICIENT`)

### Italian Language Usage

**This codebase is primarily in Italian:**
- UI text and labels
- Database field names (`nome`, `cognome`, `provincia`)
- Model attributes
- Comments
- Variable names in some cases

**When making changes:**
- Follow existing Italian naming for consistency
- Use Italian for user-facing text
- Use English for technical terms (e.g., `status`, `response`)
- Database migrations can use English

### State Management (Angular)

**Pattern: BehaviorSubjects for shared state**

```typescript
// In services
private dataSubject = new BehaviorSubject<any>(null);
public data$ = this.dataSubject.asObservable();

// Update data
updateData(newData: any) {
  this.dataSubject.next(newData);
}

// Subscribe in components
ngOnInit() {
  this.service.data$.subscribe(data => {
    this.localData = data;
  });
}
```

**No Redux/NgRx is used** - state is managed through services with RxJS.

### Error Handling

**Backend:**
```php
try {
    // Operation
    return response()->json([
        "response" => "ok",
        "status" => "200",
        "body" => ["risposta" => $data]
    ]);
} catch (\Exception $e) {
    Log::error('Context: ' . $e->getMessage());
    return response()->json([
        "response" => "error",
        "status" => "500",
        "message" => $e->getMessage()
    ]);
}
```

**Frontend:**
```typescript
this.apiService.method().subscribe({
  next: (response) => {
    if (response.response === 'ok') {
      // Success
    } else {
      // API returned error status
      this.handleError(response.message);
    }
  },
  error: (error) => {
    // HTTP error (network, 500, etc.)
    console.error('HTTP Error:', error);
    this.showToast('Errore di connessione');
  }
});
```

---

## Database Schema Quick Reference

### Core Tables

**users**
```sql
id, email, password, nome, cognome, codice_fiscale, telefono,
role_id, qualification_id, user_id_padre (hierarchy),
punti_valore_maturati, punti_carriera_maturati,
punti_bonus, punti_spesi
```

**contracts**
```sql
id, codice_contratto, inserito_da_user_id, associato_a_user_id,
product_id, customer_data_id, status_contract_id, payment_mode_id,
data_inserimento, data_stipula
```

**tickets**
```sql
id, ticket_number, title, description, status, previous_status,
priority, contract_id, created_by_user_id, assigned_to_user_id,
created_at, updated_at, deleted_at
```

**ticket_messages**
```sql
id, ticket_id, user_id, message, message_type, has_attachments,
created_at, updated_at
```

**ticket_attachments** (NEW - normalized from messages)
```sql
id, ticket_id, ticket_message_id, user_id,
file_name, file_path, file_size, mime_type, hash,
created_at, updated_at
```

**articles** (E-commerce)
```sql
id, sku, article_name, description, pv_price,
is_digital, available, category_id, store_id,
thumbnail_asset_id
```

**cart_items**
```sql
id, user_id, article_id, quantity,
pv_bloccati (blocked points), cart_status_id
```

**orders**
```sql
id, order_number, user_id, total_pv,
order_status_id, payment_mode_id
```

### Important Relationships

```
User → Role (many-to-one)
User → Qualification (many-to-one)
User → User (parent hierarchy via user_id_padre)
User → Contracts (one-to-many as creator & associate)
User → Leads (one-to-many as assegnato_a)
User → CartItems (one-to-many)
User → Orders (one-to-many)
User → TicketMessages (one-to-many)

Contract → User (creator & associate)
Contract → Product (many-to-one)
Contract → StatusContract (many-to-one)
Contract → CustomerData (many-to-one)
Contract → SpecificData (one-to-many)
Contract → Tickets (one-to-many)

Ticket → Contract (many-to-one)
Ticket → User (creator, many-to-one)
Ticket → User (assigned, many-to-one)
Ticket → TicketMessages (one-to-many)
Ticket → TicketAttachments (one-to-many)
Ticket → TicketChangeLogs (one-to-many)

Article → Category (many-to-one)
Article → Store (many-to-one)
Article → Asset (thumbnail, many-to-one)
Article → ArticleAssets (gallery, one-to-many)
Article → Stocks (one-to-many per location)
```

---

## Common Tasks & Solutions

### 1. Adding a New Field to an Existing Model

**Scenario:** Add `note` field to tickets

```bash
# 1. Create migration
cd Laravel
php artisan make:migration add_note_to_tickets_table --table=tickets

# 2. Edit migration
Schema::table('tickets', function (Blueprint $table) {
    $table->text('note')->nullable();
});

# 3. Run migration
php artisan migrate

# 4. Add to Ticket model $fillable
protected $fillable = [..., 'note'];

# 5. Update TicketController to handle new field
# 6. Update Angular service & component
```

### 2. Creating a New API Endpoint

**Scenario:** Get tickets by status

```php
// routes/api.php
Route::get('/getTicketsByStatus/{status}',
    [TicketController::class, 'getByStatus']);

// TicketController.php
public function getByStatus($status) {
    try {
        $tickets = Ticket::where('status', $status)
            ->with(['createdByUser', 'assignedToUser', 'contract'])
            ->get();

        return response()->json([
            "response" => "ok",
            "status" => "200",
            "body" => ["risposta" => $tickets]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            "response" => "error",
            "status" => "500",
            "message" => $e->getMessage()
        ]);
    }
}
```

### 3. Adding Role-Based Access

```php
// In controller method
$userRole = Auth::user()->role->id;

// Define allowed roles for this action
$allowedRoles = [1, 5, 6, 10]; // Admin and BackOffice only

if (!in_array($userRole, $allowedRoles)) {
    return response()->json([
        "response" => "error",
        "status" => "403",
        "message" => "Accesso negato"
    ], 403);
}

// Proceed with action
```

### 4. File Upload Handling

**Backend:**
```php
public function uploadFile(Request $request) {
    $file = $request->file('file');
    $userId = Auth::id();

    // Store in storage/app/public/{userId}/
    $path = $file->store("public/{$userId}");
    $fileName = $file->getClientOriginalName();

    // Save to database
    $attachment = TicketAttachment::create([
        'file_name' => $fileName,
        'file_path' => $path,
        'file_size' => $file->getSize(),
        'mime_type' => $file->getMimeType(),
        'hash' => hash_file('sha256', $file->getRealPath())
    ]);

    return response()->json([
        "response" => "ok",
        "status" => "200",
        "body" => ["risposta" => $attachment]
    ]);
}
```

**Frontend:**
```typescript
uploadFile(file: File) {
  const formData = new FormData();
  formData.append('file', file);

  return this.http.post(`${this.url}uploadFile`, formData);
}
```

### 5. Implementing Soft Deletes

```php
// In migration
$table->softDeletes(); // Adds deleted_at column

// In model
use SoftDeletes;

protected $dates = ['deleted_at'];

// Usage
$model->delete(); // Soft delete
$model->forceDelete(); // Permanent delete
$model->restore(); // Restore

// Queries
Model::all(); // Excludes soft deleted
Model::withTrashed()->get(); // Includes soft deleted
Model::onlyTrashed()->get(); // Only soft deleted
```

---

## Important Gotchas & Warnings

### 1. **Security: Always Validate Input**

```php
// BAD - Direct assignment without validation
$user = User::create($request->all());

// GOOD - Validate first
$validator = Validator::make($request->all(), [
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8'
]);

if ($validator->fails()) {
    return response()->json([
        "response" => "error",
        "status" => "400",
        "message" => $validator->errors()
    ]);
}

$user = User::create($validator->validated());
```

### 2. **N+1 Query Problem: Use Eager Loading**

```php
// BAD - N+1 queries
$tickets = Ticket::all();
foreach ($tickets as $ticket) {
    echo $ticket->user->name; // Separate query for each user!
}

// GOOD - Eager loading
$tickets = Ticket::with(['user', 'contract'])->get();
foreach ($tickets as $ticket) {
    echo $ticket->user->name; // No additional queries
}
```

### 3. **JWT Token Refresh**

- Tokens expire after configured time (default: 60 minutes)
- Frontend should catch 401 errors and attempt refresh
- Implement in `interceptor.service.ts`

### 4. **CORS Configuration**

- Backend CORS config: `config/cors.php`
- Ensure frontend URL is in `allowed_origins`
- For development: `'*'` or specific `http://localhost:4200`

### 5. **Italian Field Names**

- When querying, use Italian field names: `nome`, `cognome`, not `name`, `surname`
- Watch for inconsistencies between old and new code

### 6. **Points System Calculations**

```php
// When contract is created by referred user
$inviter = User::find($contract->associato_a_user_id);
$contractPV = $product->pv_value;

// Inviter gets 50% bonus
$bonus = $contractPV * Contract::BONUS_COEFFICIENT; // 0.5
$inviter->punti_bonus += $bonus;
$inviter->save();
```

### 7. **File Storage Paths**

- Stored in: `storage/app/public/{userId}/{filename}`
- Public access via: `/public/storage/{fileId}/{fileName}`
- Create symbolic link: `php artisan storage:link`

### 8. **Ticket Status Transitions**

```php
// Valid transitions (implement validation)
open -> in_progress
in_progress -> closed
closed -> in_progress (reopened)
* -> deleted (soft delete)
```

### 9. **Cart PV Blocking**

```php
// When adding to cart, block points
$user = Auth::user();
$article = Article::find($articleId);
$pvNeeded = $article->pv_price * $quantity;

// Check available points
$available = $user->punti_valore_maturati + $user->punti_bonus - $user->punti_spesi;
$blocked = CartItem::where('user_id', $user->id)
    ->where('cart_status_id', CartStatus::ATTIVO)
    ->sum('pv_bloccati');

if ($available - $blocked < $pvNeeded) {
    return response()->json([
        "response" => "error",
        "message" => "Punti insufficienti"
    ]);
}

// Block points
CartItem::create([
    'user_id' => $user->id,
    'article_id' => $article->id,
    'quantity' => $quantity,
    'pv_bloccati' => $pvNeeded,
    'cart_status_id' => CartStatus::ATTIVO
]);
```

---

## Testing Strategy

### Backend Testing (PHPUnit)

```bash
cd Laravel

# Run all tests
php artisan test

# Run specific test
php artisan test --filter=TicketTest

# With coverage
php artisan test --coverage
```

**Test Structure:**
```php
// tests/Feature/TicketTest.php
public function test_can_create_ticket()
{
    $user = User::factory()->create(['role_id' => 1]);

    $response = $this->actingAs($user, 'api')
        ->postJson('/api/createTicket', [
            'title' => 'Test Ticket',
            'description' => 'Test Description'
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'response' => 'ok'
        ]);
}
```

### Frontend Testing (Jasmine/Karma)

```bash
cd Angular

# Run tests
ng test

# Run with coverage
ng test --code-coverage

# E2E tests
ng e2e
```

---

## Environment Setup

### Backend Setup

```bash
cd Laravel

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Configure .env
# - DB_DATABASE, DB_USERNAME, DB_PASSWORD
# - JWT_SECRET (will be generated)

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed

# Create storage link
php artisan storage:link

# Start server
php artisan serve  # http://localhost:8000
```

### Frontend Setup

```bash
cd Angular

# Install dependencies
npm install

# Update environment
# Edit src/environments/environment.ts
# Set apiUrl to backend URL

# Start dev server
ng serve  # http://localhost:4200
```

### Docker Setup (Optional)

```bash
cd Laravel/docker

# Choose PHP version (8.0, 8.1, 8.2, 8.3)
cd 8.3

# Start containers
docker-compose up -d

# Access container
docker exec -it container_name bash
```

---

## Git Workflow

### Branch Strategy

- **Main branch:** `main` or `master`
- **Feature branches:** `feature/feature-name`
- **Bug fixes:** `bugfix/issue-description`
- **Current branch:** `claude/claude-md-mibqg3n4yysn49y6-01TZsTNAx62HGTb3AJaU4BVi`

### Commit Message Convention

```
[Type] Brief description

Detailed description if needed

Examples:
[Feature] Add ticket attachment preview modal
[Fix] Resolve N+1 query in ticket listing
[Refactor] Extract cart logic to service
[Docs] Update API documentation
```

### Making Commits

```bash
# Stage changes
git add .

# Commit with descriptive message
git commit -m "[Feature] Add new endpoint for ticket status"

# Push to feature branch
git push -u origin branch-name
```

---

## Performance Optimization Tips

### 1. **Database Queries**

```php
// Use select() to limit columns
$users = User::select('id', 'nome', 'email')->get();

// Use chunk() for large datasets
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process
    }
});

// Cache expensive queries
$users = Cache::remember('all_users', 3600, function () {
    return User::all();
});
```

### 2. **Angular Performance**

```typescript
// Use OnPush change detection
@Component({
  changeDetection: ChangeDetectionStrategy.OnPush
})

// Unsubscribe from observables
ngOnDestroy() {
  this.subscription.unsubscribe();
}

// Use trackBy in *ngFor
<div *ngFor="let item of items; trackBy: trackByFn">

trackByFn(index, item) {
  return item.id;
}
```

### 3. **API Response Optimization**

```php
// Paginate large results
$tickets = Ticket::with('user')->paginate(20);

// Return only needed relationships
$ticket = Ticket::with(['user:id,nome,email', 'contract:id,codice_contratto'])
    ->find($id);
```

---

## Key File References

### Backend Critical Files

| File | Purpose | Lines |
|------|---------|-------|
| `routes/api.php` | All API endpoints | 165 |
| `app/Http/Controllers/AuthController.php` | Authentication logic | 624 |
| `app/Http/Controllers/TicketController.php` | Ticket management | 1000+ |
| `app/Http/Controllers/ContractController.php` | Contract management | 1500+ |
| `app/Models/User.php` | User model with points system | ~200 |
| `config/auth.php` | Auth configuration | ~120 |
| `config/jwt.php` | JWT configuration | ~200 |

### Frontend Critical Files

| File | Purpose | Size |
|------|---------|------|
| `src/app/app.module.ts` | Main module | 279 lines |
| `src/app/app.routing.ts` | Route definitions | ~200 lines |
| `src/app/servizi/api.service.ts` | API communication | 27KB |
| `src/app/servizi/auth.service.ts` | Auth logic | ~1.5KB |
| `src/app/pages/ticket-management/` | Ticket UI | 73KB |
| `src/environments/environment.ts` | Configuration | ~20 lines |

---

## Frequently Asked Questions

### Q: How do I add a new user role?

A:
1. Add role to `roles` table via seeder or SQL
2. Update role checks in controllers: `in_array($userRole, [...])`
3. Update frontend role-based UI logic

### Q: How does the referral bonus system work?

A: When a user (B) invited by another user (A) creates a contract, user A receives 50% of the contract's PV value as `punti_bonus`.

### Q: Where are uploaded files stored?

A: Files are stored in `Laravel/storage/app/public/{userId}/{filename}` and accessed via `/public/storage/{fileId}/{fileName}`.

### Q: How do I debug API issues?

A:
1. Check `Laravel/storage/logs/laravel.log`
2. Use `Log::info()` or `Log::error()` in controllers
3. Check browser Network tab for response details
4. Verify JWT token is valid

### Q: What's the difference between POST /api/getContratti and GET?

A: This API uses POST for many read operations to send complex filter criteria in the request body. This is a design choice, not REST-standard.

### Q: How do I reset a user's password?

A:
1. Frontend: POST `/web/password/email` (triggers email)
2. User clicks link with token
3. Frontend: POST `/web/password/reset` with token + new password

---

## Recent Changes & Migration Notes

### Latest Features (2025)

1. **Ticket Attachment System Refactor**
   - Separated attachments from messages (normalized structure)
   - Added `TicketAttachment` model
   - Migration: `2025_10_30_165908_create_ticket_attachments_table.php`
   - Added file hash for duplicate detection
   - Supports multiple attachments per message

2. **Attachment Preview Modal**
   - New component: `attachment-preview-modal`
   - In-app file preview without download
   - Supports images, PDFs, documents

3. **Ticket Soft Deletes**
   - Added `deleted_at` column
   - Status: `deleted` instead of hard delete
   - Admin-only access to deleted tickets

4. **E-Commerce Module**
   - Complete order & cart system
   - Point-based purchasing (PV currency)
   - Multi-location stock management
   - Digital & physical products

---

## Best Practices Summary

### DO:

✅ Read existing code before modifying
✅ Follow Italian naming for consistency
✅ Use eager loading for relationships
✅ Validate all user input
✅ Use try-catch blocks for error handling
✅ Return standardized API responses
✅ Check user role before sensitive operations
✅ Log errors with context
✅ Use transactions for multi-step DB operations
✅ Unsubscribe from Angular observables
✅ Test locally before committing

### DON'T:

❌ Hard-code user IDs or role IDs
❌ Skip input validation
❌ Expose sensitive data in API responses
❌ Use `SELECT *` for large tables
❌ Forget to add fields to `$fillable`
❌ Skip error handling
❌ Make breaking changes to existing APIs
❌ Delete data permanently (use soft deletes)
❌ Store sensitive data in Git
❌ Skip migrations for schema changes
❌ Forget to update both backend and frontend

---

## Quick Command Reference

### Laravel (Backend)

```bash
# Development
php artisan serve                    # Start dev server
php artisan migrate                  # Run migrations
php artisan migrate:rollback        # Rollback last migration
php artisan db:seed                 # Run seeders
php artisan tinker                  # Interactive shell

# Code Generation
php artisan make:model ModelName -m       # Model + migration
php artisan make:controller ControllerName
php artisan make:migration migration_name
php artisan make:seeder SeederName

# Maintenance
php artisan cache:clear             # Clear cache
php artisan config:clear            # Clear config cache
php artisan route:list              # List all routes
php artisan storage:link            # Create storage symlink

# Testing
php artisan test                    # Run tests
php artisan test --filter=TestName  # Run specific test
```

### Angular (Frontend)

```bash
# Development
ng serve                            # Start dev server
ng serve --port 4300               # Custom port
ng build                           # Production build
ng build --configuration=development

# Code Generation
ng generate component pages/component-name
ng generate service servizi/service-name
ng generate module module-name

# Testing
ng test                            # Run unit tests
ng test --code-coverage           # With coverage
ng e2e                            # Run E2E tests

# Maintenance
npm install                        # Install dependencies
npm update                         # Update dependencies
ng update                          # Update Angular
```

---

## Contact & Support

For questions about this codebase:

1. Check this CLAUDE.md file
2. Review existing similar implementations
3. Check Laravel/Angular official documentation
4. Review git commit history for context

---

## Conclusion

This is a mature, feature-rich CRM platform with:
- 46 models, 28 controllers, 97 API endpoints
- Complex business logic (contracts, tickets, leads, e-commerce)
- Points/rewards system
- Role-based access control
- File management
- Real-time capabilities

**Key Success Factors:**
- Understand the Italian naming conventions
- Follow existing patterns consistently
- Always validate and sanitize input
- Use eager loading for performance
- Test thoroughly before deploying
- Document changes clearly

This CLAUDE.md serves as your comprehensive guide to working effectively with this codebase. Refer to it often, and update it as the project evolves.

---

**Last Updated:** 2025-11-23
**Version:** 1.0
**Maintained for:** AI Assistants working on Semprechiaro CRM
