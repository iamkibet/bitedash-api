# Favourites and Ratings API Endpoints

## Overview
This document describes the API endpoints for managing favourites and ratings in the BiteDash application.

### Key Features:
- **Favourites**: Customers can add menu items to their favourites (no order required)
- **Ratings**: Customers can rate menu items (only after ordering and paying for them)

---

## Favourites Endpoints

### 1. Get User's Favourites
**GET** `/api/v1/favourites`

**Authentication**: Required (Customer role)

**Description**: Retrieve all menu items favourited by the authenticated customer.

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 5,
      "menu_item_id": 10,
      "menu_item": {
        "id": 10,
        "restaurant_id": 2,
        "name": "Chicken Biryani",
        "description": "Spicy rice dish with chicken",
        "price": 850.00,
        "image_url": "https://example.com/image.jpg",
        "is_available": true,
        "restaurant": {
          "id": 2,
          "name": "Spice Garden",
          ...
        }
      },
      "created_at": "2026-01-28T10:30:00Z",
      "updated_at": "2026-01-28T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

### 2. Add Menu Item to Favourites
**POST** `/api/v1/favourites`

**Authentication**: Required (Customer role)

**Description**: Add a menu item to the customer's favourites list.

**Request Body**:
```json
{
  "menu_item_id": 10
}
```

**Validation Rules**:
- `menu_item_id`: Required, integer, must exist in menu_items table

**Success Response** (201):
```json
{
  "message": "Menu item added to favourites successfully.",
  "data": {
    "id": 1,
    "user_id": 5,
    "menu_item_id": 10,
    "menu_item": { ... },
    "created_at": "2026-01-28T10:30:00Z",
    "updated_at": "2026-01-28T10:30:00Z"
  }
}
```

**If Already Favourited** (200):
```json
{
  "message": "Menu item is already in your favourites.",
  "data": { ... }
}
```

---

### 3. Remove Menu Item from Favourites
**DELETE** `/api/v1/favourites/{menuItem}`

**Authentication**: Required (Customer role)

**Description**: Remove a menu item from the customer's favourites list.

**URL Parameters**:
- `menuItem`: The ID of the menu item (route model binding)

**Success Response** (200):
```json
{
  "message": "Menu item removed from favourites successfully."
}
```

**Not Found Response** (404):
```json
{
  "message": "Menu item is not in your favourites."
}
```

---

## Ratings Endpoints

### 1. Get Ratings for a Menu Item
**GET** `/api/v1/menu-items/{menuItem}/ratings`

**Authentication**: Not required (Public endpoint)

**Description**: Retrieve all ratings for a specific menu item. This endpoint is public and can be accessed by anyone.

**URL Parameters**:
- `menuItem`: The ID of the menu item (route model binding)

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 5,
      "menu_item_id": 10,
      "rating": 5,
      "comment": "Excellent! Very tasty and well-prepared.",
      "user": {
        "id": 5,
        "name": "John Doe"
      },
      "menu_item": { ... },
      "created_at": "2026-01-28T10:30:00Z",
      "updated_at": "2026-01-28T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1,
    "average_rating": 4.5,
    "total_ratings": 10
  }
}
```

---

### 2. Create a Rating
**POST** `/api/v1/ratings`

**Authentication**: Required (Customer role)

**Description**: Create a rating for a menu item. **Only customers who have ordered and paid for the menu item can rate it.**

**Request Body**:
```json
{
  "menu_item_id": 10,
  "rating": 5,
  "comment": "Excellent! Very tasty and well-prepared."
}
```

**Validation Rules**:
- `menu_item_id`: Required, integer, must exist in menu_items table
- `rating`: Required, integer, min: 1, max: 5
- `comment`: Optional, string, max: 1000 characters

**Business Rule Validation**:
- The customer must have ordered and paid for the menu item before rating
- If validation fails, returns error: "You can only rate menu items that you have ordered and paid for."

**Success Response** (201):
```json
{
  "message": "Rating created successfully.",
  "data": {
    "id": 1,
    "user_id": 5,
    "menu_item_id": 10,
    "rating": 5,
    "comment": "Excellent! Very tasty and well-prepared.",
    "user": {
      "id": 5,
      "name": "John Doe"
    },
    "menu_item": { ... },
    "created_at": "2026-01-28T10:30:00Z",
    "updated_at": "2026-01-28T10:30:00Z"
  }
}
```

**If Already Rated** (422):
```json
{
  "message": "You have already rated this menu item. Use update to modify your rating.",
  "data": { ... }
}
```

---

### 3. Update a Rating
**PUT/PATCH** `/api/v1/ratings/{rating}`

**Authentication**: Required (Customer role)

**Description**: Update an existing rating. Only the owner of the rating can update it.

**URL Parameters**:
- `rating`: The ID of the rating (route model binding)

**Request Body**:
```json
{
  "rating": 4,
  "comment": "Updated comment"
}
```

**Validation Rules**:
- `rating`: Optional, integer, min: 1, max: 5 (if provided)
- `comment`: Optional, string, max: 1000 characters

**Success Response** (200):
```json
{
  "message": "Rating updated successfully.",
  "data": {
    "id": 1,
    "user_id": 5,
    "menu_item_id": 10,
    "rating": 4,
    "comment": "Updated comment",
    ...
  }
}
```

**Unauthorized Response** (403):
```json
{
  "message": "You can only update your own ratings."
}
```

---

### 4. Delete a Rating
**DELETE** `/api/v1/ratings/{rating}`

**Authentication**: Required (Customer role)

**Description**: Delete a rating. Only the owner of the rating can delete it.

**URL Parameters**:
- `rating`: The ID of the rating (route model binding)

**Success Response** (200):
```json
{
  "message": "Rating deleted successfully."
}
```

**Unauthorized Response** (403):
```json
{
  "message": "You can only delete your own ratings."
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "Forbidden. Insufficient permissions."
}
```

### 404 Not Found
```json
{
  "message": "Resource not found."
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "menu_item_id": [
      "The menu item id field is required."
    ],
    "rating": [
      "The rating must be between 1 and 5."
    ]
  }
}
```

---

## Database Schema

### Favourites Table
- `id`: Primary key
- `user_id`: Foreign key to users table
- `menu_item_id`: Foreign key to menu_items table
- `created_at`: Timestamp
- `updated_at`: Timestamp
- Unique constraint on (`user_id`, `menu_item_id`)

### Ratings Table
- `id`: Primary key
- `user_id`: Foreign key to users table
- `menu_item_id`: Foreign key to menu_items table
- `rating`: Integer (1-5)
- `comment`: Text (nullable)
- `created_at`: Timestamp
- `updated_at`: Timestamp
- Unique constraint on (`user_id`, `menu_item_id`)

---

## Notes

1. **Favourites**: Can be added/removed without any order requirement. Stored per customer.

2. **Ratings**: 
   - Can only be created after a customer has ordered and paid for the menu item
   - One rating per customer per menu item
   - Ratings are public and can be viewed by anyone
   - Customers can update or delete their own ratings

3. **Authorization**:
   - Favourites endpoints require customer role
   - Rating creation/update/delete requires customer role
   - Rating viewing is public

4. **Menu Item Relationships**:
   - Menu items now have `average_rating` and `ratings_count` attributes
   - These can be accessed via `$menuItem->average_rating` and `$menuItem->ratings_count`
