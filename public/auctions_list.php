<?php
// public/auctions_list.php

// Przyjmujemy, że front–controller przekazuje tablicę $auctions
// Każdy $a ma pola: _id, title, images (array), starting_price, current_price, ends_at (UTCDateTime)

// 1) Podziel aukcje na trwające i zakończone
$now = new DateTime('now', new DateTimeZone('Europe/Warsaw'));
$ongoingAuctions = [];
$endedAuctions   = [];

foreach ($auctions as $a) {
    // Konwertuj ends_at do DateTime w Europe/Warsaw
    $dtEnds = $a->ends_at instanceof MongoDB\BSON\UTCDateTime
        ? $a->ends_at->toDateTime()
        : new DateTime($a->ends_at, new DateTimeZone('UTC'));
    $dtEnds->setTimezone(new DateTimeZone('Europe/Warsaw'));

    if ($dtEnds < $now) {
        $endedAuctions[] = ['auction' => $a, 'dt' => $dtEnds];
    } else {
        $ongoingAuctions[] = ['auction' => $a, 'dt' => $dtEnds];
    }
}

/**
 * Renderuje kafelki aukcji.
 * @param array $list  Tablica elementów ['auction'=>..., 'dt'=>DateTime]
 * @param bool  $isEnded  Czy aukcje już się zakończyły
 */
function renderAuctions(array $list, bool $isEnded = false): void {
    if (empty($list)) {
        echo '<div class="alert alert-info">';
        echo $isEnded ? 'Brak zakończonych aukcji.' : 'Brak dostępnych aukcji.';
        echo '</div>';
        return;
    }
    echo '<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">';
    foreach ($list as $item) {
        $a   = $item['auction'];
        $dt  = $item['dt'];
        // Obrazek
        $imgFile = (!empty($a->images) && is_array($a->images))
            ? $a->images[0]
            : 'placeholder.png';
        $assetPath = __DIR__ . '/assets/img_uploads/' . $imgFile;
        $imgSrc = file_exists($assetPath)
            ? '/assets/img_uploads/' . rawurlencode($imgFile)
            : '/img/placeholder.png';

        // Cena
        $currPrice = isset($a->current_price) && $a->current_price > 0
            ? $a->current_price
            : $a->starting_price;

        // Nagłówek i data
        $dateLabel = $isEnded
            ? 'Zakończona: ' . $dt->format('d.m.Y H:i')
            : 'Kończy się: ' . $dt->format('d.m.Y H:i');

        echo '<div class="col">';
        echo '  <div class="card h-100 shadow-sm">';
        echo "    <img src=\"".htmlspecialchars($imgSrc)."\" class=\"card-img-top\" style=\"height:160px;object-fit:cover;\" alt=\"Zdjęcie aukcji\">";
        echo '    <div class="card-body d-flex flex-column">';
        echo '      <h5 class="card-title">';
        echo "        <a href=\"/auction/".(string)$a->_id."\" class=\"stretched-link text-decoration-none\">";
        echo htmlspecialchars($a->title);
        echo '        </a>';
        echo '      </h5>';
        echo '      <p class="card-text mb-2">';
        echo '<strong>Aktualna cena:</strong> '.number_format($currPrice,2,',',' ').' zł';
        echo '      </p>';
        echo '      <p class="card-text mt-auto">';
        echo "<small class=\"text-muted\">{$dateLabel}</small>";
        echo '      </p>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
    echo '</div>';
}
?>

<main class="container mb-5">
  <h1 class="mb-4">Aukcje</h1>

  <ul class="nav nav-tabs mb-4" id="auctionTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="ongoing-tab" data-bs-toggle="tab" data-bs-target="#ongoing" type="button" role="tab" aria-controls="ongoing" aria-selected="true">
        Trwające (<?= count($ongoingAuctions) ?>)
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="ended-tab" data-bs-toggle="tab" data-bs-target="#ended" type="button" role="tab" aria-controls="ended" aria-selected="false">
        Zakończone (<?= count($endedAuctions) ?>)
      </button>
    </li>
  </ul>

  <div class="tab-content" id="auctionTabsContent">
    <div class="tab-pane fade show active" id="ongoing" role="tabpanel" aria-labelledby="ongoing-tab">
      <?php renderAuctions($ongoingAuctions, false); ?>
    </div>
    <div class="tab-pane fade" id="ended" role="tabpanel" aria-labelledby="ended-tab">
      <?php renderAuctions($endedAuctions, true); ?>
    </div>
  </div>
</main>
