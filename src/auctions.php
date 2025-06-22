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
    return $result->getModifiedCount() > 0;
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
    $manager->executeBulkWrite('auction.auctions', $bulk);
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