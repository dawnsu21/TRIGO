# Frontend Cancellation Integration Guide

## 📋 Overview

After the backend cancellation update, your frontend needs to:
1. Call the cancellation endpoint correctly
2. Handle success and error responses
3. Update the UI after cancellation
4. Show proper status information

## 🔌 API Endpoint Details

**Endpoint:** `POST /api/passenger/rides/{rideId}/cancel`

**Headers Required:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body (Optional):**
```json
{
  "reason": "Changed my mind"  // Optional, max 500 characters
}
```

**Success Response (200):**
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

**Error Responses:**

**403 Forbidden (Not Owner):**
```json
{
  "message": "You can only cancel your own rides.",
  "error": "unauthorized"
}
```

**422 Unprocessable Entity (Cannot Cancel):**
```json
{
  "message": "Ride can no longer be canceled.",
  "current_status": "in_progress",
  "status_label": "In Progress",
  "can_cancel": false,
  "reason": "Ride is already in progress. Driver has picked up the passenger."
}
```

**404 Not Found:**
```json
{
  "message": "No query results for model [App\\Models\\Ride] {ride_id}"
}
```

## 💻 Frontend Implementation Examples

### React/Next.js Example

```jsx
import { useState } from 'react';
import axios from 'axios';

function CancelRideButton({ rideId, onCancelSuccess }) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [showReasonModal, setShowReasonModal] = useState(false);
  const [reason, setReason] = useState('');

  const handleCancel = async () => {
    // Optional: Show reason modal first
    setShowReasonModal(true);
  };

  const confirmCancel = async () => {
    setLoading(true);
    setError(null);

    try {
      const token = localStorage.getItem('token'); // or from your auth context
      
      const response = await axios.post(
        `${process.env.NEXT_PUBLIC_API_URL}/api/passenger/rides/${rideId}/cancel`,
        {
          reason: reason || undefined, // Only send if provided
        },
        {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
        }
      );

      // Success
      if (response.status === 200) {
        // Show success message
        alert('Ride cancelled successfully');
        
        // Update UI - refresh ride data
        if (onCancelSuccess) {
          onCancelSuccess(response.data.data.ride);
        }
        
        // Close modal
        setShowReasonModal(false);
        setReason('');
        
        // Optionally redirect or refresh
        window.location.reload(); // or navigate to dashboard
      }
    } catch (err) {
      setLoading(false);
      
      if (err.response) {
        const { status, data } = err.response;
        
        if (status === 403) {
          setError('You can only cancel your own rides.');
        } else if (status === 422) {
          setError(data.reason || data.message);
        } else if (status === 404) {
          setError('Ride not found.');
        } else {
          setError(data.message || 'Failed to cancel ride. Please try again.');
        }
      } else {
        setError('Network error. Please check your connection.');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <button
        onClick={handleCancel}
        disabled={loading}
        className="btn btn-danger"
      >
        {loading ? 'Cancelling...' : 'Cancel Ride'}
      </button>

      {error && (
        <div className="alert alert-danger mt-2">
          {error}
        </div>
      )}

      {/* Optional: Reason Modal */}
      {showReasonModal && (
        <div className="modal show">
          <div className="modal-dialog">
            <div className="modal-content">
              <div className="modal-header">
                <h5>Cancel Ride</h5>
                <button onClick={() => setShowReasonModal(false)}>×</button>
              </div>
              <div className="modal-body">
                <p>Are you sure you want to cancel this ride?</p>
                <label>
                  Reason (optional):
                  <textarea
                    value={reason}
                    onChange={(e) => setReason(e.target.value)}
                    maxLength={500}
                    rows={3}
                    className="form-control mt-2"
                    placeholder="Why are you cancelling?"
                  />
                  <small className="text-muted">
                    {reason.length}/500 characters
                  </small>
                </label>
              </div>
              <div className="modal-footer">
                <button
                  onClick={() => setShowReasonModal(false)}
                  className="btn btn-secondary"
                >
                  Keep Ride
                </button>
                <button
                  onClick={confirmCancel}
                  disabled={loading}
                  className="btn btn-danger"
                >
                  {loading ? 'Cancelling...' : 'Confirm Cancel'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
```

### Vue.js Example

```vue
<template>
  <div>
    <button
      @click="showCancelModal = true"
      :disabled="loading || !canCancel"
      class="btn btn-danger"
    >
      Cancel Ride
    </button>

    <!-- Cancel Modal -->
    <div v-if="showCancelModal" class="modal-overlay" @click.self="showCancelModal = false">
      <div class="modal">
        <h3>Cancel Ride</h3>
        <p>Are you sure you want to cancel this ride?</p>
        
        <label>
          Reason (optional):
          <textarea
            v-model="reason"
            maxlength="500"
            rows="3"
            placeholder="Why are you cancelling?"
          />
          <small>{{ reason.length }}/500 characters</small>
        </label>

        <div v-if="error" class="alert alert-danger">
          {{ error }}
        </div>

        <div class="modal-actions">
          <button @click="showCancelModal = false" class="btn btn-secondary">
            Keep Ride
          </button>
          <button
            @click="confirmCancel"
            :disabled="loading"
            class="btn btn-danger"
          >
            {{ loading ? 'Cancelling...' : 'Confirm Cancel' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: {
    rideId: {
      type: Number,
      required: true,
    },
    canCancel: {
      type: Boolean,
      default: true,
    },
  },
  data() {
    return {
      loading: false,
      error: null,
      showCancelModal: false,
      reason: '',
    };
  },
  methods: {
    async confirmCancel() {
      this.loading = true;
      this.error = null;

      try {
        const token = localStorage.getItem('token');
        
        const response = await axios.post(
          `${process.env.VUE_APP_API_URL}/api/passenger/rides/${this.rideId}/cancel`,
          {
            reason: this.reason || undefined,
          },
          {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Content-Type': 'application/json',
            },
          }
        );

        if (response.status === 200) {
          // Success
          this.$toast.success('Ride cancelled successfully');
          
          // Emit event to parent
          this.$emit('ride-cancelled', response.data.data.ride);
          
          // Close modal
          this.showCancelModal = false;
          this.reason = '';
          
          // Refresh or redirect
          this.$router.push('/passenger/dashboard');
        }
      } catch (err) {
        if (err.response) {
          const { status, data } = err.response;
          
          if (status === 403) {
            this.error = 'You can only cancel your own rides.';
          } else if (status === 422) {
            this.error = data.reason || data.message;
          } else if (status === 404) {
            this.error = 'Ride not found.';
          } else {
            this.error = data.message || 'Failed to cancel ride.';
          }
        } else {
          this.error = 'Network error. Please check your connection.';
        }
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
```

### Vanilla JavaScript Example

```javascript
async function cancelRide(rideId, reason = null) {
  const token = localStorage.getItem('token');
  const apiUrl = 'http://localhost:8000/api'; // Your API URL
  
  try {
    const response = await fetch(`${apiUrl}/passenger/rides/${rideId}/cancel`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        reason: reason || undefined,
      }),
    });

    const data = await response.json();

    if (response.ok) {
      // Success
      alert('Ride cancelled successfully');
      
      // Update UI
      updateRideStatus(data.data.ride);
      
      // Redirect or refresh
      window.location.href = '/passenger/dashboard';
    } else {
      // Handle errors
      if (response.status === 403) {
        alert('You can only cancel your own rides.');
      } else if (response.status === 422) {
        alert(data.reason || data.message);
      } else if (response.status === 404) {
        alert('Ride not found.');
      } else {
        alert(data.message || 'Failed to cancel ride.');
      }
    }
  } catch (error) {
    console.error('Error cancelling ride:', error);
    alert('Network error. Please check your connection.');
  }
}

// Usage
document.getElementById('cancelBtn').addEventListener('click', () => {
  const rideId = getCurrentRideId(); // Your function to get ride ID
  const reason = prompt('Reason for cancellation (optional):');
  
  if (confirm('Are you sure you want to cancel this ride?')) {
    cancelRide(rideId, reason);
  }
});
```

## 🎨 UI/UX Best Practices

### 1. Show Cancellation Availability

Use the `can_cancel` flag from the current ride endpoint:

```javascript
// GET /api/passenger/rides/current
const currentRide = await fetchCurrentRide();

// Show/hide cancel button based on can_cancel
if (currentRide.can_cancel) {
  showCancelButton();
} else {
  hideCancelButton();
  showMessage(`Cannot cancel: ${currentRide.status_label}`);
}
```

### 2. Disable Button for Non-Cancellable Rides

```jsx
<button
  disabled={!canCancel || ride.status === 'in_progress'}
  className={canCancel ? 'btn-danger' : 'btn-secondary'}
>
  {ride.status === 'in_progress' 
    ? 'Ride in Progress' 
    : 'Cancel Ride'}
</button>
```

### 3. Show Status Information

Display the ride status clearly:

```jsx
<div className="ride-status">
  <span className={`badge badge-${getStatusColor(ride.status)}`}>
    {ride.status_label || ride.status}
  </span>
  {ride.can_cancel && (
    <button onClick={handleCancel}>Cancel</button>
  )}
</div>
```

### 4. Confirmation Modal

Always ask for confirmation before cancelling:

```jsx
const handleCancel = () => {
  if (confirm('Are you sure you want to cancel this ride?')) {
    proceedWithCancellation();
  }
};
```

### 5. Loading States

Show loading indicator during cancellation:

```jsx
{loading ? (
  <span>Cancelling...</span>
) : (
  <button onClick={handleCancel}>Cancel Ride</button>
)}
```

### 6. Success Feedback

Show success message and update UI:

```jsx
if (response.status === 200) {
  toast.success('Ride cancelled successfully');
  refreshRideData(); // Reload current ride
  navigate('/passenger/dashboard');
}
```

### 7. Error Handling

Display specific error messages:

```jsx
if (error.response?.status === 422) {
  const { reason, status_label } = error.response.data;
  toast.error(`${status_label}: ${reason}`);
} else {
  toast.error(error.response?.data?.message || 'Failed to cancel ride');
}
```

## 🔄 Complete Integration Flow

### Step 1: Get Current Ride with Cancellation Info

```javascript
// Fetch current ride
const response = await fetch('/api/passenger/rides/current', {
  headers: {
    'Authorization': `Bearer ${token}`,
  },
});

const { data: ride, can_cancel, status, status_label } = await response.json();
```

### Step 2: Display Cancel Button Conditionally

```jsx
{ride && (
  <div>
    <p>Status: {status_label}</p>
    {can_cancel && (
      <button onClick={handleCancelClick}>
        Cancel Ride
      </button>
    )}
    {!can_cancel && (
      <p className="text-muted">
        Cannot cancel: {status_label}
      </p>
    )}
  </div>
)}
```

### Step 3: Handle Cancellation

```javascript
const handleCancel = async () => {
  // Show confirmation
  if (!confirm('Are you sure you want to cancel this ride?')) {
    return;
  }

  // Optional: Get reason
  const reason = prompt('Reason (optional):');

  try {
    const response = await fetch(
      `/api/passenger/rides/${ride.id}/cancel`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ reason }),
      }
    );

    const result = await response.json();

    if (response.ok) {
      // Success
      showSuccessMessage('Ride cancelled successfully');
      refreshRideData(); // Reload to show updated status
    } else {
      // Error
      showErrorMessage(result.message || 'Failed to cancel ride');
    }
  } catch (error) {
    showErrorMessage('Network error. Please try again.');
  }
};
```

### Step 4: Update UI After Cancellation

```javascript
// After successful cancellation
const refreshRideData = async () => {
  // Reload current ride
  const rideResponse = await fetch('/api/passenger/rides/current');
  const { data: updatedRide } = await rideResponse.json();
  
  // Update state
  setRide(updatedRide);
  
  // If no active ride, redirect to dashboard
  if (!updatedRide) {
    navigate('/passenger/dashboard');
  }
};
```

## ✅ Checklist

- [ ] Cancel button calls `POST /api/passenger/rides/{rideId}/cancel`
- [ ] Authorization header is included (`Bearer {token}`)
- [ ] Content-Type header is set to `application/json`
- [ ] Ride ID is passed in URL (not request body)
- [ ] Optional `reason` field is sent in request body
- [ ] Success response (200) is handled
- [ ] Error responses (403, 422, 404) are handled
- [ ] Loading state is shown during request
- [ ] Confirmation dialog is shown before cancelling
- [ ] UI is updated after successful cancellation
- [ ] Current ride data is refreshed after cancellation
- [ ] User is redirected/notified after cancellation
- [ ] Cancel button is hidden/disabled when `can_cancel` is false
- [ ] Status information is displayed to user

## 🎯 Key Points

1. **Always check `can_cancel`** from the current ride endpoint before showing the cancel button
2. **Use the ride ID** from the current ride data, not from URL params
3. **Handle all error cases** - 403, 422, 404, and network errors
4. **Update UI immediately** after successful cancellation
5. **Refresh ride data** to reflect the new status
6. **Show clear feedback** to the user (success/error messages)

## 🐛 Common Issues

### Issue: Button not showing
**Solution:** Check if `can_cancel` is `true` from `/api/passenger/rides/current`

### Issue: 403 Forbidden
**Solution:** Ensure the ride belongs to the logged-in user

### Issue: 422 Unprocessable Entity
**Solution:** Check ride status - can only cancel `requested`, `assigned`, or `accepted` rides

### Issue: Network error
**Solution:** Check API URL, token validity, and CORS settings

---

**Your backend is ready!** Just implement the frontend integration using the examples above.

