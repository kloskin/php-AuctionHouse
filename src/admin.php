<?php
// src/admin.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/**
 * Pobierz listę wszystkich użytkowników.
 * @return array of stdClass
 */
function get_all_users(): array {
    $manager = getMongoManager();
    $query   = new MongoDB\Driver\Query([], ['sort' => ['created_at' => -1]]);
    return $manager->executeQuery('auction.users', $query)->toArray();
}

/**
 * Zmień rolę użytkownika.
 */
function update_user_role(string $userId, string $newRole): bool {
    if (!in_array($newRole, get_valid_roles(), true)) {
        return false;
    }
    $manager = getMongoManager();
    $bulk    = new MongoDB\Driver\BulkWrite;
    $bulk->update(
        ['_id' => new MongoDB\BSON\ObjectId($userId)],
        ['$set' => ['role' => $newRole]]
    );
    $res = $manager->executeBulkWrite('auction.users', $bulk);
    return $res->getModifiedCount() > 0;
}

/**
 * Usuń użytkownika.
 */
function delete_user(string $userId): bool {
    $manager = getMongoManager();
    $bulk    = new MongoDB\Driver\BulkWrite;
    $bulk->delete(['_id' => new MongoDB\BSON\ObjectId($userId)]);
    $res = $manager->executeBulkWrite('auction.users', $bulk);
    return $res->getDeletedCount() > 0;
}

/**
 * Pobierz wszystkie aukcje (dla admina).
 */
function get_all_auctions_admin(): array {
    $manager = getMongoManager();
    $query   = new MongoDB\Driver\Query([], ['sort' => ['starts_at' => -1]]);
    return $manager->executeQuery('auction.auctions', $query)->toArray();
}

/**
 * Zmień status aukcji (active, closed, cancelled).
 */
function update_auction_status(string $auctionId, string $status): bool {
    if (!in_array($status, ['active','closed','cancelled'], true)) {
        return false;
    }
    $manager = getMongoManager();
    $bulk    = new MongoDB\Driver\BulkWrite;
    $bulk->update(
        ['_id' => new MongoDB\BSON\ObjectId($auctionId)],
        ['$set' => ['status' => $status]]
    );
    $res = $manager->executeBulkWrite('auction.auctions', $bulk);
    return $res->getModifiedCount() > 0;
}

/**
 * Usuń aukcję.
 */
function delete_auction_admin(string $auctionId): bool {
    // po prostu delegujemy do istniejącej delete_auction
    delete_auction($auctionId);
    // jeżeli doszliśmy tu bez wyjątku, zwracamy true
    return true;
}
