# TriGo Backend Setup Guide

Complete setup guide for the TriGo tricycle booking and dispatch system backend.

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer installed
- MySQL/MariaDB or SQLite database
- Node.js (for frontend, if needed)

## 🚀 Step-by-Step Setup

### Step 1: Install Dependencies

```bash
cd "C:\Users\Angela Claire\Downloads\TRIGO-main\TRIGO-main"
composer install
```

**Note:** If you get errors about missing zip extension:
1. Open `C:\xampp\php\php.ini`
2. Find `;extension=zip` and remove the semicolon: `extension=zip`
3. Restart your terminal and run `composer install` again

### Step 2: Environment Configuration

Create a `.env` file (copy from `.env.example` if it exists):

```bash
# If .env.example exists
copy .env.example .env

# Or create manually
```

**Required `.env` settings:**

```env
APP_NAME=TriGo
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration (Choose one)

# Option 1: MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trigo_db
DB_USERNAME=root
DB_PASSWORD=

# Option 2: SQLite (Easier for development)
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# CORS Configuration
FRONTEND_ORIGINS=http://localhost:5173,http://localhost:3000

# Admin Email (for seeder)
ADMIN_EMAIL=admin@trigo.test
```

### Step 3: Generate Application Key

```bash
php artisan key:generate
```

### Step 4: Database Setup

**For MySQL:**
1. Create database: `CREATE DATABASE trigo_db;`
2. Run migrations: `php artisan migrate`

**For SQLite:**
1. Create database file: `touch database/database.sqlite` (or create manually)
2. Run migrations: `php artisan migrate`

### Step 5: Seed Database (Create Roles & Admin User)

```bash
php artisan db:seed
```

This will:
- Create roles: `admin`, `driver`, `passenger`
- Create default admin user (email from `ADMIN_EMAIL` in `.env`, default: `admin@trigo.test`)
- Admin password: `password`

### Step 6: Start the Server

```bash
php artisan serve
```

Server will run at: `http://localhost:8000`

---

## 🔐 User Roles & Authentication

### Roles

1. **Admin** - System administrator
   - Can approve/reject drivers
   - View all rides and statistics
   - Manage drivers

2. **Driver** - Tricycle driver
   - Must be approved by admin before accepting rides
   - Can update availability and location
   - Can accept and complete rides

3. **Passenger** - Regular user
   - Can request rides
   - Can view ride history
   - Can cancel rides

### Registration

**Unified Registration Endpoint:**
```
POST /api/register
```

**Request Body (Passenger):**
```json
{
  "name": "John Doe",
  "email": "passenger@example.com",
  "password": "password123",
  "role": "passenger"
}
```

**Request Body (Driver):**
```json
{
  "name": "Jane Driver",
  "email": "driver@example.com",
  "password": "password123",
  "role": "driver",
  "vehicle_type": "Tricycle",
  "plate_number": "ABC-1234",
  "license_number": "DL123456",
  "franchise_number": "FR-001"
}
```

**Response:**
```json
{
  "message": "Passenger registered successfully",
  "data": {
    "user": { ... },
    "token": "1|xxxxxxxxxxxx",
    "role": "passenger"
  }
}
```

### Login

**Endpoint:**
```
POST /api/login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "message": "Login successful",
  "token": "1|xxxxxxxxxxxx",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "driverProfile": null
  },
  "role": "passenger",
  "roles": ["passenger"]
}
```

**Frontend Dashboard Routing:**
- `role: "admin"` → Redirect to `/admin/dashboard`
- `role: "driver"` → Redirect to `/driver/dashboard`
- `role: "passenger"` → Redirect to `/passenger/dashboard`

---

## 📡 API Endpoints

### Public Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/login` | Login |
| POST | `/api/register` | Register (with role) |
| POST | `/api/register/passenger` | Register passenger (legacy) |
| POST | `/api/register/driver` | Register driver (legacy) |

### Authenticated Endpoints

| Method | Endpoint | Role Required | Description |
|--------|----------|---------------|-------------|
| GET | `/api/me` | Any | Get current user |
| POST | `/api/logout` | Any | Logout |

### Passenger Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/passenger/rides` | Request ride |
| GET | `/api/passenger/rides/current` | Get active ride |
| GET | `/api/passenger/rides` | Get ride history |
| POST | `/api/passenger/rides/{id}/cancel` | Cancel ride |

### Driver Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/driver/profile` | Get profile |
| POST | `/api/driver/availability` | Update online status |
| POST | `/api/driver/location` | Update location |
| GET | `/api/driver/rides/queue` | Get ride queue |
| POST | `/api/driver/rides/{id}/accept` | Accept ride |
| POST | `/api/driver/rides/{id}/pickup` | Pickup passenger |
| POST | `/api/driver/rides/{id}/complete` | Complete ride |

### Admin Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/dashboard` | Dashboard stats |
| GET | `/api/admin/drivers` | List drivers |
| POST | `/api/admin/drivers/{id}/status` | Approve/reject driver |
| GET | `/api/admin/rides` | List all rides |

---

## 🧪 Testing the Setup

### 1. Test Server
```bash
curl http://localhost:8000/api/login
```
Should return a validation error (expected).

### 2. Test Registration
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "role": "passenger"
  }'
```

### 3. Test Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

### 4. Test Authenticated Endpoint
```bash
curl http://localhost:8000/api/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 🔧 Troubleshooting

### Issue: "vendor/autoload.php not found"
**Solution:** Run `composer install` again

### Issue: "Database connection failed"
**Solution:** 
- Check `.env` database settings
- Ensure database exists
- For MySQL: check credentials
- For SQLite: ensure file exists and is writable

### Issue: "Role middleware not working"
**Solution:** 
- Run `php artisan db:seed` to create roles
- Clear cache: `php artisan config:clear`

### Issue: "CORS errors in frontend"
**Solution:**
- Check `config/cors.php` has your frontend URL
- Clear config cache: `php artisan config:clear`
- Restart server

### Issue: "Driver cannot accept rides"
**Solution:**
- Driver must be approved by admin first
- Admin should use: `POST /api/admin/drivers/{id}/status` with `{"status": "approved"}`

---

## 📝 Default Admin Credentials

After running `php artisan db:seed`:

- **Email:** `admin@trigo.test` (or value from `ADMIN_EMAIL` in `.env`)
- **Password:** `password`

**⚠️ Change this in production!**

---

## 🎯 Next Steps

1. ✅ Backend is set up
2. Connect your frontend to `http://localhost:8000/api`
3. Use the `role` field from login response to route users to correct dashboard
4. Implement ride booking flow
5. Test driver approval workflow

---

## 📚 Additional Resources

- Full API Documentation: See `API_ENDPOINTS.md`
- Laravel Documentation: https://laravel.com/docs
- Spatie Permission: https://spatie.be/docs/laravel-permission

---

**Need Help?** Check the error logs in `storage/logs/laravel.log`

