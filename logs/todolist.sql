CREATE DATABASE IF NOT EXISTS todolist_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE todolist_db;

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_tugas VARCHAR(255) NOT NULL,
    status ENUM('selesai', 'belum selesai') NOT NULL DEFAULT 'belum selesai',
    prioritas ENUM('tinggi', 'sedang', 'rendah') NOT NULL,
    tanggal DATE NOT NULL,
    deadline DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS deleted_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT NOT NULL,
    nama_tugas VARCHAR(255) NOT NULL,
    status ENUM('selesai', 'belum selesai') NOT NULL,
    prioritas ENUM('tinggi', 'sedang', 'rendah') NOT NULL,
    tanggal DATE NOT NULL,
    deadline DATE NOT NULL,
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_by_ip VARCHAR(45)
);

CREATE INDEX idx_status ON tasks(status);
CREATE INDEX idx_prioritas ON tasks(prioritas);
CREATE INDEX idx_deadline ON tasks(deadline);
CREATE INDEX idx_created_at ON tasks(created_at);

CREATE INDEX idx_deleted_at ON deleted_tasks(deleted_at);
CREATE INDEX idx_original_id ON deleted_tasks(original_id);

INSERT INTO tasks (nama_tugas, status, prioritas, tanggal, deadline) VALUES
('Menyelesaikan laporan bulanan', 'belum selesai', 'tinggi', '2025-01-15', '2025-01-20'),
('Meeting dengan tim development', 'belum selesai', 'sedang', '2025-01-16', '2025-01-18'),
('Review kode aplikasi', 'selesai', 'tinggi', '2025-01-10', '2025-01-15'),
('Backup database server', 'belum selesai', 'rendah', '2025-01-17', '2025-01-25'),
('Pembaruan dokumentasi API', 'belum selesai', 'sedang', '2025-01-18', '2025-01-22'),
('Testing aplikasi mobile', 'selesai', 'tinggi', '2025-01-12', '2025-01-16');

DESCRIBE tasks;
DESCRIBE deleted_tasks;

SELECT 
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN status = 'belum selesai' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN deadline < CURDATE() AND status != 'selesai' THEN 1 ELSE 0 END) as overdue_tasks
FROM tasks;