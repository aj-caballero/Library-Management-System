CREATE DATABASE IF NOT EXISTS library_management_system;
USE library_management_system;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    grade_level VARCHAR(20) DEFAULT NULL,
    role ENUM('superadmin', 'admin', 'student') NOT NULL DEFAULT 'student',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(150) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    grade_level VARCHAR(20) NOT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_books_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reading_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reading_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reading_logs_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    generated_by INT NOT NULL,
    report_type VARCHAR(100) NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reports_generated_by FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    activity VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_system_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    system_name VARCHAR(150) NOT NULL DEFAULT 'Online Library System',
    school_name VARCHAR(150) NOT NULL DEFAULT 'Paliparan National High School',
    logo_path VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO system_settings (system_name, school_name)
SELECT 'Online Library System', 'Paliparan National High School'
WHERE NOT EXISTS (SELECT 1 FROM system_settings);

INSERT IGNORE INTO users (id, fullname, email, password, grade_level, role) VALUES
(1, 'System Super Admin', 'superadmin@pnhs.edu.ph', '$2y$10$DVjvT3WR7G.T818/lb.58.5t7OeLXo3K8uDBIxEgJ/9JgrMb6GQ5W', NULL, 'superadmin'),
(2, 'Library Administrator', 'admin@pnhs.edu.ph', '$2y$10$DVjvT3WR7G.T818/lb.58.5t7OeLXo3K8uDBIxEgJ/9JgrMb6GQ5W', NULL, 'admin'),
(3, 'Aira Dela Cruz', 'aira.delacruz@pnhs.edu.ph', '$2y$10$DVjvT3WR7G.T818/lb.58.5t7OeLXo3K8uDBIxEgJ/9JgrMb6GQ5W', 'Grade 8', 'student'),
(4, 'Miguel Santos', 'miguel.santos@pnhs.edu.ph', '$2y$10$DVjvT3WR7G.T818/lb.58.5t7OeLXo3K8uDBIxEgJ/9JgrMb6GQ5W', 'Grade 7', 'student');

INSERT IGNORE INTO books (id, title, author, subject, grade_level, cover_image, file_path, status, uploaded_by) VALUES
(1, 'Science Explorers: Grade 7', 'L. Mendoza', 'Science', 'Grade 7', 'science-grade7.jpg', 'science-grade7.pdf', 'active', 2),
(2, 'English Communication Skills: Grade 8', 'R. Flores', 'English', 'Grade 8', 'english-grade8.jpg', 'english-grade8.pdf', 'active', 2),
(3, 'Mathematics in Action: Grade 9', 'D. Aquino', 'Mathematics', 'Grade 9', 'math-grade9.jpg', 'math-grade9.pdf', 'active', 2),
(4, 'Araling Panlipunan: Grade 10', 'C. Reyes', 'Araling Panlipunan', 'Grade 10', 'ap-grade10.jpg', 'ap-grade10.pdf', 'active', 2);
