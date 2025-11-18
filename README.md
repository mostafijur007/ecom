<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# E-Commerce API with JWT Authentication

A Laravel-based e-commerce REST API with JWT authentication and role-based access control.

## 🚀 Features

### Authentication & Authorization
- ✅ **JWT Authentication** - Secure token-based authentication
- ✅ **Refresh Tokens** - Long-lived tokens for seamless user experience
- ✅ **Token Rotation** - Automatic token refresh with security
- ✅ **Role-Based Access Control** - Three distinct user roles

### User Roles & Permissions

#### 👨‍💼 Admin (Full Access)
- Manage all users
- View system dashboard
- Full CRUD operations on all resources

#### 🏪 Vendor (Own Resources)
- Manage own products
- View and update own orders
- Product inventory management
- Order fulfillment

#### 🛒 Customer (Shopping)
- Place orders
- View order history
- Manage profile
- Track orders

## 📋 Prerequisites

- PHP >= 8.2
- Composer
- MySQL/MariaDB
- Laravel 12.x

## 🛠️ Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd ecom
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database**
   Update `.env` with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ecom
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Seed test users**
   ```bash
   php artisan db:seed --class=UserSeeder
   ```

7. **Start the server**
   ```bash
   php artisan serve
   ```

   API will be available at: `http://localhost:8000`

## 🧪 Testing

### Interactive API Documentation
**Swagger UI:** http://localhost:8000/api/documentation

Access the interactive API documentation to:
- Test all endpoints with a visual interface
- View request/response examples
- See authentication flow
- Try different user roles

### Test Accounts
```
Admin:    admin@example.com / password123
Vendor:   vendor@example.com / password123
Customer: customer@example.com / password123
```

### Quick Test (Windows)
```bash
test-api.bat
```

### Quick Test (Linux/Mac)
```bash
chmod +x test-api.sh
./test-api.sh
```

### Using Postman
1. Import `postman_collection.json`
2. Set `base_url` to `http://localhost:8000`
3. Test endpoints with automatic token management

## 📚 Documentation

### API Documentation
- **[Swagger UI](http://localhost:8000/api/documentation)** - Interactive API documentation ⭐
- **[SWAGGER_QUICKSTART.md](SWAGGER_QUICKSTART.md)** - Quick start for Swagger UI
- **[openapi.yaml](openapi.yaml)** - Complete OpenAPI 3.0 specification

### General Documentation
- **[JWT_AUTH_README.md](JWT_AUTH_README.md)** - Complete API documentation
- **[API_TESTING_GUIDE.md](API_TESTING_GUIDE.md)** - Quick testing guide
- **[CONFIGURATION_GUIDE.md](CONFIGURATION_GUIDE.md)** - Configuration details
- **[IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)** - Implementation overview
- **[ARCHITECTURE.md](ARCHITECTURE.md)** - System architecture diagrams
- **[SWAGGER_GUIDE.md](SWAGGER_GUIDE.md)** - Swagger documentation guide

## 🔐 API Endpoints

### Public Endpoints
```
POST   /api/v1/auth/register   - Register new user
POST   /api/v1/auth/login      - Login
POST   /api/v1/auth/refresh    - Refresh access token
```

### Protected Endpoints (Authenticated)
```
GET    /api/v1/auth/me         - Get current user
POST   /api/v1/auth/logout     - Logout
```

### Admin Endpoints (Role: admin)
```
GET    /api/v1/admin/dashboard
GET    /api/v1/admin/users
PUT    /api/v1/admin/users/{id}
DELETE /api/v1/admin/users/{id}
```

### Vendor Endpoints (Role: vendor)
```
GET    /api/v1/vendor/dashboard
GET    /api/v1/vendor/products
POST   /api/v1/vendor/products
PUT    /api/v1/vendor/products/{id}
GET    /api/v1/vendor/orders
PUT    /api/v1/vendor/orders/{id}/status
```

### Customer Endpoints (Role: customer)
```
GET    /api/v1/customer/dashboard
POST   /api/v1/customer/orders
GET    /api/v1/customer/orders
GET    /api/v1/customer/orders/{id}
DELETE /api/v1/customer/orders/{id}
GET    /api/v1/customer/profile
PUT    /api/v1/customer/profile
```

## 🔒 Security Features

- JWT token-based authentication
- Password hashing with bcrypt
- Role-based authorization
- Token expiration and refresh
- Request tracking (IP, User Agent)
- Secure token rotation
- CORS configuration

## 📦 Tech Stack

- **Framework**: Laravel 12.x
- **Authentication**: tymon/jwt-auth
- **Database**: MySQL
- **PHP Version**: 8.2+

## 🗂️ Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           ├── AuthController.php
│   │           ├── AdminController.php
│   │           ├── VendorController.php
│   │           └── CustomerController.php
│   └── Middleware/
│       └── RoleMiddleware.php
└── Models/
    ├── User.php
    └── RefreshToken.php

database/
├── migrations/
│   ├── *_add_role_to_users_table.php
│   └── *_create_refresh_tokens_table.php
└── seeders/
    └── UserSeeder.php

routes/
└── api.php
```

## 🚀 Quick Start Example

```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password123"}'

# Use the access_token from response
curl -X GET http://localhost:8000/api/v1/admin/dashboard \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

## 🔄 Token Flow

1. **Login** → Receive `access_token` & `refresh_token`
2. **API Request** → Send `access_token` in Authorization header
3. **Token Expires** → Use `refresh_token` to get new `access_token`
4. **Logout** → Invalidate both tokens

## 🛡️ Error Responses

```json
// 401 Unauthorized
{
    "success": false,
    "message": "Unauthenticated"
}

// 403 Forbidden
{
    "success": false,
    "message": "Forbidden. You do not have permission to access this resource."
}

// 422 Validation Error
{
    "success": false,
    "errors": {
        "email": ["The email field is required."]
    }
}
```

## 📝 Next Steps

- [ ] Implement Product CRUD operations
- [ ] Implement Order management
- [ ] Add payment gateway integration
- [ ] Implement email notifications
- [ ] Add password reset functionality
- [ ] Implement file upload for products
- [ ] Add comprehensive testing
- [ ] Add API rate limiting
- [ ] Implement audit logging

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
