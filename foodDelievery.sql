CREATE DATABASE IF NOT EXISTS foodDelievery;
USE foodDelievery;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('customer', 'restaurant', 'delivery'),
    phone VARCHAR(20),
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100),
    address TEXT,
    phone VARCHAR(20),
    image VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id)
);



CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT,
    name VARCHAR(100),
    description TEXT,
    price DECIMAL(10, 2),
    available BOOLEAN DEFAULT TRUE,
    image VARCHAR(255),
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id)
);


CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    restaurant_id INT,
    delivery_id INT, -- nullable until assigned
    total DECIMAL(10, 2),
    status ENUM('placed',  'ready', 'picked_up', 'delivered') DEFAULT 'placed',
    customer_latitude DOUBLE,
    customer_longitude DOUBLE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id),
    FOREIGN KEY (delivery_id) REFERENCES users(id)
);


CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    menu_item_id INT,
    quantity INT,
    price DECIMAL(10, 2),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);


CREATE TABLE delivery_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_id INT,
    order_id INT,
    latitude DOUBLE,
    longitude DOUBLE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (delivery_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);


ALTER TABLE users ADD COLUMN address TEXT AFTER phone;