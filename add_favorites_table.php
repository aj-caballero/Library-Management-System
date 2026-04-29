<?php
require_once __DIR__ . '/config/config.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, book_id INT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY user_book_unique (user_id, book_id));");
    echo "Success!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
