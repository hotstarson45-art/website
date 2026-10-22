<?php
//foutmeldingen zichtbaar maken
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
INI_SET('display_errors', 1);
error_reporting(E_ALL);

//$link = new mysqli('localhost', '##USER##', '##PASSWORD##', '##DATABASE_NAME##');
// mysqli_select_db('deb102926_schoonmaak');

// $mysqli = new mysqli("localhost", "my_user", "my_password", "test");

mysql_connect("localhost", "pn128209", "28K6mnb8");
mysqli_select_db("pn128209_schoonmaak");

// inloggen
if (!isset($_SERVER['PHP_AUTH_USER'])) {
	header('WWW-Authenticate: Basic realm="Schoonmaakrooster Stars on 45"');
	header('HTTP/1.0 401 Unauthorized');
	echo 'Je moet ingelogd zijn om het schoonmaakrooster te kunnen zien.<br />De gebruikersnaam en wachtwoord zijn hetzelfde als eetlijst.';
	exit;
} else {
    if (($_SERVER['PHP_AUTH_USER'] == "stars") && ($_SERVER['PHP_AUTH_PW'] == "goedvoer")) {
    	$schoonmaakmeester = false;
    } else if (($_SERVER['PHP_AUTH_USER'] == "schoonmaakmeester") && ($_SERVER['PHP_AUTH_PW'] == "jopie")) {
    	$schoonmaakmeester = true;
    } else {
    	header('WWW-Authenticate: Basic realm="Schoonmaakrooster Stars on 45"');
		header('HTTP/1.0 401 Unauthorized');
		echo 'Je moet ingelogd zijn om het schoonmaakrooster te kunnen zien.<br />De gebruikersnaam en wachtwoord zijn hetzelfde als eetlijst.';
		exit;
	}
	
	if (isset($requireSchoonmaakmeester) && $requireSchoonmaakmeester && !$schoonmaakmeester) {
		header('WWW-Authenticate: Basic realm="Schoonmeester Stars on 45"');
		header('HTTP/1.0 401 Unauthorized');
		echo 'Voor deze pagina moet je ingelogd zijn als schoonmaakmeester.';
		exit;
	}
}

// met alle inkomende get en post values addslashes() doen
if (!get_magic_quotes_gpc()) {
	foreach ($_POST as $key => $value) {
		$_POST[$key] = addslashes($value);
	}
	foreach ($_GET as $key => $value) {
		$_GET[$key] = addslashes($value);
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
		// value is een array
		eval ("\$config[\$row['key']] = ". $row['value'] .";");
		foreach ($config[$row['key']] as $key=>$value) {
			$config[$row['key']][$key] = $value;
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
	$result = preg_replace("/(<\/?)(\w+)([^>]*>)/e", '', $result);
	return $result;
}
?>