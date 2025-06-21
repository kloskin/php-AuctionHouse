<?php
require_once __DIR__ . '/../src/db.php';

// Tworzenie przykładowego użytkownika
$bulk = new MongoDB\Driver\BulkWrite;

$bulk->insert([
    'username' => 'admin',
    'email' => 'admin@example.com',
    'password' => password_hash('admin123', PASSWORD_BCRYPT),
    'created_at' => new MongoDB\BSON\UTCDateTime()
]);

// Wstawienie do kolekcji users
$mongo->executeBulkWrite('auction.users', $bulk);

// Dodanie przykładowej aukcji
$bulk = new MongoDB\Driver\BulkWrite;

$bulk->insert([
    'title' => 'Laptop Dell',
    'description' => 'Używany laptop, stan dobry',
    'starting_price' => 500,
    'current_price' => 500,
    'ends_at' => new MongoDB\BSON\UTCDateTime(strtotime('+3 days') * 1000),
    'owner_id' => null,
    'images' => [],
    'status' => 'active'
]);

$mongo->executeBulkWrite('auction.auctions', $bulk);

echo "MongoDB zainicjalizowane\n";
?>
