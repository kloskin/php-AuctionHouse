<?php
require_once __DIR__ . '/db.php';

/**
 * Złóż ofertę w aukcji.
 *
 * @param string $auctionId Identyfikator ObjectId aukcji jako ciąg hex
 * @param string $userId    Identyfikator ObjectId użytkownika jako ciąg hex
 * @param float  $amount    Kwota oferty
 * @return bool True w przypadku powodzenia
 */
function place_bid(string $auctionId, string $userId, float $amount): bool {
    $manager = getMongoManager();
    $bulk    = new MongoDB\Driver\BulkWrite;

    // 1. Wstaw dokument oferty
    $bidDoc = [
        'auction_id' => new MongoDB\BSON\ObjectId($auctionId),
        'user_id'    => new MongoDB\BSON\ObjectId($userId),
        'amount'     => $amount,
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ];
    $bidId = $bulk->insert($bidDoc);
    $manager->executeBulkWrite('auction.bids', $bulk);

    // 2. Zaktualizuj bieżącą cenę aukcji
    $bulkUpdate = new MongoDB\Driver\BulkWrite;
    $bulkUpdate->update(
        ['_id' => new MongoDB\BSON\ObjectId($auctionId)],
        ['$set' => ['current_price' => $amount]],
        ['multi' => false, 'upsert' => false]
    );
    $manager->executeBulkWrite('auction.auctions', $bulkUpdate);

    // 3. Dodaj do posortowanego zbioru Redis dla rankingu na żywo
    $redis   = getRedisClient();
    $redisKey = "auction:{$auctionId}:bids";
    $redis->zAdd($redisKey, $amount, (string) $bidId);
    $redis->del("auction:{$auctionId}:detail");
    $redis->del("auctions:ending_soon:4");
    return true;
}

/**
 * Pobierz najwyższą ofertę dla aukcji.
 *
 * @param string $auctionId Identyfikator ObjectId aukcji jako ciąg hex
 * @return object|null Dokument oferty lub null jeśli brak
 */
function get_highest_bid(string $auctionId) {
    $redis    = getRedisClient();
    $redisKey = "auction:{$auctionId}:bids";

    // Najwyższa oferta (najwyższy wynik)
    $entries = $redis->zRevRange($redisKey, 0, 0, ['WITHSCORES' => true]);
    if (empty($entries)) {
        return null;
    }
    $bidId = key($entries);

    // Pobierz ofertę z MongoDB
    $manager = getMongoManager();
    $filter  = ['_id' => new MongoDB\BSON\ObjectId($bidId)];
    $query   = new MongoDB\Driver\Query($filter);
    $result  = $manager->executeQuery('auction.bids', $query)->toArray();
    return $result[0] ?? null;
}

/**
 * Pobierz historię ofert dla aukcji (od najnowszych).
 *
 * @param string $auctionId Identyfikator ObjectId aukcji jako ciąg hex
 * @param int    $limit     Liczba ofert do zwrócenia
 * @return array Lista dokumentów ofert
 */
function get_bid_history(string $auctionId, int $limit = 10): array {
    $redis    = getRedisClient();
    $redisKey = "auction:{$auctionId}:bids";

    // Identyfikatory najnowszych ofert
    $bidIds = $redis->zRevRange($redisKey, 0, $limit - 1);
    if (empty($bidIds)) {
        return [];
    }

    // Konwersja na tablicę ObjectId
    $objectIds = array_map(fn($id) => new MongoDB\BSON\ObjectId($id), $bidIds);

    // Pobierz z MongoDB
    $manager = getMongoManager();
    $filter  = ['_id' => ['$in' => $objectIds]];
    $query   = new MongoDB\Driver\Query($filter);
    $bids    = $manager->executeQuery('auction.bids', $query)->toArray();

    // Sortuj malejąco po created_at
    usort($bids, fn($a, $b) => $b->created_at->toDateTime() <=> $a->created_at->toDateTime());

    return $bids;
}
/**
 * Zwraca tablicę wszystkich ofert złożonych przez użytkownika.
 *
 * @param string $userId
 * @return MongoDB\Model\BSONDocument[]|array
 */
function get_user_bids(string $userId): array {
    $manager = getMongoManager();
    $filter  = ['user_id' => new MongoDB\BSON\ObjectId($userId)];
    $options = ['sort' => ['created_at' => -1]];
    $query   = new MongoDB\Driver\Query($filter, $options);
    return $manager->executeQuery('auction.bids', $query)->toArray();
}