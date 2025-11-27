# TriGo Places/Locations System

## ✅ Implementation Complete

The Places system has been fully implemented according to your requirements. Users can now select from predefined locations in Bulan instead of entering raw coordinates.

## 📊 Database Structure

### Places Table
```sql
places
├── id
├── name (e.g., "Bulan Public Market")
├── address
├── latitude
├── longitude
├── category (landmark, barangay, establishment, school, government)
├── is_active
└── timestamps
```

### Updated Rides Table
```sql
rides
├── pickup_place_id (foreign key → places.id)
├── dropoff_place_id (foreign key → places.id)
├── pickup_lat, pickup_lng (kept for backward compatibility)
└── drop_lat, drop_lng (kept for backward compatibility)
```

## 🚀 API Endpoints

### Public Endpoints (No Auth Required)

#### Get All Places
```
GET /api/places
GET /api/places?category=landmark
GET /api/places?q=market
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Bulan Public Market",
      "address": "Poblacion, Bulan, Sorsogon",
      "latitude": 12.6714,
      "longitude": 123.8750,
      "category": "landmark",
      "is_active": true
    }
  ]
}
```

#### Search Places
```
GET /api/places/search?q=market
```

#### Get Single Place
```
GET /api/places/{id}
```

### Admin Endpoints (Require Admin Role)

#### Create Place
```
POST /api/admin/places
Authorization: Bearer {token}

Body: {
  "name": "New Location",
  "address": "Full address",
  "latitude": 12.6714,
  "longitude": 123.8750,
  "category": "landmark",
  "is_active": true
}
```

#### Update Place
```
PUT /api/admin/places/{id}
Authorization: Bearer {token}
```

#### Delete Place
```
DELETE /api/admin/places/{id}
Authorization: Bearer {token}
```

## 📝 Updated Ride Booking

### New Booking Endpoint (Using Places)

```
POST /api/passenger/rides
Authorization: Bearer {token}

Body: {
  "pickup_place_id": 1,
  "dropoff_place_id": 2
}
```

**OR** (Backward compatible - still accepts coordinates):

```
POST /api/passenger/rides
Authorization: Bearer {token}

Body: {
  "pickup_lat": 12.6714,
  "pickup_lng": 123.8750,
  "drop_lat": 12.6740,
  "drop_lng": 123.8720
}
```

**Response includes places:**
```json
{
  "message": "Ride requested. Waiting for nearby driver.",
  "data": {
    "id": 1,
    "pickup_place_id": 1,
    "dropoff_place_id": 2,
    "pickup_place": {
      "id": 1,
      "name": "Bulan Public Market",
      "address": "Poblacion, Bulan, Sorsogon"
    },
    "dropoff_place": {
      "id": 2,
      "name": "Bulan Port",
      "address": "Port Area, Bulan, Sorsogon"
    },
    "fare": 25.50,
    "status": "requested"
  }
}
```

## 🗺️ Pre-seeded Bulan Locations

The seeder includes:

### Landmarks
- Bulan Public Market
- Bulan Port
- Bulan Town Plaza
- Bulan Church

### Schools
- Bulan National High School
- Bulan Central School

### Government Offices
- Bulan Municipal Hall
- Bulan Police Station
- Bulan Rural Health Unit

### Barangays
- Barangay Poblacion
- Barangay Zone 1
- Barangay Zone 2
- Barangay Zone 3

### Establishments
- Bulan Bus Terminal

## 🔧 Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Places
```bash
php artisan db:seed --class=BulanPlacesSeeder
```

Or run all seeders:
```bash
php artisan db:seed
```

## 💡 Frontend Integration

### Step 1: Fetch Places
```javascript
// Get all places
const places = await fetch('http://localhost:8000/api/places')
  .then(res => res.json());

// Search places
const results = await fetch('http://localhost:8000/api/places/search?q=market')
  .then(res => res.json());
```

### Step 2: Display Places in Dropdown
```javascript
// In your booking form
<select name="pickup_place_id">
  {places.data.map(place => (
    <option key={place.id} value={place.id}>
      {place.name} - {place.address}
    </option>
  ))}
</select>
```

### Step 3: Book Ride with Place IDs
```javascript
const response = await fetch('http://localhost:8000/api/passenger/rides', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    pickup_place_id: selectedPickupPlaceId,
    dropoff_place_id: selectedDropoffPlaceId
  })
});
```

## 🎯 Benefits

✅ **User-Friendly**: Users select from familiar locations  
✅ **Accurate**: Consistent location data  
✅ **Easier Navigation**: Drivers know exact destinations  
✅ **Scalable**: Easy to add new locations  
✅ **Backward Compatible**: Still accepts coordinates  

## 📋 Next Steps

1. ✅ Places system implemented
2. ✅ Ride booking updated
3. ⏳ Add more Bulan locations (barangays, schools, etc.)
4. ⏳ Implement real-time location updates
5. ⏳ Add driver matching based on proximity to places

## 🔍 Category Types

- `landmark` - Popular landmarks
- `barangay` - Barangay locations
- `establishment` - Businesses, terminals, etc.
- `school` - Educational institutions
- `government` - Government offices

---

**The Places system is ready to use!** 🎉

