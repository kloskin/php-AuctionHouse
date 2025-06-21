<?php
// Routing dla aplikacji aukcyjnej

// Załaduj wszystkie moduły (połączenia, auth, funkcje aukcji itd.)
require_once __DIR__ . '/../src/init.php';

// Pobierz “czystą” ścieżkę, bez query stringów
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

// Mapowanie ścieżek na kontrolery/widoki
switch ($uri) {

  case '':
  case 'home':
  case 'index':
    // Strona główna / landing
    $pageTitle = 'Strona główna - Aukcje24';
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/home.php';         // Twój przerobiony landing.html
    include __DIR__ . '/../templates/footer.php';
    break;

  case 'auctions':
    // Lista aukcji
    $pageTitle = 'Wszystkie aukcje';
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/auctions_list.php';   // np. extract z public/index.php
    include __DIR__ . '/../templates/footer.php';
    break;

  case 'login':
    // 1) Obsługa POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($token = login_user($email, $password)) {
            // jeżeli poprawne dane, od razu przekieruj i exit
            header('Location: /home');
            exit;
        }
        // w pozostałych przypadkach np. ustawiasz $error i pokazujesz formę dalej
    }

    // 2) Jeżeli nie był POST lub dane niepoprawne, pokaż formularz:
    $pageTitle = 'Zaloguj się';
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/login.php';   // tylko czysta forma HTML/PHP, bez header(), bez require init
    include __DIR__ . '/../templates/footer.php';
    break;

  case 'register':
    // 1) obsługa POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $confirm  = $_POST['confirm-password'] ?? '';

        if ($password === $confirm) {
            $userId = register_user($username, $email, $password);
            if ($userId !== false) {
                // logowanie użytkownika po rejestracji
                $token = login_user($email, $password);
                if ($token !== false) {
                    // regeneruj sesję dla bezpieczeństwa
                    session_regenerate_id(true);
                    header('Location: /home');
                    exit;
                }
            } else {
                $error = 'Rejestracja nie powiodła się.';
            }
        } else {
            $error = 'Hasła nie są zgodne.';
        }
    }

    // 2) jeżeli GET lub błąd – renderuj formularz:
    $pageTitle = 'Rejestracja';
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/register.php';
    include __DIR__ . '/../templates/footer.php';
    break;

  case 'logout':
    // 1) wyloguj
    logout_user();
    // 2) przekieruj i przerwij skrypt
    header('Location: /home');
    exit;

  case 'profile':
    // Strona główna / landing
    $pageTitle = 'Konto użytkownika';
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/profile.php';         // Twój przerobiony landing.html
    include __DIR__ . '/../templates/footer.php';
    break;

  // dynamiczny parametr, np. /auction/685705c8f37d5843ba0e4035
  default:
    if (preg_match('#^auction/([0-9a-fA-F]{24})$#', $uri, $m)) {
      $_GET['id'] = $m[1];
      $pageTitle = 'Szczegóły aukcji';
      include __DIR__ . '/../templates/header.php';
      include __DIR__ . '/auction.php';
      include __DIR__ . '/../templates/footer.php';
      break;
    }

    // 404
    http_response_code(404);
    $pageTitle = '404 Nie znaleziono';
    include __DIR__ . '/../templates/header.php';
    echo '<div class="container text-center"><h1>404 – Strona nie znaleziona</h1></div>';
    include __DIR__ . '/../templates/footer.php';
    break;
}
