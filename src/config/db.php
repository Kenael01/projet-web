<?php
// config/db.php - Configuration de la base de données

define('DB_HOST', 'localhost');
define('DB_USER', 'kenav');
define('DB_PASS', 'azerty12345');
define('DB_NAME', 'cesi_bike');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>