<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# 🛒 E-Commerce API with JWT Authentication

A comprehensive Laravel-based e-commerce REST API featuring JWT authentication, role-based access control, product management, order processing, and PDF invoice generation.

## ✨ Features

### 🔐 Authentication & Authorization
- **JWT Authentication** - Secure token-based authentication with tymon/jwt-auth
- **Refresh Tokens** - Long-lived tokens stored in database for enhanced security
- **Token Rotation** - Automatic token refresh with security best practices
- **Role-Based Access Control (RBAC)** - Three distinct user roles with permissions

### 👥 User Roles & Capabilities

#### 👨‍💼 Admin (Full System Access)
- Manage all users and roles
- Full CRUD on products, categories, and orders
- View system statistics and analytics
- Manage vendor accounts
- Access all orders and transactions

#### 🏪 Vendor (Own Products & Orders)
- Manage own product inventory
- View and fulfill own orders
- Update product stock levels
- Track sales and revenue
- Upload product images

#### 🛍️ Customer (Shopping & Orders)
- Browse products and categories
- Place and manage orders
- View order history and status
- Manage profile and shipping addresses
- Download PDF invoices

### 📦 Core Functionality
- **Product Management** - Categories, variants, inventory tracking
- **Order Processing** - Cart, checkout, payment status
- **Invoice Generation** - Automatic PDF invoice creation
- **Search & Filters** - Advanced product search with SQLite/MySQL support
- **Inventory Management** - Stock tracking and low-stock alerts
- **Soft Deletes** - Safe data deletion with recovery option

## 📋 Prerequisites

- **PHP** >= 8.2
- **Composer** >= 2.0
- **MySQL** >= 8.0 or **MariaDB** >= 10.3 (or SQLite for testing)
- **Node.js** >= 18.x (for frontend assets)
- **Laravel** 11.x

## 🚀 Installation & Setup

### 1. Clone Repository
```bash
git clone https://github.com/mostafijur007/ecom.git
cd ecom
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install Node Dependencies (Optional)
```bash
npm install
```

### 4. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret
```

### 5. Database Configuration

Update your `.env` file with database credentials:

**For MySQL/MariaDB (Production):**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecom
DB_USERNAME=root
DB_PASSWORD=your_password
```

**For SQLite (Development/Testing):**
```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

### 6. JWT Configuration

Ensure JWT settings in `.env`:
```env
JWT_SECRET=your_generated_secret
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

### 7. Run Database Migrations
```bash
php artisan migrate
```

### 8. Seed Database (Optional)
```bash
# Seed test users
php artisan db:seed

# Or seed specific seeder
php artisan db:seed --class=UserSeeder
```

Default test accounts will be created:
- **Admin**: `admin@example.com` / `password`
- **Vendor**: `vendor@example.com` / `password`
- **Customer**: `customer@example.com` / `password`

### 9. Storage Link (For File Uploads)
```bash
php artisan storage:link
```

### 10. Start Development Server
```bash
php artisan serve
```

Your API will be available at: **http://localhost:8000**

## 🧪 Testing

This project uses **Pest PHP** - an elegant testing framework with a focus on simplicity.

### Running Tests

```bash
# Run all tests
php artisan test

# Run tests with coverage
php artisan test --coverage

# Run specific test file
php artisan test --filter=AuthTest

# Run tests in parallel
php artisan test --parallel
```

### Test Coverage Summary

| Test Suite | Tests | Status | Coverage |
|------------|-------|--------|----------|
| **Auth Tests** | 8 | ✅ Passing | Registration, Login, Logout, Refresh, Profile |
| **Product Tests (Admin)** | 8 | ✅ Passing | CRUD, Search, Validation, Authorization |
| **Order Tests (Admin)** | 9 | ✅ Passing | List, View, Update Status, Stats |
| **Vendor Product Tests** | 11 | ✅ Passing | Isolation, CRUD, Stock Management |
| **Customer Order Tests** | 7 | ✅ Passing | Create, View, History |
| **Skipped Tests** | 3 | ⏭️ Skipped | Unimplemented routes |
| **TOTAL** | **47** | **44 passing, 3 skipped** | **131 assertions** |

### Test Database

Tests use **SQLite in-memory database** for fast, isolated testing:
- No MySQL required for testing
- Tests run in complete isolation
- Automatic database recreation per test
- Configured in `phpunit.xml`

### Test Organization

```
tests/
├── Feature/
│   ├── Api/
│   │   └── V1/
│   │       ├── AuthTest.php              # 8 tests - Authentication
│   │       ├── Admin/
│   │       │   ├── ProductControllerTest.php    # 8 tests - Admin products
│   │       │   └── OrderControllerTest.php      # 9 tests - Admin orders
│   │       ├── Vendor/
│   │       │   └── VendorProductControllerTest.php  # 11 tests - Vendor products
│   │       └── Customer/
│   │           └── CustomerOrderControllerTest.php  # 10 tests - Customer orders
│   └── ExampleTest.php
└── Unit/
    └── ExampleTest.php
```

### Test Factories

All models have comprehensive factories for testing:

```php
// Category Factory
Category::factory()->create();
Category::factory()->inactive()->create();
Category::factory()->child()->create();

// Product Factory
Product::factory()->create();
Product::factory()->inactive()->create();
Product::factory()->featured()->create();
Product::factory()->outOfStock()->create();
Product::factory()->lowStock()->create();
Product::factory()->onSale()->create();

// Order Factory
Order::factory()->create();
Order::factory()->pending()->create();
Order::factory()->processing()->create();
Order::factory()->shipped()->create();
Order::factory()->delivered()->create();
Order::factory()->cancelled()->create();

// User Factory
User::factory()->create();
User::factory()->admin()->create();
User::factory()->vendor()->create();
User::factory()->customer()->create();
```

### Writing Tests

Tests use Pest's elegant functional syntax:

```php
use App\Models\User;
use App\Models\Product;

test('admin can create product', function () {
    $admin = User::factory()->admin()->create();
    $token = auth()->login($admin);
    
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token
    ])->postJson('/api/v1/admin/products', [
        'name' => 'Test Product',
        'price' => 99.99,
        // ... other fields
    ]);
    
    $response->assertStatus(201)
             ->assertJson(['success' => true]);
});
```

### Test Best Practices

1. **Use Factories** - Always use factories instead of manual model creation
2. **Isolate Tests** - Each test should be independent
3. **Use beforeEach** - Set up common test data in beforeEach hooks
4. **Test Happy & Sad Paths** - Test both success and failure scenarios
5. **Clear Assertions** - Make assertions specific and meaningful
6. **SQLite Compatibility** - Avoid MySQL-specific features in application code

### Test Accounts (For Manual Testing)

Default test accounts created by seeding:
- **Admin**: `admin@example.com` / `password`
- **Vendor**: `vendor@example.com` / `password`
- **Customer**: `customer@example.com` / `password`

## 📚 API Documentation

### Quick Start - Token Flow

1. **Register or Login** to get tokens:
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

Response:
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "def50200a1b2c3d4e5f6...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

2. **Use Access Token** for authenticated requests:
```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

3. **Refresh Token** when access token expires:
```bash
curl -X POST http://localhost:8000/api/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "def50200a1b2c3d4e5f6..."
  }'
```

## 🔐 API Endpoints

### 🌐 Public Endpoints (No Authentication)

#### Authentication
```http
POST   /api/v1/auth/register    # Register new user
POST   /api/v1/auth/login       # Login and get tokens
POST   /api/v1/auth/refresh     # Refresh access token
```

**Example: Register**
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "customer"
  }'
```

**Example: Login**
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### 🔒 Protected Endpoints (Authenticated Users)

All protected endpoints require Bearer token:
```http
Authorization: Bearer {access_token}
```

#### User Profile
```http
GET    /api/v1/auth/me          # Get current user profile
POST   /api/v1/auth/logout      # Logout (invalidate tokens)
```

**Example: Get Profile**
```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer {access_token}"
```

### 👨‍💼 Admin Endpoints (Role: admin)

#### Product Management
```http
GET    /api/v1/admin/products              # List all products (paginated)
POST   /api/v1/admin/products              # Create new product
GET    /api/v1/admin/products/{id}         # Get product details
PUT    /api/v1/admin/products/{id}         # Update product
DELETE /api/v1/admin/products/{id}         # Delete product
GET    /api/v1/admin/products/search       # Search products
```

**Example: Create Product**
```bash
curl -X POST http://localhost:8000/api/v1/admin/products \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Wireless Mouse",
    "description": "Ergonomic wireless mouse",
    "price": 29.99,
    "stock_quantity": 100,
    "vendor_id": 1,
    "category_id": 1,
    "sku": "WM-001",
    "is_active": true
  }'
```

**Example: Search Products**
```bash
curl -X GET "http://localhost:8000/api/v1/admin/products/search?q=mouse&price_min=20&price_max=50" \
  -H "Authorization: Bearer {access_token}"
```

#### Order Management
```http
GET    /api/v1/admin/orders                # List all orders (paginated)
GET    /api/v1/admin/orders/{id}           # Get order details
PUT    /api/v1/admin/orders/{id}/status    # Update order status
GET    /api/v1/admin/orders/stats          # Get order statistics
```

**Example: Update Order Status**
```bash
curl -X PUT http://localhost:8000/api/v1/admin/orders/1/status \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "processing"
  }'
```

### 🏪 Vendor Endpoints (Role: vendor)

Vendors can only manage their own products.

#### Product Management
```http
GET    /api/v1/vendor/products             # List vendor's products
POST   /api/v1/vendor/products             # Create new product
GET    /api/v1/vendor/products/{id}        # Get product details
PUT    /api/v1/vendor/products/{id}        # Update product
DELETE /api/v1/vendor/products/{id}        # Delete product
GET    /api/v1/vendor/products/search      # Search vendor's products
PUT    /api/v1/vendor/products/{id}/stock  # Update stock quantity
```

**Example: Update Stock**
```bash
curl -X PUT http://localhost:8000/api/v1/vendor/products/1/stock \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "stock_quantity": 50
  }'
```

#### Order Management
```http
GET    /api/v1/vendor/orders               # List orders for vendor's products
GET    /api/v1/vendor/orders/{id}          # Get order details
```

### 🛍️ Customer Endpoints (Role: customer)

#### Order Management
```http
GET    /api/v1/customer/orders             # List customer's orders
POST   /api/v1/customer/orders             # Create new order
GET    /api/v1/customer/orders/{id}        # Get order details
GET    /api/v1/customer/orders/{id}/invoice # Download PDF invoice
```

**Example: Create Order**
```bash
curl -X POST http://localhost:8000/api/v1/customer/orders \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "product_id": 1,
        "quantity": 2,
        "price": 29.99
      }
    ],
    "shipping_address": {
      "street": "123 Main St",
      "city": "Springfield",
      "state": "IL",
      "zip_code": "62701",
      "country": "USA"
    },
    "payment_method": "credit_card"
  }'
```

**Example: Download Invoice**
```bash
curl -X GET http://localhost:8000/api/v1/customer/orders/1/invoice \
  -H "Authorization: Bearer {access_token}" \
  --output invoice.pdf
```

### 📄 Response Format

All API responses follow a consistent format:

**Success Response:**
```json
{
  "success": true,
  "data": {
    // Response data
  },
  "message": "Operation successful"
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

**Paginated Response:**
```json
{
  "success": true,
  "data": [
    // Array of items
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 73
  },
  "links": {
    "first": "http://localhost:8000/api/v1/admin/products?page=1",
    "last": "http://localhost:8000/api/v1/admin/products?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/v1/admin/products?page=2"
  }
}
```

## 🔒 Security Features

- **JWT Token Authentication** - Secure, stateless authentication
- **Password Hashing** - Bcrypt with automatic salting
- **Role-Based Authorization** - Middleware-enforced access control
- **Token Expiration** - Short-lived access tokens (60 minutes)
- **Refresh Token Rotation** - Database-stored refresh tokens
- **Request Validation** - Comprehensive input validation
- **CORS Configuration** - Secure cross-origin requests
- **SQL Injection Protection** - Eloquent ORM with parameter binding
- **Mass Assignment Protection** - Fillable/guarded attributes on models

## 📦 Tech Stack

### Backend
- **Framework**: Laravel 11.x
- **PHP**: 8.2+
- **Authentication**: tymon/jwt-auth
- **PDF Generation**: barryvdh/laravel-dompdf
- **Testing**: Pest PHP v3.x

### Database
- **Production**: MySQL 8.0+ / MariaDB 10.3+
- **Testing**: SQLite (in-memory)
- **ORM**: Eloquent

### Development Tools
- **Dependency Management**: Composer
- **Asset Building**: Vite
- **Code Quality**: PHPStan (optional)
- **Version Control**: Git

## 🗂️ Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           ├── AuthController.php           # Authentication
│   │           ├── Admin/
│   │           │   ├── ProductController.php    # Admin products
│   │           │   └── OrderController.php      # Admin orders
│   │           ├── Vendor/
│   │           │   └── VendorProductController.php  # Vendor products
│   │           └── Customer/
│   │               └── CustomerOrderController.php  # Customer orders
│   ├── Middleware/
│   │   ├── RoleMiddleware.php                  # Role authorization
│   │   └── Authenticate.php                    # JWT verification
│   └── Requests/                               # Form validations
├── Models/
│   ├── User.php                                # User model with roles
│   ├── Product.php                             # Product model
│   ├── Category.php                            # Category model
│   ├── Order.php                               # Order model
│   └── RefreshToken.php                        # Refresh token storage
├── Repositories/
│   ├── ProductRepository.php                   # Product data access
│   └── OrderRepository.php                     # Order data access
└── Services/
    └── InvoiceService.php                      # PDF invoice generation

database/
├── factories/
│   ├── UserFactory.php                         # User test data
│   ├── CategoryFactory.php                     # Category test data
│   ├── ProductFactory.php                      # Product test data
│   └── OrderFactory.php                        # Order test data
├── migrations/
│   ├── *_create_users_table.php
│   ├── *_create_products_table.php
│   ├── *_create_categories_table.php
│   ├── *_create_orders_table.php
│   └── *_create_refresh_tokens_table.php
└── seeders/
    ├── DatabaseSeeder.php
    └── UserSeeder.php                          # Test user accounts

routes/
├── api.php                                     # API routes
├── web.php                                     # Web routes
└── console.php                                 # Console commands

tests/
├── Feature/
│   └── Api/
│       └── V1/
│           ├── AuthTest.php                    # 8 tests
│           ├── Admin/
│           │   ├── ProductControllerTest.php   # 8 tests
│           │   └── OrderControllerTest.php     # 9 tests
│           ├── Vendor/
│           │   └── VendorProductControllerTest.php  # 11 tests
│           └── Customer/
│               └── CustomerOrderControllerTest.php  # 10 tests
└── Unit/                                       # Unit tests
```

## 🎯 Key Features Explained

### JWT Authentication Flow
1. User logs in with credentials
2. Server issues two tokens:
   - **Access Token** (short-lived, 60 min) - Used for API requests
   - **Refresh Token** (long-lived, 14 days) - Used to get new access token
3. Access token included in `Authorization: Bearer {token}` header
4. When access token expires, use refresh token to get new one
5. Refresh token rotates on each use for security

### Role-Based Access Control
- **Admin**: Full system access, manage all resources
- **Vendor**: Manage own products and view related orders
- **Customer**: Place orders, view order history, download invoices

### Product Search
- Text search across name, description, SKU
- Filter by price range, category, vendor
- Pagination support (15 items per page)
- Database-agnostic (works with MySQL and SQLite)

### Order Management
- Full order lifecycle (pending → processing → shipped → delivered)
- PDF invoice generation
- Order statistics and analytics
- Role-based order visibility

## 🚀 Quick Start Example

### 1. Login as Admin
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

**Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "def50200...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

### 2. Use Access Token
```bash
curl -X GET http://localhost:8000/api/v1/admin/products \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

### 3. Create a Product
```bash
curl -X POST http://localhost:8000/api/v1/admin/products \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Gaming Laptop",
    "description": "High-performance gaming laptop",
    "price": 1299.99,
    "stock_quantity": 50,
    "vendor_id": 1,
    "category_id": 1,
    "sku": "LAPTOP-001"
  }'
```

### 4. Place an Order (as Customer)
```bash
# First login as customer
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@example.com",
    "password": "password"
  }'

# Then create order
curl -X POST http://localhost:8000/api/v1/customer/orders \
  -H "Authorization: Bearer {customer_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "product_id": 1,
        "quantity": 1,
        "price": 1299.99
      }
    ],
    "shipping_address": {
      "street": "123 Main St",
      "city": "New York",
      "state": "NY",
      "zip_code": "10001",
      "country": "USA"
    },
    "payment_method": "credit_card"
  }'
```

### 5. Download Invoice
```bash
curl -X GET http://localhost:8000/api/v1/customer/orders/1/invoice \
  -H "Authorization: Bearer {customer_token}" \
  --output invoice.pdf
```

## 🛠️ Common Tasks

### Add New Admin User
```bash
php artisan tinker
> User::create([
    'name' => 'New Admin',
    'email' => 'newadmin@example.com',
    'password' => bcrypt('password'),
    'role' => 'admin'
  ]);
```

### Clear Application Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Run Database Migrations
```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Refresh database (drop all tables and re-run)
php artisan migrate:fresh

# Refresh and seed
php artisan migrate:fresh --seed
```

### Generate API Documentation (If using L5-Swagger)
```bash
php artisan l5-swagger:generate
```

## 🐛 Troubleshooting

### Issue: "Token could not be parsed"
**Solution**: Ensure the token is correctly formatted in the Authorization header:
```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### Issue: "Unauthenticated" on protected routes
**Solution**: 
1. Check if token has expired (60 min default)
2. Use refresh token to get new access token
3. Verify user has required role for the endpoint

### Issue: Tests failing with database errors
**Solution**:
1. Ensure `phpunit.xml` is configured for SQLite
2. Check database connection in `.env.testing`
3. Run `php artisan config:clear`

### Issue: "Column not found" in tests
**Solution**: Run migrations for test database:
```bash
php artisan migrate --env=testing
```

### Issue: Product search not working
**Solution**: The app uses different search methods for SQLite (LIKE) and MySQL (FULLTEXT). Ensure your production database has FULLTEXT index:
```sql
ALTER TABLE products ADD FULLTEXT INDEX products_search_idx (name, description, sku);
```

## 📈 Performance Tips

1. **Enable Query Caching**: Use Redis for caching frequent queries
2. **Optimize Images**: Store product images in optimized formats
3. **Use Eager Loading**: Prevent N+1 queries with `with()` method
4. **Index Foreign Keys**: Ensure all foreign keys have database indexes
5. **Queue Long Tasks**: Use Laravel queues for PDF generation and emails

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation for API changes
- Use meaningful commit messages

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👥 Authors

- **Mostafijur Rahman** - [GitHub](https://github.com/mostafijur007)

## 🙏 Acknowledgments

- Laravel Framework
- JWT Auth by tymon
- Pest PHP Testing Framework
- mPDF for invoice generation
- All contributors and testers

---

**Built with ❤️ using Laravel**
