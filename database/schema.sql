-- ไอย๊าห์ Iya Database Schema
CREATE DATABASE IF NOT EXISTS iya_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE iya_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Issues table
CREATE TABLE issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    image_path VARCHAR(500),
    status ENUM('pending', 'in_progress', 'resolved', 'closed') DEFAULT 'pending',
    admin_note TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    admin_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Equipment table
CREATE TABLE equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    description TEXT,
    image_path VARCHAR(500),
    quantity INT DEFAULT 0,
    available INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Borrows table
CREATE TABLE borrows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    equipment_id INT NOT NULL,
    quantity INT DEFAULT 1,
    borrow_date DATE NOT NULL,
    return_date DATE,
    actual_return_date DATE,
    reason TEXT,
    status ENUM('pending', 'approved', 'returned', 'rejected') DEFAULT 'pending',
    admin_note TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed admin user (password: admin123)
INSERT INTO users (email, password, role) VALUES ('admin@iya.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Seed categories
INSERT INTO equipment (name, category, description, quantity, available) VALUES
('เมาส์ Logitech M330', 'อุปกรณ์คอมพิวเตอร์', 'เมาส์ไร้สาย Logitech M330 Silent Plus', 10, 8),
('คีย์บอร์ด Logitech K380', 'อุปกรณ์คอมพิวเตอร์', 'คีย์บอร์ดบลูทูธ Logitech K380', 5, 3),
('สาย HDMI 2m', 'สายเคเบิล', 'สาย HDMI 2.0 ความยาว 2 เมตร', 20, 15),
('USB Hub 4 ports', 'อุปกรณ์เสริม', 'USB Hub 3.0 แบบ 4 พอร์ต', 8, 6),
('หูฟัง JBL Tune 510BT', 'อุปกรณ์เสริม', 'หูฟังไร้สาย JBL', 6, 4),
('เว็บแคม Logitech C920', 'อุปกรณ์คอมพิวเตอร์', 'เว็บแคม Full HD 1080p', 4, 2),
('ปลั๊กพ่วง 6 ช่อง', 'อุปกรณ์ไฟฟ้า', 'ปลั๊กพ่วงมีสวิตช์ 6 ช่อง 3 เมตร', 15, 12),
('สาย LAN Cat6 5m', 'สายเคเบิล', 'สาย LAN Cat6 ความยาว 5 เมตร', 30, 25);
