<?php
try {
    $pdo = new PDO("mysql:host=db;dbname=service_center", "user", "password");
    echo "Connected to database!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}