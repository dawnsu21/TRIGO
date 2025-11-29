# TriGo Backend - Quick Start Guide

## ✅ What's Been Set Up

1. **Role-Based Authentication** ✓
   - Three roles: `admin`, `driver`, `passenger`
   - Login returns user role for dashboard routing
   - Registration includes role selection

2. **CORS Configuration** ✓
   - Configured for `http://localhost:5173`
   - All methods and headers allowed

3. **API Endpoints** ✓
   - All endpoints documented in `API_ENDPOINTS.md`
   - Role-based access control implemented

## 🚀 Quick Setup (5 Steps)

### 1. Install Dependencies
```bash
composer install
```

### 2. Create .env File
```bash
# Copy example or create manually
# Set these values:
APP_KEY=  # Will be generated
DB_CONNECTION=sqlite  # or mysql
DB_DATABASE=database/database.sqlite  # or your MySQL database name
FRONTEND_ORIGINS=http://localhost:5173
ADMIN_EMAIL=admin@trigo.test
```

### 3. Generate App Key
```bash
php artisan key:generate
```

### 4. Setup Database
```bash
# For SQLite: create the file first
# Then run:
php artisan migrate
php artisan db:seed
```

### 5. Start Server
```bash
php artisan serve
```

## 📝 Registration & Login

### Registration (Unified Endpoint)
```javascript
// Passenger Registration
POST /api/register
{
  "name": "John Doe",
  "email": "passenger@example.com",
  "password": "password123",
  "role": "passenger"
}

// Driver Registration
POST /api/register
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

### Login Response
```json
{
  "message": "Login successful",
  "token": "1|xxxxxxxxxxxx",
  "user": { ... },
  "role": "passenger",  // Use this for dashboard routing!
  "roles": ["passenger"]
}
```

**Frontend Dashboard Routing:**
```javascript
// After login, check the role:
if (response.role === 'admin') {
  router.push('/admin/dashboard');
} else if (response.role === 'driver') {
  router.push('/driver/dashboard');
} else if (response.role === 'passenger') {
  router.push('/passenger/dashboard');
}
```

## 🔑 Default Admin Account

After running `php artisan db:seed`:
- **Email:** `admin@trigo.test` (or from `ADMIN_EMAIL` in `.env`)
- **Password:** `password`

## 📚 Full Documentation

- **Setup Guide:** `SETUP_GUIDE.md`
- **API Endpoints:** `API_ENDPOINTS.md`

## ⚠️ Common Issues

1. **"vendor/autoload.php not found"**
   - Run: `composer install`

2. **"Database connection failed"**
   - Check `.env` database settings
   - For SQLite: ensure `database/database.sqlite` exists

3. **"Role middleware not working"**
   - Run: `php artisan db:seed`
   - Clear cache: `php artisan config:clear`

4. **CORS errors**
   - Check `config/cors.php`
   - Clear cache: `php artisan config:clear`
   - Restart server

---

**Ready to go!** 🎉

