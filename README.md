# High-Performance E-Commerce Backend Engine

<p align="center">
  <img src="https://laravel.com/img/logomark.min.svg" alt="Laravel" height="100"/>
  <img src="https://redis.io/wp-content/uploads/2024/04/Logotype.svg?auto=webp&quality=85,75&width=120" alt="Redis" height="80"/>
  <img src="https://nginx.org/nginx.png" alt="Nginx" height="80"/>
</p>

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)
![Redis](https://img.shields.io/badge/Redis-7.x-DC382D?logo=redis)
![Nginx](https://img.shields.io/badge/Nginx-1.30-009639?logo=nginx)
![JWT](https://img.shields.io/badge/JWT-auth-000000?logo=JSON%20web%20tokens)

</div>

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Functional Features](#functional-features)
3. [Non-Functional Requirements Implementation](#non-functional-requirements-implementation)
   - [3.1 Concurrent Access & Data Integrity](#31-concurrent-access--data-integrity)
   - [3.2 Resource Management & Rate Limiting](#32-resource-management--rate-limiting)
   - [3.3 Asynchronous Processing (Queues)](#33-asynchronous-processing-queues)
   - [3.4 Batch Processing of Large Data](#34-batch-processing-of-large-data)
   - [3.5 Load Distribution Strategies](#35-load-distribution-strategies)
   - [3.6 Distributed Caching Strategy](#36-distributed-caching-strategy)
4. [Installation & Setup](#installation--setup)
5. [API Endpoints](#api-endpoints)
6. [Project Structure](#project-structure)
7. [Testing & Performance Monitoring](#testing--performance-monitoring)
8. [Environment Configuration](#environment-configuration)
9. [References](#references)
10. [License](#license)

---

## 1. Project Overview

The **High-Performance E-Commerce Backend Engine** is a server-side application built with Laravel 11. It is designed to handle thousands of concurrent orders while maintaining data integrity, optimal resource usage, and low latency.

The primary focus is on non‑functional requirements such as race‑condition prevention, capacity control, asynchronous processing, batch operations, load distribution, and distributed caching.

Functional modules (user management, product catalog, shopping cart, order lifecycle, invoicing, and reporting) are provided as a baseline to demonstrate these non‑functional capabilities under high load.

---

## 2. Functional Features

- **Authentication & Authorization** – JWT‑based registration/login, role‑permission system (Spatie).
- **Product Management** – CRUD with stock control, optional caching layer.
- **Shopping Cart** – Per‑user cart stored in Redis (hash). Supports add, remove, update, clear, and checkout.
- **Order Processing** – Synchronous and asynchronous (queued) order confirmation with safe/unsafe modes to illustrate race conditions.
- **Order Cancellation** – Returns stock to inventory, adjusts trending product scores, and cancels associated invoices.
- **Invoice & Email Notifications** – Automatic invoice creation, queued email delivery with retry logic.
- **Daily Sales Aggregation** – Background jobs process delivered orders in chunks and generate daily summaries.
- **Excel Reports** – Monthly sales and daily totals exported as .xlsx files via Maatwebsite.

---

## 3. Non-Functional Requirements Implementation

### 3.1 Concurrent Access & Data Integrity

The system provides both **unsafe** and **safe** endpoints to demonstrate race‑condition handling.

- **Unsafe mode** – Uses a standard `SELECT` without locking and decrements stock. Under high concurrency, overselling may occur.
- **Safe mode** – Employs pessimistic locking (`lockForUpdate`) inside a database transaction to guarantee atomic stock deduction.

**Example – Safe Order Confirmation (`OrderService::confirmOrderSafe`)**

```php
return DB::transaction(function () use ($order, $items) {
    $total = 0;
    foreach ($items as $item) {
        $product = $this->productRepo->findAndLockForUpdate($item['product_id']);
        // ... validate stock
        $product->decrement('stock', $item['quantity']);
        // ... create OrderItem
    }
    // ... update order status
}, 5);
```

The same logic is applied in the queued path (`ProcessOrderJob`) by passing `mode = 'safe'` or `'unsafe'`.

### 3.2 Resource Management & Rate Limiting

Three custom rate limiters are defined in `App\Providers\RouteServiceProvider`:

| Limiter | Limit | Key |
| :--- | :--- | :--- |
| api | 60 requests/min | user ID or IP |
| orders | 10 requests/min | user ID or IP |
| orders-queue | 10 requests/min | user ID or IP |

These are attached to specific route groups using the `throttle` middleware, preventing resource exhaustion under heavy load.

```php
RateLimiter::for('orders', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
});
```

### 3.3 Asynchronous Processing (Queues)

Time‑consuming tasks are moved out of the request lifecycle using Laravel queues (database or Redis driver).

The queue system handles the following jobs:

| Job Name | Queue | Description |
| :--- | :--- | :--- |
| ProcessOrderJob | orders | Confirms the order (safe/unsafe), deducts stock, increments trending scores. |
| CreateInvoiceJob | invoices | Generates an invoice after a successful order confirmation. |
| SendOrderEmailJob | notifications | Sends confirmation email and updates order status to preparing. |
| ProcessDailySalesJobWithChunk | sales | Aggregates daily sales using chunked reading. |
| ProcessDailySalesJobWithoutChunk | sales | Aggregates daily sales without chunking (alternative). |
| GenerateMonthlySalesReportJob | reports | Creates a monthly sales Excel report. |
| GenerateDailySalesTotalsReportJob | reports | Creates a daily totals Excel report. |

#### Checkout Flow – Asynchronous (Cart → Queue)

1. `CartController::checkout` creates a pending order via `OrderService::createPendingOrder`.
2. `ProcessOrderJob` is dispatched to the `orders` queue.
3. The job performs stock deduction and trending increment.
4. On success, `CreateInvoiceJob` is dispatched.
5. `CreateInvoiceJob` creates the invoice and dispatches `SendOrderEmailJob`.
6. The email job sends the confirmation and sets the order to preparing.

**Synchronous Alternative** – The `OrderController` (sync endpoints) processes the order immediately, still dispatching the email job asynchronously for non‑blocking response.

### 3.4 Batch Processing of Large Data

Two strategies are implemented for daily sales aggregation:

- **Without Chunking** – `ProcessDailySalesJobWithoutChunk` loads all delivered orders into memory (suitable for small datasets).
- **With Chunking** – `ProcessDailySalesJobWithChunk` uses Eloquent’s `chunk()` method to process records in batches of 100, reducing memory footprint.

Additionally, `DispatchDailySalesBatch` splits order IDs into smaller chunks and dispatches them as separate batch jobs. Both jobs update the `daily_product_sales` and `daily_sales_totals` tables.

### 3.5 Load Distribution Strategies

The application can be scaled horizontally behind Nginx acting as a reverse proxy/load balancer.

Two strategies are configured:

#### Round‑Robin (default)

```nginx
upstream laravel_backend {
    server 127.0.0.1:8001;
    server 127.0.0.1:8002;
    server 127.0.0.1:8003;
}
```

#### Least Connections (recommended for variable workload)

```nginx
upstream laravel_backend {
    least_conn;
    server 127.0.0.1:8001 max_fails=3 fail_timeout=30s;
    server 127.0.0.1:8002 max_fails=3 fail_timeout=30s;
    server 127.0.0.1:8003 max_fails=3 fail_timeout=30s;
}
```

#### Why Least Connections is Suitable
Because the application is stateless (sessions and cart are stored in Redis, JWT tokens are used), any server can handle any request. The `least_conn` directive forwards a new request to the backend with the fewest active connections, which balances actual load more effectively when request processing times vary (e.g., checkout vs. product listing).

### 3.6 Distributed Caching Strategy

- **Shopping Cart** – Stored in Redis as a hash (`cart:user:{id}`) with a 7‑day TTL. This enables instant retrieval and allows any application server to access the cart.
- **Trending Products** – A Redis sorted set (`popular_products`) is incremented after each successful order. The top 20 products are cached (`top_products_list`) for 1 hour and invalidated automatically upon any increment/decrement.
- **Product Details (optional)** – `ProductService` can optionally cache individual products using `Cache::tags(['products'])` to reduce database queries.

---

## 4. Installation & Setup

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL 5.7+ / MariaDB
- Redis server (or Memurai for Windows)
- (Optional) Nginx for load balancing

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/majddakhoul/HighPerformanceECommerce.git
cd HighPerformanceECommerceBackendEngine

# 2. Install dependencies
composer install

# 3. Create environment file and generate keys
cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# 4. Configure database and Redis in .env, then run migrations and seeders
php artisan migrate:fresh --seed

# 5. Start Redis (or Memurai)
redis-server                           # Linux/macOS
memurai.exe                            # Windows

# 6. Start Laravel instances (for load balancing simulation)
php artisan serve --port=8001
php artisan serve --port=8002
php artisan serve --port=8003

# 7. Start queue worker
php artisan queue:work --queue=orders,invoices,notifications --tries=5
```

---

## 5. API Endpoints

### Authentication
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| POST | `/api/register` | Register new user |
| POST | `/api/login` | Login, obtain JWT |
| GET | `/api/me` | Get authenticated user |
| POST | `/api/logout` | Logout (clears cart) |
| POST | `/api/refresh` | Refresh token |

### Products
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| GET | `/api/products` | List all products |
| GET | `/api/products/{id}` | Show single product |
| POST | `/api/products` | Create product (admin) |
| PUT | `/api/products` | Update product (admin) |
| DELETE | `/api/products` | Delete product (admin) |
| GET | `/api/products/top` | Top 20 trending products |

### Shopping Cart
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| GET | `/api/cart` | View current cart |
| POST | `/api/cart/add` | Add item to cart |
| POST | `/api/cart/remove` | Remove item from cart |
| PUT | `/api/cart/update` | Update item quantity |
| POST | `/api/cart/clear` | Empty the cart |
| POST | `/api/cart/checkout` | Place order (async queue) |

### Orders
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| GET | `/api/orders` | List all orders (admin) |
| GET | `/api/orders/my` | List orders of authenticated user |
| GET | `/api/orders/{id}` | Show order details |
| PUT | `/api/orders` | Update order status (admin) |
| DELETE | `/api/orders` | Delete order (admin) |
| POST | `/api/orders/cancel` | Cancel order (user) |

### Test Endpoints (Race Condition / Rate Limiting)

#### Synchronous
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| POST | `/api/orders/test/no-limit/unsafe` | Sync, unsafe, no rate limit |
| POST | `/api/orders/test/no-limit/safe` | Sync, safe, no rate limit |
| POST | `/api/orders/test/with-limit/unsafe` | Sync, unsafe, rate limited |
| POST | `/api/orders/test/with-limit/safe` | Sync, safe, rate limited |

#### Asynchronous (Queued)
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| POST | `/api/orders/queue/with-limit/unsafe` | Async, unsafe, rate limited |
| POST | `/api/orders/queue/with-limit/safe` | Async, safe, rate limited |
| POST | `/api/orders/queue/no-limit/unsafe` | Async, unsafe, no rate limit |
| POST | `/api/orders/queue/no-limit/safe` | Async, safe, no rate limit |

> All endpoints require `Authorization: Bearer {token}` except `/register` and `/login`.

---

## 6. Project Structure

```text
app/
├── Console/Commands/        # Custom Artisan commands (TestCaseTester, PerformanceTester)
├── Contracts/               # Interfaces (CartServiceInterface, TopProductsServiceInterface)
├── DTOs/                    # Data Transfer Objects (AddToCartDTO, CancelOrderDTO, etc.)
├── Exports/                 # Excel exports (DailySalesTotalsExport, MonthlySalesExport)
├── Exceptions/Handler.php   # API exception handler
├── Http/
│   ├── Controllers/         # Auth, Cart, Order, Product, User, Role controllers
│   ├── Middleware/          # PerformanceMiddleware (logs request metrics)
│   ├── Requests/            # Form request validation classes
│   └── Resources/           # JSON resources (OrderResource, ProductResource)
├── Jobs/                    # Queue jobs (ProcessOrderJob, CreateInvoiceJob, etc.)
├── Listeners/               # Event listeners (DeleteCartOnLogout)
├── Mail/                    # Mailable classes (OrderCreatedMail)
├── Models/                  # Eloquent models (User, Product, Order, Invoice, etc.)
├── Policies/                # Authorization policies
├── Repositories/
│   ├── Contracts/           # Repository interfaces
│   └── Eloquent/            # Eloquent implementations
├── Services/                # Business logic (CartService, OrderProcessingService, TopProductsService)
└── Traits/ApiResponse.php   # Standardized JSON responses
```

---

## 7. Testing & Performance Monitoring

- Artisan command `test:case` – Interactive scenario runner that simulates race conditions, rate limits, and async processing.
- Artisan command `test:performance` – Runs a predefined set of performance tests.
- `PerformanceMiddleware` – Logs every request with metrics:
  - Duration (ms)
  - Memory usage (KB)
  - Database query count and slowest query
- Custom `X-Trace-Id`, `X-Response-Time-ms`, `X-Memory-Used-kb` headers added to responses.
- Postman Collection – A comprehensive collection with all endpoints, variables, and tests is included (see `/postman` folder).

---

## 8. Environment Configuration

Key `.env` variables:

```ini
CACHE_DRIVER=redis          # Redis for cache
QUEUE_CONNECTION=database   # or 'redis' for queue storage
SESSION_DRIVER=redis        # Shared sessions for multi‑server setups
REDIS_CLIENT=predis         # Required on Windows when using Memurai

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password

JWT_SECRET=your_jwt_secret
```

> For Windows development, set `REDIS_CLIENT=predis` and use Memurai as the Redis server.

---

## 9. References

- Laravel Documentation
- JWT Auth Package
- Spatie Laravel Permission
- Predis Library
- Memurai – Redis for Windows
- Nginx Load Balancing
- Maatwebsite Excel for Laravel
- Redis Sorted Sets
- Laravel Queue Documentation
- Rate Limiting in Laravel

---

## 10. License

This project is open‑sourced software licensed under the [MIT license](LICENSE).
