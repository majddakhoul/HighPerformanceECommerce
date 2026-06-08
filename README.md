# High-Performance E-Commerce Backend Engine

<p align="center">
<img src="https://laravel.com/img/logomark.min.svg" height="80" alt="Laravel">
<img src="https://redis.io/wp-content/uploads/2024/04/Logotype.svg?auto=webp&quality=85,75&width=120" height="80" alt="Redis">
<img src="https://nginx.org/nginx.png" height="80" alt="Nginx">
<img src="https://www.docker.com/wp-content/uploads/2022/03/Moby-logo.png" height="80" alt="Docker">
</p>

<p align="center">
<img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel" alt="Laravel 11.x">
<img src="https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php" alt="PHP 8.2+">
<img src="https://img.shields.io/badge/Redis-7.x-DC382D?logo=redis" alt="Redis 7.x">
<img src="https://img.shields.io/badge/Nginx-1.30-009639?logo=nginx" alt="Nginx 1.30">
<img src="https://img.shields.io/badge/JWT-auth-000000?logo=JSON%20web%20tokens" alt="JWT Auth">
<img src="https://img.shields.io/badge/Docker-27.0.3-2496ED?logo=docker" alt="Docker">
<img src="https://img.shields.io/badge/Swoole-6.x-0089FF?logo=swoole" alt="Swoole">
<img src="https://img.shields.io/badge/license-MIT-green" alt="License">
</p>

---

## Table of Contents

- [Overview](#overview)
- [Functional Features](#functional-features)
- [Non-Functional Requirements Implemented](#non-functional-requirements-implemented)
  - [Concurrent Access and Data Integrity](#concurrent-access-and-data-integrity)
  - [Resource Management and Rate Limiting](#resource-management-and-rate-limiting)
  - [Asynchronous Processing (Queues)](#asynchronous-processing-queues)
  - [Batch Processing of Large Data](#batch-processing-of-large-data)
  - [Load Distribution (Load Balancing)](#load-distribution-load-balancing)
  - [Distributed Caching Strategy](#distributed-caching-strategy)
  - [Transaction Integrity (ACID)](#transaction-integrity-acid)
  - [Stress Testing and Stability](#stress-testing-and-stability)
  - [Benchmarking and Bottleneck Analysis](#benchmarking-and-bottleneck-analysis)
- [Project Structure](#project-structure)
- [Installation and Setup](#installation-and-setup)
  - [Local Environment](#local-environment)
  - [Docker Environment](#docker-environment)
- [API Endpoints](#api-endpoints)
  - [Authentication](#authentication)
  - [Products](#products)
  - [Shopping Cart](#shopping-cart)
  - [Orders](#orders)
  - [Order Processing – Pessimistic Locking](#order-processing--pessimistic-locking)
  - [Order Processing – Optimistic Locking](#order-processing--optimistic-locking)
  - [Race Condition Test Endpoints](#race-condition-test-endpoints)
  - [Rate Limiting Test Endpoints](#rate-limiting-test-endpoints)
  - [Transaction Integrity Test](#transaction-integrity-test)
- [Testing and Performance Monitoring](#testing-and-performance-monitoring)
  - [K6 Load Test Scripts](#k6-load-test-scripts)
  - [Stress Test Results (Redis + Octane)](#stress-test-results-redis--octane)
  - [Bottleneck Analysis Results](#bottleneck-analysis-results)
- [Environment Configuration](#environment-configuration)
- [License](#license)

---

## Overview

The **High-Performance E-Commerce Backend Engine** is a server‑side application built with Laravel 11 and designed to handle thousands of concurrent orders while maintaining data integrity, optimal resource utilisation, and low latency. The system focuses on implementing non‑functional requirements such as race‑condition prevention, rate limiting, asynchronous queues, batch processing, load distribution, distributed caching, transaction integrity, and stress testing. A full set of functional modules (user authentication, product catalogue, shopping cart, order lifecycle, invoicing, and reporting) is provided as a baseline to demonstrate these capabilities under heavy load.

---

## Functional Features

- JWT‑based registration, login, token refresh, and logout.
- Role‑permission system (Spatie) – admin and user roles.
- Product CRUD with stock management, pessimistic and optimistic locking.
- Per‑user shopping cart stored in Redis (hash).
- Order placement with synchronous and asynchronous (queued) confirmation.
- Order cancellation with stock restoration and trending‑score adjustment.
- Automatic invoice creation, queued email delivery with retry logic.
- Daily sales aggregation with chunked background jobs.
- Excel reports for monthly and daily sales totals (Maatwebsite).
- Built‑in performance middleware logging request metrics and slow queries.
- Interactive Artisan commands for manual performance and race‑condition testing.

---

## Non-Functional Requirements Implemented

### Concurrent Access and Data Integrity

Two locking strategies are available, with dedicated API endpoints to demonstrate race‑condition handling.

**Pessimistic Locking** Uses `lockForUpdate` inside a database transaction to serialise access to a product row. If multiple requests try to buy the same product, only the first succeeds; the rest receive a `409 Insufficient stock` error.

**Optimistic Locking** A `version` column is added to the `products` table. Each update checks that the version has not changed since the record was read. If it has changed (another process updated it), the update fails with `409 Optimistic lock failure`. Unsafe variants (no locking) are provided to intentionally cause overselling for comparison.

### Resource Management and Rate Limiting

Custom rate limiters protect resource‑intensive order endpoints:

| Limiter          | Limit            | Key              |
|------------------|------------------|------------------|
| `api`            | unlimited        | –                |
| `orders`         | 10 requests/min  | user ID or IP    |
| `orders-queue`   | 10 requests/min  | user ID or IP    |

Routes are grouped under `throttle:orders` and `throttle:orders-queue` middleware.

### Asynchronous Processing (Queues)

Time‑consuming tasks are offloaded to Laravel queues (database or Redis driver). The job chain is:

1. `ProcessOrderJob` – confirms the order, deducts stock, increments trending scores.
2. `CreateInvoiceJob` – generates an invoice.
3. `SendOrderEmailJob` – sends confirmation email and updates order status to `preparing`.

The checkout from the cart is fully asynchronous: a `pending` order is created and the job is dispatched immediately, returning a `202` response.

### Batch Processing of Large Data

Daily sales aggregation is performed by background jobs that process `delivered` orders in chunks of 100 records to keep memory usage constant. Two job variants exist for comparison:

- `ProcessDailySalesJobWithChunk` – uses Eloquent `chunk()`.
- `ProcessDailySalesJobWithoutChunk` – loads all records at once.

Both update the `daily_product_sales` and `daily_sales_totals` tables.

### Load Distribution (Load Balancing)

Three application containers run behind an Nginx reverse proxy. The `least_conn` load‑balancing algorithm is used because the application is stateless (sessions and cart are stored in Redis, authentication uses JWT). This directs new requests to the server with the fewest active connections, balancing actual load effectively.

Nginx configuration snippet:

```nginx
upstream laravel_backend {
    least_conn;
    server 127.0.0.1:8001;
    server 127.0.0.1:8002;
    server 127.0.0.1:8003;
}

The `X-Upstream` header is added to responses to verify request distribution.

### Distributed Caching Strategy

* **Shopping Cart** – stored in Redis as a hash (`cart:user:{id}`) with a 7‑day TTL.
* **Trending Products** – a Redis sorted set (`popular_products`) is incremented after each successful order.
* **The top 20 products** are cached (`top_products_list`) for 1 hour and invalidated automatically upon any change.
* **Sessions and Cache** – configured to use Redis (`SESSION_DRIVER=redis`, `CACHE_DRIVER=redis`) for shared, high‑performance state across all containers.

### Transaction Integrity (ACID)

All composite operations (stock deduction + invoice creation + order confirmation) are wrapped in database transactions. If any step fails, the entire transaction is rolled back. A dedicated test endpoint (`/orders/test/transaction-failure`) intentionally throws an exception after stock deduction, demonstrating that the stock is restored and no order or invoice is persisted.

### Stress Testing and Stability

K6 load tests were conducted with up to 120 concurrent virtual users executing a realistic workflow (browse products, add to cart, checkout).

**Key results (Redis + Octane):**

| Metric | Value |
|---|---|
| Total requests | 55,123 |
| Request rate | 91.0 req/s |
| Avg response time | 81.72 ms |
| p95 response time | 188.11 ms |
| HTTP 5xx errors | 0.00% |
| Checkout success | 94% |

Data integrity checks after the test showed zero negative stock, matching invoices, and no orphaned orders.

### Benchmarking and Bottleneck Analysis

A before/after comparison identified the primary bottleneck as the single‑threaded nature of the built‑in `php artisan serve` server. Switching to Laravel Octane with Swoole (20 workers per container) reduced p95 latency from 14.4 seconds to 188 ms (a 98.7% improvement) and eliminated timeout errors completely. A secondary test with file‑based sessions and cache showed no significant degradation for this particular workload because JWT auth and Redis‑based cart operations bypassed those drivers.

---

## Project Structure

```text
app/
├── Console/Commands/          # Artisan commands (TestCaseTester, PerformanceTester)
├── Contracts/                 # Interfaces (CartServiceInterface, TopProductsServiceInterface)
├── DTOs/                      # Data Transfer Objects
├── Exports/                   # Excel exports
├── Exceptions/Handler.php     # Centralised API exception handler
├── Http/
│   ├── Controllers/           # Auth, Cart, Order, Product, User, Role
│   ├── Middleware/            # PerformanceMiddleware
│   ├── Requests/              # Form request validation
│   └── Resources/             # JSON resources
├── Jobs/                      # Queue jobs (ProcessOrder, CreateInvoice, SendEmail, etc.)
├── Listeners/                 # Event listeners (DeleteCartOnLogout)
├── Mail/                      # Mailable classes
├── Models/                    # Eloquent models
├── Policies/                  # Authorization policies
├── Repositories/
│   ├── Contracts/             # Repository interfaces
│   └── Eloquent/              # Eloquent implementations
├── Services/                  # Business logic (OrderService, CartService, etc.)
└── Traits/ApiResponse.php     # Standardised JSON responses

## References & Documentations

The development of this backend engine heavily relies on the official documentations and best practices provided by the following technologies and packages:

* **Laravel 11.x:** [https://laravel.com/docs/11.x](https://laravel.com/docs/11.x)
* **Laravel Octane & Swoole:** [https://laravel.com/docs/11.x/octane](https://laravel.com/docs/11.x/octane)
* **Redis:** [https://redis.io/docs/](https://redis.io/docs/)
* **Tymon JWT Auth:** [https://jwt-auth.readthedocs.io/](https://jwt-auth.readthedocs.io/)
* **Spatie Laravel Permission:** [https://spatie.be/docs/laravel-permission](https://spatie.be/docs/laravel-permission)
* **Maatwebsite Laravel Excel:** [https://docs.laravel-excel.com/](https://docs.laravel-excel.com/)
* **Docker:** [https://docs.docker.com/](https://docs.docker.com/)
* **K6 Load Testing:** [https://k6.io/docs/](https://k6.io/docs/)
* **Nginx Reverse Proxy:** [https://docs.nginx.com/nginx/admin-guide/web-server/reverse-proxy/](https://docs.nginx.com/nginx/admin-guide/web-server/reverse-proxy/)

## Installation and Setup

### Local Environment

**Prerequisites**
* PHP 8.1 or higher
* Composer
* MySQL / MariaDB
* Redis (or Memurai on Windows)

**Steps**
```bash
git clone [https://github.com/majddakhoul/HighPerformanceECommerce.git](https://github.com/majddakhoul/HighPerformanceECommerce.git)
cd HighPerformanceECommerce
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
# configure your database and Redis connection in .env
php artisan migrate:fresh --seed
php artisan serve --port=8000

## Docker Environment

### Prerequisites
- Docker Desktop

### Build and run

```bash
# Build the image (no email, Redis for sessions and cache)
docker build --no-cache -t laravel-octane .

# Run three application containers
docker run -d -p 8001:8001 -e PORT=8001 --name octane1 laravel-octane
docker run -d -p 8002:8002 -e PORT=8002 --name octane2 laravel-octane
docker run -d -p 8003:8003 -e PORT=8003 --name octane3 laravel-octane

# Run a dedicated queue worker container
docker run -d -p 8004:8004 -e PORT=8004 --name Queueoctane4 laravel-octane

# Prepare database and tokens
docker exec -it octane1 php artisan migrate:fresh --seed
docker exec -d Queueoctane4 php artisan queue:work --queue=sales,orders,invoices,notifications --tries=5

# Generate JWT tokens for load tests (run inside octane1)
docker exec octane1 php -r "
\$tokens = [];
for (\$i = 1; \$i <= 100; \$i++) {
    \$user = \App\Models\User::where('email', \"user{\$i}@example.com\")->first();
    if (\$user) {
        \$token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser(\$user);
        \$tokens[] = ['email' => \$user->email, 'token' => \$token];
    }
}
file_put_contents('/var/www/storage/app/tokens.json', json_encode(\$tokens));
echo 'Generated ' . count(\$tokens) . ' tokens.';
"

# Copy tokens to your host
docker cp octane1:/var/www/storage/app/tokens.json storage/app/tokens.json

## API Endpoints

### Authentication
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/register` | Register new user |
| POST | `/api/login` | Obtain JWT |
| GET | `/api/me` | Current user |
| POST | `/api/logout` | Logout |
| POST | `/api/refresh` | Refresh token |

### Products
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/products` | List all products |
| GET | `/api/products/{id}` | Show product |
| POST | `/api/products` | Create product (admin) |
| PUT | `/api/products` | Update product (admin) |
| DELETE | `/api/products` | Delete product (admin) |
| GET | `/api/products/top` | Top 20 trending products |

### Shopping Cart
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/cart` | View cart |
| POST | `/api/cart/add` | Add item |
| POST | `/api/cart/remove` | Remove item |
| PUT | `/api/cart/update` | Update quantity |
| POST | `/api/cart/clear` | Empty cart |
| POST | `/api/cart/checkout` | Place order (pessimistic) |
| POST | `/api/cart/checkout/optimistic` | Place order (optimistic) |

### Orders
| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/orders` | List all orders (admin) |
| GET | `/api/orders/my` | Orders of current user |
| GET | `/api/orders/{id}` | Show order |
| PUT | `/api/orders` | Update order status (admin) |
| DELETE | `/api/orders` | Delete order (admin) |
| POST | `/api/orders/cancel` | Cancel order |

### Order Processing – Pessimistic Locking
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/orders/pessimistic/test/no-limit/unsafe` | Sync, unsafe, no limit |
| POST | `/api/orders/pessimistic/test/no-limit/safe` | Sync, safe, no limit |
| POST | `/api/orders/pessimistic/test/with-limit/unsafe` | Sync, unsafe, rate limit |
| POST | `/api/orders/pessimistic/test/with-limit/safe` | Sync, safe, rate limit |
| POST | `/api/orders/queue/pessimistic/with-limit/unsafe` | Queue, unsafe, rate limit |
| POST | `/api/orders/queue/pessimistic/with-limit/safe` | Queue, safe, rate limit |
| POST | `/api/orders/queue/pessimistic/no-limit/unsafe` | Queue, unsafe, no limit |
| POST | `/api/orders/queue/pessimistic/no-limit/safe` | Queue, safe, no limit |

### Order Processing – Optimistic Locking
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/orders/optimistic/test/no-limit/unsafe` | Sync, unsafe, no limit |
| POST | `/api/orders/optimistic/test/no-limit/safe` | Sync, safe, no limit |
| POST | `/api/orders/optimistic/test/with-limit/unsafe` | Sync, unsafe, rate limit |
| POST | `/api/orders/optimistic/test/with-limit/safe` | Sync, safe, rate limit |
| POST | `/api/orders/queue/optimistic/with-limit/unsafe` | Queue, unsafe, rate limit |
| POST | `/api/orders/queue/optimistic/with-limit/safe` | Queue, safe, rate limit |
| POST | `/api/orders/queue/optimistic/no-limit/unsafe` | Queue, unsafe, no limit |
| POST | `/api/orders/queue/optimistic/no-limit/safe` | Queue, safe, no limit |

### Race Condition Test Endpoints
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/products/optimistic/decrement/unsafe` | Direct decrement, unsafe |
| POST | `/api/products/optimistic/decrement/safe` | Direct decrement, optimistic lock |

### Rate Limiting Test Endpoints
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/orders/pessimistic/test/no-limit/safe` | No rate limit (comparison baseline) |
| POST | `/api/orders/pessimistic/test/with-limit/safe` | Rate limit applied (10 req/min) |

### Transaction Integrity Test
| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/orders/test/transaction-failure` | Intentionally fails after stock deduction to prove rollback |

## Testing and Performance Monitoring

### K6 Load Test Scripts
All K6 scripts are located in `tests/Testers/`:

| Script | Target Environment | Max VUs |
|---|---|---|
| `stress-test-redis.js` | Redis + Octane via Nginx (8080) | 120 |
| `stress-test-file.js` | File driver + Octane direct (8005) | 120 |
| `Race-Condition-Test-SAFE.js` | Pessimistic locking test | 50 |
| `Race-Condition-Test-UNSAFE.js` | Unsafe test (no locking) | 50 |
| `rate-limit-test.js` | Rate limiting comparison | 50 |
| `transaction-failure-test.js` | Intentional transaction failure | 10 |

### Stress Test Results (Redis + Octane)
Ran with 120 concurrent users for 10 minutes.

| Metric | Value |
|---|---|
| Total requests | 55,123 |
| Request rate | 91.0 req/s |
| Avg response time | 81.72 ms |
| p95 response time | 188.11 ms |
| p99 response time | ~260 ms |
| HTTP 5xx errors | 0.00% |
| Checks pass rate | 96.51% |
| Checkout success | 94% |

### Bottleneck Analysis Results
Comparison between the built‑in `artisan serve` server and Octane/Swoole.

| Metric | `artisan serve` (120 VUs) | Octane/Swoole (120 VUs) |
|---|---|---|
| Avg response time | ~7,300 ms | 81.72 ms |
| p95 response time | ~14,400 ms | 188.11 ms |
| Checkout success | 0% | 94% |
| Timeout errors | Thousands | 0 |

## Environment Configuration

Key `.env` variables for the optimised setup (replace with your own values):

```ini
APP_DEBUG=false
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
DB_HOST=your_db_host
REDIS_HOST=your_redis_host
REDIS_CLIENT=predis
OCTANE_SERVER=swoole
MAIL_MAILER=smtp
MAIL_HOST=your_mail_host
MAIL_USERNAME=your_mail_username
MAIL_PASSWORD=your_mail_password

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
