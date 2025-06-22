<main class="container mb-5">
  <h1 class="mb-4">Nowa aukcja</h1>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form action="" method="post" enctype="multipart/form-data" class="w-100" style="max-width:600px;">
    <div class="mb-3">
      <label for="title" class="form-label">Tytuł aukcji:</label>
      <input type="text" id="title" name="title" required class="form-control"
             value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
    </div>

    <div class="mb-3">
      <label for="description" class="form-label">Opis:</label>
      <textarea id="description" name="description" required class="form-control" rows="4"><?= 
        htmlspecialchars($_POST['description'] ?? '') 
      ?></textarea>
    </div>

    <div class="mb-3">
      <label for="image" class="form-label">Zdjęcie produktu (opcjonalnie):</label>
      <input type="file" id="image" name="image" class="form-control" accept="image/*">
    </div>

    <div class="mb-3">
      <label for="price" class="form-label">Cena wywoławcza (zł):</label>
      <input type="number" id="price" name="price" required class="form-control"
             step="0.01" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
    </div>

    <div class="mb-3">
      <label for="end-date" class="form-label">Data i godzina zakończenia:</label>
      <input type="datetime-local" id="end-date" name="end-date" required class="form-control"
             value="<?= htmlspecialchars($_POST['end-date'] ?? '') ?>">
    </div>

    <button type="submit" class="btn btn-primary">Zapisz aukcję</button>
  </form>
</main>
