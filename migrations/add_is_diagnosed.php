<?php
/**
 * Migration: Add is_diagnosed column to appointments table
 * This script adds the is_diagnosed column to track whether a consultation has been diagnosed
 */

session_start();
require_once __DIR__ . '/../dbconnect.php';

try {
    // Check if column already exists
    $checkCol = "SHOW COLUMNS FROM appointments LIKE 'is_diagnosed'";
    $result = $conn->query($checkCol);
    
    if ($result->num_rows === 0) {
        // Add the column if it doesn't exist
        $altQuery = "ALTER TABLE appointments ADD COLUMN is_diagnosed TINYINT(1) DEFAULT 0 AFTER status";
        if ($conn->query($altQuery)) {
            echo "Migration successful: is_diagnosed column added to appointments table.";
        } else {
            echo "Error adding column: " . $conn->error;
        }
    } else {
        echo "Column is_diagnosed already exists.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
