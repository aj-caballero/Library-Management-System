<?php
require_once __DIR__ . '/config/config.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN lrn VARCHAR(50) DEFAULT NULL");
    echo "Success! LRN column added to users table.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
