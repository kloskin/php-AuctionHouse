<main class="container mb-5">
  <h1 class="mb-4">Edytuj aukcję</h1>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form action="?id=<?= htmlspecialchars($auction->_id) ?>" method="post" enctype="multipart/form-data"
        class="w-100" style="max-width:600px;">
    <div class="mb-3">
      <label class="form-label" for="title">Tytuł:</label>
      <input type="text" id="title" name="title" required class="form-control"
             value="<?= htmlspecialchars($auction->title) ?>">
    </div>

    <div class="mb-3">
      <label class="form-label" for="description">Opis:</label>
      <textarea id="description" name="description" rows="4" required class="form-control"><?= 
        htmlspecialchars($auction->description) 
      ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label" for="price">Cena wywoławcza (zł):</label>
      <input type="number" id="price" name="price" required step="0.01" class="form-control"
             value="<?= number_format($auction->starting_price,2,'.','') ?>">
    </div>

    <div class="mb-3">
      <label class="form-label" for="end-date">Data i godzina zakończenia:</label>
      <?php
        // Prefill datetime-local: format YYYY-MM-DDThh:mm
        $dt = $auction->ends_at instanceof MongoDB\BSON\UTCDateTime
            ? $auction->ends_at->toDateTime()
            : new DateTime($auction->ends_at);
        $dt->setTimezone(new DateTimeZone('Europe/Warsaw'));
        $value = $dt->format('Y-m-d\TH:i');
      ?>
      <input type="datetime-local" id="end-date" name="end-date" required class="form-control"
             value="<?= htmlspecialchars($value) ?>">
    </div>

    <div class="mb-3">
      <label class="form-label" for="image">Dodaj nowe zdjęcie (opcjonalnie):</label>
      <input type="file" id="image" name="image" accept="image/*" class="form-control">
      <?php if (!empty($auction->images)): ?>
        <small class="text-muted">Obecne zdjęcia: <?= count($auction->images) ?></small>
      <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
  </form>
</main>
