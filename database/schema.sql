-- Attendance Management System Database Schema

CREATE DATABASE IF NOT EXISTS attendance_db;
USE attendance_db;

-- Roles Table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
);

-- Insert Default Roles
INSERT IGNORE INTO roles (role_name) VALUES ('Super Admin'), ('HR'), ('HOD'), ('Employee');

-- Branches Table
CREATE TABLE IF NOT EXISTS branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_name VARCHAR(100) NOT NULL UNIQUE,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    radius_meters INT DEFAULT 500,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Sample Branches
INSERT IGNORE INTO branches (branch_name, latitude, longitude, radius_meters) VALUES ('Main Office', 23.8103, 90.4125, 500);

-- Departments Table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dept_name VARCHAR(100) NOT NULL UNIQUE
);

-- Insert Sample Departments
INSERT IGNORE INTO departments (dept_name) VALUES ('IT'), ('HR'), ('Finance'), ('Marketing'), ('Operations');
-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT,
    dept_id INT,
    branch_id INT,
    designation VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (dept_id) REFERENCES departments(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

-- Leaves Table
CREATE TABLE IF NOT EXISTS leaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    leave_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    hod_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    hr_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    final_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Attendance Table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    attendance_date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    status ENUM('Present', 'Absent', 'Late', 'Leave') DEFAULT 'Present',
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_date (user_id, attendance_date)
);

-- Permissions Table (Simplified for Roles)
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT,
    module_name VARCHAR(100),
    can_view BOOLEAN DEFAULT FALSE,
    can_edit BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Default Super Admin (Password: admin123)
-- Hash: $2y$10$kFhue18jzB7QPXHTge0freo1ixGiX2oXH5tbZIu/I8IH2gnywtnZK
INSERT IGNORE INTO users (name, email, password, role_id, dept_id, branch_id) 
VALUES ('Super Admin', 'admin@example.com', '$2y$10$kFhue18jzB7QPXHTge0freo1ixGiX2oXH5tbZIu/I8IH2gnywtnZK', 1, 1, 1);
