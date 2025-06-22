<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';
session_start();

/**
 * Rejestracja nowego użytkownika z domyślną rolą 'user'.
 *
 * @param string $username  Nazwa użytkownika
 * @param string $email     Adres e-mail użytkownika
 * @param string $password  Hasło użytkownika
 * @param string $role      Rola użytkownika (admin, moderator, user)
 * @return string|false     Zwraca ID nowego użytkownika lub false w przypadku błędu
 */
function register_user(string $username, string $email, string $password, string $role = 'user') {
    // Walidacja: niepusty username, poprawny e-mail i poprawna rola
    if (trim($username) === '' || !is_valid_email($email) || !in_array($role, get_valid_roles(), true)) {
        return false;
    }

    $manager = getMongoManager();
    if (get_user_by_email($email)) {
        return false;
    }

    $bulk = new MongoDB\Driver\BulkWrite;
    $hash = hash_password($password);
    $user = [
        'username'   => $username,
        'email'      => $email,
        'password'   => $hash,
        'role'       => $role,
        'created_at' => new MongoDB\BSON\UTCDateTime(),
    ];
    $userId = $bulk->insert($user);
    $manager->executeBulkWrite('auction.users', $bulk);

    return (string)$userId;
}

/**
 * Aktualizuje dane użytkownika: username, email i opcjonalnie hasło.
 *
 * @param string $userId   ID użytkownika (string)
 * @param string $username Nowa nazwa użytkownika
 * @param string $email    Nowy email
 * @param string|null $password Nowe hasło (jeśli null lub pusty – nie zmieniamy)
 * @return bool            true jeżeli update się powiódł, false w przeciwnym razie
 */
function update_user(string $userId, string $username, string $email, ?string $password = null): bool {
    // Walidacja
    if (trim($username) === '' || !is_valid_email($email)) {
        return false;
    }

    $manager = getMongoManager();

    // Sprawdź, czy email nie jest już w użyciu przez innego użytkownika
    $filter = ['email' => $email, '_id' => ['$ne' => new MongoDB\BSON\ObjectId($userId)]];
    $query = new MongoDB\Driver\Query($filter);
    $existing = $manager->executeQuery('auction.users', $query)->toArray();
    if (count($existing) > 0) {
        return false;
    }

    // Budujemy BulkWrite
    $bulk = new MongoDB\Driver\BulkWrite;
    $update = [
        '$set' => [
            'username' => $username,
            'email'    => $email,
        ]
    ];
    // Opcjonalna zmiana hasła
    if ($password !== null && $password !== '') {
        $update['$set']['password'] = hash_password($password);
    }

    $bulk->update(
        ['_id' => new MongoDB\BSON\ObjectId($userId)],
        $update
    );

    $result = $manager->executeBulkWrite('auction.users', $bulk);
    return ($result->getModifiedCount() > 0);
}

/**
 * Przypisanie nowej roli istniejącemu użytkownikowi.
 *
 * @param string $userId ID użytkownika
 * @param string $role   Nowa rola (admin, moderator, user)
 * @return bool          true w razie powodzenia, false w razie błędu
 */
function assign_role(string $userId, string $role): bool {
    if (!in_array($role, get_valid_roles(), true)) {
        return false;
    }
    $manager = getMongoManager();
    $bulk = new MongoDB\Driver\BulkWrite;
    // Aktualizacja pola 'role'
    $bulk->update(
        ['_id' => new MongoDB\BSON\ObjectId($userId)],
        ['$set' => ['role' => $role]],
        ['multi' => false, 'upsert' => false]
    );
    $manager->executeBulkWrite('auction.users', $bulk);
    return true;
}

/**
 * Logowanie użytkownika i utworzenie sesji.
 *
 * @param string $email    Adres e-mail
 * @param string $password Hasło
 * @return string|false    Token sesji lub false w razie błędu
 */
function login_user(string $email, string $password) {
    $user = get_user_by_email($email);
    // Weryfikacja użytkownika i hasła
    if (!$user || !verify_password($password, $user->password)) {
        return false;
    }
    $token = generate_token(32);
    $redis = getRedisClient();
    // Zapis tokenu w Redis na 1 dzień
    $redis->setex("session:{$token}", 86400, (string)$user->_id);

    // Ustawienie ciasteczka sesyjnego
    setcookie('SESSION_TOKEN', $token, [
        'expires'  => time() + 86400,
        'path'     => '/',
        'httponly' => true,
        'secure'   => false,
        'samesite' => 'Lax',
    ]);

    return $token;
}

/**
 * Pobranie aktualnie zalogowanego użytkownika na podstawie tokenu.
 *
 * @return object|null Dokument użytkownika lub null, jeśli brak sesji
 */
function current_user() {
    if (empty($_COOKIE['SESSION_TOKEN'])) {
        return null;
    }
    $token = $_COOKIE['SESSION_TOKEN'];
    $redis = getRedisClient();
    $userId = $redis->get("session:{$token}");
    if (!$userId) {
        return null;
    }
    return get_user_by_id($userId);
}

/**
 * Wylogowanie użytkownika (usunięcie sesji).
 */
function logout_user() {
    if (!empty($_COOKIE['SESSION_TOKEN'])) {
        $token = $_COOKIE['SESSION_TOKEN'];
        $redis = getRedisClient();
        $redis->del("session:{$token}");
        setcookie('SESSION_TOKEN', '', time() - 3600, '/');
    }
}

/**
 * Sprawdza, czy obecny użytkownik ma wymaganą rolę lub role.
 * W przeciwnym razie zwraca HTTP 403 i kończy skrypt.
 *
 * @param string|array $roles Pojedyncza rola lub lista ról
 */
function require_role($roles) {
    $user = current_user();
    if (!$user || !user_has_role($user, $roles)) {
        http_response_code(403);
        $pageTitle = 'Brak dostępu';
        include __DIR__ . '/../templates/header.php';
        echo '<div class="container text-center"><h1>Brak dostępu</h1></div>';
        include __DIR__ . '/../templates/footer.php';
        exit;
    }
}

/**
 * Sprawdza, czy dokument użytkownika zawiera jedną z ról.
 *
 * @param object       $user  Dokument użytkownika
 * @param string|array $roles Rola lub lista dostępnych ról
 * @return bool              true jeśli rola pasuje
 */
function user_has_role($user, $roles): bool {
    if (is_string($roles)) {
        $roles = [$roles];
    }
    return in_array($user->role ?? 'user', $roles, true);
}

/**
 * Pobiera użytkownika na podstawie adresu e-mail.
 *
 * @param string $email Adres e-mail
 * @return object|null  Dokument użytkownika lub null
 */
function get_user_by_email(string $email) {
    $manager = getMongoManager();
    $filter  = ['email' => $email];
    $query   = new MongoDB\Driver\Query($filter, ['limit' => 1]);
    $result  = $manager->executeQuery('auction.users', $query)->toArray();
    return $result[0] ?? null;
}

/**
 * Pobiera użytkownika na podstawie ID.
 *
 * @param string $userId ID użytkownika
 * @return object|null   Dokument użytkownika lub null
 */
function get_user_by_id(string $userId) {
    $manager = getMongoManager();
    $filter  = ['_id' => new MongoDB\BSON\ObjectId($userId)];
    $query   = new MongoDB\Driver\Query($filter, ['limit' => 1]);
    $result  = $manager->executeQuery('auction.users', $query)->toArray();
    return $result[0] ?? null;
}
