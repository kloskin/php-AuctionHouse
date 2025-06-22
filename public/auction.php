<?php
// public/auction.php

// 1) Pobierz zalogowanego użytkownika i ID aukcji z parametru
$user      = current_user();
$auctionId = $_GET['id'] ?? '';

if (!$auctionId) {
    echo '<div class="alert alert-danger">Brak ID aukcji.</div>';
    return;
}

// 2) Obsługa formularza licytacji
$error = '';
$redis   = getRedisClient();
$auction = get_auction_cached($auctionId, $redis);
if (!$auction) {
    echo '<div class="alert alert-danger">Nie znaleziono aukcji.</div>';
    return;
}

// 1) Zlicznik globalny – Sorted Set
$redis->zincrby('auction:views', 1, $auctionId);

// 2) Statystyki dzienne – Hash pod kluczem stats:YYYY-MM-DD
$todayKey = 'stats:' . date('Y-m-d');
$redis->hincrby($todayKey, $auctionId, 1);

$bids = get_bid_history($auctionId);

?>

<main class="container mb-5">
  <!-- Szczegóły aukcji -->
  <div class="row mb-4">
    <div class="col-md-6 d-flex justify-content-center align-items-center">
      <?php if (!empty($auction->images) && is_array($auction->images)): ?>
      <div class="row g-2 justify-content-center w-100">
        <?php foreach ($auction->images as $img): ?>
        <div class="col-12 col-sm-10 col-md-12">
          <div class="ratio ratio-1x1 rounded bg-light overflow-hidden">
          <img src="/assets/img_uploads/<?= htmlspecialchars($img) ?>"
             class="img-fluid object-fit-cover w-100 h-100"
             style="object-fit: cover; object-position: center;"
             alt="Zdjęcie aukcji">
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="ratio ratio-1x1 rounded bg-light overflow-hidden" style="max-width: 400px; width: 100%;">
        <img src="/img/placeholder.png"
           class="img-fluid object-fit-cover w-100 h-100"
           style="object-fit: cover; object-position: center;"
           alt="Brak zdjęć">
      </div>
      <?php endif; ?>
    </div>
    <div class="col-md-6">
      <h1><?= htmlspecialchars($auction->title) ?></h1>
      <p><?= nl2br(htmlspecialchars($auction->description)) ?></p>
      <p><strong>Cena wywoławcza:</strong>
         <?= number_format($auction->starting_price,2,',',' ') ?> zł</p>
      <p><strong>Aktualna cena:</strong>
         <?= number_format($auction->current_price,2,',',' ') ?> zł</p>
      <p><strong>Start:</strong> <?= fmtDate($auction->starts_at) ?></p>
      <p><strong>Koniec:</strong> <?= fmtDate($auction->ends_at) ?></p>
    </div>
  </div>

  <!-- Formularz licytacji -->
  <?php if ($user && $auction->status === 'active'): ?>
    <div class="row mb-5">
      <div class="col-md-6">
        <h4>Złóż ofertę</h4>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" class="d-flex gap-2">
          <input
            type="number"
            name="bid_amount"
            step="0.01"
            min="<?= $auction->current_price + 0.01 ?>"
            class="form-control"
            placeholder="Kwota (zł)"
            required
          >
          <button type="submit" class="btn btn-success">Licytuj</button>
        </form>
      </div>
    </div>
  <?php elseif (!$user): ?>
    <p class="text-warning">Zaloguj się, aby wziąć udział w licytacji.</p>
  <?php endif; ?>

  <!-- Historia licytacji -->
  <div class="row">
    <div class="col-12">
      <h4>Historia licytacji</h4>
      <?php if (empty($bids)): ?>
        <p>Brak ofert dla tej aukcji.</p>
      <?php else: ?>
        <table class="table table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Użytkownik</th>
              <th>Kwota (zł)</th>
              <th>Czas</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bids as $i => $bid): ?>
              <?php
                $bidUser = get_user_by_id((string)$bid->user_id);
              ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($bidUser->username ?? '—') ?></td>
                <td><?= number_format($bid->amount,2,',',' ') ?></td>
                <td><?= fmtDate($bid->created_at) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</main>
