<?php
$user = current_user();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? 'Aukcje24') ?></title>
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Twoje style -->
  <link rel="stylesheet" href="/assets/style.css" />
</head>
<body>
  <header class="bg-dark text-white p-3 mb-4">
    <nav class="navbar navbar-expand-md  navbar-dark">
      <div class="container">
        <a class="navbar-brand h4 mb-0 text-white" href="/home">Aukcje24</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Przełącz nawigację">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
          <ul class="navbar-nav ms-auto mb-2 mb-md-0">
            <?php if (!$user): ?>
              <li class="nav-item"><a class="nav-link" href="/auctions">Wszystkie Aukcje</a></li>
              <li class="nav-item"><a class="nav-link" href="/login">Zaloguj się</a></li>
              <li class="nav-item"><a class="nav-link" href="/register">Utwórz konto</a></li>
            <?php else: ?>
              <li class="nav-item"><a class="nav-link" href="/auctions">Wszystkie Aukcje</a></li>
              <li class="nav-item"><a class="nav-link" href="/create_auction">Dodaj aukcję</a></li>
              <li class="nav-item"><a class="nav-link" href="/my-auctions">Moje aukcje</a></li>
              <li class="nav-item"><a class="nav-link" href="/my-bids">Moje oferty</a></li>
              <?php if (user_has_role($user, 'admin')): ?>
                <li class="nav-item"><a class="nav-link" href="/admin">Panel administratora</a></li>
              <?php elseif (user_has_role($user, 'moderator')): ?>
                <li class="nav-item"><a class="nav-link" href="/moderator-panel">Panel moderatora</a></li>
              <?php endif; ?>
              <li class="nav-item"><a class="nav-link" href="/profile">Konto</a></li>
              <li class="nav-item"><a class="nav-link" href="/logout">Wyloguj</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
  </header>