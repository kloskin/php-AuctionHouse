<?php
require_once __DIR__ . '/../src/db.php';

$manager = getMongoManager();

// 1) Wyczyść stare dane
foreach (['auction.users', 'auction.auctions', 'auction.bids'] as $ns) {
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->delete([]);
    $manager->executeBulkWrite($ns, $bulk);
}
echo "Stare dane wyczyszczone.\n";

// 2) Tworzenie użytkowników
$bulkUsers = new MongoDB\Driver\BulkWrite;
$pwHash = password_hash('password', PASSWORD_BCRYPT);
$users = [
    ['admin','admin@admin.com','admin'],
    ['mod','mod@domain.com','moderator'],
    ['user1','user1@domain.com','user'],
    ['user2','user2@domain.com','user'],
];
foreach ($users as [$u,$e,$r]) {
    $bulkUsers->insert([
        'username'   => $u,
        'email'      => $e,
        'password'   => $pwHash,
        'role'       => $r,
        'created_at' => new MongoDB\BSON\UTCDateTime(),
    ]);
}
$manager->executeBulkWrite('auction.users', $bulkUsers);
echo "Użytkownicy utworzeni.\n";

// 3) Tworzenie aukcji
$bulkAuctions = new MongoDB\Driver\BulkWrite;
for ($i = 1; $i <= 6; $i++) {
    $starting = rand(100, 1000);
    $bulkAuctions->insert([
        'title'          => "Przedmiot #{$i}",
        'description'    => "Opis przedmiotu nr {$i}.",
        'starting_price' => $starting,
        'current_price'  => $starting,
        'starts_at'      => new MongoDB\BSON\UTCDateTime(),
        'ends_at'        => new MongoDB\BSON\UTCDateTime(strtotime("+".rand(1,7)." days")*1000),
        'owner_id'       => random_user_id($manager),
        'images'         => [],
        'status'         => 'active',
    ]);
}
$manager->executeBulkWrite('auction.auctions', $bulkAuctions);
echo "Aukcje utworzone.\n";

// 4) Pobierz aukcje i seeduj bids w porządku rosnącym z zachowaniem sekwencyjnych timestampów
$queryA = new MongoDB\Driver\Query([], ['projection'=>['_id'=>1, 'starting_price'=>1]]);
$aucs = $manager->executeQuery('auction.auctions', $queryA)->toArray();

$bulkBids = new MongoDB\Driver\BulkWrite;
$bulkUpd  = new MongoDB\Driver\BulkWrite;
foreach ($aucs as $a) {
    $prev = $a->starting_price;
    $num  = rand(1,5);
    $interval = 3600; // 1 godzina
    $baseTs = time() - ($num * $interval);
    for ($j = 1; $j <= $num; $j++) {
        $inc    = rand(1,200);
        $amount = $prev + $inc;
        $prev   = $amount;
        $createdAtSec = $baseTs + ($j * $interval);
        $created = new MongoDB\BSON\UTCDateTime($createdAtSec * 1000);
        $bulkBids->insert([
            'auction_id' => $a->_id,
            'user_id'    => random_user_id($manager),
            'amount'     => $amount,
            'created_at' => $created,
        ]);
    }
    // aktualizacja current_price na ostatnią ofertę
    $bulkUpd->update(
        ['_id' => $a->_id],
        ['$set'=>['current_price'=>$prev]]
    );
}
$manager->executeBulkWrite('auction.bids', $bulkBids);
$manager->executeBulkWrite('auction.auctions', $bulkUpd);
echo "Oferty (bids) utworzone i current_price zaktualizowane.\n";

// 5) Indeksy (dopasowane do istniejących lub nowe)
$db = 'auction';
// Usuń indeks 'idx_bid_history' jeśli istnieje, by uniknąć konfliktu
try {
    $manager->executeCommand($db, new MongoDB\Driver\Command([
        'dropIndexes' => 'bids',
        'index'       => 'idx_bid_history'
    ]));
} catch (MongoDB\Driver\Exception\Exception $e) {
    // ignoruj jeżeli nie istnieje
}

// Stwórz indeksy ponownie
$manager->executeCommand($db, new MongoDB\Driver\Command([
    'createIndexes' => 'auctions',
    'indexes' => [
        ['key'=>['ends_at'=>1],'name'=>'ttl_ends_at','expireAfterSeconds'=>0],
        ['key'=>['owner_id'=>1],'name'=>'idx_owner']
    ]
]));
$manager->executeCommand($db, new MongoDB\Driver\Command([
    'createIndexes' => 'bids',
    'indexes' => [[
        'key'=>['auction_id'=>1,'created_at'=>1],
        'name'=>'idx_bid_history'
    ]]
]));
echo "Indeksy utworzone.\n";

/**
 * Zwraca losowe ID użytkownika z kolekcji users.
 */
function random_user_id($manager) {
    static $ids;
    if (!$ids) {
        $q = new MongoDB\Driver\Query([], ['projection'=>['_id'=>1]]);
        $docs = $manager->executeQuery('auction.users', $q)->toArray();
        $ids = array_map(fn($u)=> $u->_id, $docs);
    }
    return $ids[array_rand($ids)];
}
