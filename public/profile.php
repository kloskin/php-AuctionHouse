<?php

$user = current_user();

$error   = '';
$success = '';

// Obsługa POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm-password'] ?? '';

    // Walidacja
    if ($username === '' || $email === '') {
        $error = 'Nazwa użytkownika i email są wymagane.';
    } elseif ($password !== '' && $password !== $confirm) {
        $error = 'Nowe hasła nie są zgodne.';
    } else {
        // Wywołaj update_user; jeśli password jest pusty, przekaż null
        $newPass = $password === '' ? null : $password;
        $ok = update_user((string)$user->_id, $username, $email, $newPass);

        if ($ok) {
            $success = 'Dane zostały zapisane.';
            // Odświeża dane w $user
            $user = get_user_by_id((string)$user->_id);
        } else {
            $error = 'Aktualizacja nie powiodła się (być może email jest już zajęty).';
        }
    }
}
?>

<main class="container mb-5">
  <h1 class="mb-4">Moje konto</h1>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="post" class="w-100" style="max-width:500px;">
    <div class="mb-3">
      <label for="username" class="form-label">Nazwa użytkownika:</label>
      <input
        type="text"
        id="username"
        name="username"
        required
        class="form-control"
        value="<?= htmlspecialchars($user->username) ?>"
      >
    </div>

    <div class="mb-3">
      <label for="email" class="form-label">Adres e-mail:</label>
      <input
        type="email"
        id="email"
        name="email"
        required
        class="form-control"
        value="<?= htmlspecialchars($user->email) ?>"
      >
    </div>

    <hr>

    <div class="mb-3">
      <label for="password" class="form-label">Nowe hasło:</label>
      <input
        type="password"
        id="password"
        name="password"
        class="form-control"
      >
    </div>

    <div class="mb-3">
      <label for="confirm-password" class="form-label">Powtórz nowe hasło:</label>
      <input
        type="password"
        id="confirm-password"
        name="confirm-password"
        class="form-control"
      >
    </div>

    <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
  </form>
</main>
