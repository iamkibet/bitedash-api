# BiteDash API – Expo / React Native Integration Prompt

Use this prompt to integrate an existing Expo (React Native) mobile app with the BiteDash Laravel API. The app should support three roles: **customer**, **restaurant**, and **rider**.

---

## 1. API base and auth

- **Base URL**: `http://bitedash-api.test/api/v1` (local development; API not deployed yet). For production, switch to your deployed API and use a `.env` or config such as `EXPO_PUBLIC_API_URL` so the app can point to the right host.  
  **Note**: From a physical device or Android emulator, `bitedash-api.test` may not resolve (it’s a Valet/host domain). Use your machine’s LAN IP (e.g. `http://192.168.1.x/api/v1`) or Android emulator `http://10.0.2.2` (for host localhost) and ensure the API is reachable; iOS Simulator can often use `localhost` or the same LAN IP.
- **Version**: All routes live under `/api/v1`. Do not use `/restaurants` or `/restaurants/...`; use `/stores` and `/stores/...` instead.
- **Auth**: Laravel Sanctum. After login or register, the API returns a **Bearer token**. Send it on every protected request:
  - Header: `Authorization: Bearer <token>`.
- **Content type**: Send JSON: `Content-Type: application/json` and `Accept: application/json`.

---

## 2. Authentication endpoints

### Register  
**POST** `/register`  
**Auth**: None  

**Body**:
```json
{
  "name": "string (required, max 255)",
  "email": "string (required, unique)",
  "phone": "string (required, Kenyan: +254XXXXXXXXX, 9 digits after +254)",
  "password": "string (required, min 8, mixed case, numbers, symbols)",
  "password_confirmation": "string (required, must match password)",
  "role": "customer | restaurant | rider"
}
```

**Success (201)**: `{ "message", "user": { "id", "name", "email", "phone", "role", "created_at", "updated_at" }, "token" }`  
**Errors**: 422 with `errors` object for validation (e.g. email/phone taken, weak password, invalid phone).

---

### Login  
**POST** `/login`  
**Auth**: None  
**Rate limit**: 5 attempts per minute (returns 429 if exceeded).

**Body**:
```json
{
  "email": "string (required)",
  "password": "string (required)"
}
```

**Success (200)**: `{ "message", "user": { same as register }, "token" }`  
**Invalid credentials (422)**: `{ "message": "Invalid credentials.", "errors": { "email": [...] } }`

---

### Logout  
**POST** `/logout`  
**Auth**: Required  

**Success (200)**: `{ "message": "Logged out successfully." }`

---

### Get current user  
**GET** `/me` or **GET** `/user`  
**Auth**: Required  

**Success (200)**: `{ "user": { "id", "name", "email", "phone", "role", "created_at", "updated_at" } }`  
Use this on app start to restore session (with stored token) and get `user.role` for role-based screens.

---

## 3. Public endpoints (no auth)

- **GET** `/stores` – List all stores (restaurants). Optional query: `is_open` (boolean).
- **GET** `/stores/{id}` – Single store details.
- **GET** `/stores/{id}/menu-items` – Menu items for a store. Optional: `is_available` (boolean). Paginated.
- **GET** `/menu-items/{id}` – Single menu item. If the user is logged in, response includes their rating in `ratings.user_rating`.
- **GET** `/menu-items/{id}/ratings` – List ratings for a menu item (paginated). Meta includes `average_rating`, `total_ratings`.

All list responses follow: `{ "data": [...], "meta": { "current_page", "last_page", "per_page", "total" } }`.

---

## 4. Customer-only endpoints (auth + role: customer)

### Orders
- **GET** `/orders` – Current user’s orders (paginated). Policy: customer sees only their orders.
- **POST** `/orders` – Create order.

  **Body**:
  ```json
  {
    "restaurant_id": "integer (required)",
    "items": [
      { "menu_item_id": "integer (required)", "quantity": "integer (required, 1-50)" }
    ],
    "delivery_address": "string (optional, max 500)",
    "notes": "string (optional, max 1000)"
  }
  ```
  **Success (201)**: `{ "message", "data": Order }`  
  **Validation (422)**: `errors` for invalid restaurant_id, menu_item_id, quantity, etc.

- **GET** `/orders/{id}` – Order details (customer can only view own).
- **POST** `/orders/{id}/cancel` – Cancel order (allowed when status allows it).

### Payments
- **POST** `/orders/{orderId}/payments/initiate` – Start M-Pesa payment.

  **Body**:
  ```json
  { "phone_number": "string (required, Kenyan: +254XXXXXXXXX or 0XXXXXXXXX)" }
  ```
  **Success**: Check API response for reference/status.  
  **422**: Already paid, cancelled order, or invalid phone.

- **GET** `/payments/{reference}/verify` – Verify payment status (auth required).

### Favourites
- **GET** `/favourites` – List current user’s favourites (paginated). Each item includes `menu_item` (with nested restaurant).
- **POST** `/favourites` – Add favourite. **Body**: `{ "menu_item_id": integer }`. Returns 200 if already favourited, 201 if created.
- **DELETE** `/favourites/{menuItemId}` – Remove favourite (menu item id in URL).

### Ratings
- **POST** `/ratings` – Create rating. **Rule**: User must have **ordered and paid** for that menu item; one rating per user per menu item.

  **Body**:
  ```json
  {
    "menu_item_id": "integer (required)",
    "rating": "integer (required, 1-5)",
    "comment": "string (optional, max 1000)"
  }
  ```
  **201**: Created. **422**: Already rated (`message` + `data`) or not allowed to rate (e.g. not ordered/paid; see `errors.menu_item_id`).

- **PUT** `/ratings/{ratingId}` – Update own rating. **Body**: `{ "rating": 1-5, "comment": "string" }`. 403 if not owner.
- **DELETE** `/ratings/{ratingId}` – Delete own rating. 403 if not owner.

---

## 5. Restaurant-only endpoints (auth + role: restaurant)

- **GET** `/stores/my-store` – Get authenticated restaurant owner’s store (404 if none).
- **POST** `/stores` – Create store. Body: name, description, location, latitude, longitude, etc. (match API validation).
- **PUT** / **PATCH** `/stores/{id}` – Update store (only own store).
- **DELETE** `/stores/{id}` – Delete store (only own).
- **POST** `/stores/{id}/toggle-status` – Open/close store.
- **GET** `/menu-items/my-restaurant` – Menu items for own restaurant (paginated).
- **POST** `/menu-items` – Create menu item (restaurant_id, name, price, description, image, is_available as per API).
- **PUT** / **PATCH** `/menu-items/{id}` – Update menu item (only own restaurant’s).
- **DELETE** `/menu-items/{id}` – Delete menu item (only own).
- **POST** `/menu-items/{id}/toggle-availability` – Toggle availability.
- **GET** `/stores/{id}/orders` – Orders for that store.
- **GET** `/stores/{id}/orders/pending` – Pending orders for that store.
- **GET** `/orders/{id}` – View order (restaurant sees orders for their store).
- **PUT** / **PATCH** `/orders/{id}` – Update order (e.g. status, rider_id). Allowed status transitions: pending → preparing | cancelled; preparing → on_the_way | cancelled; on_the_way → delivered | cancelled.

---

## 6. Rider-only endpoints (auth + role: rider)

- **GET** `/orders/available` – Orders available for pickup (e.g. paid, preparing).
- **GET** `/orders/my-rider` – Orders assigned to current rider.
- **GET** `/orders/{id}` – Order details (rider sees only orders assigned to them).
- **PUT** / **PATCH** `/orders/{id}` – Update order (e.g. status).
- **POST** `/orders/{id}/accept` – Accept order (assigns rider).

---

## 7. Other protected endpoints (auth, role in controller)

- **GET** `/users` – List users. Allowed: **restaurant** and **admin** only (403 for customer/rider). Optional query: `role` (customer, restaurant, rider, admin).
- **GET** `/riders` – List riders. Allowed: **restaurant** and **admin** only (403 for customer/rider).

---

## 8. Response shapes (summary)

- **User**: `id`, `name`, `email`, `phone`, `role`, `created_at`, `updated_at`.
- **Store (Restaurant)**: `id`, `name`, `description`, `image_url`, `location`, `latitude`, `longitude`, `is_open`, `owner` (when loaded), `menu_items` (when loaded).
- **MenuItem**: `id`, `restaurant_id`, `name`, `description`, `price`, `image_url`, `is_available`, `restaurant` (when loaded), `ratings`: `{ average, count, user_rating?: { id, rating, comment, created_at, updated_at } }`, `created_at`, `updated_at`.
- **Order**: `id`, `customer`, `restaurant`, `rider`, `total_amount` / `total`, `status`, `payment_status`, `delivery_address`, `notes`, `order_items`, `payments`, `created_at`, `updated_at`.
- **Order status**: `pending` | `preparing` | `on_the_way` | `delivered` | `cancelled`.
- **Payment status**: `unpaid` | `paid` | `failed`.

---

## 9. Tips to make the Expo app work with this API

1. **Base URL**  
   For now use `http://bitedash-api.test/api/v1` (local). Later, use a config (e.g. `EXPO_PUBLIC_API_URL`) so you can switch to the deployed API without code changes. Prepend the base to every path (e.g. `${API_URL}/stores`); no trailing slash on base.

2. **Token storage**  
   Store the token securely (e.g. `expo-secure-store`). On launch, read token → if present, call `GET /me` to validate and get `user` (including `role`). If 401, clear token and show login.

3. **Axios / fetch instance**  
   Create a shared client that:
   - Sets `Authorization: Bearer <token>` when token exists.
   - Sets `Content-Type: application/json` and `Accept: application/json`.
   - On 401: clear token, redirect to login (or trigger global “session expired”).
   - On 403: show “You don’t have permission” (role-based).
   - On 422: show validation errors from `response.data.errors` (field → array of messages).

4. **Role-based UI**  
   After `/me`, branch by `user.role`:
   - **customer**: tabs for browse stores, menu, cart, orders, favourites, profile.
   - **restaurant**: my store, menu management, orders, toggle open/closed.
   - **rider**: available orders, my orders, accept/update delivery.

5. **Pagination**  
   All list endpoints return `meta.current_page`, `meta.last_page`, `meta.per_page`, `meta.total`. Use `?page=1`, `?page=2` for infinite scroll or “Load more”; stop when `current_page >= last_page`.

6. **Images**  
   Menu item and store `image_url` can be full URL or path. If it’s relative (e.g. `/storage/...`), prepend base URL: `baseUrl + image_url`. Handle null with a placeholder.

7. **Orders**  
   - Only customers create orders and initiate payments.  
   - Restaurant updates status (and optionally assigns rider); rider accepts and updates status.  
   - Use `payment_status === 'paid'` before allowing rider-related actions and before allowing rating.

8. **Ratings**  
   - Show `ratings.average` and `ratings.count` on menu item screen.  
   - If logged in as customer, show `ratings.user_rating` (pre-filled form to edit/delete).  
   - Allow “Rate” only if the customer has ordered and paid for that item; handle 422 “You can only rate menu items that you have ordered and paid for” and “You have already rated this menu item”.

9. **Favourites**  
   - Customer only. Add/remove by menu item id; handle 200 “already in favourites” so UI stays in sync (e.g. show heart filled).

10. **Errors**  
    - 401: Unauthenticated → re-login.  
    - 403: Forbidden → wrong role or resource ownership; show friendly message.  
    - 404: Not found; show “Store/order/item not found”.  
    - 422: Validation; display `errors` per field.  
    - 429: Rate limit (login); show “Too many attempts. Try again later.”

11. **CORS / network**  
    API must allow your app’s origin (Expo Go / dev client origin). For native builds, ensure `Authorization` and `Content-Type` are allowed in CORS if the API uses browser CORS.

12. **Deprecated routes**  
    Do not use `/restaurants` or `/restaurants/...`; use `/stores` and `/stores/...` for all store and store-scoped endpoints.

---

## 10. Checklist for integration

- [ ] Config: base URL set to `http://bitedash-api.test/api/v1` for now; use `EXPO_PUBLIC_API_URL` when you deploy so it can be overridden.
- [ ] Auth: Register, Login, Logout, GET /me; token stored and sent as Bearer.
- [ ] Public: List stores, store detail, store menu items, menu item detail, menu item ratings.
- [ ] Customer: Orders (list, create, detail, cancel), payment initiate/verify, favourites (list, add, remove), ratings (list on item, create, update, delete with “ordered & paid” rule).
- [ ] Restaurant: My store, CRUD store/menu, orders/pending, update order status (and rider).
- [ ] Rider: Available orders, my orders, accept order, update order status.
- [ ] Role-based navigation and 403 handling.
- [ ] Pagination on all list screens.
- [ ] Error handling: 401 → logout, 422 → show field errors, 403/404/429 user-friendly messages.
- [ ] Images: base URL + path, placeholder when null.

Using this prompt, implement or refactor the Expo app so it uses every endpoint above correctly and follows the tips so the app works reliably with the BiteDash API.
