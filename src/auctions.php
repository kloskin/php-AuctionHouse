<?php
// src/auctions.php

require_once __DIR__ . '/db.php';

/**
 * Pobierz wszystkie aukcje, opcjonalnie z filtrem lub paginacją.
 *
 * @param array $filter Filtr MongoDB
 * @param array $options Opcje takie jak sortowanie, limit, skip
 * @return array Lista aukcji jako dokumenty BSON
 */
function get_auctions(array $filter = [], array $options = []): array {
    $manager = getMongoManager();
    $query   = new MongoDB\Driver\Query($filter, $options);
    $cursor  = $manager->executeQuery('auction.auctions', $query);
    return $cursor->toArray();
}

/**
 * Pobierz pojedynczą aukcję po jej ID.
 *
 * @param string $auctionId ObjectId aukcji jako hex string
 * @return object|null Dokument aukcji lub null jeśli nie znaleziono
 */
function get_auction_by_id(string $auctionId) {
    $manager = getMongoManager();
    $filter  = ['_id' => new MongoDB\BSON\ObjectId($auctionId)];
    $query   = new MongoDB\Driver\Query($filter);
    $result  = $manager->executeQuery('auction.auctions', $query)->toArray();
    return $result[0] ?? null;
}

/**
 * Utwórz nową aukcję.
 *
 * @param array $data Dane aukcji: title, description, starting_price, ends_at (ISO date string), owner_id
 * @return string ID dodanej aukcji
 */
function create_auction(array $data): string {
    $manager = getMongoManager();
    $bulk    = new MongoDB\Driver\BulkWrite;

    // Przygotuj pola
    $data['starts_at']    = new MongoDB\BSON\UTCDateTime();
    $data['ends_at']      = new MongoDB\BSON\UTCDateTime(strtotime($data['ends_at']) * 1000);
    $data['current_price'] = $data['starting_price'];

    $insertedId = $bulk->insert($data);
    $manager->executeBulkWrite('auction.auctions', $bulk);

    // czyszczenie cache
    $redis = getRedisClient();
    $redis->del("auctions:ending_soon:4");

    return (string) $insertedId;
}
/**
 * Aktualizuje dane aukcji.
 *
 * @param string $auctionId   ID aukcji jako string
 * @param array  $data        Tablica z kluczami:
 *                            - title (string)
 *                            - description (string)
 *                            - starting_price (float)
 *                            - ends_at (ISO datetime string)
 *                            - images (tablica nazw plików) — może być pusta
 * @return bool               true jeśli zaktualizowano, false w przeciwnym razie
 */
function update_auction(string $auctionId, array $data): bool {
    $manager = getMongoManager();
    $bulk    = new MongoDB\Driver\BulkWrite;

    // Zbuduj sekcję $set
    $set = [
        'title'          => $data['title'],
        'description'    => $data['description'],
        'starting_price' => (float)$data['starting_price'],
        'ends_at'        => new MongoDB\BSON\UTCDateTime((new DateTime($data['ends_at'], new DateTimeZone('UTC')))->getTimestamp() * 1000),
    ];
    // Jeżeli przekazano obrazy (może być pusty array)
    if (isset($data['images'])) {
        $set['images'] = $data['images'];
    }

    $bulk->update(
        ['_id' => new MongoDB\BSON\ObjectId($auctionId)],
        ['$set' => $set]
    );
    $result = $manager->executeBulkWrite('auction.auctions', $bulk);
    $modified = $result->getModifiedCount() > 0;

    if ($modified) {
        $redis = getRedisClient();
        $redis->del("auction:{$auctionId}:detail");
        $redis->del("auctions:ending_soon:4");
    }
    return $modified;
}

/**
 * Usuń aukcję po ID.
 *
 * @param string $auctionId
 * @return void
 */
function delete_auction(string $auctionId): void {
    $manager = getMongoManager();
    $bulk    = new MongoDB\Driver\BulkWrite;

    $bulk->delete(['_id' => new MongoDB\BSON\ObjectId($auctionId)]);
    $result = $manager->executeBulkWrite('auction.auctions', $bulk);
    $deleted = $result->getDeletedCount() > 0;

    if ($deleted) {
        $redis = getRedisClient();
        $redis->del("auction:{$auctionId}:detail");
        $redis->del("auctions:ending_soon:4");
    }
}
function get_auctions_by_status(string $status, array $options = []): array {
    $now = new MongoDB\BSON\UTCDateTime();
    if ($status === 'open') {
        $filter = ['ends_at' => ['$gt' => $now]];
    } else { // closed
        $filter = ['ends_at' => ['$lte' => $now]];
    }
    return get_auctions($filter, $options);
}

/**
 * Pobierz aukcje wystawione przez konkretnego użytkownika
 */
function get_user_auctions(string $ownerId, array $options = []): array {
    $filter = ['owner_id' => new MongoDB\BSON\ObjectId($ownerId)];
    return get_auctions($filter, $options);
}
function get_ending_soon_auctions(int $limit = 4): array {
    $manager = getMongoManager();
    // Filtrujemy tylko aukcje 'active'
    $filter  = ['status' => 'active'];
    // Sortujemy po ends_at rosnąco, dając pierwsze te, co kończą się najwcześniej
    $options = [
        'sort'  => ['ends_at' => 1],
        'limit' => $limit,
    ];
    $query   = new MongoDB\Driver\Query($filter, $options);
    return $manager->executeQuery('auction.auctions', $query)->toArray();
}
/**
 * Zwraca szczegóły aukcji, najpierw próbując z cache, inaczej z Mongo.
 */
function get_auction_cached(string $auctionId, Redis $redis): stdClass {
    $cacheKey = "auction:{$auctionId}:detail";
    $json = $redis->get($cacheKey);
    if ($json !== false) {
        // udało się z cache
        return json_decode($json);
    }

    // inaczej pobierz z MongoDB
    $auction = get_auction_by_id($auctionId);
    if ($auction) {
        // zapisz w Redis na 60 sekund
        $redis->setex($cacheKey, 60, json_encode($auction));
    }
    return $auction;
}
function get_ending_soon_auctions_cached(int $limit, Redis $redis): array {
    $cacheKey = "auctions:ending_soon:{$limit}";
    $json = $redis->get($cacheKey);
    if ($json !== false) {
        // z cache: dekoduj do tablicy stdClass
        return json_decode($json);
    }

    $list = get_ending_soon_auctions($limit);
    // cache na 30 sekund
    $redis->setex($cacheKey, 30, json_encode($list));
    return $list;
}
/**
 * Zwraca TOP K aukcji po liczbie wyświetleń (globalnie).
 * @param int $k
 * @return array  Tablica [auctionId => score]
 */
function get_top_viewed_auctions(int $k = 4): array {
    $redis = getRedisClient();
    // Pobieramy k elementów o najwyższym score
    $entries = $redis->zRevRange('auction:views', 0, $k - 1, ['WITHSCORES' => true]);
    // Rzutujemy na int
    return array_map('intval', $entries);
}
function get_daily_view_stats(string $date = null): array {
    $redis = getRedisClient();
    $d = $date ?: date('Y-m-d');
    // HGETALL zwraca wszystkie pola i wartości jako flat array
    $flat = $redis->hGetAll("stats:{$d}");
    $stats = [];
    for ($i = 0; $i < count($flat); $i += 2) {
        $stats[$flat[$i]] = (int)$flat[$i + 1];
    }
    return $stats;
}