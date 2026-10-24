<?php
include __DIR__ . '/include.php';
require_once __DIR__ . '/beer-common.php';

$beerLink = beerDatabaseConnect($localConfig);
beerSyncPeople($beerLink, $link);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	beerVerifyCsrf();
	try {
		$personId = beerPostId(isset($_POST['person_id']) ? $_POST['person_id'] : '', 'persoon');
		$drinkId = beerPostId(isset($_POST['drink_id']) ? $_POST['drink_id'] : '', 'drank');

		$beerLink->begin_transaction();
		try {
			$drinkQuery = $beerLink->prepare(
				'SELECT drink_name, active FROM beer_drinks WHERE drink_id = ? FOR UPDATE'
			);
			$drinkQuery->bind_param('i', $drinkId);
			$drinkQuery->execute();
			$drink = $drinkQuery->get_result()->fetch_assoc();
			if (!$drink || !(int) $drink['active']) {
				throw new DomainException('Deze drankoptie is niet beschikbaar.');
			}

			$personQuery = $beerLink->prepare(
				'SELECT person_name FROM beer_people WHERE person_id = ? AND active = 1 FOR UPDATE'
			);
			$personQuery->bind_param('i', $personId);
			$personQuery->execute();
			$person = $personQuery->get_result()->fetch_assoc();
			if (!$person) {
				throw new DomainException('Deze persoon staat niet meer op de actieve bewonerslijst.');
			}

			$batchQuery = $beerLink->prepare(
				'SELECT batch_id, quantity_total, quantity_remaining, total_price_cents
				FROM beer_stock_batches
				WHERE drink_id = ? AND quantity_remaining > 0
				ORDER BY batch_id ASC
				LIMIT 1
				FOR UPDATE'
			);
			$batchQuery->bind_param('i', $drinkId);
			$batchQuery->execute();
			$batch = $batchQuery->get_result()->fetch_assoc();
			if (!$batch) {
				throw new DomainException('Deze drank is op. Voeg eerst voorraad toe via Bierbeheer.');
			}

			$quantityRemaining = (int) $batch['quantity_remaining'];
			$priceCents = beerBatchUnitPrice($batch);
			$newQuantity = $quantityRemaining - 1;
			$batchId = (int) $batch['batch_id'];

			$updateBatch = $beerLink->prepare(
				'UPDATE beer_stock_batches SET quantity_remaining = ? WHERE batch_id = ?'
			);
			$updateBatch->bind_param('ii', $newQuantity, $batchId);
			$updateBatch->execute();

			$insertStripe = $beerLink->prepare(
				'INSERT INTO beer_stripes (person_id, drink_id, batch_id, price_cents)
				VALUES (?, ?, ?, ?)'
			);
			$insertStripe->bind_param('iiii', $personId, $drinkId, $batchId, $priceCents);
			$insertStripe->execute();
			$beerLink->commit();
		} catch (Throwable $error) {
			$beerLink->rollback();
			throw $error;
		}

		beerSetFlash(
			'Een ' . $drink['drink_name'] . ' voor ' . $person['person_name']
			. ' geregistreerd voor EUR ' . number_format($priceCents / 100, 2, ',', '.') . '.',
			false
		);
	} catch (InvalidArgumentException $exception) {
		beerSetFlash($exception->getMessage(), true);
	} catch (DomainException $exception) {
		beerSetFlash($exception->getMessage(), true);
	}
	beerRedirect('bier.php');
}

$peopleResult = $beerLink->query(
	'SELECT person_id, person_name FROM beer_people WHERE active = 1 ORDER BY sort_order ASC, person_id ASC'
);
$people = array();
while ($person = $peopleResult->fetch_assoc()) {
	$people[] = $person;
}

$drinksResult = $beerLink->query(
	'SELECT d.drink_id, d.drink_name, COALESCE(SUM(b.quantity_remaining), 0) AS stock
	FROM beer_drinks AS d
	LEFT JOIN beer_stock_batches AS b ON b.drink_id = d.drink_id
	WHERE d.active = 1
	GROUP BY d.drink_id, d.drink_name
	ORDER BY d.drink_name ASC'
);
$drinks = array();
while ($drink = $drinksResult->fetch_assoc()) {
	$drinks[] = $drink;
}

$nextPrices = array();
$batchResult = $beerLink->query(
	'SELECT drink_id, quantity_total, quantity_remaining, total_price_cents
	FROM beer_stock_batches
	WHERE quantity_remaining > 0
	ORDER BY drink_id ASC, batch_id ASC'
);
while ($batch = $batchResult->fetch_assoc()) {
	$drinkId = (int) $batch['drink_id'];
	if (!isset($nextPrices[$drinkId])) {
		$nextPrices[$drinkId] = beerBatchUnitPrice($batch);
	}
}

$counts = array();
$countResult = $beerLink->query(
	'SELECT person_id, drink_id, COUNT(*) AS stripe_count
	FROM beer_stripes
	GROUP BY person_id, drink_id'
);
while ($row = $countResult->fetch_assoc()) {
	$counts[(int) $row['person_id']][(int) $row['drink_id']] = (int) $row['stripe_count'];
}
$flash = beerTakeFlash();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Bierstrepen - Stars on 45</title>
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body class="beer-page">
	<header class="beer-header">
		<div>
			<p class="titel">Bierstrepen</p>
			<p>Druk op de knop in de juiste rij en kolom om een drankje te registreren.</p>
		</div>
		<nav class="beer-nav">
			<a href="bier_totalen.php">Totalen</a>
			<?php if ($schoonmaakmeester): ?> &nbsp; | &nbsp; <a href="beer_admin.php">Bierbeheer</a><?php endif; ?>
			&nbsp; | &nbsp; <a href="./">Schoonmaakrooster</a>
		</nav>
	</header>

	<?php if ($flash): ?>
	<div class="beer-message<?php if ($flash['error']) echo ' beer-message-error'; ?>"><?php echo beerEscape($flash['message']); ?></div>
	<?php endif; ?>

	<?php if (count($drinks) === 0): ?>
	<p>Er zijn nog geen drankopties. De beheerder kan drank en voorraad toevoegen via Bierbeheer.</p>
	<?php elseif (count($people) === 0): ?>
	<p>Er staan geen actieve bewoners in het schoonmaakrooster.</p>
	<?php else: ?>
	<div class="beer-table-wrap">
		<table class="beer-table">
			<thead>
				<tr>
					<th scope="col">Drank</th>
					<?php foreach ($people as $person): ?>
					<th scope="col"><?php echo beerEscape($person['person_name']); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($drinks as $drink): $drinkId = (int) $drink['drink_id']; $stock = (int) $drink['stock']; ?>
				<tr>
					<th scope="row">
						<?php echo beerEscape($drink['drink_name']); ?><br />
						<small><?php echo $stock; ?> op voorraad</small><br />
						<small><?php if (isset($nextPrices[$drinkId])): ?>Volgende: &euro; <?php echo beerFormatMoney($nextPrices[$drinkId]); ?><?php else: ?>Geen voorraad<?php endif; ?></small>
					</th>
					<?php foreach ($people as $person): $personId = (int) $person['person_id']; $count = isset($counts[$personId][$drinkId]) ? $counts[$personId][$drinkId] : 0; ?>
					<td>
						<span class="beer-count"><?php echo $count; ?> keer</span>
						<form class="beer-stripe-form" method="post" action="bier.php" onsubmit="this.querySelector('button').disabled = true;">
							<input type="hidden" name="csrf_token" value="<?php echo beerEscape(beerCsrfToken()); ?>" />
							<input type="hidden" name="person_id" value="<?php echo $personId; ?>" />
							<input type="hidden" name="drink_id" value="<?php echo $drinkId; ?>" />
							<button class="beer-stripe-button" type="submit" aria-label="Streep <?php echo beerEscape($drink['drink_name']); ?> voor <?php echo beerEscape($person['person_name']); ?>"<?php if ($stock < 1) echo ' disabled="disabled"'; ?>>+ Streep</button>
						</form>
					</td>
					<?php endforeach; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
</body>
</html>
