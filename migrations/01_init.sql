-- --- filename: migrations/01_init.sql ---
CREATE DATABASE IF NOT EXISTS time_tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE time_tracking;

-- Сотрудники
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'director', 'senior_nurse', 'employee') NOT NULL,
    position VARCHAR(50),
    department VARCHAR(50),
    hire_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- График работы (месяц + смены)
CREATE TABLE work_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    shift_type ENUM('day', 'night', 'off') NOT NULL DEFAULT 'off',
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_date_employee (employee_id, date)
);

-- Заявки на отпуск
CREATE TABLE vacation_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES employees(id) ON DELETE SET NULL
);

-- Нормы и настройки (коэффициенты, часы)
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(50) UNIQUE NOT NULL,
    value JSON NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Вставляем администратора (пароль: admin123)
INSERT INTO employees (full_name, phone, password_hash, role, position, department, hire_date)
VALUES (
    'Администратор',
    '79991234567',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- hash от "admin123"
    'admin',
    'Системный администратор',
    'IT',
    '2024-01-01'
);
