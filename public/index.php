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
  case 'admin':
    // dostęp dla zalogowanych w roli admina
    require_role('admin');
    $pageTitle = 'Panel administratora';
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/admin_panel.php';
    include __DIR__ . '/../templates/footer.php';
    break;
  case 'admin/users':
    require_role('admin');

    // obsługa POST do zmiany roli lub usunięcia
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST['action']) && $_POST['action']==='delete' && !empty($_POST['user_id'])) {
            delete_user($_POST['user_id']);
        }
        if (!empty($_POST['action']) && $_POST['action']==='role' 
            && !empty($_POST['user_id']) && !empty($_POST['role'])) {
            update_user_role($_POST['user_id'], $_POST['role']);
        }
        header('Location: /admin/users');
        exit;
    }

    $users = get_all_users();
    $pageTitle = 'Zarządzanie użytkownikami';
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/admin_users.php';
    include __DIR__ . '/../templates/footer.php';
    break;
  case 'admin/auctions':
    require_role('admin');
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST['action']) && $_POST['action']==='delete' && !empty($_POST['auction_id'])) {
            delete_auction_admin($_POST['auction_id']);
        }
        if (!empty($_POST['action']) && $_POST['action']==='status' 
            && !empty($_POST['auction_id']) && !empty($_POST['status'])) {
            update_auction_status($_POST['auction_id'], $_POST['status']);
        }
        header('Location: /admin/auctions');
        exit;
    }

    $auctionsAdmin = get_all_auctions_admin();
    $pageTitle = 'Zarządzanie aukcjami';
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/admin_auctions.php';
    include __DIR__ . '/../templates/footer.php';
    break;

  case 'auctions':
    // Lista aukcji
    $pageTitle = 'Wszystkie aukcje';
    $auctions = get_auctions_by_status('open', [
        'sort' => ['ends_at' => 1], // sortuj po dacie zakończenia rosnąco
        'limit' => 20,              // maksymalnie 20 aukcji na stronie
    ]);
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

  case 'create_auction':
    // dostęp dla zalogowanych w rolach user/moderator/admin
    require_role(['user', 'admin', 'moderator']);
    $user = current_user();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = floatval($_POST['price'] ?? 0);
        $endDateTime = trim($_POST['end-date'] ?? '');  // przykład: "2025-06-25T14:30"

        // podstawowa walidacja
        if ($title === '' || $description === '' || $price <= 0 || $endDateTime === '') {
            $error = 'Wypełnij tytuł, opis, cenę i datę/zegar zakończenia aukcji.';
        } else {
            // obsługa pliku — tylko jeśli rzeczywiście wgrano poprawnie
            $images = [];
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir  = __DIR__ . '/assets/';
                $ext        = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fileName   = uniqid('img_') . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                    $images[] = $fileName;
                }
            }
            
            // przygotuj dane do create_auction()
            $data = [
                'title'          => $title,
                'description'    => $description,
                'starting_price' => $price,
                'ends_at'        => $endDateTime,               // przekazujemy razem datę i godzinę
                'owner_id'       => new MongoDB\BSON\ObjectId((string)$user->_id),
                'images'         => $images,
                'status'         => 'active',
            ];

            // stwórz aukcję i przekieruj
            $auctionId = create_auction($data);
            header("Location: /auction/{$auctionId}");
            exit;
        }
    }

    $pageTitle = 'Nowa aukcja';
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/create_auction.php';
    include __DIR__ . '/../templates/footer.php';
    break;

  case 'my-auctions':

    require_role(['user', 'admin', 'moderator']);

    $user     = current_user();
    $ownerId  = (string)$user->_id;
    $auctions = get_user_auctions($ownerId);

    $pageTitle = 'Moje aukcje';
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/my_auctions.php';         // Twój przerobiony landing.html
    include __DIR__ . '/../templates/footer.php';
    break;
  case 'my-bids':

    require_role(['user', 'admin', 'moderator']);

    $user = current_user();
    $ownerId  = (string)$user->_id;
    $bids = get_user_bids($ownerId);

    $auctionsUser = [];
    foreach ($bids as $bid) {
        $aid = (string)$bid->auction_id;
        if (!isset($auctionsUser[$aid])) {
            // Pobierz dane aukcji
            $auc = get_auction_by_id($aid);
            if (!$auc) {
                continue;
            }
            // Sformatuj czas złożenia oferty
            $dt = $bid->created_at instanceof MongoDB\BSON\UTCDateTime
                ? $bid->created_at->toDateTime()
                : new DateTime($bid->created_at);
            $dt->setTimezone(new DateTimeZone('Europe/Warsaw'));

            // Zapisz unikalną aukcję + kwotę i czas
            $auctionsUser[$aid] = [
                'auction' => $auc,
                'user_bid' => $bid->amount,
                'bid_time' => $dt,
            ];
        }
    }

    $pageTitle = 'Moje oferty';
    include __DIR__ . '/../templates/header.php';
    include __DIR__ . '/my_bids.php';         // Twój przerobiony landing.html
    include __DIR__ . '/../templates/footer.php';
    break;
  // dynamiczny parametr, np. /auction/685705c8f37d5843ba0e4035
  default:
    if (preg_match('#^edit_auction/([0-9a-fA-F]{24})$#', $uri, $matches)) {
        $error   = '';
        $success = '';

        $auctionId = $matches[1];
        $_GET['id'] = $auctionId;

        $auction = get_auction_by_id($auctionId);
        // uprawnienia i sprawdzenie, czy można edytować
        $user = current_user();
        if (
            !$user ||
            (string)$user->_id !== $matches[1] && !in_array($user->role, ['admin','moderator'], true)
            || count(get_bid_history($auctionId)) > 0
        ) {
            http_response_code(403);
            echo '<div class="container"><h1>Brak dostępu lub aukcja ma już oferty</h1></div>';
            exit;
        }

        // obsługa POST przed header.php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
          $title       = trim($_POST['title'] ?? '');
          $description = trim($_POST['description'] ?? '');
          $price       = floatval($_POST['price'] ?? 0);
          $endDateTime = trim($_POST['end-date'] ?? '');

          if ($title === '' || $description === '' || $price <= 0 || $endDateTime === '') {
            $error = 'Wypełnij wszystkie pola.';
          } else {
            // opcjonalny upload
            $images = $auction->images; // zachowaj obecne
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
              $dir    = __DIR__ . '/assets/';
              $ext    = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
              $file   = uniqid('img_') . '.' . $ext;
              if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $file)) {
                $images[] = $file;
              }
            }

            // update
            $ok = update_auction($auctionId, [
              'title'          => $title,
              'description'    => $description,
              'starting_price' => $price,
              'ends_at'        => $endDateTime,
              'images'         => $images,
            ]);

            if ($ok) {
              $success = 'Dane zostały zapisane.';
              // Odświeża dane w $auction
              $auction = get_auction_by_id($auctionId);
            } else {
              $error = 'Nie udało się zapisać zmian.';
            }
          }
        }

      $pageTitle = 'Edytuj aukcję';
      include __DIR__ . '/../templates/header.php';
      include __DIR__ . '/edit_auction.php';
      include __DIR__ . '/../templates/footer.php';
      exit;
    }
    if (preg_match('#^auction/([0-9a-fA-F]{24})$#', $uri, $m)) {
      $auctionId = $m[1];
      $_GET['id'] = $auctionId;

      // 1) Obsługa POST (przed include header.php)
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && current_user()) {
        try {
          place_bid(
            $auctionId,
            (string)current_user()->_id,
            floatval($_POST['bid_amount'] ?? 0)
          );
          header("Location: /auction/{$auctionId}");
          exit;
        } catch (\Exception $e) {
          // przekaż komunikat błędu do widoku
          $bidError = $e->getMessage();
        }
      }

      // 2) Teraz, po ewentualnym redirect/exit, render page
      $pageTitle = 'Szczegóły aukcji';
      include __DIR__ . '/../templates/header.php';
      include __DIR__ . '/auction.php';       // tutaj korzystasz z $bidError, jeśli jest
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
