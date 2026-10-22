<?php
$_SERVER['PHP_AUTH_USER'] = 'stars';
$_SERVER['PHP_AUTH_PW'] = 'goedvoer';

include("include.php");
require_once 'iCalcreator-2.6/iCalcreator.class.php';

$v = new vcalendar();
$v->setConfig('unique_id', 'starson45-schoonmaakrooster');
$v->setProperty('method', 'PUBLISH');

$v->setProperty( "X-WR-TIMEZONE", "Europe/Amsterdam" );

$persoonId = intval($_GET['persoonId']);
// persoonNaam opvragen
$query = "SELECT persoonNaam FROM personen WHERE persoonId='". $persoonId ."'";
$result = $link->query($query);
if ($result->num_rows < 1) { die("Er zijn geen personen gevonden."); }
$persoonNaam = mysql_result($result, 0);

// Verkrijg taken
$sql = 'SELECT taakNaam, deadline FROM taken WHERE persoonId = "' . $persoonId . '" AND deadline>=\''.date("Y-m-d").'\'';
$query = $link->query($sql);

if (mysqli_num_rows($query) < 1) { die("Er zijn geen toekomstige schoonmaakdiensten ingepland."); }
$v->setProperty( "x-wr-calname", "Stars on 45 schoonmaakrooster voor ". $persoonNaam);


while ($data = mysql_fetch_object($query)) {
	$deadline = strtotime($data->deadline);
	
	$vevent = new vevent();
	$start = array( 'year'=> date('Y', $deadline), 
					'month'=>date('m', $deadline), 
					'day'=>date('d', $deadline), 
	/*				'hour'=>0, 
					'min'=>0, 
					'sec'=>0 */);
	#$vevent->setProperty( 'dtstart', $start );
	$end = array( 	'year'=> date('Y', $deadline), 
					'month'=>date('m', $deadline), 
					'day'=> (date('d', $deadline) + 1), 
			/*		'hour'=>23, 
					'min'=>59, 
					'sec'=>00 */ ); 
	$date = date('Ymd', $deadline);
	$data->taakNaam = html_entity_decode($data->taakNaam, ENT_QUOTES);
	//Geen einddatum is all-day event
	$vevent->setProperty( 'dtstart', $date, array('VALUE' => 'DATE'));
	#$vevent->setProperty( 'dtend', $end );
	$vevent->setProperty( 'summary', 'Deadline ' . $data->taakNaam . ' schoonmaken');
	$vevent->setProperty( 'description', 'Deadline ' . $data->taakNaam . ' schoonmaken');
	$v->setComponent ( $vevent );
}

$v->returnCalendar();
?>