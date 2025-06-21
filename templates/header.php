<?php
// Pobieramy aktualnie zalogowanego użytkownika
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
    <div class="container d-flex justify-content-between align-items-center">
      <div class="h4 m-0">
        <a href="/home" class="text-white text-decoration-none">Aukcje24</a>
      </div>
      <nav class="d-none d-md-block">
        <ul class="nav">
          <?php if (!$user): ?>
            <!-- Gość -->
            <li class="nav-item"><a class="nav-link text-white" href="/home">Aukcje</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/login">Zaloguj się</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/register">Utwórz konto</a></li>
          <?php else: ?>
            <!-- Wszyscy zalogowani -->
            <li class="nav-item"><a class="nav-link text-white" href="/home">Aukcje</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/create_auction">Dodaj aukcję</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/my-auctions">Moje aukcje</a></li>
            <?php if (user_has_role($user, 'admin')): ?>
              <!-- Admin -->
              <li class="nav-item"><a class="nav-link text-white" href="/admin-panel">Panel administratora</a></li>
            <?php elseif (user_has_role($user, 'moderator')): ?>
              <!-- Moderator -->
              <li class="nav-item"><a class="nav-link text-white" href="/moderator-panel">Panel moderatora</a></li>
            <?php endif; ?>

            <li class="nav-item"><a class="nav-link text-white" href="/profile">Konto</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/logout">Wyloguj</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </header>
