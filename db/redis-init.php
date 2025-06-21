<?php
require_once __DIR__ . '/../src/db.php';

// Wyczyść przykładowe dane
$redis->flushAll();

// Dodaj przykładowe liczniki/klucze
$redis->set('site:visits', 0);
$redis->set('popular:auction:1', 0);

// Sorted Set – symulacja ofert na aukcji 1
$redis->zAdd('auction:1:bids', time(), json_encode([
    'user_id' => 1,
    'amount' => 600,
    'timestamp' => time()
]));

echo "Redis zainicjalizowany\n";
?>
