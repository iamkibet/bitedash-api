# BiteDash Frontend – Full Specification for Cursor

Use this document as the **single source of truth** to build the entire BiteDash React frontend. Implement features in the **Implementation order** section. Do **not** use `/restaurants` or `/restaurants/...` in any request; use **`/stores`** and **`/stores/...`** only. All API paths are relative to the base URL below.

---

## 1. Instructions for Cursor

- **Base URL for all API calls**: `http://bitedash-api.test/api/v1` (no trailing slash). Configure via `VITE_API_BASE_URL` in `.env`.
- **Authentication**: Laravel Sanctum. Send `Authorization: Bearer <token>` on every request that requires auth. Store token after login/register; clear on logout and on 401.
- **Headers**: Always send `Content-Type: application/json` and `Accept: application/json`.
- **Paths**: Use the paths in the "Complete API Endpoint Reference" table exactly (e.g. `GET /stores`, `GET /stores/{id}/menu-items`, not `/restaurants`).
- **Errors**: Handle 401 (redirect to login), 403 (show "You don't have permission"), 404 (not found), 422 (show `errors` object per field), 429 (rate limit message).
- **Pagination**: List endpoints return `{ data: T[], meta: { current_page, last_page, per_page, total } }`. Use query param `?page=1`, `?page=2` for next page; optional filters as specified per endpoint.
- **Types**: Define TypeScript types/interfaces for every API response and request body listed in this doc; use them in API client and components.

---

## 2. API Base URL and Environment

```
Base URL: http://bitedash-api.test/api/v1
Authentication: Bearer Token (Laravel Sanctum)
```

**.env (Vite)**
```env
VITE_API_BASE_URL=http://bitedash-api.test/api/v1
VITE_APP_NAME=BiteDash
```

Use `import.meta.env.VITE_API_BASE_URL` for the base URL. Do not hardcode the API URL in components.

---

## 3. Complete API Endpoint Reference

Every request is `BASE_URL + path`. Auth = send Bearer token. Role = API returns 403 if user role is not allowed.

| Method | Path | Auth | Role | Description |
|--------|------|------|------|-------------|
| POST | `/register` | No | - | Register. Body: name, email, phone, password, password_confirmation, role (customer\|restaurant\|rider). |
| POST | `/login` | No | - | Login. Body: email, password. Rate limit: 5/min. |
| POST | `/logout` | Yes | - | Logout. |
| GET | `/me` | Yes | - | Current user. Also GET `/user` (alias). |
| GET | `/stores` | No | - | List stores. Query: `is_open` (bool, optional). Paginated. |
| GET | `/stores/my-store` | Yes | restaurant | Restaurant owner's own store. 404 if none. |
| GET | `/stores/{id}` | No | - | Single store. |
| POST | `/stores` | Yes | restaurant | Create store. |
| PUT/PATCH | `/stores/{id}` | Yes | - | Update store (own only; policy). |
| DELETE | `/stores/{id}` | Yes | - | Delete store (own only). |
| POST | `/stores/{id}/toggle-status` | Yes | restaurant | Toggle open/closed. |
| GET | `/stores/{id}/menu-items` | No | - | Menu items for store. Query: `is_available` (bool, optional). Paginated. |
| GET | `/menu-items/my-restaurant` | Yes | restaurant | Menu items for own restaurant. Paginated. |
| GET | `/menu-items/{id}` | No | - | Single menu item. If auth customer: includes `ratings.user_rating`. |
| POST | `/menu-items` | Yes | restaurant | Create menu item. |
| PUT/PATCH | `/menu-items/{id}` | Yes | - | Update menu item (own restaurant only). |
| DELETE | `/menu-items/{id}` | Yes | - | Delete menu item (own restaurant only). |
| POST | `/menu-items/{id}/toggle-availability` | Yes | restaurant | Toggle availability. |
| GET | `/menu-items/{id}/ratings` | No | - | Ratings for menu item. Paginated. Meta: average_rating, total_ratings. |
| POST | `/ratings` | Yes | customer | Create rating (must have ordered & paid for item). Body: menu_item_id, rating (1-5), comment (optional). |
| PUT | `/ratings/{id}` | Yes | customer | Update own rating. Body: rating, comment. |
| DELETE | `/ratings/{id}` | Yes | customer | Delete own rating. |
| GET | `/orders` | Yes | - | User's orders (customer: own; restaurant: filtered by policy; rider: assigned). Paginated. |
| GET | `/orders/available` | Yes | rider | Orders available for riders. Paginated. |
| GET | `/orders/my-rider` | Yes | rider | Rider's assigned orders. Paginated. |
| GET | `/orders/my-restaurant` | Yes | restaurant | Restaurant's orders. Paginated. |
| POST | `/orders` | Yes | customer | Create order. Body: restaurant_id, items: [{ menu_item_id, quantity }], delivery_address?, notes?. |
| GET | `/orders/{id}` | Yes | - | Order detail (policy: customer own, restaurant own store, rider assigned). |
| PUT/PATCH | `/orders/{id}` | Yes | - | Update order (e.g. status, rider_id). Policy per role. |
| DELETE | `/orders/{id}` | Yes | - | Delete order (policy). |
| POST | `/orders/{id}/cancel` | Yes | - | Cancel order. |
| POST | `/orders/{id}/accept` | Yes | rider | Rider accept order. |
| POST | `/orders/{id}/payments/initiate` | Yes | customer | Initiate M-Pesa. Body: phone_number (Kenyan). |
| GET | `/payments/{reference}/verify` | Yes | - | Verify payment. |
| GET | `/stores/{id}/orders` | Yes | restaurant | Orders for store. Paginated. |
| GET | `/stores/{id}/orders/pending` | Yes | restaurant | Pending orders for store. Paginated. |
| GET | `/favourites` | Yes | customer | User's favourites. Paginated. |
| POST | `/favourites` | Yes | customer | Add favourite. Body: menu_item_id. |
| DELETE | `/favourites/{menuItemId}` | Yes | customer | Remove favourite (id = menu item id). |
| GET | `/users` | Yes | restaurant, admin | List users. Query: role (optional). Paginated. |
| GET | `/riders` | Yes | restaurant, admin | List riders. Paginated. |

---

## 4. Request and Response Shapes

### 4.1 Authentication

**POST /register**  
Request:
```json
{
  "name": "string (required, max 255)",
  "email": "string (required, unique)",
  "phone": "string (required, regex: +254XXXXXXXXX)",
  "password": "string (required, min 8, mixed case, numbers, symbols)",
  "password_confirmation": "string (required)",
  "role": "customer | restaurant | rider"
}
```
Success 201: `{ "message": string, "user": User, "token": string }`  
Error 422: `{ "message": string, "errors": Record<string, string[]> }`

**POST /login**  
Request: `{ "email": string, "password": string }`  
Success 200: `{ "message": string, "user": User, "token": string }`  
Error 422: `{ "message": "Invalid credentials.", "errors": { "email": string[] } }`  
Error 429: Too many attempts.

**GET /me**  
Success 200: `{ "user": User }`

### 4.2 User and Store (Restaurant)

**User**: `{ id: number, name: string, email: string, phone: string, role: 'customer' | 'restaurant' | 'rider' | 'admin', created_at: string (ISO), updated_at: string (ISO) }`

**Store (Restaurant)**: `{ id: number, name: string, description: string | null, image_url: string | null, location: string, latitude: number | null, longitude: number | null, is_open: boolean, owner?: User, menu_items?: MenuItem[], created_at: string, updated_at: string }`

### 4.3 Menu Item

**MenuItem**: `{ id: number, restaurant_id: number, name: string, description: string | null, price: number, image_url: string | null, is_available: boolean, restaurant?: Store, ratings: { average: number | null, count: number, user_rating: { id: number, rating: number, comment: string | null, created_at: string, updated_at: string } | null }, created_at: string, updated_at: string }`

- For list endpoints, `ratings` may be omitted or simplified; for GET `/menu-items/{id}` when logged in as customer, `ratings.user_rating` is present if they rated.

### 4.4 Order

**Order**: `{ id: number, customer?: User, restaurant?: Store, rider?: User, total_amount: number, total?: number, status: OrderStatus, payment_status: 'unpaid' | 'paid' | 'failed', delivery_address: string | null, notes: string | null, order_items?: OrderItem[], payments?: Payment[], created_at: string, updated_at: string }`

**OrderStatus**: `'pending' | 'preparing' | 'on_the_way' | 'delivered' | 'cancelled'`

**OrderItem**: `{ id: number, menu_item_id: number, quantity: number, unit_price: number, menu_item?: MenuItem }`

**POST /orders**  
Request: `{ restaurant_id: number, items: { menu_item_id: number, quantity: number }[], delivery_address?: string, notes?: string }`  
- items: at least one; quantity 1–50 per item.  
Success 201: `{ "message": string, "data": Order }`  
Error 422: validation errors in `errors`.

### 4.5 Payment

**POST /orders/{id}/payments/initiate**  
Request: `{ phone_number: string }` — Kenyan: +254XXXXXXXXX, 254XXXXXXXXX, or 0XXXXXXXXX.  
Success: API returns payment reference/status; handle per API response.  
Error 422: already paid, cancelled order, or invalid phone.

**GET /payments/{reference}/verify**  
Success: payment status in response body.

### 4.6 Favourites

**GET /favourites**  
Success 200: `{ data: Favourite[], meta: PaginationMeta }`

**Favourite**: `{ id: number, user_id: number, menu_item_id: number, menu_item: MenuItem & { restaurant?: Store }, created_at: string, updated_at: string }`

**POST /favourites**  
Request: `{ menu_item_id: number }`  
Success 201: `{ "message": string, "data": Favourite }`  
Success 200: already favourited — `{ "message": "Menu item is already in your favourites.", "data": Favourite }`

**DELETE /favourites/{menuItemId}**  
Success 200: `{ "message": string }`  
Error 404: not in favourites.

### 4.7 Ratings

**GET /menu-items/{id}/ratings**  
Success 200: `{ data: Rating[], meta: PaginationMeta & { average_rating: number, total_ratings: number } }`

**Rating**: `{ id: number, user_id: number, menu_item_id: number, rating: number (1-5), comment: string | null, user: { id: number, name: string }, created_at: string, updated_at: string }`

**POST /ratings**  
Request: `{ menu_item_id: number, rating: number (1-5), comment?: string (max 1000) }`  
- Allowed only if customer has ordered and paid for that menu item; one rating per user per menu item.  
Success 201: `{ "message": string, "data": Rating }`  
Error 422: already rated or not allowed (e.g. "You can only rate menu items that you have ordered and paid for." in errors).

**PUT /ratings/{id}**  
Request: `{ rating?: number (1-5), comment?: string }`  
Success 200: `{ "message": string, "data": Rating }`  
Error 403: not owner.

**DELETE /ratings/{id}**  
Success 200: `{ "message": string }`  
Error 403: not owner.

### 4.8 Pagination

**PaginationMeta**: `{ current_page: number, last_page: number, per_page: number, total: number }`

All list endpoints: `GET ?page=1` (default), `?page=2`, etc. Stop loading more when `current_page >= last_page`.

---

## 5. Error Handling (Implement Consistently)

| Status | Meaning | Action |
|--------|---------|--------|
| 401 | Unauthenticated | Clear token, redirect to login. |
| 403 | Forbidden (wrong role or resource) | Show message "You don't have permission." Do not redirect to login if already logged in. |
| 404 | Not found | Show "Resource not found" or page-specific message. |
| 422 | Validation error | Show `response.data.errors`: object of field names to array of messages. Display per-field below inputs. |
| 429 | Rate limit (e.g. login) | Show "Too many attempts. Try again later." |
| 500 | Server error | Show generic "Something went wrong. Please try again." |

Always use `Accept: application/json` so the API returns JSON errors.

---

## 6. Tech Stack and Project Structure

### Tech Stack
- **React 18+** with TypeScript
- **State**: Zustand or Redux Toolkit
- **Routing**: React Router v6
- **HTTP**: Axios (one instance, base URL + interceptors for token and error handling)
- **UI**: Tailwind CSS + Headless UI or shadcn/ui
- **Forms**: React Hook Form + Zod (align validation with API rules)
- **Toasts**: React Hot Toast or Sonner
- **Dates**: date-fns or dayjs
- **Maps**: React Leaflet (store locations)
- **Icons**: Lucide React or Heroicons

### Suggested Project Structure
```
src/
├── api/
│   ├── client.ts          # Axios instance: baseURL, Authorization header, 401/422 handling
│   ├── auth.ts
│   ├── stores.ts
│   ├── menuItems.ts
│   ├── orders.ts
│   ├── payments.ts
│   ├── favourites.ts
│   └── ratings.ts
├── components/
│   ├── auth/
│   ├── stores/
│   ├── menu/
│   ├── orders/
│   ├── payments/
│   ├── favourites/
│   ├── ratings/
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
│   ├── useStores.ts
│   ├── useMenuItems.ts
│   ├── useOrders.ts
│   ├── useFavourites.ts
│   └── useRatings.ts
├── store/
│   ├── authStore.ts
│   ├── cartStore.ts
│   └── orderStore.ts
├── types/
│   ├── api.types.ts       # User, Store, MenuItem, Order, OrderItem, Payment, Favourite, Rating
│   ├── pagination.types.ts
│   └── auth.types.ts
├── utils/
│   ├── formatters.ts      # KES, phone, date
│   ├── validators.ts      # phone +254, password rules
│   └── constants.ts       # order status labels, roles
└── App.tsx
```

---

## 7. User Roles and Route Guards

- **customer**: Browse stores/menu, cart, place order, pay, favourites, ratings, own orders.
- **restaurant**: My store, menu CRUD, store orders, update order status / assign rider.
- **rider**: Available orders, my orders, accept order, update status to on_the_way / delivered.
- **admin**: Full access (users, riders, all stores/orders).

Implement route guards: after loading user from GET `/me`, redirect to role-specific layout. If user hits a route not allowed for their role, show 403 message or redirect to their home.

---

## 8. Implementation Order (Build in This Order)

1. **Setup**
   - Create React + TypeScript + Vite project, install dependencies (Tailwind, React Router, Axios, state, forms, toasts).
   - Add `.env` with `VITE_API_BASE_URL=http://bitedash-api.test/api/v1`.
   - Define TypeScript types for User, Store, MenuItem, Order, OrderItem, Payment, Favourite, Rating, pagination, and API error shape.

2. **API client**
   - Create Axios instance: baseURL from env, request interceptor adds `Authorization: Bearer <token>` when token exists, response interceptor on 401 clears token and redirects to login; on 422 pass through `errors` for forms.
   - Implement API functions for auth (register, login, logout, me), stores (list, get, create, update, delete, toggle-status), menu items (list by store, get one, my-restaurant, create, update, delete, toggle-availability), orders (list, get, create, update, cancel, accept, payments initiate, payments verify, store orders, pending), favourites (list, add, remove), ratings (list by menu item, create, update, delete), users, riders. Use the endpoint table and request/response shapes above.

3. **Auth and layout**
   - Auth store: token + user (from login/register or GET /me on init). Persist token (e.g. localStorage); on app load if token exists call GET /me and set user (and role).
   - Login and Register pages with validation (phone +254, password rules, role). Handle 422 and 429.
   - Role-based layout: after auth, route to Customer / Restaurant / Rider / Admin shell with role-specific nav (e.g. customer: Stores, Cart, Orders, Favourites, Profile; restaurant: My Store, Menu, Orders; rider: Available, My Deliveries).

4. **Public and customer**
   - **Stores**: List (GET /stores) with pagination and optional is_open filter; store card (name, description, image, is_open). Detail page GET /stores/{id} and GET /stores/{id}/menu-items with pagination.
   - **Menu**: Menu item cards (image, name, price, is_available, ratings.average/count). Single item GET /menu-items/{id}; show ratings and, if customer and ratings.user_rating exists, show “Your rating” and edit/delete.
   - **Cart**: Client-side cart (store in Zustand + localStorage); add from menu with quantity (max 50); restrict cart to one restaurant at a time; show subtotal and total (server calculates final on order).
   - **Checkout**: Order summary, delivery_address, notes. POST /orders; on success redirect to payment step. Handle 422 with field errors.
   - **Payment**: Form with phone_number (Kenyan). POST /orders/{id}/payments/initiate; show “Check your phone” and poll or use GET /payments/{reference}/verify. Show success/failure; on success show order status.
   - **Orders (customer)**: List GET /orders, detail GET /orders/{id}. Status badges: pending, preparing, on_the_way, delivered, cancelled. Cancel: POST /orders/{id}/cancel when allowed.
   - **Favourites**: List GET /favourites; add POST /favourites (menu_item_id); remove DELETE /favourites/{menuItemId}. Show heart on menu item when in favourites; handle 200 “already in favourites”.
   - **Ratings**: On menu item detail, if customer and they have ordered & paid, allow create rating (POST /ratings) or show form; if ratings.user_rating exists show edit (PUT) and delete (DELETE). Display GET /menu-items/{id}/ratings list with average and total in meta. Handle 422 “already rated” and “only rate items you ordered and paid for”.

5. **Restaurant**
   - **My store**: GET /stores/my-store; if 404 show “Create restaurant” (POST /stores). Else edit (PUT /stores/{id}), toggle open/closed (POST /stores/{id}/toggle-status).
   - **Menu**: GET /menu-items/my-restaurant; CRUD (POST/PUT/DELETE /menu-items, toggle-availability). Upload image per API (multipart if API supports it).
   - **Orders**: GET /stores/{id}/orders and GET /stores/{id}/orders/pending; list with status. Detail GET /orders/{id}; update status (PUT /orders/{id}) and assign rider (rider_id) when applicable. Status flow: pending → preparing → on_the_way (when rider accepts) → delivered.

6. **Rider**
   - **Available**: GET /orders/available; show list; accept POST /orders/{id}/accept.
   - **My deliveries**: GET /orders/my-rider; detail GET /orders/{id}; update status to on_the_way then delivered via PUT /orders/{id}.

7. **Admin**
   - GET /users (optional ?role=), GET /riders; list with pagination. View stores and orders (reuse order list/detail with policy allowing admin). No separate “admin API”; admin uses same endpoints with broader policy.

8. **Polish**
   - Loading states for all API calls; skeleton or spinner.
   - Toasts for success/error (order placed, payment initiated, rating added, etc.).
   - Responsive layout (mobile-first); breakpoints as in original doc.
   - KES formatting; phone display +254; accessibility (labels, focus, contrast).

---

## 9. UI/UX Requirements (Summary)

- **Design**: Modern, clean, mobile-first. Kenyan context: KES, +254 phone, local addresses.
- **Navigation**: Role-based; profile dropdown; cart icon with count (customer); optional notifications for restaurant/rider.
- **Forms**: Consistent styling, real-time validation, field-level errors from 422, loading state on submit, success feedback.
- **Cards**: Store (image, name, status); menu item (image, name, price, add to cart, favourite heart, rating stars); order (status, items summary, actions).
- **Status badges**: Order: Pending, Preparing, On the Way, Delivered, Cancelled. Payment: Unpaid, Paid, Failed. Store: Open, Closed. Menu item: Available, Unavailable.
- **Responsive**: Mobile &lt; 640px, Tablet 640–1024px, Desktop &gt; 1024px.

---

## 10. Validation Rules (Match API)

- **Phone**: Kenyan +254XXXXXXXXX (9 digits after +254). Accept also 254XXXXXXXXX and 0XXXXXXXXX for input; normalize before send if needed.
- **Password**: Min 8, mixed case, numbers, symbols; confirmation must match.
- **Order**: restaurant_id required; items array min 1; each item: menu_item_id, quantity 1–50; delivery_address optional max 500; notes optional max 1000.
- **Rating**: rating 1–5; comment optional max 1000. Only allow submit if user has ordered and paid for that menu item (API enforces; show friendly message on 422).

---

## 11. Kenyan Market and Security

- Display all prices in KES (e.g. KES 1,500).
- Phone: +254 format in forms and display.
- Store token securely; never log token or sensitive data. Prefer not to store token in localStorage in production if you later move to httpOnly cookie.
- Use HTTPS in production; ensure CORS allows your frontend origin.

---

## 12. Deliverables Checklist

- [ ] All endpoints from the reference table implemented in API client with correct paths and types.
- [ ] Auth flow: register, login, logout, GET /me on load; token in headers; 401 → logout and redirect.
- [ ] Role-based routing and nav (customer, restaurant, rider, admin).
- [ ] Public: store list/detail, menu list/detail, ratings list; pagination everywhere.
- [ ] Customer: cart, place order, payment initiate/verify, order list/detail/cancel, favourites list/add/remove, ratings create/update/delete with API rules.
- [ ] Restaurant: my store CRUD, toggle status, menu CRUD, toggle availability, store orders and pending, order detail and status/rider update.
- [ ] Rider: available orders, accept, my orders, order detail, status updates to on_the_way and delivered.
- [ ] Admin: users and riders lists; access to stores and orders as per API policy.
- [ ] Error handling: 401, 403, 404, 422, 429, 500 with consistent UX.
- [ ] Loading and empty states; toasts; responsive layout; accessibility basics.

---

**Use this specification as the single source of truth. Implement in the order given. Use `/stores` (not `/restaurants`) for all store and store-scoped endpoints. Ensure every API call uses the base URL and types defined here.**
