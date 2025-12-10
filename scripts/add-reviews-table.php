<?php
// Script to add reviews table if it doesn't exist (for fresh installations)

$conn = new mysqli('localhost', 'root', '', 'archi_id_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = "CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_project_id (project_id),
    INDEX idx_user_id (user_id)
)";

if ($conn->query($query)) {
    echo "Reviews table created successfully or already exists";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
