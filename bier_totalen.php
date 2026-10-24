<?php
include __DIR__ . '/include.php';
require_once __DIR__ . '/beer-common.php';

$beerLink = beerDatabaseConnect($localConfig);
beerSyncPeople($beerLink, $link);

$peopleResult = $beerLink->query(
	'SELECT person_id, person_name, active
	FROM beer_people
	ORDER BY active DESC, sort_order ASC, person_id ASC'
);
$people = array();
while ($person = $peopleResult->fetch_assoc()) {
	$people[] = $person;
}

$drinksResult = $beerLink->query('SELECT drink_id, drink_name, active FROM beer_drinks ORDER BY drink_name ASC');
$drinks = array();
while ($drink = $drinksResult->fetch_assoc()) {
	$drinks[] = $drink;
}

$totals = array();
$paidTotals = array();
$grandTotalCents = 0;
$result = $beerLink->query(
	'SELECT person_id, drink_id, COUNT(*) AS stripe_count, SUM(price_cents) AS total_cents
	FROM beer_stripes
	GROUP BY person_id, drink_id'
);
while ($row = $result->fetch_assoc()) {
	$personId = (int) $row['person_id'];
	$drinkId = (int) $row['drink_id'];
	$totals[$personId][$drinkId] = array(
		'count' => (int) $row['stripe_count'],
		'cents' => (int) $row['total_cents']
	);
	$grandTotalCents += (int) $row['total_cents'];
}

$paidResult = $beerLink->query(
	'SELECT paid_by_person_id, SUM(total_price_cents) AS total_paid_cents
	FROM beer_stock_batches
	WHERE paid_by_person_id IS NOT NULL
	GROUP BY paid_by_person_id'
);
$totalPaidCents = 0;
while ($row = $paidResult->fetch_assoc()) {
	$personId = (int) $row['paid_by_person_id'];
	$paidTotals[$personId] = (int) $row['total_paid_cents'];
	$totalPaidCents += (int) $row['total_paid_cents'];
}

$purchasesResult = $beerLink->query(
	'SELECT quantity_total, quantity_remaining, total_price_cents
	FROM beer_stock_batches'
);
$totalPurchasedCents = 0;
$remainingStockValueCents = 0;
while ($batch = $purchasesResult->fetch_assoc()) {
	$totalPurchasedCents += (int) $batch['total_price_cents'];
	$remainingStockValueCents += beerBatchRemainingValue($batch);
}

$flash = beerTakeFlash();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Biertotalen - Stars on 45</title>
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body class="beer-page">
	<header class="beer-header">
		<div>
			<p class="titel">Biertotalen</p>
			<p>Een betaling telt als tegoed voor de betaler; de kosten van elke streep worden daarvan afgetrokken.</p>
		</div>
		<nav class="beer-nav">
			<a href="bier.php">Terug naar bierstrepen</a>
			<?php if ($schoonmaakmeester): ?> &nbsp; | &nbsp; <a href="beer_admin.php">Bierbeheer</a><?php endif; ?>
			&nbsp; | &nbsp; <a href="./">Schoonmaakrooster</a>
		</nav>
	</header>

	<?php if ($flash): ?>
	<div class="beer-message<?php if ($flash['error']) echo ' beer-message-error'; ?>"><?php echo beerEscape($flash['message']); ?></div>
	<?php endif; ?>

	<div class="beer-summary-cards">
		<div><span>Totaal ingekocht</span><strong>&euro; <?php echo beerFormatMoney($totalPurchasedCents); ?></strong></div>
		<div><span>Toegewezen betalingen</span><strong>&euro; <?php echo beerFormatMoney($totalPaidCents); ?></strong></div>
		<div><span>Waarde resterende voorraad</span><strong>&euro; <?php echo beerFormatMoney($remainingStockValueCents); ?></strong></div>
		<div><span>Totaal verbruikt / verschuldigd</span><strong>&euro; <?php echo beerFormatMoney($grandTotalCents); ?></strong></div>
	</div>

	<?php if (count($people) === 0): ?>
	<p>Er staan geen bewoners in het schoonmaakrooster.</p>
	<?php else: ?>
	<div class="beer-table-wrap">
		<table class="beer-table">
			<thead>
				<tr>
					<th scope="col">Persoon</th>
					<?php foreach ($drinks as $drink): ?>
					<th scope="col"><?php echo beerEscape($drink['drink_name']); ?><?php if (!(int) $drink['active']) echo ' (inactief)'; ?></th>
					<?php endforeach; ?>
					<th scope="col">Betaald</th>
					<th scope="col">Gedronken</th>
					<th scope="col">Saldo</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($people as $person): $personId = (int) $person['person_id']; $personTotal = 0; $personPaid = isset($paidTotals[$personId]) ? $paidTotals[$personId] : 0; ?>
				<tr>
					<th scope="row"><?php echo beerEscape($person['person_name']); ?><?php if (!(int) $person['active']) echo ' (niet meer actief)'; ?></th>
					<?php foreach ($drinks as $drink): $drinkId = (int) $drink['drink_id']; $entry = isset($totals[$personId][$drinkId]) ? $totals[$personId][$drinkId] : array('count' => 0, 'cents' => 0); $personTotal += $entry['cents']; ?>
					<td><?php if ($entry['count'] > 0): ?><?php echo $entry['count']; ?> keer<br />&euro; <?php echo beerFormatMoney($entry['cents']); ?><?php else: ?>&mdash;<?php endif; ?></td>
					<?php endforeach; ?>
					<td>&euro; <?php echo beerFormatMoney($personPaid); ?></td>
					<td class="beer-total">&euro; <?php echo beerFormatMoney($personTotal); ?></td>
					<td class="beer-total">&euro; <?php echo beerFormatMoney($personPaid - $personTotal); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<th scope="row" colspan="<?php echo count($drinks) + 1; ?>">Totaal</th>
					<td class="beer-total">&euro; <?php echo beerFormatMoney($totalPaidCents); ?></td>
					<td class="beer-total">&euro; <?php echo beerFormatMoney($grandTotalCents); ?></td>
					<td class="beer-total">&euro; <?php echo beerFormatMoney($totalPaidCents - $grandTotalCents); ?></td>
				</tr>
			</tfoot>
		</table>
	</div>
	<?php endif; ?>
</body>
</html>
