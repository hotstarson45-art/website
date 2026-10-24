<?php
$requireSchoonmaakmeester = true;
include __DIR__ . '/include.php';
require_once __DIR__ . '/beer-common.php';

$beerLink = beerDatabaseConnect($localConfig);
beerSyncPeople($beerLink, $link);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	beerVerifyCsrf();
	$action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
	try {
		if ($action === 'create') {
			$rawDrinkName = isset($_POST['drink_name']) && is_string($_POST['drink_name'])
				? stripslashes($_POST['drink_name'])
				: '';
			$drinkName = trim($rawDrinkName);
			if (preg_match('/\A.{1,128}\z/us', $drinkName) !== 1) {
				throw new InvalidArgumentException('Vul een naam van maximaal 128 tekens in.');
			}
			$quantity = beerParseQuantity(isset($_POST['quantity']) ? $_POST['quantity'] : '');
			$totalPriceCents = beerParseMoney(isset($_POST['total_price']) ? $_POST['total_price'] : '');

			$beerLink->begin_transaction();
			try {
				$duplicateQuery = $beerLink->prepare('SELECT drink_id FROM beer_drinks WHERE drink_name = ?');
				$duplicateQuery->bind_param('s', $drinkName);
				$duplicateQuery->execute();
				if ($duplicateQuery->get_result()->fetch_assoc()) {
					throw new DomainException('Deze drankoptie bestaat al. Gebruik het formulier om voorraad toe te voegen.');
				}

				$insertDrink = $beerLink->prepare('INSERT INTO beer_drinks (drink_name) VALUES (?)');
				$insertDrink->bind_param('s', $drinkName);
				$insertDrink->execute();
				$drinkId = (int) $beerLink->insert_id;

				$insertBatch = $beerLink->prepare(
					'INSERT INTO beer_stock_batches (drink_id, quantity_total, quantity_remaining, total_price_cents)
					VALUES (?, ?, ?, ?)'
				);
				$insertBatch->bind_param('iiii', $drinkId, $quantity, $quantity, $totalPriceCents);
				$insertBatch->execute();
				$beerLink->commit();
			} catch (Throwable $error) {
				$beerLink->rollback();
				throw $error;
			}
			beerSetFlash('Drankoptie en eerste voorraadbatch toegevoegd.', false);
		} elseif ($action === 'restock') {
			$drinkId = beerPostId(isset($_POST['drink_id']) ? $_POST['drink_id'] : '', 'drank');
			$quantity = beerParseQuantity(isset($_POST['quantity']) ? $_POST['quantity'] : '');
			$totalPriceCents = beerParseMoney(isset($_POST['total_price']) ? $_POST['total_price'] : '');

			$beerLink->begin_transaction();
			try {
				$drinkQuery = $beerLink->prepare(
					'SELECT drink_name FROM beer_drinks WHERE drink_id = ? AND active = 1 FOR UPDATE'
				);
				$drinkQuery->bind_param('i', $drinkId);
				$drinkQuery->execute();
				$drink = $drinkQuery->get_result()->fetch_assoc();
				if (!$drink) {
					throw new DomainException('Deze drankoptie bestaat niet of is inactief.');
				}

				$insertBatch = $beerLink->prepare(
					'INSERT INTO beer_stock_batches (drink_id, quantity_total, quantity_remaining, total_price_cents)
					VALUES (?, ?, ?, ?)'
				);
				$insertBatch->bind_param('iiii', $drinkId, $quantity, $quantity, $totalPriceCents);
				$insertBatch->execute();
				$beerLink->commit();
			} catch (Throwable $error) {
				$beerLink->rollback();
				throw $error;
			}
			beerSetFlash('Voorraadbatch toegevoegd. Eerder ingekochte voorraad behoudt zijn eigen prijs.', false);
		} elseif ($action === 'active') {
			$drinkId = beerPostId(isset($_POST['drink_id']) ? $_POST['drink_id'] : '', 'drank');
			$activeValue = isset($_POST['active']) && is_string($_POST['active']) ? $_POST['active'] : '';
			if (!in_array($activeValue, array('0', '1'), true)) {
				throw new InvalidArgumentException('Kies een geldige status.');
			}
			$active = (int) $activeValue;
			$updateDrink = $beerLink->prepare('UPDATE beer_drinks SET active = ? WHERE drink_id = ?');
			$updateDrink->bind_param('ii', $active, $drinkId);
			$updateDrink->execute();
			if ($updateDrink->affected_rows === 0) {
				$existsQuery = $beerLink->prepare('SELECT drink_id FROM beer_drinks WHERE drink_id = ?');
				$existsQuery->bind_param('i', $drinkId);
				$existsQuery->execute();
				if (!$existsQuery->get_result()->fetch_assoc()) {
					throw new DomainException('Deze drankoptie bestaat niet.');
				}
			}
			beerSetFlash($active ? 'Drankoptie geactiveerd.' : 'Drankoptie gedeactiveerd.', false);
		} else {
			throw new InvalidArgumentException('Ongeldige beheeractie.');
		}
	} catch (InvalidArgumentException $exception) {
		beerSetFlash($exception->getMessage(), true);
	} catch (DomainException $exception) {
		beerSetFlash($exception->getMessage(), true);
	}
	beerRedirect('beer_admin.php');
}

$drinksResult = $beerLink->query(
	'SELECT d.drink_id, d.drink_name, d.active,
		COALESCE(SUM(b.quantity_remaining), 0) AS stock
	FROM beer_drinks AS d
	LEFT JOIN beer_stock_batches AS b ON b.drink_id = d.drink_id
	GROUP BY d.drink_id, d.drink_name, d.active
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
$flash = beerTakeFlash();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Bierbeheer - Stars on 45</title>
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body class="admin-page">
	<header class="admin-header">
		<div>
			<p class="titel">Bierbeheer</p>
			<p>Beheer drankopties en voorraad.</p>
		</div>
		<a href="bier.php">Terug naar bierstrepen</a>
	</header>

	<?php if ($flash): ?>
	<div class="beer-message<?php if ($flash['error']) echo ' beer-message-error'; ?>"><?php echo beerEscape($flash['message']); ?></div>
	<?php endif; ?>

	<main class="admin-layout">
		<section class="admin-card">
			<h1>Drankoptie toevoegen</h1>
			<p>Voer de totale prijs van de verpakking in. De prijs per drankje wordt over de batch verdeeld.</p>
			<form method="post" action="beer_admin.php">
				<input type="hidden" name="csrf_token" value="<?php echo beerEscape(beerCsrfToken()); ?>" />
				<input type="hidden" name="action" value="create" />
				<label for="new-drink-name">Naam</label>
				<input id="new-drink-name" name="drink_name" type="text" maxlength="128" required />
				<label for="new-drink-quantity">Aantal drankjes in deze voorraadbatch</label>
				<input id="new-drink-quantity" name="quantity" type="number" min="1" max="999999" required />
				<label for="new-drink-price">Totale prijs van de batch (EUR)</label>
				<input id="new-drink-price" name="total_price" type="text" inputmode="decimal" placeholder="24,95" pattern="[0-9]+([,.][0-9]{1,2})?" required />
				<button type="submit">Drank en voorraad toevoegen</button>
			</form>
			<p class="admin-help">Er wordt geen statiegeld bijgehouden. Een batch mag ook EUR 0,00 kosten.</p>
		</section>

		<section class="admin-card">
			<h2>Voorraad aanvullen</h2>
			<?php if (count($drinks) === 0): ?>
			<p>Voeg eerst een drankoptie toe.</p>
			<?php else: ?>
			<form method="post" action="beer_admin.php">
				<input type="hidden" name="csrf_token" value="<?php echo beerEscape(beerCsrfToken()); ?>" />
				<input type="hidden" name="action" value="restock" />
				<label for="restock-drink">Drankoptie</label>
				<select id="restock-drink" name="drink_id" required>
					<option value="">Selecteer drank</option>
					<?php foreach ($drinks as $drink): if (!(int) $drink['active']) continue; ?>
					<option value="<?php echo (int) $drink['drink_id']; ?>"><?php echo beerEscape($drink['drink_name']); ?></option>
					<?php endforeach; ?>
				</select>
				<label for="restock-quantity">Aantal drankjes in de nieuwe batch</label>
				<input id="restock-quantity" name="quantity" type="number" min="1" max="999999" required />
				<label for="restock-price">Totale prijs van de batch (EUR)</label>
				<input id="restock-price" name="total_price" type="text" inputmode="decimal" placeholder="24,95" pattern="[0-9]+([,.][0-9]{1,2})?" required />
				<button type="submit">Voorraad aanvullen</button>
			</form>
			<?php endif; ?>
		</section>

		<section class="admin-card admin-card-wide">
			<h2>Drankopties en huidige voorraad</h2>
			<?php if (count($drinks) === 0): ?>
			<p>Er zijn nog geen drankopties.</p>
			<?php else: ?>
			<div class="beer-table-wrap">
				<table class="beer-table">
					<thead>
						<tr><th scope="col">Drank</th><th scope="col">Voorraad</th><th scope="col">Volgende streepprijs</th><th scope="col">Status</th><th scope="col">Actie</th></tr>
					</thead>
					<tbody>
						<?php foreach ($drinks as $drink): ?>
						<tr>
							<th scope="row"><?php echo beerEscape($drink['drink_name']); ?></th>
							<td><?php echo (int) $drink['stock']; ?></td>
							<td><?php if (isset($nextPrices[(int) $drink['drink_id']])): ?>&euro; <?php echo beerFormatMoney($nextPrices[(int) $drink['drink_id']]); ?><?php else: ?>&mdash;<?php endif; ?></td>
							<td><?php echo ((int) $drink['active']) ? 'Actief' : 'Inactief'; ?></td>
							<td>
								<form method="post" action="beer_admin.php">
									<input type="hidden" name="csrf_token" value="<?php echo beerEscape(beerCsrfToken()); ?>" />
									<input type="hidden" name="action" value="active" />
									<input type="hidden" name="drink_id" value="<?php echo (int) $drink['drink_id']; ?>" />
									<input type="hidden" name="active" value="<?php echo ((int) $drink['active']) ? '0' : '1'; ?>" />
									<button type="submit"><?php echo ((int) $drink['active']) ? 'Deactiveren' : 'Activeren'; ?></button>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<p class="admin-help">Drankopties worden niet verwijderd, zodat de strepen en bedragen uit het verleden behouden blijven.</p>
			<?php endif; ?>
		</section>
	</main>
</body>
</html>
