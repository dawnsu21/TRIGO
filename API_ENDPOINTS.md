# TRIGO API Endpoints Documentation

**Base URL:** `http://localhost:8000/api`

**CORS:** ✅ Configured for `http://localhost:5173`

**Authentication:** Most endpoints require a Bearer token in the `Authorization` header:
```
Authorization: Bearer {token}
```

---

## 🔓 Public Endpoints (No Authentication Required)

### 1. Login
**POST** `/api/login`

Login with email/username and password.

**Request Body:**
```json
{
  "email": "user@example.com",        // Optional: email or identifier
  "identifier": "username",            // Optional: username or email
  "password": "password123",           // Required
  "device_name": "My Device"          // Optional: defaults to "apiToken"
}
```

**Response (200):**
```json
{
  "message": "Login successful",
  "token": "1|xxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    ...
  }
}
```

**Error (401):**
```json
{
  "message": "Invalid credentials"
}
```

---

### 2. Register Passenger
**POST** `/api/register/passenger`

Register a new passenger account.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "passenger@example.com",
  "password": "password123"
}
```

**Response (201):**
```json
{
  "message": "Passenger registered successfully",
  "data": {
    "user": { ... },
    "token": "1|xxxxxxxxxxxxxxxxxxxx"
  }
}
```

---

### 3. Register Driver
**POST** `/api/register/driver`

Register a new driver account (requires admin approval).

**Request Body:**
```json
{
  "name": "Jane Driver",
  "email": "driver@example.com",
  "password": "password123",
  "vehicle_type": "Sedan",
  "plate_number": "ABC-1234",
  "license_number": "DL123456",
  "franchise_number": "FR-001"        // Optional
}
```

**Response (201):**
```json
{
  "message": "Driver registered. Awaiting admin approval.",
  "data": {
    "user": { ... },
    "token": "1|xxxxxxxxxxxxxxxxxxxx"
  }
}
```

---

## 🔒 Authenticated Endpoints (Require Bearer Token)

### 4. Get Current User
**GET** `/api/me`

Get authenticated user's profile.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "driverProfile": null  // or driver profile if user is a driver
  }
}
```

---

### 5. Logout
**POST** `/api/logout`

Logout and invalidate current token.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "message": "Logged out"
}
```

---

## 👤 Passenger Endpoints (Require `passenger` role)

### 6. Request a Ride
**POST** `/api/passenger/rides`

Request a new ride.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "pickup_lat": 14.5995,              // Required: -90 to 90
  "pickup_lng": 120.9842,              // Required: -180 to 180
  "pickup_address": "123 Main St",     // Optional
  "drop_lat": 14.6042,                 // Required: -90 to 90
  "drop_lng": 120.9792,                // Required: -180 to 180
  "drop_address": "456 Oak Ave"       // Optional
}
```

**Response (201):**
```json
{
  "message": "Ride requested. Waiting for nearby driver.",
  "data": {
    "id": 1,
    "passenger_id": 1,
    "pickup_lat": "14.5995000",
    "pickup_lng": "120.9842000",
    "drop_lat": "14.6042000",
    "drop_lng": "120.9792000",
    "fare": 25.50,
    "status": "requested",
    "requested_at": "2025-11-27T12:00:00.000000Z",
    ...
  }
}
```

---

### 7. Get Current Ride
**GET** `/api/passenger/rides/current`

Get passenger's active ride (requested, assigned, or in progress).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "status": "assigned",
    "driver": {
      "id": 2,
      "name": "Jane Driver",
      "email": "driver@example.com"
    },
    ...
  }
}
```

**Response (200) - No active ride:**
```json
{
  "data": null
}
```

---

### 8. Get Ride History
**GET** `/api/passenger/rides`

Get passenger's completed and canceled rides (paginated).

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional): Page number for pagination

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "status": "completed",
      "fare": 25.50,
      "completed_at": "2025-11-27T12:30:00.000000Z",
      ...
    }
  ],
  "current_page": 1,
  "per_page": 10,
  "total": 5,
  ...
}
```

---

### 9. Cancel Ride
**POST** `/api/passenger/rides/{ride}/cancel`

Cancel a requested, assigned, or accepted ride (before driver picks up passenger).

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
- `{ride}` - The ride ID (integer)

**Request Body:**
```json
{
  "reason": "Changed my mind"  // Optional, max 500 characters
}
```

**Response (200) - Success:**
```json
{
  "message": "Ride cancelled successfully",
  "data": {
    "ride": {
      "id": 1,
      "status": "canceled",
      "passenger_id": 3,
      "driver_id": 5,
      "fare": 45.50,
      "canceled_at": "2025-01-15T10:45:00.000000Z",
      "cancellation_reason": "Changed my mind",
      "pickupPlace": {...},
      "dropoffPlace": {...},
      "driver": {...}
    },
    "status": "canceled",
    "status_label": "Canceled",
    "canceled_at": "2025-01-15T10:45:00.000000Z"
  }
}
```

**Error (403) - Not Owner:**
```json
{
  "message": "You can only cancel your own rides.",
  "error": "unauthorized"
}
```

**Error (422) - Cannot Cancel:**
```json
{
  "message": "Ride can no longer be canceled.",
  "current_status": "in_progress",
  "status_label": "In Progress",
  "can_cancel": false,
  "reason": "Ride is already in progress. Driver has picked up the passenger."
}
```

**Error (404) - Ride Not Found:**
```json
{
  "message": "No query results for model [App\\Models\\Ride] {ride_id}"
}
```

**Notes:**
- Rides can only be canceled if status is: `requested`, `assigned`, or `accepted`
- Once driver picks up passenger (status = `in_progress`), cancellation is not allowed
- The `reason` field is optional but recommended for tracking
- Cancellation automatically notifies the passenger

---

## 🚗 Driver Endpoints (Require `driver` role + Approved Profile)

### 10. Get Driver Profile
**GET** `/api/driver/profile`

Get driver's profile information.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": {
    "id": 2,
    "name": "Jane Driver",
    "email": "driver@example.com",
    "driverProfile": {
      "id": 1,
      "vehicle_type": "Sedan",
      "plate_number": "ABC-1234",
      "status": "approved",
      "is_online": true,
      "current_lat": "14.5995",
      "current_lng": "120.9842",
      ...
    }
  }
}
```

**Error (403):**
```json
{
  "message": "Driver profile is pending approval."
}
```

---

### 11. Update Availability
**POST** `/api/driver/availability`

Update driver's online/offline status.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "is_online": true  // Required: boolean
}
```

**Response (200):**
```json
{
  "message": "Availability updated."
}
```

---

### 12. Update Location
**POST** `/api/driver/location`

Update driver's current location.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "lat": 14.5995,   // Required: -90 to 90
  "lng": 120.9842   // Required: -180 to 180
}
```

**Response (200):**
```json
{
  "message": "Location updated."
}
```

---

### 13. Get Ride Queue
**GET** `/api/driver/rides/queue`

Get list of available ride requests (driver must be online).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "passenger_id": 1,
      "pickup_lat": "14.5995000",
      "pickup_lng": "120.9842000",
      "drop_lat": "14.6042000",
      "drop_lng": "120.9792000",
      "fare": 25.50,
      "status": "requested",
      "requested_at": "2025-11-27T12:00:00.000000Z",
      ...
    }
  ]
}
```

**Error (409):**
```json
{
  "message": "Go online to view ride requests."
}
```

---

### 14. Accept Ride
**POST** `/api/driver/rides/{rideId}/accept`

Accept a ride request.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "message": "Ride accepted.",
  "data": {
    "id": 1,
    "driver_id": 2,
    "status": "assigned",
    "accepted_at": "2025-11-27T12:05:00.000000Z",
    ...
  }
}
```

**Error (409):**
```json
{
  "message": "Go online before accepting rides."
}
```
or
```json
{
  "message": "Ride is no longer available."
}
```

---

### 15. Pickup Passenger
**POST** `/api/driver/rides/{rideId}/pickup`

Mark passenger as picked up.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "message": "Passenger picked up."
}
```

**Error (422):**
```json
{
  "message": "Ride must be accepted before pickup."
}
```

---

### 16. Complete Ride
**POST** `/api/driver/rides/{rideId}/complete`

Mark ride as completed.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "message": "Ride completed."
}
```

**Error (422):**
```json
{
  "message": "Ride must be in progress to complete."
}
```

---

## 👨‍💼 Admin Endpoints (Require `admin` role)

### 17. Dashboard Stats
**GET** `/api/admin/dashboard`

Get admin dashboard statistics.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "message": "Admin dashboard overview.",
  "stats": {
    "passengers": 10,
    "drivers": 5,
    "rides": 50,
    "active_rides": 3
  }
}
```

---

### 18. Get Drivers List
**GET** `/api/admin/drivers`

Get paginated list of drivers.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` (optional): Filter by status (`pending`, `approved`, `rejected`)
- `page` (optional): Page number

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 2,
      "vehicle_type": "Sedan",
      "plate_number": "ABC-1234",
      "status": "approved",
      "user": {
        "id": 2,
        "name": "Jane Driver",
        "email": "driver@example.com"
      },
      ...
    }
  ],
  "current_page": 1,
  "per_page": 15,
  ...
}
```

---

### 19. Update Driver Status
**POST** `/api/admin/drivers/{driverProfileId}/status`

Approve or reject a driver.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "status": "approved"  // Required: "approved" or "rejected"
}
```

**Response (200):**
```json
{
  "message": "Driver status updated."
}
```

---

### 20. Get All Rides
**GET** `/api/admin/rides`

Get paginated list of all rides.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional): Page number

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "passenger_id": 1,
      "driver_id": 2,
      "status": "completed",
      "fare": 25.50,
      "passenger": {
        "id": 1,
        "name": "John Doe"
      },
      "driver": {
        "id": 2,
        "name": "Jane Driver"
      },
      ...
    }
  ],
  "current_page": 1,
  "per_page": 20,
  ...
}
```

---

## 📝 Notes

### Authentication Flow
1. Register or Login to get a token
2. Include token in all authenticated requests:
   ```
   Authorization: Bearer {token}
   ```
3. Token is valid until logout or expiration

### Ride Status Flow
- `requested` → Passenger requests a ride
- `assigned` → Driver accepts the ride
- `in_progress` → Driver picks up passenger
- `completed` → Ride finished
- `canceled` → Ride canceled by passenger

### Driver Status Flow
- `pending` → Driver registered, awaiting admin approval
- `approved` → Driver can accept rides
- `rejected` → Driver application rejected

### Error Codes
- `200` - Success
- `201` - Created
- `401` - Unauthorized (invalid credentials or missing token)
- `403` - Forbidden (insufficient permissions)
- `409` - Conflict (business logic error)
- `422` - Validation error or invalid state
- `500` - Server error

### CORS Configuration
✅ CORS is configured for:
- `http://localhost:5173`
- `http://localhost:3000`
- `http://127.0.0.1:5173`

All methods and headers are allowed.


