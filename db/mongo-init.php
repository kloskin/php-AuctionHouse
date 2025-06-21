<?php
require_once __DIR__ . '/../src/db.php';

// Inicjalizacja MongoDB
$manager = getMongoManager();

// Tworzenie przykładowego użytkownika
$bulkUsers = new MongoDB\Driver\BulkWrite;
$passwordHash = password_hash('password', PASSWORD_BCRYPT);

$users = [
    ['username' => 'admin', 'email' => 'admin@admin.com', 'role' => 'admin'],
    ['username' => 'moderator', 'email' => 'moderator@domain.com', 'role' => 'moderator'],
    ['username' => 'user', 'email' => 'user@domain.com', 'role' => 'user'],
];


foreach ($users as $u) {
    $bulkUsers->insert([
        'username'=> $u['username'],
        'email'      => $u['email'],
        'password'   => $passwordHash,
        'role'       => $u['role'],
        'created_at' => new MongoDB\BSON\UTCDateTime(),
    ]);
}

$manager->executeBulkWrite('auction.users', $bulkUsers);
echo "Użytkownicy utworzeni poprawnie.\n";

// 2. Dodanie przykładowej aukcji (własność pierwszego użytkownika)
// Pobranie ID admina
$queryAdmin = new MongoDB\Driver\Query(['email' => 'admin@admin.com'], ['limit' => 1]);
$admin = $manager->executeQuery('auction.users', $queryAdmin)->toArray()[0] ?? null;

$ownerId = $admin ? $admin->_id : null;

$bulkAuction = new MongoDB\Driver\BulkWrite;
$bulkAuction->insert([
    'title'          => 'Laptop Dell',
    'description'    => 'Używany laptop, stan dobry',
    'starting_price' => 500,
    'current_price'  => 500,
    'starts_at'      => new MongoDB\BSON\UTCDateTime(),
    'ends_at'        => new MongoDB\BSON\UTCDateTime(strtotime('+3 days') * 1000),
    'owner_id'       => $ownerId,
    'images'         => [],
    'status'         => 'active',
]);

$manager->executeBulkWrite('auction.auctions', $bulkAuction);
echo "Przykładowa aukcja utworzona.\n";

?>
