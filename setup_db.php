<?php
require_once __DIR__ . '/config/database.php';
$sql = file_get_contents(__DIR__ . '/database/init.sql');
try {
    $pdo->exec($sql);
    echo "Database tables created successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
