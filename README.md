# High-Performance E-Commerce Backend Engine

<p align="center">
  <img src="https://laravel.com/img/logomark.min.svg" alt="Laravel" height="100"/>
  <img src="https://redis.io/wp-content/uploads/2024/04/Logotype.svg?auto=webp&quality=85,75&width=120" alt="Redis" height="80"/>
  <img src="https://nginx.org/nginx.png" alt="Nginx" height="80"/>
</p>

<p align="center">

![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)
![Redis](https://img.shields.io/badge/Redis-7.x-DC382D?logo=redis)
![Nginx](https://img.shields.io/badge/Nginx-1.30-009639?logo=nginx)
![JWT](https://img.shields.io/badge/JWT-Authentication-black?logo=jsonwebtokens)
![License](https://img.shields.io/badge/License-MIT-green)

</p>

---

# Table of Contents

- [Project Overview](#project-overview)
- [System Architecture](#system-architecture)
- [Functional Features](#functional-features)
- [Non-Functional Requirements](#non-functional-requirements)
  - [Concurrent Access & Data Integrity](#concurrent-access--data-integrity)
  - [Rate Limiting & Resource Management](#rate-limiting--resource-management)
  - [Asynchronous Processing](#asynchronous-processing)
  - [Batch Processing](#batch-processing)
  - [Load Balancing](#load-balancing)
  - [Distributed Caching](#distributed-caching)
- [Technology Stack](#technology-stack)
- [Installation & Setup](#installation--setup)
- [Environment Configuration](#environment-configuration)
- [Queue Configuration](#queue-configuration)
- [API Endpoints](#api-endpoints)
- [Project Structure](#project-structure)
- [Performance Monitoring](#performance-monitoring)
- [Testing](#testing)
- [Future Improvements](#future-improvements)
- [References](#references)
- [License](#license)

---

# Project Overview

The **High-Performance E-Commerce Backend Engine** is a scalable backend application built using Laravel 11.

The project demonstrates how to build an e-commerce system capable of handling thousands of concurrent requests while maintaining:

- Data consistency
- Fault tolerance
- Scalability
- Low latency
- Efficient resource utilization

The primary objective is to implement and evaluate critical non-functional requirements commonly found in large-scale production systems.

---

# System Architecture

```text
                    ┌───────────────────┐
                    │      Client       │
                    └─────────┬─────────┘
                              │
                              ▼
                    ┌───────────────────┐
                    │       Nginx       │
                    │ Load Balancer     │
                    └─────────┬─────────┘
                              │
          ┌───────────────────┼───────────────────┐
          ▼                   ▼                   ▼

 ┌──────────────┐   ┌──────────────┐   ┌──────────────┐
 │ Laravel App  │   │ Laravel App  │   │ Laravel App  │
 │   Instance1  │   │   Instance2  │   │   Instance3  │
 └──────┬───────┘   └──────┬───────┘   └──────┬───────┘
        │                  │                  │
        └──────────────────┼──────────────────┘
                           │
                           ▼

                 ┌─────────────────┐
                 │      Redis      │
                 │ Cache + Queues  │
                 └────────┬────────┘
                          │
                          ▼

                 ┌─────────────────┐
                 │      MySQL      │
                 │   Database      │
                 └─────────────────┘
```

---

# Functional Features

## Authentication & Authorization

- User registration
- Login
- JWT authentication
- Token refresh
- Logout
- Role management
- Permission management

## Product Management

- Create products
- Update products
- Delete products
- Inventory management
- Product caching

## Shopping Cart

- Redis-based cart storage
- Add item
- Remove item
- Update quantity
- Clear cart
- Checkout

## Order Management

- Create order
- Confirm order
- Cancel order
- Track status
- Safe and unsafe processing modes

## Invoice Management

- Automatic invoice generation
- Invoice storage
- Invoice retrieval

## Email Notifications

- Order confirmation emails
- Queue-based email delivery
- Retry support

## Reporting

- Daily sales reports
- Monthly sales reports
- Excel export

---

# Non-Functional Requirements

## Concurrent Access & Data Integrity

The system provides two order confirmation strategies.

### Unsafe Strategy

Uses normal database reads without locking.

```php
$product = Product::find($id);

$product->decrement('stock', $quantity);
```

Potential issue:

- Race conditions
- Overselling

### Safe Strategy

Uses transactions and pessimistic locking.

```php
return DB::transaction(function () use ($order, $items) {

    foreach ($items as $item) {

        $product = Product::lockForUpdate()
            ->find($item['product_id']);

        if ($product->stock < $item['quantity']) {
            throw new Exception("Insufficient stock");
        }

        $product->decrement('stock', $item['quantity']);
    }

}, 5);
```

Benefits:

- Prevents overselling
- Ensures atomic updates
- Guarantees consistency

---

## Rate Limiting & Resource Management

Custom Laravel rate limiters are implemented.

| Limiter | Limit |
|----------|--------|
| api | 60 requests/minute |
| orders | 10 requests/minute |
| orders-queue | 10 requests/minute |

Example:

```php
RateLimiter::for('orders', function (Request $request) {

    return Limit::perMinute(10)
        ->by($request->user()?->id ?: $request->ip());

});
```

Benefits:

- Protects resources
- Prevents abuse
- Improves system stability

---

## Asynchronous Processing

The system uses Laravel Queues to move heavy tasks outside the request lifecycle.

### Queue Workflow

```text
Checkout
   │
   ▼
Create Pending Order
   │
   ▼
ProcessOrderJob
   │
   ▼
CreateInvoiceJob
   │
   ▼
SendOrderEmailJob
```

### Jobs

| Job | Queue |
|-------|---------|
| ProcessOrderJob | orders |
| CreateInvoiceJob | invoices |
| SendOrderEmailJob | notifications |
| ProcessDailySalesJob | sales |
| GenerateMonthlySalesReportJob | reports |

Benefits:

- Faster response times
- Better scalability
- Improved user experience

---

## Batch Processing

### Without Chunking

```php
$orders = Order::all();
```

Loads all records into memory.

### With Chunking

```php
Order::chunk(100, function ($orders) {

    foreach ($orders as $order) {

        // Process order

    }

});
```

Benefits:

- Lower memory consumption
- Better performance
- Improved scalability

---

## Load Balancing

### Round Robin

```nginx
upstream laravel_backend {

    server 127.0.0.1:8001;
    server 127.0.0.1:8002;
    server 127.0.0.1:8003;

}
```

### Least Connections

```nginx
upstream laravel_backend {

    least_conn;

    server 127.0.0.1:8001;
    server 127.0.0.1:8002;
    server 127.0.0.1:8003;

}
```

### Why Least Connections?

Because:

- Requests have different execution times
- Checkout requests are heavier than product browsing
- Better real-world load distribution

---

## Distributed Caching

### Shopping Cart Cache

```text
cart:user:{id}
```

Storage:

```text
Redis Hash
```

TTL:

```text
7 Days
```

### Trending Products

```text
popular_products
```

Storage:

```text
Redis Sorted Set
```

Top products cache:

```text
top_products_list
```

TTL:

```text
1 Hour
```

### Product Cache

```php
Cache::tags(['products']);
```

Benefits:

- Reduced database queries
- Faster responses
- Improved scalability

---

# Technology Stack

| Component | Technology |
|------------|-------------|
| Framework | Laravel 11 |
| Language | PHP 8.2+ |
| Database | MySQL |
| Cache | Redis |
| Queue | Redis / Database |
| Authentication | JWT |
| Permissions | Spatie Permission |
| Reports | Laravel Excel |
| Load Balancer | Nginx |
| Mail | SMTP |

---

# Installation & Setup

## Clone Repository

```bash
git clone https://github.com/your-org/HighPerformanceECommerceBackendEngine.git

cd HighPerformanceECommerceBackendEngine
```

## Install Dependencies

```bash
composer install
```

## Create Environment File

```bash
cp .env.example .env
```

## Generate Keys

```bash
php artisan key:generate

php artisan jwt:secret
```

## Database Migration

```bash
php artisan migrate:fresh --seed
```

---

# Environment Configuration

```env
APP_NAME=ECommerce

CACHE_DRIVER=redis

QUEUE_CONNECTION=database

SESSION_DRIVER=redis

REDIS_CLIENT=predis

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525

JWT_SECRET=YOUR_SECRET_KEY
```

---

# Queue Configuration

Start worker:

```bash
php artisan queue:work \
--queue=orders,invoices,notifications,reports,sales \
--tries=5
```

---

# API Endpoints

## Authentication

| Method | Endpoint |
|----------|----------|
| POST | /api/register |
| POST | /api/login |
| GET | /api/me |
| POST | /api/logout |
| POST | /api/refresh |

## Products

| Method | Endpoint |
|----------|----------|
| GET | /api/products |
| GET | /api/products/{id} |
| POST | /api/products |
| PUT | /api/products/{id} |
| DELETE | /api/products/{id} |
| GET | /api/products/top |

## Cart

| Method | Endpoint |
|----------|----------|
| GET | /api/cart |
| POST | /api/cart/add |
| PUT | /api/cart/update |
| POST | /api/cart/remove |
| POST | /api/cart/clear |
| POST | /api/cart/checkout |

## Orders

| Method | Endpoint |
|----------|----------|
| GET | /api/orders |
| GET | /api/orders/my |
| GET | /api/orders/{id} |
| PUT | /api/orders/{id} |
| DELETE | /api/orders/{id} |
| POST | /api/orders/cancel |

---

# Project Structure

```text
app/

├── Console/
├── Contracts/
├── DTOs/
├── Exports/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Mail/
├── Models/
├── Policies/
├── Repositories/
├── Services/
├── Traits/
└── Exceptions/
```

---

# Performance Monitoring

The system includes a custom middleware that records:

- Request duration
- Memory usage
- Database query count
- Slowest query

Response headers:

```text
X-Trace-Id
X-Response-Time-ms
X-Memory-Used-kb
```

---

# Testing

## Performance Testing

```bash
php artisan test:performance
```

## Scenario Testing

```bash
php artisan test:case
```

Scenarios:

- Race condition simulation
- Rate limiting validation
- Queue testing
- Cache testing

---

# Future Improvements

- Kubernetes deployment
- Docker support
- Elasticsearch integration
- RabbitMQ support
- Prometheus monitoring
- Grafana dashboards
- Distributed tracing
- Multi-region deployment

---

# References

- Laravel Documentation
- Laravel Queue Documentation
- Laravel Rate Limiting
- Redis Documentation
- Redis Sorted Sets
- JWT Auth
- Spatie Laravel Permission
- Laravel Excel
- Nginx Load Balancing
- Memurai

---

# License

This project is licensed under the MIT License.

---

Developed as a High-Performance Distributed E-Commerce Backend System using Laravel, Redis, Queues, Caching, and Load Balancing concepts.
