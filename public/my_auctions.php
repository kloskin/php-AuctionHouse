<main class="container mb-5">
  <h1 class="mb-4">Moje aukcje</h1>
  <div class="mb-4">
    <a href="create_auction" class="btn btn-success">Utwórz aukcję</a>
  </div>

  <?php foreach ($auctions as $a):
    // 1) Pobierz historię ofert (by sprawdzić, czy są oferty)
    $bids     = get_bid_history((string)$a->_id);
    $hasBid   = count($bids) > 0;

    // 2) Jeżeli są oferty, pobierz najwyższą
    $currentPrice = $a->starting_price;
    if ($hasBid) {
      $highestBid   = get_highest_bid((string)$a->_id);
      if ($highestBid && isset($highestBid->amount)) {
        $currentPrice = $highestBid->amount;
      }
    }

    // 3) Konwersja daty zakończenia (z utils.php fmtDate lub ręcznie)
    $dt = $a->ends_at instanceof MongoDB\BSON\UTCDateTime
         ? $a->ends_at->toDateTime()
         : new DateTime($a->ends_at);
  ?>
    <div class="mb-4 border p-4 rounded shadow-sm bg-white">
      <h2 class="h5">
        <a href="/auction/<?= (string)$a->_id ?>">
          <?= htmlspecialchars($a->title) ?>
        </a>
      </h2>

      <p>
        <strong>Cena wywoławcza:</strong>
        <?= number_format($a->starting_price, 2, ',', ' ') ?> zł
      </p>
      <p>
        <strong>Aktualna cena:</strong>
        <?= number_format($currentPrice, 2, ',', ' ') ?> zł
      </p>
      <p>
        <strong>Termin zakończenia:</strong>
        <?= $dt->format('d.m.Y H:i') ?>
      </p>

      <?php if (!$hasBid): ?>
        <a href="/edit_auction/<?= (string)$a->_id ?>" 
           class="btn btn-secondary me-2">Edytuj aukcję</a>
        <form action="delete_auction.php" method="POST" style="display:inline;">
          <input type="hidden" name="id" value="<?= (string)$a->_id ?>">
          <button type="submit" class="btn btn-danger"
                  onclick="return confirm('Na pewno usunąć tę aukcję?');">
            Usuń aukcję
          </button>
        </form>
      <?php else: ?>
        <span class="text-muted">
          Aukcja posiada już oferty ‒ nie można edytować ani usunąć
        </span>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</main>
