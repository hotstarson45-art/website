<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', '0');

$localConfigPath = __DIR__ . '/config.local.php';
if (!is_file($localConfigPath)) {
	http_response_code(500);
	exit('Missing config.local.php. Copy config.example.php and configure the database and login details.');
}

$localConfig = require $localConfigPath;
if (!is_array($localConfig)) {
	http_response_code(500);
	exit('Invalid local configuration.');
}
$requiredConfig = array(
	isset($localConfig['database']['host']),
	isset($localConfig['database']['username']),
	isset($localConfig['database']['password']),
	isset($localConfig['database']['name']),
	isset($localConfig['database']['charset']),
	isset($localConfig['users']['resident']['username']),
	isset($localConfig['users']['resident']['password']),
	isset($localConfig['users']['admin']['username']),
	isset($localConfig['users']['admin']['password'])
);
if (in_array(false, $requiredConfig, true)) {
	http_response_code(500);
	exit('Invalid local configuration.');
}
$credentials = array(
	$localConfig['database']['host'],
	$localConfig['database']['username'],
	$localConfig['database']['password'],
	$localConfig['database']['name'],
	$localConfig['users']['resident']['username'],
	$localConfig['users']['resident']['password'],
	$localConfig['users']['admin']['username'],
	$localConfig['users']['admin']['password']
);
if (in_array('replace-me', $credentials, true)
	|| $localConfig['users']['resident']['username'] === $localConfig['users']['admin']['username']
	|| $localConfig['users']['resident']['password'] === ''
	|| $localConfig['users']['admin']['password'] === ''
	|| $localConfig['users']['resident']['password'] === 'set-a-long-resident-password'
	|| $localConfig['users']['admin']['password'] === 'set-a-long-admin-password') {
	http_response_code(500);
	exit('Set unique resident/admin usernames and non-empty passwords in config.local.php.');
}

try {
	$link = new mysqli(
		$localConfig['database']['host'],
		$localConfig['database']['username'],
		$localConfig['database']['password'],
		$localConfig['database']['name']
	);
	$link->set_charset($localConfig['database']['charset']);
} catch (mysqli_sql_exception $exception) {
	error_log('Cleaning schedule database connection failed: ' . $exception->getMessage());
	http_response_code(500);
	exit('The cleaning schedule database is unavailable. Check the server error log.');
}

// inloggen
if (PHP_SAPI === 'cli' && defined('SCHOONMAAK_CRON')) {
	$schoonmaakmeester = false;
} elseif (!isset($_SERVER['PHP_AUTH_USER'])) {
	header('WWW-Authenticate: Basic realm="Schoonmaakrooster Stars on 45"');
	header('HTTP/1.0 401 Unauthorized');
	echo 'Je moet ingelogd zijn om het schoonmaakrooster te kunnen zien.';
	exit;
} else {
	$authenticatedUser = $_SERVER['PHP_AUTH_USER'];
	$authenticatedPassword = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
	$residentLogin = $localConfig['users']['resident'];
	$adminLogin = $localConfig['users']['admin'];

	if ($authenticatedUser === $adminLogin['username'] && hash_equals($adminLogin['password'], $authenticatedPassword)) {
		$schoonmaakmeester = true;
	} elseif ($authenticatedUser === $residentLogin['username'] && hash_equals($residentLogin['password'], $authenticatedPassword)) {
		$schoonmaakmeester = false;
	} else {
		header('WWW-Authenticate: Basic realm="Schoonmaakrooster Stars on 45"');
		header('HTTP/1.0 401 Unauthorized');
		echo 'Je moet ingelogd zijn om het schoonmaakrooster te kunnen zien.';
		exit;
	}
	
	if (isset($requireSchoonmaakmeester) && $requireSchoonmaakmeester && !$schoonmaakmeester) {
		header('WWW-Authenticate: Basic realm="Schoonmeester Stars on 45"');
		header('HTTP/1.0 401 Unauthorized');
		echo 'Voor deze pagina moet je ingelogd zijn als schoonmaakmeester.';
		exit;
	}
}

// Preserve the legacy escaped-input convention while avoiding removed PHP APIs.
foreach (array('_POST', '_GET') as $inputName) {
	foreach ($GLOBALS[$inputName] as $key => $value) {
		if (is_string($value)) {
			$GLOBALS[$inputName][$key] = addslashes($value);
		}
	}
}

$dagenTxt = array("maandag", "dinsdag", "woensdag", "donderdag", "vrijdag", "zaterdag", "zondag");
$maandenTxt = array("januari", "februari", "maart", "april", "mei", "juni", "juli", "augustus", "september", "oktober", "november", "december");

// config uit database halen
$query = "SELECT * FROM config";
$result = $link->query($query);
if($result->num_rows == 0) {
	echo "ERROR: geen config gevonden in database!";
	exit;
}
$config = array();
while ($row = mysqli_fetch_assoc($result)) {
	if (substr($row['value'], 0, 5) == "array") {
		// Legacy config arrays are stored as PHP array("value", ...) strings.
		preg_match_all('/"(?:\\\\.|[^"\\\\])*"/s', $row['value'], $matches);
		$config[$row['key']] = array();
		foreach ($matches[0] as $value) {
			$config[$row['key']][] = stripcslashes(substr($value, 1, -1));
		}
	} else {
		// value is een gewone waarde
		$config[$row['key']] = $row['value'];
	}
}

// Geeft een Date terug met de dag waarop de eerstvolgende deadline is.
function getDeadlineDag($date) {
	global $config;
	$huidigeDag = date("N", $date);
	$verschil = $config['deadlineDag'] - $huidigeDag;
	if ($verschil < 0)
		$verschil += 7;
	return $date + ($verschil * 24 * 60 * 60);
}

// zet de mysql date van YYYY-mm-dd (bijv. 2009-07-25) om naar een php date()
function sqldate2phpdate($date) {
	if ($date == '0000-00-00')
		return 0;
	else {
		$tijdsdelen = explode('-', $date);
		return mktime(2, 0, 0, intval($tijdsdelen[1]), intval($tijdsdelen[2]), intval($tijdsdelen[0]));
	}
}

// zet een week (bijv. 2009-05) om in een Date
function week2date($week) {
	$tijdsdelen = explode('-', $week);
	$jaar = $tijdsdelen[0];
	$week = intval($tijdsdelen[1]);
	// eerst weeknummer op 1 juni bereken en dan naar verschil met $weekId kijken
	$juni = sqldate2phpdate($jaar .'-06-01');
	$juni = getDeadlineDag($juni);
	$juniWeek = date("W", $juni);
	$verschil = $week - $juniWeek;
	return $juni + ($verschil * 7 * 24 * 60 * 60);
}

// Zet html karakters om naar tekst (met html_entity_decode) en verwijderd html-tags
// @parama $html  Kan array zijn met allemaal strings of anders string zelf
function htmlToPlainTxt($html) {
	$result;
	
	if (!is_array($html)) {
		$result = _htmlToPlainTxt($html);
	} else {
		$result = array();
		foreach ($html as $key=>$value) {
			$result[$key] = _htmlToPlainTxt($value);
		}
	}
	
	return $result;
}

// zelfde als htmlToPlainTxt(), maar doet echte rekenwerk en gaat uit van dat $html String is.
// @private
function _htmlToPlainTxt($html) {
	$result = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
	return strip_tags($result);
}
?>