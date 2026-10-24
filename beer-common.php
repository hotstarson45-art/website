<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

function beerDatabaseConnect($localConfig)
{
	if (!isset($localConfig['beer_database']['host'])
		|| !isset($localConfig['beer_database']['username'])
		|| !isset($localConfig['beer_database']['password'])
		|| !isset($localConfig['beer_database']['name'])
		|| !isset($localConfig['beer_database']['charset'])) {
		http_response_code(500);
		exit('Add the beer_database connection details to config.local.php. See BEER-STRIPES-SETUP.md.');
	}

	try {
		$beerLink = new mysqli(
			$localConfig['beer_database']['host'],
			$localConfig['beer_database']['username'],
			$localConfig['beer_database']['password'],
			$localConfig['beer_database']['name']
		);
		$beerLink->set_charset($localConfig['beer_database']['charset']);
	} catch (mysqli_sql_exception $exception) {
		error_log('Beer tracker database connection failed: ' . $exception->getMessage());
		http_response_code(500);
		exit('The beer tracker database is unavailable. Check its config.local.php settings and the server error log.');
	}

	return $beerLink;
}

function beerSyncPeople($beerLink, $scheduleLink)
{
	$sourcePeople = $scheduleLink->query(
		'SELECT persoonId, persoonNaam, kolomNr FROM personen ORDER BY kolomNr ASC'
	);

	$beerLink->begin_transaction();
	try {
		$beerLink->query('UPDATE beer_people SET active = 0');
		$upsertPerson = $beerLink->prepare(
			'INSERT INTO beer_people (person_id, person_name, sort_order, active)
			VALUES (?, ?, ?, 1)
			ON DUPLICATE KEY UPDATE person_name = VALUES(person_name), sort_order = VALUES(sort_order), active = 1'
		);
		while ($person = $sourcePeople->fetch_assoc()) {
			$personId = (int) $person['persoonId'];
			$personName = html_entity_decode($person['persoonNaam'], ENT_QUOTES, 'UTF-8');
			$sortOrder = (int) $person['kolomNr'];
			$upsertPerson->bind_param('isi', $personId, $personName, $sortOrder);
			$upsertPerson->execute();
		}
		$beerLink->commit();
	} catch (Throwable $error) {
		$beerLink->rollback();
		throw $error;
	}
}

function beerEscape($value)
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function beerFormatMoney($cents)
{
	return number_format(((int) $cents) / 100, 2, ',', '.');
}

function beerBatchUnitPrice($batch)
{
	$quantityTotal = (int) $batch['quantity_total'];
	$consumedCount = $quantityTotal - (int) $batch['quantity_remaining'];
	$totalPriceCents = (int) $batch['total_price_cents'];
	$basePriceCents = intdiv($totalPriceCents, $quantityTotal);
	$remainderCents = $totalPriceCents % $quantityTotal;
	return $basePriceCents + ($consumedCount < $remainderCents ? 1 : 0);
}

function beerBatchRemainingValue($batch)
{
	$quantityTotal = (int) $batch['quantity_total'];
	$quantityRemaining = (int) $batch['quantity_remaining'];
	$consumedCount = $quantityTotal - $quantityRemaining;
	$totalPriceCents = (int) $batch['total_price_cents'];
	$basePriceCents = intdiv($totalPriceCents, $quantityTotal);
	$remainderCents = $totalPriceCents % $quantityTotal;
	$remainingRemainderCents = max(0, $remainderCents - $consumedCount);
	return ($basePriceCents * $quantityRemaining) + $remainingRemainderCents;
}

function beerParseMoney($value)
{
	if (!is_scalar($value)) {
		throw new InvalidArgumentException('Vul een geldig bedrag in, bijvoorbeeld 24,95.');
	}
	$value = str_replace(',', '.', trim((string) $value));
	if (!preg_match('/^\d{1,7}(?:\.\d{1,2})?$/', $value)) {
		throw new InvalidArgumentException('Vul een geldig bedrag in, bijvoorbeeld 24,95.');
	}
	$parts = explode('.', $value, 2);
	$whole = (int) $parts[0];
	$fraction = isset($parts[1]) ? str_pad($parts[1], 2, '0') : '00';
	return ($whole * 100) + (int) $fraction;
}

function beerParseQuantity($value)
{
	if (!is_scalar($value)) {
		throw new InvalidArgumentException('Vul een geldig aantal in.');
	}
	$value = trim((string) $value);
	if (!preg_match('/^[1-9]\d{0,5}$/', $value)) {
		throw new InvalidArgumentException('Vul een aantal tussen 1 en 999999 in.');
	}
	return (int) $value;
}

function beerPostId($value, $label)
{
	if (!is_scalar($value)) {
		throw new InvalidArgumentException('Kies een geldige ' . $label . '.');
	}
	$value = (string) $value;
	if (!preg_match('/^[1-9]\d{0,9}$/', $value)) {
		throw new InvalidArgumentException('Kies een geldige ' . $label . '.');
	}
	return (int) $value;
}

function beerCsrfToken()
{
	if (!isset($_SESSION['beer_csrf_token'])) {
		$_SESSION['beer_csrf_token'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['beer_csrf_token'];
}

function beerVerifyCsrf()
{
	$submitted = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
		? $_POST['csrf_token']
		: '';
	$expected = isset($_SESSION['beer_csrf_token']) ? $_SESSION['beer_csrf_token'] : '';
	if ($expected === '' || !hash_equals($expected, $submitted)) {
		http_response_code(403);
		exit('Ongeldig of verlopen formulier. Vernieuw de pagina en probeer opnieuw.');
	}
}

function beerSetFlash($message, $isError)
{
	$_SESSION['beer_flash'] = array(
		'message' => $message,
		'error' => (bool) $isError
	);
}

function beerTakeFlash()
{
	if (!isset($_SESSION['beer_flash'])) {
		return null;
	}
	$flash = $_SESSION['beer_flash'];
	unset($_SESSION['beer_flash']);
	return $flash;
}

function beerRedirect($path)
{
	header('Location: ' . $path);
	exit;
}
?>
