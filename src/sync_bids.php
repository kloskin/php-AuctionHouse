<?php
require_once __DIR__ . '/db.php';

$manager = getMongoManager();
$redis   = getRedisClient();

// Pobierz wszystkie klucze z patternem „auction:*:bids”
$keys = $redis->keys('auction:*:bids');

foreach ($keys as $key) {
    // extract auctionId from „auction:{id}:bids”
    if (!preg_match('#^auction:([^:]+):bids$#', $key, $m)) {
        continue;
    }
    $auctionId = $m[1];

    // Weź wszystkie entry i ich score (kwota)
    $entries = $redis->zRange($key, 0, -1, ['WITHSCORES' => true]);

    $bulk = new MongoDB\Driver\BulkWrite;
    foreach ($entries as $bidId => $score) {
        // Zapisz do osobnej kolekcji „bids_history”
        $bulk->insert([
            '_id'         => new MongoDB\BSON\ObjectId($bidId),
            'auction_id'  => new MongoDB\BSON\ObjectId($auctionId),
            'amount'      => (float)$score,
            'synced_at'   => new MongoDB\BSON\UTCDateTime(),
        ]);
    }

    if ($bulk->getInsertedCount() > 0) {
        $manager->executeBulkWrite('auction.bids_history', $bulk);
    }
}
