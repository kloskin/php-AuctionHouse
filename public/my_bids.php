<main class="container mb-5">
  <h1 class="mb-4">Aukcje, w których licytowałeś</h1>

  <?php if (empty($auctionsUser)): ?>
    <div class="alert alert-info">Nie złożyłeś jeszcze żadnej oferty.</div>
  <?php else: ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
      <?php foreach ($auctionsUser as $info):
        $auc      = $info['auction'];
        $userBid  = $info['user_bid'];
        $bidTime  = $info['bid_time'];

        // Wybierz pierwsze zdjęcie lub placeholder
        $imgFile = (!empty($auc->images) && is_array($auc->images))
          ? $auc->images[0]
          : 'placeholder.png';
        $assetPath = __DIR__ . '/assets/img_uploads/' . $imgFile;
        $imgSrc = file_exists($assetPath)
          ? '/assets/img_uploads/' . rawurlencode($imgFile)
          : '/img/placeholder.png';
      ?>
        <div class="col">
          <div class="card h-100 shadow-sm">
            <img
              src="<?= htmlspecialchars($imgSrc) ?>"
              class="card-img-top"
              style="height:160px; object-fit:cover;"
              alt="Zdjęcie aukcji"
            >
            <div class="card-body d-flex flex-column">
              <h5 class="card-title">
                <a href="/auction/<?= (string)$auc->_id ?>" class="stretched-link text-decoration-none">
                  <?= htmlspecialchars($auc->title) ?>
                </a>
              </h5>
              <p class="card-text mb-1">
                <strong>Twoja najwyższa oferta:</strong>
                <?= number_format($userBid, 2, ',', ' ') ?> zł
              </p>
              <p class="card-text mb-3">
                <small class="text-muted">
                  Złożono: <?= $bidTime->format('d.m.Y H:i') ?>
                </small>
              </p>
              <p class="mt-auto mb-0">
                <strong>Akt. cena:</strong>
                <?= number_format(get_highest_bid((string)$auc->_id)->amount ?? $auc->starting_price, 2, ',', ' ') ?> zł
              </p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>