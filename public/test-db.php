<?php
require_once 'includes/db.php';

try {
    $stmt = $pdo->query('SELECT * FROM users');
    $users = $stmt->fetchAll();
    echo '<pre>';
    print_r($users);
    echo '</pre>';
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}