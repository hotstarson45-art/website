<?php
$requireSchoonmaakmeester = true;
include("include.php");

$van = $_GET['vanJaar'] .'-'. $_GET['vanWeek'];
$tot = $_GET['totJaar'] .'-'. $_GET['totWeek'];

if (empty($_GET['vanWeek']) || empty($_GET['vanJaar'])) {
	$tmp = time() - (5 * 7 * 24 * 60 * 60);
	$van = date("o-W", $tmp);
}
if (empty($_GET['totWeek']) || empty($_GET['totJaar'])) {
	$tmp = time() + (5 * 7 * 24 * 60 * 60);
	$tot = date("o-W", $tmp);
}

header("Content-type: application/octet-stream");
header("Cache-Control: no-cache, must-revalidate");
header('Content-Disposition: attachment; filename="rooster_'. $van .'_'. $tot .'.csv"');

// personen opvragen
$query = "SELECT persoonId, persoonNaam FROM personen ORDER BY kolomNr ASC";
$result = $link->query($query);
if($result->num_rows == 0) {
	echo "ERROR: geen personen gevonden";
	exit;
}

$personenKolom = array();
echo ";";

for ($kolom = 1; $persoon = mysqli_fetch_assoc($result); $kolom++) {
	echo html_entity_decode($persoon['persoonNaam'], ENT_QUOTES, 'UTF-8') .";";
	$personenKolom[$persoon['persoonId']] = $kolom;
}

echo "
";

$vanDate = week2date($van);
$totDate = week2date($tot);

for ($i = $vanDate; $i <= $totDate; $i += (7 * 24 * 60 * 60)) {
	// alle weken afgaan
	$weekTxt = (date("o", $i) == date("o")) ? date("j-n", $i) : date("j-n-Y", $i);
	echo "Wk ". intval(date("W", $i)) ." (". $weekTxt .");";
	
	$huidigeKolom = 1;
	
	$query = "SELECT t.taakNaam, t.persoonId FROM taken AS t, personen AS p WHERE t.week='". date("o-W", $i) ."' AND t.persoonId=p.persoonId ORDER BY p.kolomNr ASC";
	$result = $link->query($query);
	while ($row  = mysqli_fetch_assoc($result)) {
		// eerst lege kolommen printen
		$verschilKolom = $personenKolom[$row['persoonId']] - $huidigeKolom;
		for ($j = 0; $j < $verschilKolom; $j++) {
			echo ";";
		}
		echo html_entity_decode($row['taakNaam'], ENT_QUOTES, 'UTF-8');
		$huidigeKolom = $personenKolom[$row['persoonId']];
	}
	// resteren lege kolommen printen
	$maxKolom = $personenKolom[count($personenKolom)-1];
	$verschilKolom = $maxKolom - $huidigeKolom;
	for ($j = 0; $j < $verschilKolom; $j++) {
		echo ";";
	}
	echo "
";
}
?>