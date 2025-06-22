<section class="bg-light text-center py-5">
  <div class="container">
    <h1 class="display-5 mb-3">Witamy na Aukcje24!</h1>
    <p class="lead">Kupuj i sprzedawaj przedmioty w prosty sposób. Dołącz do społeczności użytkowników już dziś.</p>
  </div>
</section>
<section class="bg-light mb-5">
  <div class="container">
    <h2 class="text-center mb-4">Jak to działa?</h2>
    <div class="row text-center">
      <div class="col-md-4">
        <h5>1. Załóż konto</h5>
        <p>Dołącz do Aukcje24 i zacznij przygodę z handlem online.</p>
      </div>
      <div class="col-md-4">
        <h5>2. Dodaj swoją aukcję</h5>
        <p>Wystaw dowolny przedmiot – szybko i wygodnie.</p>
      </div>
      <div class="col-md-4">
        <h5>3. Licytuj i wygrywaj</h5>
        <p>Znajdź okazje i licytuj!</p>
      </div>
    </div>
  </div>
</section>

<main class="container mb-5">
  <h1 class="mb-4">Kończące się aukcje ⌛</h1>
  <div class="row g-4">
    <?php
    // Pobieramy aukcje
    $redis = getRedisClient();
    $auctions = get_ending_soon_auctions_cached(4, $redis);
    $topViewed = get_top_viewed_auctions(4);
    foreach ($auctions as $a):
      $fileName = (!empty($a->images) && is_array($a->images) && $a->images[0])
        ? $a->images[0]
        : null;

      // 2) Sprawdzamy na dysku
      $assetPath = __DIR__ . '/assets/img_uploads/' . $fileName;
      if ($fileName && file_exists($assetPath)) {
        // jeśli obrazek istnieje w public/assets/
        $imgSrc = 'assets/img_uploads' . rawurlencode($fileName);
      } else {
        // fall-back na placeholder
        $imgSrc = 'img/placeholder.png';
      }

      $dtEnds = fmtDate($a->ends_at);
    ?>
      <div class="col-md-6">
        <div class="d-flex border rounded p-3 h-100 acard">
          <img
            src="<?= htmlspecialchars($imgSrc) ?>"
            class="img-thumbnail me-3"
            style="width:120px; height:120px; object-fit:cover"
            alt="Zdjęcie aukcji"
          >
          <div>
            <h5><?= htmlspecialchars($a->title) ?></h5>
            <p class="mb-1">
              <strong>Cena wywoławcza:</strong>
              <?= number_format($a->starting_price, 2, ',', ' ') ?> zł
            </p>
            <p class="mb-1">
              <strong>Aktualna cena:</strong>
              <?= number_format($a->current_price, 2, ',', ' ') ?> zł
            </p>
            <p class="mb-1">
              <strong>Do:</strong>
              <?= $dtEnds ?>
            </p>
            <?php $id = oid($a->_id); ?>
            <a
              href="auction/<?= htmlspecialchars($id) ?>"
              class="btn btn-sm btn-outline-primary mt-2"
            >Zobacz aukcję</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="text-center mt-5">
    <a href="/auctions" class="btn btn-primary btn-lg">
      Pokaż wszystkie aukcje
    </a>
  </div>
</main>
<main class="container mt-5">
  <h2 class="mb-4">Najpopularniejsze aukcje 🔥</h2>
  <?php if (empty($topViewed)): ?>
    <div class="alert alert-info">Brak danych o wyświetleniach.</div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($topViewed as $aid => $views):
        // Pobierz szczegóły aukcji (możesz użyć cache’owanej wersji)
        $auc = get_auction_cached($aid, $redis);

        // Przygotuj obrazek
        $fileName = (!empty($auc->images) && is_array($auc->images) && $auc->images[0])
          ? $auc->images[0] : null;
        $assetPath = __DIR__ . '/assets/img_uploads/' . $fileName;
        $imgSrc = ($fileName && file_exists($assetPath))
          ? 'assets/img_uploads/' . rawurlencode($fileName)
          : 'img/placeholder.png';

        // Sformatuj datę zakończenia
        $ends = fmtDate($auc->ends_at);

        // Rzutowanie ID
        $urlId = htmlspecialchars(oid($aid));
      ?>
        <div class="col-md-6">
          <div class="d-flex border rounded p-3 h-100 acard">
            <img
              src="<?= htmlspecialchars($imgSrc) ?>"
              class="img-thumbnail me-3"
              style="width:120px; height:120px; object-fit:cover"
              alt="Zdjęcie aukcji"
            >
            <div>
              <h5><?= htmlspecialchars($auc->title) ?></h5>
              <p class="mb-1">
                <strong>Wyświetleń:</strong> <?= number_format($views, 0, ',', ' ') ?>
              </p>
              <p class="mb-1">
                <strong>Aktualna cena:</strong>
                <?= number_format($auc->current_price, 2, ',', ' ') ?> zł
              </p>
              <p class="mb-1">
                <strong>Kończy się:</strong> <?= $ends ?>
              </p>
              <a
                href="/auction/<?= $urlId ?>"
                class="btn btn-sm btn-outline-primary mt-2"
              >Zobacz aukcję</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

