<?php

declare(strict_types=1);

/**
 * Database Migration: Add inactivity tracking columns
 * Run this once to update your database schema
 */

require_once __DIR__ . '/config/config.php';

try {
    // Check if columns already exist
    $checkResult = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login_at'");
    if ($checkResult->rowCount() > 0) {
        echo "Migration already applied. Columns exist.\n";
        exit;
    }

    echo "Starting migration...\n";

    // Add new columns to users table
    $migrations = [
        "ALTER TABLE users ADD COLUMN last_login_at DATETIME DEFAULT NULL AFTER created_at",
        "ALTER TABLE users ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active",
        "ALTER TABLE users ADD COLUMN archived_reason VARCHAR(255) DEFAULT NULL AFTER is_archived",
        "ALTER TABLE users ADD COLUMN inactivity_warning_sent_at DATETIME DEFAULT NULL AFTER archived_reason",
    ];

    foreach ($migrations as $sql) {
        $pdo->exec($sql);
        echo "✓ Executed: " . substr($sql, 0, 50) . "...\n";
    }

    echo "\n✓ Migration completed successfully!\n";
    echo "New columns added to users table:\n";
    echo "  - last_login_at\n";
    echo "  - is_archived\n";
    echo "  - archived_reason\n";
    echo "  - inactivity_warning_sent_at\n";

} catch (PDOException $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>
