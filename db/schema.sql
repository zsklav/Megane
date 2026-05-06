-- Eyewear Inventory & Sales Management System
-- Schema + seed data

CREATE DATABASE IF NOT EXISTS eyewear_db;
USE eyewear_db;

DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(120),
    email VARCHAR(150),
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type ENUM('Frame', 'Lens') NOT NULL,
    brand VARCHAR(100),
    style VARCHAR(80),
    material VARCHAR(80),
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150),
    phone VARCHAR(30),
    address VARCHAR(300),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

INSERT INTO products (name, type, brand, style, material, price, stock, image_url) VALUES
('Aviator Classic Gold', 'Frame', 'Ray-Ban', 'Aviator', 'Metal', 14999.00, 25, 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=400'),
('Wayfarer Original Black', 'Frame', 'Ray-Ban', 'Wayfarer', 'Acetate', 15999.00, 30, 'https://images.unsplash.com/photo-1577803645773-f96470509666?w=400'),
('Round Metal Tortoise', 'Frame', 'Oakley', 'Round', 'Titanium', 19999.00, 15, 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=400'),
('Cat Eye Vintage', 'Frame', 'Prada', 'Cat Eye', 'Acetate', 24999.00, 12, 'https://images.unsplash.com/photo-1556306535-0f09a537f0a3?w=400'),
('Square Modern Matte', 'Frame', 'Tom Ford', 'Square', 'Metal', 27999.00, 20, 'https://images.unsplash.com/photo-1574258495973-f010dfbb5371?w=400'),
('Rimless Titanium Pro', 'Frame', 'Silhouette', 'Rimless', 'Titanium', 32999.00, 8, 'https://images.unsplash.com/photo-1591076482161-42ce6da69f67?w=400'),
('Single Vision Lens', 'Lens', 'Zeiss', 'Single Vision', NULL, 8999.00, 100, NULL),
('Progressive Lens', 'Lens', 'Essilor', 'Progressive', NULL, 19999.00, 80, NULL),
('Bifocal Lens', 'Lens', 'Hoya', 'Bifocal', NULL, 12999.00, 60, NULL),
('Anti-Blue Light Lens', 'Lens', 'Zeiss', 'Single Vision', NULL, 11999.00, 75, NULL),
('Photochromic Lens', 'Lens', 'Transitions', 'Single Vision', NULL, 15999.00, 50, NULL),
('Polarized Sunglasses Lens', 'Lens', 'Carl Zeiss', 'Single Vision', NULL, 13999.00, 40, NULL);

INSERT INTO customers (name, email, phone, address) VALUES
('Aiko Tanaka', 'aiko.tanaka@example.com', '+81-90-1234-5678', '2-1-1 Shibuya, Tokyo, Japan'),
('Hiroshi Nakamura', 'h.nakamura@example.com', '+81-80-2345-6789', '4-3-2 Umeda, Osaka, Japan'),
('Priya Sharma', 'priya.sharma@example.com', '+91-98765-43210', 'Bandra West, Mumbai, India'),
('John Smith', 'john.smith@example.com', '+1-555-123-4567', '500 5th Ave, New York, USA'),
('Yuki Sato', 'yuki.sato@example.com', '+81-70-3456-7890', '1-5-9 Naka-ku, Yokohama, Japan'),
('Ananya Verma', 'ananya.v@example.com', '+91-99887-66554', 'Koramangala, Bangalore, India');

INSERT INTO orders (customer_id, product_id, quantity, total_price) VALUES
(1, 1, 1, 14999.00),
(1, 7, 2, 17998.00),
(2, 3, 1, 19999.00),
(3, 2, 1, 15999.00),
(4, 8, 1, 19999.00),
(5, 4, 1, 24999.00),
(5, 10, 2, 23998.00),
(6, 5, 1, 27999.00);
