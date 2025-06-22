<main class="container mb-5">
  <h1 class="mb-4">Zarządzanie aukcjami</h1>
  <table class="table table-hover align-middle">
    <thead>
      <tr>
        <th>#ID</th><th>Tytuł</th><th>Właściciel</th><th>Status</th><th>Akcje</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($auctionsAdmin as $a): 
        $owner = get_user_by_id((string)$a->owner_id);
      ?>
      <tr>
        <td><?= htmlspecialchars((string)$a->_id) ?></td>
        <td>
          <a href="/auction/<?= (string)$a->_id ?>"><?= htmlspecialchars($a->title) ?></a>
        </td>
        <td><?= htmlspecialchars($owner->username ?? '—') ?></td>
        <td><?= htmlspecialchars($a->status) ?></td>
        <td>
          <form method="post" class="d-inline">
            <input type="hidden" name="auction_id" value="<?= (string)$a->_id ?>">
            <select name="status" class="form-select form-select-sm d-inline w-auto">
              <?php foreach (['active','closed','cancelled'] as $st): ?>
                <option value="<?= $st ?>" <?= $a->status=== $st ? 'selected' : '' ?>>
                  <?= ucfirst($st) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button name="action" value="status" class="btn btn-sm btn-outline-primary">
              Zmień
            </button>
          </form>
          <form method="post" class="d-inline"
                onsubmit="return confirm('Na pewno usunąć aukcję „<?= htmlspecialchars($a->title) ?>”?');">
            <input type="hidden" name="auction_id" value="<?= (string)$a->_id ?>">
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
