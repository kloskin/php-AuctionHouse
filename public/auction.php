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

// 3) Pobierz dane aukcji i wszystkie oferty
$auction = get_auction_by_id($auctionId);
if (!$auction) {
    echo '<div class="alert alert-danger">Nie znaleziono aukcji.</div>';
    return;
}
$bids = get_bid_history($auctionId);

?>

<main class="container mb-5">
  <!-- Szczegóły aukcji -->
  <div class="row mb-4">
    <div class="col-md-6">
      <?php if (!empty($auction->images) && is_array($auction->images)): ?>
        <?php foreach ($auction->images as $img): ?>
          <img src="/assets/<?= htmlspecialchars($img) ?>"
               class="img-fluid mb-2"
               alt="Zdjęcie aukcji">
        <?php endforeach; ?>
      <?php else: ?>
        <img src="/img/placeholder.png"
             class="img-fluid mb-2"
             alt="Brak zdjęć">
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
