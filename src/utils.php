<?php
/**
 * Sprawdza poprawność formatu adresu e-mail.
 *
 * @param string $email E-mail do weryfikacji
 * @return bool         true jeśli adres jest poprawny
 */
function is_valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Haszuje hasło użytkownika przy użyciu bezpiecznego algorytmu.
 *
 * @param string $password Hasło jawne
 * @return string          Hash hasła
 */
function hash_password(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Weryfikuje hasło względem jego hasha.
 *
 * @param string $password Hasło jawne
 * @param string $hash     Zapisany hash
 * @return bool            true jeśli hasło pasuje
 */
function verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Generuje losowy token o zadanej długości.
 *
 * @param int $length Długość tokenu (w bajtach)
 * @return string     Zwraca token hex-encoded
 */
function generate_token(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

/**
 * Zwraca listę wszystkich dostępnych ról w systemie.
 *
 * @return array Lista ról ['admin', 'moderator', 'user']
 */
function get_valid_roles(): array {
    return ['admin', 'moderator', 'user'];
}

/**
 * Formatuje czas UTCDateTime ze MongoDB (lub string) do strefy Europe/Warsaw
 *
 * @param MongoDB\BSON\UTCDateTime|string $utc
 * @return string  Sformatowana data np. "24.06.2025 21:30"
 */
function fmtDate($utc): string {
    // 1) Bezpośredni obiekt UTCDateTime
    if ($utc instanceof MongoDB\BSON\UTCDateTime) {
        $dt = $utc->toDateTime();
        $dt->setTimezone(new DateTimeZone('Europe/Warsaw'));
        return $dt->format('d.m.Y H:i');
    }

    // 2) Rozszerzony JSON z MongoDB (stdClass {"$date": ...})
    if (is_object($utc) && property_exists($utc, '$date')) {
        $raw = $utc->{'$date'};
        // JSON kształtu {"$date": {"$numberLong": "1234567890"}}
        if (is_object($raw) && property_exists($raw, '$numberLong')) {
            $millis = (int)$raw->{'$numberLong'};
        } else {
            $millis = is_string($raw) || is_int($raw) ? (int)$raw : 0;
        }
        $seconds = floor($millis / 1000);
        $dt = new DateTime('@' . $seconds);
        $dt->setTimezone(new DateTimeZone('Europe/Warsaw'));
        return $dt->format('d.m.Y H:i');
    }

    // 3) ciąg znaków (np. ISO-date string)
    if (is_string($utc)) {
        try {
            $dt = new DateTime($utc, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone('Europe/Warsaw'));
            return $dt->format('d.m.Y H:i');
        } catch (Exception $e) {
            return '';
        }
    }

    // 4) liczba sekund lub timestamp
    if (is_int($utc) || is_numeric($utc)) {
        $dt = new DateTime('@' . (int)$utc);
        $dt->setTimezone(new DateTimeZone('Europe/Warsaw'));
        return $dt->format('d.m.Y H:i');
    }

    // Inne przypadki
    return '';
}
/**
 * Zwraca stringowe ID niezależnie od formatu:
 * - MongoDB\BSON\ObjectId
 * - stdClass { '$oid': '...' }
 * - plain string
 */
function oid(string|object $id): string {
    if ($id instanceof MongoDB\BSON\ObjectId) {
        return (string)$id;
    }
    if (is_object($id) && property_exists($id, '$oid')) {
        return $id->{'$oid'};
    }
    return (string)$id;
}