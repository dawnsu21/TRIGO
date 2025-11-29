# Driver Approval Troubleshooting Guide

## Issue: Driver Can't Go Online After Approval

### Possible Causes:

1. **Driver Profile Doesn't Exist**
   - Driver may not have completed registration
   - Check if driver has a `driverProfile` record

2. **Status Not Updated Correctly**
   - Admin approval may not have saved
   - Status might still be 'pending' instead of 'approved'

3. **Wrong DriverProfile ID Used**
   - Admin might be using wrong ID when approving
   - Check the ID in the approval request

4. **Cache Issue**
   - Laravel might be caching the old status
   - Clear cache after approval

## 🔍 Diagnostic Steps

### Step 1: Check Driver Profile Status

**As Admin, check the driver:**
```
GET /api/admin/drivers
```

Look for the driver and check:
- Does the driver have a `driverProfile` record?
- What is the current `status`? (should be 'approved')
- What is the `id` of the driverProfile?

### Step 2: Verify Approval Request

**When approving, make sure you're using the correct endpoint:**
```
POST /api/admin/drivers/{driverProfile}/status
Authorization: Bearer {admin_token}

Body: {
  "status": "approved"
}
```

**Important:** Use the `driverProfile` ID, NOT the user ID!

### Step 3: Check Driver Profile Directly

**As Driver, check your profile:**
```
GET /api/driver/profile
Authorization: Bearer {driver_token}
```

Check the response:
- Does `status` show "approved"?
- Does `driverProfile` exist?

### Step 4: Try Going Online

**As Driver:**
```
POST /api/driver/availability
Authorization: Bearer {driver_token}

Body: {
  "is_online": true
}
```

**Error Messages:**
- "Driver profile not found" → Driver hasn't completed registration
- "Driver profile is pending admin approval" → Status is still 'pending'
- "Driver profile has been rejected" → Status is 'rejected'
- "Driver profile is not approved" → Status is something else

## ✅ Solution Steps

### If Status is Still 'pending':

1. **Verify Admin Approval:**
   - Check admin used correct `driverProfile` ID
   - Check request body has `{"status": "approved"}`
   - Check response shows status updated

2. **Clear Cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

3. **Re-approve the Driver:**
   - Use correct `driverProfile` ID
   - Send: `POST /api/admin/drivers/{driverProfile}/status` with `{"status": "approved"}`

### If Driver Profile Doesn't Exist:

1. **Driver needs to complete registration:**
   - Driver should register using: `POST /api/register` with `"role": "driver"`
   - This creates the `driverProfile` record

2. **Check registration was successful:**
   - Driver should have received a `driverProfile` after registration
   - Status should be 'pending' initially

## 🧪 Test Commands

### Check Driver Status (via Tinker):
```bash
php artisan tinker
```

Then:
```php
// Find driver by email
$driver = \App\Models\User::where('email', 'driver@example.com')->first();
$profile = $driver->driverProfile;
echo "Status: " . $profile->status . "\n";
echo "Is Online: " . ($profile->is_online ? 'Yes' : 'No') . "\n";
```

### Manually Approve Driver (via Tinker):
```php
$driver = \App\Models\User::where('email', 'driver@example.com')->first();
$profile = $driver->driverProfile;
$profile->update(['status' => 'approved']);
echo "Driver approved!\n";
```

## 📝 Common Mistakes

1. **Using User ID instead of DriverProfile ID:**
   - ❌ Wrong: `POST /api/admin/drivers/5/status` (if 5 is user_id)
   - ✅ Correct: `POST /api/admin/drivers/2/status` (if 2 is driverProfile id)

2. **Wrong Status Value:**
   - ❌ Wrong: `{"status": "approve"}`
   - ❌ Wrong: `{"status": "APPROVED"}`
   - ✅ Correct: `{"status": "approved"}`

3. **Not Refreshing After Approval:**
   - Driver might need to log out and log back in
   - Or clear frontend cache

## 🔧 Quick Fix

If you know the driver's email, you can manually approve via database:

```sql
UPDATE driver_profiles 
SET status = 'approved' 
WHERE user_id = (SELECT id FROM users WHERE email = 'driver@example.com');
```

Then clear cache:
```bash
php artisan cache:clear
```

---

**After fixing, the driver should be able to:**
1. ✅ Go online: `POST /api/driver/availability` with `{"is_online": true}`
2. ✅ Update location: `POST /api/driver/location`
3. ✅ See ride queue: `GET /api/driver/rides/queue`

