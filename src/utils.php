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
