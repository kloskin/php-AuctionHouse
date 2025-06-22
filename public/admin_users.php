<main class="container mb-5">
  <h1 class="mb-4">Zarządzanie użytkownikami</h1>
  <table class="table table-hover">
    <thead>
      <tr>
        <th>#ID</th><th>Login</th><th>Email</th><th>Rola</th><th>Akcje</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><?= htmlspecialchars((string)$u->_id) ?></td>
        <td><?= htmlspecialchars($u->username) ?></td>
        <td><?= htmlspecialchars($u->email) ?></td>
        <td><?= htmlspecialchars($u->role) ?></td>
        <td>
          <form method="post" class="d-inline">
            <input type="hidden" name="user_id" value="<?= (string)$u->_id ?>">
            <select name="role" class="form-select form-select-sm d-inline w-auto">
              <?php foreach (get_valid_roles() as $r): ?>
                <option value="<?= $r ?>" <?= $u->role=== $r ? 'selected' : '' ?>>
                  <?= ucfirst($r) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button name="action" value="role" class="btn btn-sm btn-outline-primary">Zmień</button>
          </form>
          <form method="post" class="d-inline" 
                onsubmit="return confirm('Usuń użytkownika <?= htmlspecialchars($u->username) ?>?');">
            <input type="hidden" name="user_id" value="<?= (string)$u->_id ?>">
            <button name="action" value="delete" class="btn btn-sm btn-outline-danger">
              Usuń
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>
