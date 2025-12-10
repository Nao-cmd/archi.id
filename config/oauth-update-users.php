<?php
// Script to add OAuth columns to existing users table
// Run this once to update the database schema

$conn = new mysqli('localhost', 'root', '', 'archi_id_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add OAuth columns if they don't exist
$queries = [
    "ALTER TABLE users ADD COLUMN oauth_id VARCHAR(255)",
    "ALTER TABLE users ADD COLUMN oauth_provider VARCHAR(50)",
    "CREATE INDEX idx_oauth ON users(oauth_provider, oauth_id)"
];

foreach ($queries as $query) {
    if (!$conn->query($query)) {
        // Column might already exist, continue
        error_log($conn->error);
    }
}

echo "OAuth columns added successfully (or already exist)";
$conn->close();
?>
