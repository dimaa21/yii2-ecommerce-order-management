# Order Management System

A small admin application for an online store: categories, products, customers and orders. It is a Yii2 portfolio project focused on ActiveRecord, RBAC, a thin controller with a service layer, and a REST API.

The interesting part is not the screens. It is how an order is saved. Creating an order checks the customer and the products, copies the price onto each line, writes the order and its lines, and decreases stock. All of that happens in one database transaction. If any step fails, the transaction is rolled back and nothing is left half-saved.

## Features

- Session login for the admin UI, HTTP Basic and Bearer token for the API
- RBAC with two roles: `admin` and `manager`
- CRUD for products, categories, customers and staff users
- Order creation with stock checks, price snapshots and status changes
- Search, filters, sorting and pagination through ActiveQuery
- Dashboard totals read from the database, including low-stock products
- JSON API with explicit status codes
- Migrations, demo data and unit tests for `OrderService`

## Technologies

- PHP 8.2+
- Yii 2.0.55
- MySQL 8
- Bootstrap 5
- Composer
- Codeception for tests

The automated tests run on SQLite so they do not need a MySQL server. The application itself is written for MySQL 8: foreign keys, check constraints and `SELECT ... FOR UPDATE`.

## Architecture

```
controllers/          HTTP: read the request, check access, call a service or a search model
controllers/api/      same for JSON
models/               ActiveRecord: rules, relations, labels
models/search/        list filters (ProductSearch, OrderSearch, CustomerSearch)
models/query/         named scopes such as lowStock() and paid()
services/OrderService.php
services/DashboardService.php
services/UserService.php
migrations/           schema and RBAC setup
commands/SeedController.php
```

Controllers stay thin on purpose. `OrderController` validates the form and calls `OrderService`. It does not open a transaction and it does not touch stock. The service is the only place that knows the order of operations, so the web form and the API cannot drift apart.

`UserService` is smaller and exists for the same reason: saving a user and assigning an RBAC role are two writes, and they should commit or roll back together.

Search models keep list queries out of controllers. Dashboard aggregates use the query builder. Product and order lists use ActiveQuery.

### Why a service, not the controller

A controller is an adapter for HTTP. The order rules are not HTTP rules:

1. The customer must exist.
2. Every product must exist and be active.
3. Requested quantity must be greater than zero.
4. Stock must cover the quantity at the moment of the write, not at the moment the form was rendered.
5. The line stores the price from that moment. A later catalog price change must not rewrite the sale.
6. The order, the lines, the total and the stock change are one unit of work.

If that lived in the controller, the API would copy it or call the web action. A service gives both entry points one implementation and gives the tests a way to run the rules without a browser.

### Relationships

| Relation | Why it exists |
| --- | --- |
| `Product` belongs to `Category` | Every product sits in one category. Lists and the API can show the category without a hand-written join. |
| `Category` has many `Product` | The inverse side. Used to count products and to refuse deleting a category that still has them. |
| `Order` belongs to `Customer` | An order is always placed by one customer. |
| `Customer` has many `Order` | The customer page lists their history, and a customer with orders cannot be deleted. |
| `Order` has many `OrderItem` | The lines are what the total and the stock change are calculated from. |
| `OrderItem` belongs to `Order` and `Product` | A line points at a real product. `price` on the line is a copy taken at purchase time, not a live link to the catalog price. |

The order table is named `orders` because `ORDER` is a reserved word in SQL.

### Order transaction

`OrderService::create()` does the following inside `beginTransaction()`:

1. Merge duplicate product lines so two rows cannot oversell the same SKU.
2. Load the customer.
3. Lock the product rows in id order (`FOR UPDATE` on MySQL) so two checkouts cannot deadlock or sell the same unit.
4. Reject inactive products.
5. Insert the order and its lines. The line total is `price * quantity` with bcmath, not floats.
6. Store the sum on the order.
7. Decrease stock with `UPDATE ... WHERE stock >= quantity`. If that updates zero rows, stock was not enough.

Any exception rolls the transaction back. The tests cover a successful total, a missing product, a missing customer, an inactive product, merged lines, and the case where the first product's stock was already decreased and the second product fails. After that failure there is no order row and both stock values are unchanged.

Cancelling an order is the reverse: the same transaction returns stock and then marks the order cancelled. Cancelled is a terminal status.

### Access

|  | Admin | Manager |
| --- | --- | --- |
| Dashboard | yes | yes |
| Products | yes | yes |
| Customers | yes | yes |
| Orders | yes | yes |
| Categories | yes | no |
| Users | yes | no |

Admin inherits the manager role, then adds category and user management. Permissions are checked with Yii RBAC (`DbManager`), not with a role column.

Managers can still read categories through the API, because they need them when working with products. They cannot create or edit categories.

## Security

- Passwords are stored with `generatePasswordHash()`. The hash is not a form field, so it cannot be mass-assigned.
- Web forms use CSRF. The API is stateless and does not use the CSRF cookie.
- Output in views goes through `Html::encode`.
- Queries use ActiveRecord and the query builder with bound parameters. User input is not concatenated into SQL.
- Validation rules are the safe attribute list. `id` and timestamps are not mass-assignable.
- RBAC blocks a manager from users and categories. Guests are sent to the login page.
- API errors are JSON. Production (`YII_DEBUG=false`) does not put exception text or a stack trace in the response.
- Foreign keys on MySQL use `ON DELETE RESTRICT`, so an order line cannot point at a missing product, and a product that was sold cannot be deleted. Deactivate it instead.

## Requirements

- PHP 8.2 or newer, with `pdo_mysql`, `mbstring`, `openssl`, `intl` and `bcmath`
- Composer 2
- MySQL 8

## Installation

```bash
composer install
cp .env.example .env
```

Create the database:

```sql
CREATE DATABASE oms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Set `DB_DSN`, `DB_USERNAME` and `DB_PASSWORD` in `.env`. Then:

```bash
php yii migrate --interactive=0
php yii seed/demo
php yii serve --router=@app/web/index.php
```

Open http://localhost:8080/login

`php yii serve` needs the router flag so pretty URLs reach `web/index.php`. Apache uses `web/.htaccess` instead.

Docker is optional:

```bash
docker compose up -d
```

That starts MySQL 8 with database `oms`, user `root`, password `root`. Point `.env` at it before migrating.

To replace demo data later:

```bash
php yii seed/demo --force=1
```

## Demo accounts

| Username | Password | Role |
| --- | --- | --- |
| admin | admin12345 | admin |
| manager | manager12345 | manager |

These are demo passwords. The seed command also prints a Bearer token for each user.

The seed loads 5 categories, 20 products, 10 customers and 15 orders. A few products end at stock 5 or below so the dashboard low-stock list is not empty. One product, Atlas Fold, is inactive.

## API

All API routes require authentication. Send HTTP Basic or a Bearer token.

```bash
curl -u admin:admin12345 http://localhost:8080/api/products
curl -H "Authorization: Bearer <token>" http://localhost:8080/api/products
```

Successful responses:

```json
{
  "success": true,
  "data": {}
}
```

Lists add pagination inside `data`:

```json
{
  "success": true,
  "data": {
    "items": [],
    "meta": { "total": 20, "page": 1, "per_page": 20, "page_count": 1 }
  }
}
```

`page` is 1-based. `per_page` defaults to 20 and is capped at 100.

Errors:

```json
{
  "success": false,
  "message": "Product not found."
}
```

Validation errors also include an `errors` object. Status codes:

| Code | When |
| --- | --- |
| 200 | Read, update, delete |
| 201 | Create |
| 401 | Missing or bad credentials |
| 403 | Authenticated, but the role cannot do this |
| 404 | Unknown id or route |
| 422 | Validation, bad status transition, or not enough stock |
| 500 | Unexpected failure. No stack trace when `YII_DEBUG` is false |

### Routes

| Method | Path | Who |
| --- | --- | --- |
| GET | `/api/products` | admin, manager |
| GET | `/api/products/{id}` | admin, manager |
| POST | `/api/products` | admin, manager |
| PUT | `/api/products/{id}` | admin, manager |
| DELETE | `/api/products/{id}` | admin, manager |
| GET | `/api/categories` | admin, manager |
| GET | `/api/categories/{id}` | admin, manager |
| GET | `/api/customers` | admin, manager |
| GET | `/api/customers/{id}` | admin, manager |
| GET | `/api/orders` | admin, manager |
| GET | `/api/orders/{id}` | admin, manager |
| POST | `/api/orders` | admin, manager |
| PUT | `/api/orders/{id}` | admin, manager |

Add related records with `?expand=category` on a product, or `?expand=customer,items` on an order. Order details include the customer and lines by default.

### Product filters

```bash
curl -u admin:admin12345 "http://localhost:8080/api/products?category_id=1"
curl -u admin:admin12345 "http://localhost:8080/api/products?status=active"
curl -u admin:admin12345 "http://localhost:8080/api/products?min_price=10&max_price=100"
curl -u admin:admin12345 "http://localhost:8080/api/products?search=phone"
curl -u admin:admin12345 "http://localhost:8080/api/products?sort=-price&page=1&per_page=10"
```

`search` matches name, SKU and description. `sort` uses Yii's format: `price` ascending, `-price` descending. You can also filter `min_stock` and `max_stock`.

### Orders and customers

```bash
curl -u admin:admin12345 "http://localhost:8080/api/orders?status=completed&payment_status=paid"
curl -u admin:admin12345 "http://localhost:8080/api/orders?customer=smith&date_from=2026-10-01&date_to=2026-10-31"
curl -u admin:admin12345 "http://localhost:8080/api/customers?search=john"
```

### Create a product

```bash
curl -u admin:admin12345 \
  -H "Content-Type: application/json" \
  -d '{"category_id":1,"name":"USB Hub","price":29.90,"sku":"SKU-AC-100","stock":12,"status":"active"}' \
  http://localhost:8080/api/products
```

### Create an order

```bash
curl -u admin:admin12345 \
  -H "Content-Type: application/json" \
  -d '{"customer_id":1,"items":[{"product_id":1,"quantity":2},{"product_id":2,"quantity":1}]}' \
  http://localhost:8080/api/orders
```

### Change status

```bash
curl -u admin:admin12345 \
  -X PUT -H "Content-Type: application/json" \
  -d '{"status":"processing","payment_status":"paid"}' \
  http://localhost:8080/api/orders/1
```

Allowed order transitions:

- `new` → `processing`, `completed`, `cancelled`
- `processing` → `completed`, `cancelled`
- `completed` → `cancelled`
- `cancelled` → nothing

Payment status can be `pending`, `paid`, `failed` or `refunded`.

## Admin UI

| URL | Purpose |
| --- | --- |
| `/login` | Sign in |
| `/dashboard` | Totals, revenue, orders today and this month, low stock |
| `/products` | Search, category, status, price range, stock range |
| `/categories` | Admin only |
| `/customers` | Search by name, email or phone |
| `/orders` | Customer, status, payment, date range |
| `/orders/create` | Place an order |
| `/users` | Admin only |

Low stock means an active product with `stock <= 5`. The threshold is `lowStockThreshold` in `config/params.php`.

## Tests

```bash
vendor/bin/codecept run Unit
```

`OrderServiceTest` checks:

- a successful order: total `19.99 * 3 + 5.50 * 2 = 70.97`, stock decreased, and the line price stays put after the catalog price changes
- insufficient stock rolls back the order and the stock change of the earlier line
- a missing product creates nothing
- a missing customer creates nothing
- an inactive product is rejected
- duplicate lines are merged before stock is checked
- cancelling restores stock, and a cancelled order cannot be reopened

## Project layout

```
assets/                 Bootstrap 5 and the small order-form script
commands/SeedController.php
components/             RBAC names, money helper, migration base
config/                 web, console, test, .env loader
controllers/            web controllers
controllers/api/        REST controllers
exceptions/             domain errors mapped to HTTP status codes
migrations/
models/
models/query/
models/search/
services/
tests/Unit/Services/OrderServiceTest.php
views/
web/                    document root
```

## Production notes

In `.env`:

```
YII_DEBUG=false
YII_ENV=prod
```

Change `COOKIE_VALIDATION_KEY`. Do not use the demo passwords. With `YII_ENV=dev` the Yii debug toolbar is on; it is off in production.
