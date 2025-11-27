# TriGo Database Structure - Roles & Users

## ✅ Your Current Structure is CORRECT!

The **users table does NOT need a role column** because you're using **Spatie Laravel Permission**, which uses a **many-to-many relationship** through pivot tables.

## 📊 Database Tables

### 1. `users` Table
```sql
users
├── id (primary key)
├── name
├── email (unique)
├── email_verified_at (nullable)
├── password
├── remember_token
├── created_at
└── updated_at
```

**✅ No role column needed!** Roles are stored separately.

---

### 2. `roles` Table (Created by Spatie Permission)
```sql
roles
├── id (primary key)
├── name (admin, driver, passenger)
├── guard_name (web, sanctum)
├── created_at
└── updated_at
```

**Example Data:**
| id | name      | guard_name |
|----|-----------|------------|
| 1  | admin     | web        |
| 2  | driver    | web        |
| 3  | passenger | web        |

---

### 3. `model_has_roles` Table (Pivot Table)
```sql
model_has_roles
├── role_id (foreign key → roles.id)
├── model_type (App\Models\User)
├── model_id (foreign key → users.id)
└── PRIMARY KEY (role_id, model_id, model_type)
```

**Example Data:**
| role_id | model_type        | model_id |
|---------|-------------------|----------|
| 1       | App\Models\User   | 1        | (User 1 is admin)
| 2       | App\Models\User   | 2        | (User 2 is driver)
| 3       | App\Models\User   | 3        | (User 3 is passenger)

---

## 🔗 How It Works

### When You Assign a Role:
```php
$user->assignRole('passenger');
```

**What Happens:**
1. Finds or creates role with name 'passenger' in `roles` table
2. Creates a record in `model_has_roles` linking user to role

### When You Check a Role:
```php
$user->hasRole('admin');  // Returns true/false
$user->getRoleNames();    // Returns ['admin']
```

**What Happens:**
1. Queries `model_has_roles` table
2. Joins with `roles` table
3. Returns role names

---

## 💡 Why This Structure is Better

### ❌ Bad Approach (Single Column):
```sql
users
├── id
├── name
├── email
└── role (ENUM: 'admin', 'driver', 'passenger')  ← Limited!
```

**Problems:**
- Can only have ONE role per user
- Hard to add new roles
- No flexibility for permissions
- Not scalable

### ✅ Good Approach (Your Current Structure):
```sql
users ←→ model_has_roles ←→ roles
```

**Benefits:**
- ✅ Users can have MULTIPLE roles
- ✅ Easy to add new roles
- ✅ Supports permissions system
- ✅ Industry standard
- ✅ Scalable and flexible

---

## 📝 Example Queries

### Get All Users with Their Roles:
```php
$users = User::with('roles')->get();
foreach ($users as $user) {
    echo $user->name . ' - Roles: ' . $user->getRoleNames()->implode(', ');
}
```

### Get All Admins:
```php
$admins = User::role('admin')->get();
```

### Check if User is Driver:
```php
if ($user->hasRole('driver')) {
    // User is a driver
}
```

### Assign Role to User:
```php
$user->assignRole('passenger');
```

### Remove Role from User:
```php
$user->removeRole('passenger');
```

---

## 🎯 For Your TriGo System

### Current Roles:
1. **admin** - System administrator
2. **driver** - Tricycle driver
3. **passenger** - Regular commuter

### Typical User Flow:
1. User registers → Gets assigned ONE role (passenger or driver)
2. User logs in → System checks role from `model_has_roles` table
3. User accesses dashboard → Based on their role

### In Your Code:
```php
// Registration
$user->assignRole('passenger');  // Creates record in model_has_roles

// Login
$role = $user->getRoleNames()->first();  // Queries model_has_roles

// Middleware
Route::middleware('role:admin')  // Checks model_has_roles table
```

---

## ✅ Conclusion

**Your database structure is PERFECT!** 

- ✅ Users table is correct (no role column needed)
- ✅ Spatie Permission handles roles via pivot table
- ✅ This is the industry standard approach
- ✅ More flexible and scalable than a single column

**Don't change anything!** The structure is working exactly as it should. 🎉

