<?php
require_once __DIR__ . '/../src/db.php';

$manager = getMongoManager();
$redis   = getRedisClient();

// 1) Wyczyść cały Redis
$redis->flushAll();

echo "Redis: stary cache wyczyszczony.\n";

// 2) Licznik wizyt strony
$redis->set('site:visits', 0);

echo "Redis: site:visits ustawiony na 0.\n";

// 3) Ranking wyświetleń (przykładowe dane)
// Pobierz wszystkie aukcje by mieć ich ID
$queryAucs = new MongoDB\Driver\Query([], ['projection'=>['_id'=>1]]);
$aucDocs   = $manager->executeQuery('auction.auctions', $queryAucs)->toArray();

foreach ($aucDocs as $auc) {
    $aid    = (string)$auc->_id;
    $views  = rand(0, 500);
    $redis->zAdd('auction:views', $views, $aid);
}

echo "Redis: auction:views zainicjalizowane.\n";

// 4) Statystyki dzienne (ostatnie 3 dni)
for ($d = 0; $d < 3; $d++) {
    $dateKey = date('Y-m-d', strtotime("-{$d} days"));
    foreach ($aucDocs as $auc) {
        $aid = (string)$auc->_id;
        $redis->hIncrBy("stats:{$dateKey}", $aid, rand(0, 50));
    }
    $redis->expire("stats:{$dateKey}", 86400 * 7);
}

echo "Redis: statystyki dzienne utworzone.\n";

// 5) Synchronizacja bids z MongoDB do Redis Sorted Sets
// Poprawiona projekcja pól
$queryBids = new MongoDB\Driver\Query(
    [],
    ['projection'=>['_id'=>1, 'auction_id'=>1, 'amount'=>1]]
);
$bids = $manager->executeQuery('auction.bids', $queryBids)->toArray();

foreach ($bids as $bid) {
    // upewnij się, że pola istnieją
    if (!isset($bid->auction_id) || !isset($bid->amount)) continue;
    $aid    = (string)$bid->auction_id;
    $amount = (float)$bid->amount;
    $member = (string)$bid->_id;
    $redis->zAdd("auction:{$aid}:bids", $amount, $member);
}

echo "Redis: auction:{id}:bids zsynchronizowane z MongoDB.\n";

// 6) Buforowanie listy kończących się aukcji
define('END_SOON_KEY', 'auctions:ending_soon:4');
require_once __DIR__ . '/../src/auctions.php';
$ending = get_ending_soon_auctions(4);
$redis->setex(END_SOON_KEY, 30, json_encode($ending));

echo "Redis: bufor listy kończących się aukcji zapisany pod kluczem ".END_SOON_KEY.".\n";
