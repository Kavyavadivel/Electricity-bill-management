-- Create database
CREATE DATABASE IF NOT EXISTS electricity_billing;
USE electricity_billing;

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(15) NOT NULL,
    meter_id VARCHAR(20) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin table
CREATE TABLE admin (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL
);

-- Meter readings table
CREATE TABLE meter_readings (
    reading_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    reading_date DATE NOT NULL,
    current_reading DECIMAL(10,2) NOT NULL,
    previous_reading DECIMAL(10,2) NOT NULL,
    units_consumed DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Bills table
CREATE TABLE bills (
    bill_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    reading_id INT,
    bill_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    due_date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (reading_id) REFERENCES meter_readings(reading_id)
);

-- Rate slabs table
CREATE TABLE rate_slabs (
    slab_id INT PRIMARY KEY AUTO_INCREMENT,
    min_units DECIMAL(10,2) NOT NULL,
    max_units DECIMAL(10,2) NOT NULL,
    rate_per_unit DECIMAL(10,2) NOT NULL
);

-- Comments table
CREATE TABLE comments (
    comment_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Complaints table
CREATE TABLE complaints (
    complaint_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    complaint_text TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Insert default admin
INSERT INTO admin (username, password, email) 
VALUES ('admin', '$2y$10$8K1p/a0dR1Ux5Yg3zQb6QOQZQZQZQZQZQZQZQZQZQZQZQZQZQZQZ', 'admin@example.com');

-- Insert default rate slabs
INSERT INTO rate_slabs (min_units, max_units, rate_per_unit) VALUES
(0, 100, 3.50),
(101, 200, 4.50),
(201, 300, 5.50),
(301, 400, 6.50),
(401, 999999, 7.50); 