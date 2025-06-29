<?php

$error = '';
?>
<main class="container mb-5">
  <h1 class="mb-4">Zaloguj się</h1>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form action="" method="POST" class="w-100" style="max-width: 500px;">
    <div class="mb-3">
      <label for="email" class="form-label">Email:</label>
      <input type="email" id="email" name="email" required class="form-control" value="<?= htmlspecialchars($email ?? '') ?>" />
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Hasło:</label>
      <input type="password" id="password" name="password" required class="form-control" />
    </div>
    <button type="submit" class="btn btn-primary">Zaloguj się</button>
  </form>
</main>
