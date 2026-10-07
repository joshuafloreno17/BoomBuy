# BoomBuy Rider API (v1)

The API behind the BoomBuy Rider app (`mobile/boombuy_rider`). It follows the same rules as the rider web pages, because both go through `DeliveryService`.

- **Base URL:** `https://boombuy.store/api/v1`. Locally it's `http://<your PC's IP>:8000/api/v1`.
- **Format:** JSON in and JSON out. Send `Accept: application/json`.
- **Sign-in:** call `POST /login` once, then send `Authorization: Bearer <token>` on every other request.
  - A token lasts 30 days from its last use.
  - A token stops working after logout, after a password change, or when the account is suspended or deactivated.
- **Errors:** every error has a `message` in plain words that you can show the rider as is.

| Status | Meaning |
|---|---|
| 401 | Not signed in, or signed out. Go back to the login screen. |
| 403 | Not allowed. For example, the account isn't a rider or isn't approved yet. |
| 404 | Not this rider's delivery. |
| 422 | A rule blocked the action. For example, "Take a photo…" or "This order must be moved to Out for Delivery first". |
| 429 | Too many tries. Wait a minute. |

## Endpoints

### `POST /login`
Body: `email`, `password`, `device_name` (optional).
```json
{ "token": "12|Xk3…", "rider": { "id": 12, "name": "Jun Dela Cruz", "email": "jun@…", "phone": "0917…", "area": "Santa Cruz, Laguna", "photo": null } }
```
Only approved, active riders can sign in. Buyers and sellers get a 403.

### `POST /logout`
Ends this token.

### `GET /me`
Returns the rider, today's numbers, the COD cash still to hand in, the failure reasons to offer, and the unread notification count.
```json
{
  "rider": { … },
  "stats": { "active": 2, "delivered": 40, "delivered_today": 3 },
  "cod_to_hand_in": { "orders": 2, "amount": 1100 },
  "failure_reasons": ["Buyer refused the parcel", "Buyer not available", "Buyer unreachable by phone", "Wrong or incomplete address", "Other"],
  "unread_notifications": 1
}
```

### `GET /deliveries?filter=active|history`
- `active` (the default) returns deliveries that are Assigned for Delivery or Out for Delivery, oldest first.
- `history` returns the last 100 that ended as Delivered or Delivery Failed.

Each delivery:
```json
{ "id": 66, "waybill": "BB-000066", "status": "Assigned for Delivery", "buyer_name": "Ana", "phone": "0917…",
  "address": "15 P. Guevarra St, Poblacion, Santa Cruz, Laguna", "cod": true, "collect_amount": 550,
  "items_count": 1, "updated_at": "…", "can": { "start": true, "deliver": false, "fail": false } }
```
Use `can` to decide which buttons to show.

### `GET /deliveries/{id}`
Returns the same fields, plus:
- `items` (name, option, quantity)
- `pickup_center` (the Sorting Center to collect it from)
- `delivery_attempts`
- `failure_reason`
- `delivered_at`
- `maps_url` (opens Google Maps at the address)

### `POST /deliveries/{id}/out-for-delivery`
Use this when the rider has the parcel and is on the way.

### `POST /deliveries/{id}/delivered`
Send it as `multipart/form-data` with `photo`: a JPG, PNG or WEBP of up to 5 MB showing the parcel handed over. The photo is required. For COD, the cash then counts as collected by the rider.

### `POST /deliveries/{id}/failed`
Body:
- `reason`: one of `failure_reasons`.
- `details`: required when the reason is `Other`.

"Buyer refused the parcel" is final: the parcel goes back to the seller. For any other reason, the rider brings the parcel back to the Sorting Center.

The three status actions return `{ "message": "…", "delivery": { …full delivery… } }`.

### `GET /earnings?from=YYYY-MM-DD&to=YYYY-MM-DD`
Covers the last 30 days by default. It uses the fee saved on each delivery when it was delivered.
```json
{ "from": "…", "to": "…", "deliveries": 12, "total": 600, "days": [ { "date": "2026-10-08", "deliveries": 3, "earned": 150 } ] }
```

### `GET /notifications`
Returns the latest 50: `id`, `title`, `message`, `order_id` (for delivery notifications), `read`, and `created_at`.

### `POST /notifications/{id}/read`
Marks one notification as read.

## Server notes
- Code:
  - routes: `routes/api.php`
  - controller: `app/Http/Controllers/Api/RiderApiController.php`
  - sign-in: `app/Support/ApiToken.php` and `app/Http/Middleware/AuthenticateApiToken.php`
- Tokens are stored as SHA-256 hashes in `api_tokens`.
- No extra Composer package is needed. A new server only needs `php artisan migrate`.
- Tests: `tests/Feature/Scenarios/RiderApiScenariosTest.php`
