# Megane — Eyewear Inventory & Sales Management System

> *Megane (めがね)* — Japanese for "glasses".

A full-stack web application for managing an eyewear retail business — inventory (frames & lenses), customer records, sales, and users — built with **HTML, CSS, JavaScript, PHP, and MySQL**, fully containerized with Docker. Includes session-based authentication, role-based access control, and sales analytics.

## Demo Credentials

| Role | Username | Password | Access |
|---|---|---|---|
| Admin | `admin` | `admin123` | Full access — all CRUD + user management |
| Staff | `staff` | `staff123` | View inventory + record sales (no delete/edit) |

## Features

### Core Operations
- **Dashboard** — at-a-glance summary of products, customers, orders, revenue, stock; **sales-trend line chart** (last 14 days) and **revenue-by-type doughnut chart**; top-selling products and low-stock alerts
- **Inventory Management** — full CRUD for eyewear products (frames & lenses) with type/brand/style/material/price/stock fields, image thumbnails, search, and type filter
- **Customer Management** — full CRUD with order count and lifetime spend per customer
- **Sales Management** — record sales with live total preview; stock auto-decrements via DB transactions; restoring stock on order deletion

### Authentication & Authorization
- **Session-based login** with bcrypt password hashing (`password_hash` / `password_verify`)
- **Role-based access control** — `admin` (full access) vs `staff` (read + sales only)
- **Admin-only user management page** — create, edit, delete users; reset passwords; assign roles
- **Last-login tracking**, session regeneration on auth, secure logout
- **Self-protection** — users cannot delete their own account

### UX Polish
- Clean, minimal Japanese-inspired design system
- User menu with avatar, role badge, profile info
- Live search/filter, animated flash messages, hover states, smooth transitions
- Responsive on mobile

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Vanilla JavaScript, Chart.js |
| Backend | PHP 8.2 (PDO with prepared statements) |
| Database | MySQL 8.0 |
| Server | Apache (via official `php:8.2-apache` image) |
| Auth | Session-based, bcrypt password hashing |
| Containerization | Docker + Docker Compose (Podman-compatible) |

## Project Structure

```
eyewear-management-system/
├── docker-compose.yml          # Multi-container orchestration
├── docker/Dockerfile           # PHP 8.2 + Apache image
├── db/schema.sql               # Schema + seed data
└── public/                     # Web root
    ├── auth.php                # Sessions, role checks, default-user seeding
    ├── login.php / logout.php  # Authentication endpoints
    ├── db.php                  # PDO connection (with retry logic)
    ├── header.php / footer.php # Shared layout
    ├── index.php               # Dashboard (stats + charts)
    ├── inventory.php           # Product CRUD (admin) / view (staff)
    ├── customers.php           # Customer CRUD
    ├── sales.php               # Order entry + history
    ├── users.php               # User management (admin only)
    ├── css/style.css           # Styles
    └── js/app.js               # Validation, live total, Chart.js init
```

## Getting Started

### Prerequisites
- Docker & Docker Compose, **OR** Podman + podman-compose (Fedora-friendly)

### Run with Docker

```bash
docker compose up --build
```

### Run with Podman (Fedora)

```bash
podman-compose up --build
```

Then open: **http://localhost:8080**

The first run will:
1. Build the PHP/Apache image
2. Pull MySQL 8.0
3. Initialize the database with schema and seed data (12 products, 6 customers, 8 orders)
4. Auto-seed two default users (admin + staff) on first request

### Stop / reset

```bash
podman-compose down       # stop containers
podman-compose down -v    # stop + wipe database volume (full reset)
```

## Database Schema

Four normalized tables with foreign-key constraints:

| Table | Key columns |
|---|---|
| `users` | id, username, password_hash, full_name, email, role (`admin`/`staff`), last_login |
| `products` | id, name, type (`Frame`/`Lens`), brand, style, material, price, stock, image_url |
| `customers` | id, name, email, phone, address |
| `orders` | id, customer_id, product_id, quantity, total_price, order_date |

Sales transactions use **database transactions** to atomically insert the order and decrement stock; deletions reverse the stock change.

## Security

- All database queries use **PDO prepared statements** (no SQL injection)
- All user input is escaped with `htmlspecialchars()` on output (no XSS)
- Passwords hashed with **bcrypt** (`PASSWORD_DEFAULT`); never stored or logged in plain text
- Session ID regenerated on login (mitigates session fixation)
- HTTP-only session cookies cleared on logout
- Server-side role enforcement on every protected endpoint (`require_auth`, `require_admin`)

## Future Enhancements

- Multi-product orders / shopping cart
- PDF invoice generation
- Product image upload (vs URL-based)
- Email notifications on low stock
- REST API for mobile clients
- 2FA for admin accounts

## Author

**Vanshikha Sri** — Built as a full-stack management system project demonstrating PHP/MySQL CRUD, authentication, role-based access control, frontend implementation, data visualization, and Docker-based deployment.
