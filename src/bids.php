<?php
require_once __DIR__ . '/db.php';

/**
 * Składa ofertę w aukcji.
 *
 * @param string $auctionId Id aukcji jako hex string
 * @param string $userId    Id użytkownika jako hex string
 * @param float  $amount    Kwota oferty
 * @return bool True jeśli oferta przyjęta, false jeśli za niska
 */
function place_bid(string $auctionId, string $userId, float $amount): bool {
    $manager = getMongoManager();
    $auction = get_auction_by_id($auctionId);
    $current = $auction->current_price ?? $auction->starting_price;

    if ($amount <= $current) {
        return false;
    }

    // 1) Zapis oferty w MongoDB
    $bulk   = new MongoDB\Driver\BulkWrite;
    $bidId  = $bulk->insert([
        'auction_id' => new MongoDB\BSON\ObjectId($auctionId),
        'user_id'    => new MongoDB\BSON\ObjectId($userId),
        'amount'     => $amount,
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ]);
    $manager->executeBulkWrite('auction.bids', $bulk);

    // 2) Aktualizacja current_price
    $bulk2  = new MongoDB\Driver\BulkWrite;
    $bulk2->update(
        ['_id' => new MongoDB\BSON\ObjectId($auctionId)],
        ['$set' => ['current_price' => $amount]]
    );
    $manager->executeBulkWrite('auction.auctions', $bulk2);

    // 3) Redis: ranking i czyszczenie cache
    $redis = getRedisClient();
    $redis->zAdd("auction:{$auctionId}:bids", $amount, (string)$bidId);
    $redis->del("auction:{$auctionId}:detail");
    $redis->del("auctions:ending_soon:4");

    return true;
}

/**
 * Pobierz najwyższą ofertę dla aukcji.
 *
 * @param string $auctionId
 * @return stdClass|null
 */
function get_highest_bid(string $auctionId) {
    $redis = getRedisClient();
    $key   = "auction:{$auctionId}:bids";
    $entries = $redis->zRevRange($key, 0, 0, ['WITHSCORES' => true]);
    if (empty($entries)) {
        return null;
    }
    $bidId = key($entries);
    // walidacja: 24 hex
    if (!preg_match('/^[0-9a-fA-F]{24}$/', $bidId)) {
        return null;
    }

    $manager = getMongoManager();
    $query   = new MongoDB\Driver\Query(['_id' => new MongoDB\BSON\ObjectId($bidId)]);
    $result  = $manager->executeQuery('auction.bids', $query)->toArray();
    return $result[0] ?? null;
}

/**
 * Pobierz historię ofert dla aukcji (od najnowszych).
 *
 * @param string $auctionId
 * @param int    $limit     Ile ofert pobrać
 * @return array
 */
function get_bid_history(string $auctionId, int $limit = 10): array {
    $redis = getRedisClient();
    $key   = "auction:{$auctionId}:bids";

    // Pobierz identyfikatory
    $bidIds = $redis->zRevRange($key, 0, $limit - 1);
    if (empty($bidIds)) {
        return [];
    }

    // Filtracja i konwersja
    $objectIds = [];
    foreach ($bidIds as $id) {
        if (is_string($id) && preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
            $objectIds[] = new MongoDB\BSON\ObjectId($id);
        }
    }
    if (empty($objectIds)) {
        return [];
    }

    // Pobranie z Mongo i sortowanie
    $manager = getMongoManager();
    $filter  = ['_id' => ['$in' => $objectIds]];
    $query   = new MongoDB\Driver\Query($filter, ['sort' => ['created_at' => -1]]);
    return $manager->executeQuery('auction.bids', $query)->toArray();
}

/**
 * Pobierz wszystkie oferty złożone przez użytkownika.
 */
function get_user_bids(string $userId): array {
    if (!preg_match('/^[0-9a-fA-F]{24}$/', $userId)) {
        return [];
    }
    $manager = getMongoManager();
    $query   = new MongoDB\Driver\Query(
        ['user_id' => new MongoDB\BSON\ObjectId($userId)],
        ['sort'    => ['created_at' => -1]]
    );
    return $manager->executeQuery('auction.bids', $query)->toArray();
}
