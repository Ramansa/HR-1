CREATE DATABASE IF NOT EXISTS hr_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hr_system;

CREATE TABLE IF NOT EXISTS hr_roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS hr_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES hr_roles(id)
);

CREATE TABLE IF NOT EXISTS hr_departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS hr_employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_code VARCHAR(40) NOT NULL UNIQUE,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(50),
  department_id INT,
  position VARCHAR(120) NOT NULL,
  hire_date DATE NOT NULL,
  status ENUM('Active','Inactive') DEFAULT 'Active',
  manager_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES hr_departments(id)
);

CREATE TABLE IF NOT EXISTS hr_attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  attendance_date DATE NOT NULL,
  check_in TIME,
  check_out TIME,
  status ENUM('Present','Absent','Remote') DEFAULT 'Present',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES hr_employees(id)
);

CREATE TABLE IF NOT EXISTS hr_leaves (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  leave_type VARCHAR(80) NOT NULL,
  date_from DATE NOT NULL,
  date_to DATE NOT NULL,
  reason TEXT,
  status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
  approved_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES hr_employees(id)
);

CREATE TABLE IF NOT EXISTS hr_payroll (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  pay_period CHAR(7) NOT NULL,
  basic_salary DECIMAL(12,2) NOT NULL,
  allowance DECIMAL(12,2) NOT NULL DEFAULT 0,
  deduction DECIMAL(12,2) NOT NULL DEFAULT 0,
  net_salary DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES hr_employees(id)
);

INSERT IGNORE INTO hr_roles (id,name) VALUES
(1,'Admin'),(2,'HR Manager'),(3,'Payroll Officer'),(4,'Team Lead'),(5,'Employee');

INSERT IGNORE INTO hr_departments (id,name) VALUES
(1,'Human Resources'),(2,'Finance'),(3,'Engineering'),(4,'Sales'),(5,'Operations');

INSERT IGNORE INTO hr_users (id, full_name, email, password_hash, role_id, is_active)
VALUES (1, 'System Administrator', 'admin@company.com', '$2y$10$4p30VvGQkXjgp0YhG65J6uEpgVUQw0xNQSa8CaIrScMvwYXAWi8rS', 1, 1);
-- password: password
