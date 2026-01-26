# BiteDash Frontend Development Prompt (React + TypeScript)

## Project Overview
Build a modern, responsive React frontend application for BiteDash, a food delivery platform for the Kenyan market. The frontend will consume the BiteDash API (Laravel 12 + Sanctum) that has already been built and is ready for integration.

## API Base URL
```
Base URL: http://bitedash-api.test/api/v1
Authentication: Bearer Token (Sanctum)
```

## Core Technical Requirements

### Tech Stack
- **React 18+** with TypeScript
- **State Management**: Zustand or Redux Toolkit
- **Routing**: React Router v6
- **HTTP Client**: Axios with interceptors
- **UI Framework**: Tailwind CSS + Headless UI or shadcn/ui
- **Form Handling**: React Hook Form + Zod validation
- **Notifications**: React Hot Toast or Sonner
- **Date/Time**: date-fns or dayjs
- **Maps**: React Leaflet (for restaurant locations)
- **Icons**: Lucide React or Heroicons

### Project Structure
```
src/
├── api/
│   ├── client.ts (Axios instance with interceptors)
│   ├── auth.ts
│   ├── restaurants.ts
│   ├── menuItems.ts
│   ├── orders.ts
│   └── payments.ts
├── components/
│   ├── auth/
│   ├── restaurants/
│   ├── menu/
│   ├── orders/
│   ├── payments/
│   ├── layout/
│   └── common/
├── pages/
│   ├── Auth/
│   ├── Customer/
│   ├── Restaurant/
│   ├── Rider/
│   └── Admin/
├── hooks/
│   ├── useAuth.ts
│   ├── useRestaurants.ts
│   ├── useOrders.ts
│   └── usePayments.ts
├── store/
│   ├── authStore.ts
│   ├── cartStore.ts
│   └── orderStore.ts
├── types/
│   ├── auth.types.ts
│   ├── restaurant.types.ts
│   ├── order.types.ts
│   └── payment.types.ts
├── utils/
│   ├── formatters.ts
│   ├── validators.ts
│   └── constants.ts
└── App.tsx
```

## Authentication System

### User Roles
1. **Customer**: Browse restaurants, place orders, make payments
2. **Restaurant**: Manage restaurant, menu items, view orders
3. **Rider**: View available orders, accept and deliver orders
4. **Admin**: Full system access

### Authentication Flow

#### Registration
- **Endpoint**: `POST /api/v1/register`
- **Fields**: name, email, phone (+254XXXXXXXXX), password, password_confirmation, role (customer/restaurant/rider)
- **Validation**: 
  - Phone: Kenyan format (+254XXXXXXXXX)
  - Password: Min 8 chars, mixed case, numbers, symbols
  - Role: Cannot register as admin

#### Login
- **Endpoint**: `POST /api/v1/login`
- **Fields**: email, password
- **Rate Limited**: 5 attempts per minute
- **Response**: Returns user object + token

#### Token Management
- Store token in localStorage or httpOnly cookie
- Auto-refresh token before expiry
- Clear token on logout
- Redirect to login on 401 errors

### Protected Routes
- Implement route guards based on user roles
- Redirect unauthorized users appropriately
- Show role-specific navigation

## Customer Features

### 1. Restaurant Browsing
- **Endpoint**: `GET /api/v1/restaurants?is_open=true`
- **Features**:
  - List all restaurants with pagination
  - Filter by open/closed status
  - Search restaurants by name
  - Display restaurant details: name, description, location, is_open
  - Show restaurant on map (if coordinates available)
  - Click restaurant to view menu

### 2. Menu Viewing
- **Endpoint**: `GET /api/v1/restaurants/{restaurant_id}/menu-items?is_available=true`
- **Features**:
  - Display menu items with images, names, descriptions, prices
  - Filter by availability
  - Show "Out of Stock" for unavailable items
  - Add items to cart with quantity selector
  - Display prices in KES (Kenyan Shillings)

### 3. Shopping Cart
- **Features**:
  - Add/remove items
  - Update quantities (max 50 per item)
  - Calculate subtotal per item
  - Calculate total (client-side for display, server validates)
  - Persist cart in localStorage
  - Clear cart after successful order

### 4. Order Placement
- **Endpoint**: `POST /api/v1/orders`
- **Payload**:
  ```json
  {
    "restaurant_id": 1,
    "items": [
      {"menu_item_id": 1, "quantity": 2},
      {"menu_item_id": 3, "quantity": 1}
    ],
    "delivery_address": "Westlands, Nairobi",
    "notes": "Optional delivery notes"
  }
  ```
- **Features**:
  - Validate all items are available
  - Show order summary before submission
  - Display estimated total (server calculates actual)
  - Handle validation errors gracefully
  - Redirect to payment after order creation

### 5. Payment Integration (M-Pesa via Paystack)
- **Endpoint**: `POST /api/v1/orders/{order_id}/payments/initiate`
- **Payload**: `{"phone_number": "+254712345678"}`
- **Features**:
  - Phone number input with Kenyan format validation
  - Initiate payment request
  - Show payment instructions ("Check your phone for payment prompt")
  - Poll payment status or use webhooks
  - Display payment status: pending, success, failed
  - Retry payment on failure
  - Verify payment: `GET /api/v1/payments/{reference}/verify`

### 6. Order Tracking
- **Endpoints**: 
  - `GET /api/v1/orders` (user's orders)
  - `GET /api/v1/orders/{order_id}`
- **Features**:
  - View order history with status badges
  - Real-time order status updates:
    - 🟡 Pending (waiting for payment)
    - 🟢 Preparing (restaurant preparing)
    - 🔵 On the Way (rider delivering)
    - ✅ Delivered
    - ❌ Cancelled
  - Order details: items, quantities, prices, total, delivery address
  - Cancel order (only if pending)
  - Estimated delivery time display

## Restaurant Owner Features

### 1. Restaurant Management
- **Endpoints**:
  - `GET /api/v1/restaurants/{id}` - View restaurant
  - `POST /api/v1/restaurants` - Create restaurant
  - `PUT /api/v1/restaurants/{id}` - Update restaurant
  - `POST /api/v1/restaurants/{id}/toggle-status` - Open/Close restaurant
- **Features**:
  - Create restaurant profile (name, description, location, coordinates)
  - Edit restaurant details
  - Toggle open/closed status with visual indicator
  - Upload restaurant image
  - Set location on map

### 2. Menu Management
- **Endpoints**:
  - `GET /api/v1/restaurants/{id}/menu-items` - List menu items
  - `POST /api/v1/menu-items` - Create menu item
  - `PUT /api/v1/menu-items/{id}` - Update menu item
  - `DELETE /api/v1/menu-items/{id}` - Delete menu item
  - `POST /api/v1/menu-items/{id}/toggle-availability` - Toggle availability
- **Features**:
  - CRUD operations for menu items
  - Upload item images
  - Set prices (KES)
  - Toggle item availability (available/unavailable)
  - Bulk operations (mark multiple items as unavailable)
  - Menu item categories (optional enhancement)

### 3. Order Management
- **Endpoints**:
  - `GET /api/v1/restaurants/{id}/orders/pending` - Pending orders
  - `GET /api/v1/orders/{id}` - Order details
  - `PUT /api/v1/orders/{id}` - Update order status
- **Features**:
  - View pending orders dashboard
  - Order status workflow:
    - Pending → Preparing (when payment confirmed)
    - Preparing → On the Way (when rider accepts)
  - Order details: customer info, items, delivery address
  - Accept/reject orders (reject = cancel)
  - Order notifications/alerts for new orders
  - Order history with filters

## Rider Features

### 1. Available Orders Pool
- **Endpoint**: `GET /api/v1/orders/available`
- **Features**:
  - View all orders in "preparing" status with no rider assigned
  - Display: restaurant name, delivery address, order total, distance (if calculated)
  - Filter by location/distance
  - Sort by distance or order value

### 2. Accept Orders
- **Endpoint**: `POST /api/v1/orders/{id}/accept`
- **Features**:
  - Accept order (assigns rider to order)
  - View accepted orders
  - Update order status:
    - On the Way (when picking up)
    - Delivered (when completed)
  - Cancel accepted order (if needed)

### 3. Delivery Management
- **Features**:
  - Active deliveries list
  - Delivery route optimization (optional)
  - Mark order as delivered
  - Delivery history
  - Earnings tracking (optional enhancement)

## Admin Features

### 1. Dashboard
- **Features**:
  - System statistics (orders, users, restaurants, revenue)
  - Recent activity feed
  - Quick actions

### 2. User Management
- **Features**:
  - View all users
  - Filter by role
  - User details
  - Deactivate users

### 3. Restaurant Management
- **Features**:
  - View all restaurants
  - Approve/disable restaurants
  - Restaurant analytics

### 4. Order Management
- **Features**:
  - View all orders
  - Filter by status, date, restaurant
  - Order details and history
  - Manual order status updates

## UI/UX Requirements

### Design Principles
- **Modern & Clean**: Use modern design patterns, ample whitespace
- **Mobile-First**: Responsive design, touch-friendly
- **Kenyan Context**: Use Kenyan phone formats, KES currency, local addresses
- **Accessibility**: WCAG 2.1 AA compliance, keyboard navigation
- **Performance**: Lazy loading, code splitting, image optimization

### Color Scheme
- Primary: Food delivery theme (warm colors - orange, red, or green)
- Success: Green for successful actions
- Warning: Yellow/Orange for pending states
- Error: Red for errors/failures
- Neutral: Gray scale for text and backgrounds

### Key UI Components

#### 1. Navigation
- Role-based navigation menu
- User profile dropdown
- Cart icon with item count
- Notifications bell (for restaurants/riders)

#### 2. Forms
- Consistent form styling
- Real-time validation
- Error messages below fields
- Loading states on submit
- Success feedback

#### 3. Cards
- Restaurant cards with image, name, status badge
- Menu item cards with image, name, price, add button
- Order cards with status, items summary, actions

#### 4. Modals/Dialogs
- Order confirmation
- Payment initiation
- Order details
- Status updates

#### 5. Status Badges
- Order status: Pending, Preparing, On the Way, Delivered, Cancelled
- Payment status: Unpaid, Paid, Failed
- Restaurant status: Open, Closed
- Menu item: Available, Unavailable

### Responsive Breakpoints
- Mobile: < 640px
- Tablet: 640px - 1024px
- Desktop: > 1024px

## State Management

### Auth Store
```typescript
{
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  role: 'customer' | 'restaurant' | 'rider' | 'admin' | null;
  login: (email, password) => Promise<void>;
  register: (data) => Promise<void>;
  logout: () => void;
  updateProfile: (data) => Promise<void>;
}
```

### Cart Store
```typescript
{
  items: CartItem[];
  restaurantId: number | null;
  addItem: (menuItem, quantity) => void;
  removeItem: (menuItemId) => void;
  updateQuantity: (menuItemId, quantity) => void;
  clearCart: () => void;
  getTotal: () => number;
}
```

### Order Store
```typescript
{
  orders: Order[];
  currentOrder: Order | null;
  fetchOrders: () => Promise<void>;
  createOrder: (data) => Promise<Order>;
  updateOrderStatus: (orderId, status) => Promise<void>;
  cancelOrder: (orderId) => Promise<void>;
}
```

## API Integration Patterns

### Axios Configuration
```typescript
// api/client.ts
- Base URL configuration
- Request interceptor: Add Authorization header
- Response interceptor: Handle 401 (logout), 422 (validation errors)
- Error handling: Transform API errors to user-friendly messages
```

### API Service Functions
- Type-safe API calls with TypeScript
- Proper error handling
- Loading states
- Retry logic for failed requests
- Request cancellation for cleanup

### Real-time Updates (Optional Enhancement)
- WebSocket connection for order status updates
- Or polling mechanism for order status
- Push notifications for new orders (restaurants/riders)

## Form Validation

### Registration Form
- Name: Required, min 2 chars
- Email: Required, valid email format
- Phone: Required, Kenyan format (+254XXXXXXXXX)
- Password: Min 8, mixed case, numbers, symbols
- Password Confirmation: Must match password
- Role: Required, one of: customer, restaurant, rider

### Login Form
- Email: Required, valid email
- Password: Required

### Order Form
- Restaurant: Required
- Items: At least one item, quantities > 0
- Delivery Address: Required, max 500 chars
- Notes: Optional, max 1000 chars

### Payment Form
- Phone Number: Required, Kenyan format

## Error Handling

### Error Types
1. **Network Errors**: Show "Connection failed, please try again"
2. **Validation Errors**: Display field-specific errors
3. **Authentication Errors**: Redirect to login
4. **Authorization Errors**: Show "You don't have permission"
5. **Server Errors**: Show generic error message, log details

### Error Display
- Toast notifications for errors
- Inline errors for form fields
- Error boundaries for React errors
- Retry mechanisms where appropriate

## Loading States

### Implement Loading Indicators For:
- API requests (spinner or skeleton)
- Form submissions (disable button, show spinner)
- Page navigation (page-level loader)
- Image loading (placeholder/skeleton)

## Testing Requirements

### Unit Tests
- Utility functions
- Form validators
- State management functions

### Integration Tests
- API service functions
- Authentication flow
- Order creation flow
- Payment flow

### E2E Tests (Optional)
- Complete user journeys
- Cross-browser testing

## Performance Optimization

1. **Code Splitting**: Route-based code splitting
2. **Lazy Loading**: Images, components
3. **Memoization**: React.memo, useMemo, useCallback
4. **Virtual Scrolling**: For long lists (orders, restaurants)
5. **Image Optimization**: WebP format, lazy loading, responsive images
6. **Bundle Analysis**: Monitor bundle size

## Security Considerations

1. **Token Storage**: Secure storage, httpOnly cookies preferred
2. **XSS Prevention**: Sanitize user inputs
3. **CSRF Protection**: Include CSRF tokens if needed
4. **Input Validation**: Client-side + server-side validation
5. **HTTPS**: Always use HTTPS in production
6. **Sensitive Data**: Never log tokens or sensitive info

## Kenyan Market Specifics

1. **Phone Numbers**: Always format as +254XXXXXXXXX
2. **Currency**: Display all prices in KES (Kenyan Shillings)
3. **Addresses**: Support Kenyan address formats
4. **Language**: English (consider Swahili as future enhancement)
5. **Payment**: M-Pesa integration via Paystack
6. **Locations**: Nairobi-focused initially, expandable

## Key User Flows

### Customer Flow
1. Register/Login → Browse Restaurants → View Menu → Add to Cart → Place Order → Pay via M-Pesa → Track Order → Receive Order

### Restaurant Flow
1. Register/Login → Create Restaurant → Add Menu Items → Receive Order Notification → Accept Order → Update Status (Preparing → Ready) → Order Delivered

### Rider Flow
1. Register/Login → View Available Orders → Accept Order → Pick Up → Update Status (On the Way) → Deliver → Mark as Delivered

## API Response Formats

### Success Response
```json
{
  "message": "Operation successful",
  "data": { ... },
  "meta": { ... } // For paginated responses
}
```

### Error Response
```json
{
  "message": "Error message",
  "errors": {
    "field": ["Error message"]
  }
}
```

### Paginated Response
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

## Environment Variables

```env
VITE_API_BASE_URL=http://bitedash-api.test/api/v1
VITE_APP_NAME=BiteDash
VITE_MAP_API_KEY=your_map_api_key (optional)
```

## Deliverables

1. **Complete React Application** with all features
2. **Responsive Design** for mobile, tablet, desktop
3. **TypeScript** with proper type definitions
4. **Documentation**:
   - README with setup instructions
   - Component documentation
   - API integration guide
5. **Error Handling** throughout the application
6. **Loading States** for all async operations
7. **Form Validation** with user-friendly messages
8. **Accessibility** features
9. **Performance Optimizations**

## Additional Enhancements (Nice to Have)

1. **Dark Mode**: Theme switcher
2. **Offline Support**: Service workers, cached data
3. **Push Notifications**: For order updates
4. **Order Tracking Map**: Real-time rider location
5. **Reviews & Ratings**: Customer reviews for restaurants
6. **Favorites**: Save favorite restaurants
7. **Order History Search**: Search past orders
8. **Multi-language**: English + Swahili
9. **Analytics**: User behavior tracking
10. **A/B Testing**: For UI improvements

## Testing the Integration

### Test Scenarios
1. Register as customer → Browse → Order → Pay → Track
2. Register as restaurant → Create restaurant → Add menu → Receive order
3. Register as rider → View available orders → Accept → Deliver
4. Test error scenarios (network failures, validation errors)
5. Test authentication (token expiry, logout)
6. Test role-based access (unauthorized routes)

## Notes

- All prices are in KES (Kenyan Shillings)
- Phone numbers must be in format: +254XXXXXXXXX
- Server calculates order totals (never trust client-side totals)
- Order status transitions are validated server-side
- Payment webhooks update order status automatically
- Use the exact API endpoints and request/response formats provided
- Handle all error cases gracefully
- Implement proper loading and error states
- Follow React best practices and modern patterns

---

**Start building the frontend with this comprehensive specification. Ensure all API endpoints are properly integrated, all user roles are supported, and the application is production-ready with proper error handling, loading states, and responsive design.**
