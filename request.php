<?php
include("include.php");

$soort = $_GET['soort'];
$return = ''; // return data
$returnXML = false;

if ($soort == 'persoonInfo') {
	// persoon informatie
	$persoonId = $_GET['persoonId'];

	$query = "SELECT * FROM personen WHERE persoonId='". $persoonId ."'";
	$result = $link->query($query);
		print_r($result);

	if($result->num_rows != 1) {
		echo "ERROR: geen (of te veel) info gevonden over persoon met persoonId ". $persoonId;
		exit;
	}
	$persoonInfo = mysqli_fetch_assoc($result);

	$return .= '
<table>
	<tr>
		<td>
			Emailadres
		</td>
		<td>
			<input type="text" name="emailadres" value="'. $persoonInfo['emailadres'] .'" onchange="$.post(\'process.php\', {soort: \'persoonInfo\', persoonId: \''. $persoonId .'\', emailadres: this.value},function(data){showMelding(data);});"/>
		</td>
	</tr>
	<tr>
		<td>
			Herinnering begin van week
		</td>
		<td>
			<label for="persoonInfoForm_herinneringBeginWeek_ja">
				<input type="radio" name="herinneringBeginWeek" id="persoonInfoForm_herinneringBeginWeek_ja" value="ja" '; if ($persoonInfo['herinneringBeginWeek']) $return .= 'checked="checked" '; $return .= 'onchange="$.post(\'process.php\', {soort: \'persoonInfo\', persoonId: \''. $persoonId .'\', herinneringBeginWeek: \'ja\'},function(data){showMelding(data);});" />Ja &nbsp; 
			</label>
			<label for="persoonInfoForm_herinneringBeginWeek_nee">
				<input type="radio" name="herinneringBeginWeek" id="persoonInfoForm_herinneringBeginWeek_nee" value="nee" '; if (!$persoonInfo['herinneringBeginWeek']) $return .= 'checked="checked" '; $return .= 'onchange="$.post(\'process.php\', {soort: \'persoonInfo\', persoonId: \''. $persoonId .'\', herinneringBeginWeek: \'nee\'},function(data){showMelding(data);});" />Nee
			</label>
		</td>
	</tr>
	<tr>
		<td>
			Herinnering na deadline als taak nog niet is voltooid
		</td>
		<td>
			<label for="persoonInfoForm_herinneringNaDeadline_ja">
				<input type="radio" name="herinneringNaDeadline" id="persoonInfoForm_herinneringNaDeadline_ja" value="ja" '; if ($persoonInfo['herinneringNaDeadline']) $return .= 'checked="checked" '; $return .= 'onchange="$.post(\'process.php\', {soort: \'persoonInfo\', persoonId: \''. $persoonId .'\', herinneringNaDeadline: \'ja\'},function(data){showMelding(data);});" />Ja &nbsp; 
			</label>
			<label for="persoonInfoForm_herinneringNaDeadline_nee">
				<input type="radio" name="herinneringNaDeadline" id="persoonInfoForm_herinneringNaDeadline_nee" value="nee" '; if (!$persoonInfo['herinneringNaDeadline']) $return .= 'checked="checked" '; $return .= 'onchange="$.post(\'process.php\', {soort: \'persoonInfo\', persoonId: \''. $persoonId .'\', herinneringNaDeadline: \'nee\'},function(data){showMelding(data);});" />Nee
			</label>
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<a href="ics.php?persoonId='. $persoonId .'" target="_blank">Download persoonlijk rooster voor digitale agenda</a>
		</td>
	</tr>
</table>';
} else if ($soort == 'taakInfo') {
	// taak informatie
	$taakId = intval($_GET['taakId']);
	$isRoosterToekomst = $_GET['isRoosterToekomst'];
	$persoonId = intval($_GET['persoonId']);
	$weekId = $_GET['weekId'];
	
	if ($taakId != 0) {
		$query = "SELECT t.*, p.persoonNaam, p.herinneringNaDeadline FROM taken AS t, personen AS p WHERE t.taakId='". $taakId ."' AND t.persoonId=p.persoonId";
		$result = $link->query($query);
		if($result->num_rows != 1) {
			echo "ERROR: geen (of te veel) info gevonden over taak met taakId ". $taakId;
			exit;
		}
	
		$taakInfo = mysqli_fetch_assoc($result);
		$taakInfo['taakNaam'] = $taakInfo['taakNaam'];
	} else {
		$taakInfo = array();
		$taakInfo['taakId'] = 0;
		$taakInfo['week'] = $weekId;
		$taakInfo['taakNaam'] = '';
		$taakInfo['persoonId'] = $persoonId;
		$taakInfo['deadline'] = '0000-00-00';
		$taakInfo['gedaan'] = '';
		$taakInfo['gedaanOp'] = '0000-00-00';
		$taakInfo['boete'] = '0.00';
		$taakInfo['herinneringBeginWeekMail'] = '0000-00-00 00:00:00';
		$taakInfo['herinneringNaDeadlineMail'] = '0000-00-00 00:00:00';
		$taakInfo['boeteMeldingMail'] = '0000-00-00 00:00:00';
		$taakInfo['persoonNaam'] = '';
		$taakInfo['herinneringNaDeadline'] = '0';
		
		// persoonNaam opzoeken
		$query = "SELECT persoonNaam FROM personen WHERE persoonId='". $persoonId ."'";
		$result = $link->query($query);
		if($result->num_rows != 1) {
			echo "ERROR: geen (of te veel) info gevonden over taak met persoonId ". $persoonId;
			exit;
		}
		$tmp = mysqli_fetch_assoc($result);
		$taakInfo['persoonNaam'] = $tmp['persoonNaam'];
	}
	
	$return .= '
<script type="text/javascript">
var currentValueNaam = $(\'#taakNaam\').val();

function setCurrentValueNaam() {
	if ($(\'#taakNaam\').val() != \'-\')
		currentValueNaam = $(\'#taakNaam\').val();
}

function submitNaamTaak() {
	if ($(\'#taakNaam\').val() == \'-\') {
		// mogelijk verwijderen van taak
		if (confirm("Weet je zeker dat je deze taak wilt verwijderen?")) {
			// taak verwijderen
			$.post(\'process.php\', {soort: \'taakInfo\', save: \'taakNaam\', taakId: \''. $taakId .'\', persoonId: \''. $persoonId .'\', weekId: \''. $weekId .'\', taakNaam: $("#taakNaam").val()},function(data){
				showMelding(data);
				maakStatistieken();
				$("body").click();
				maakRoostersWeken('. $isRoosterToekomst .');
			});
		} else {
			// select taakNaam weer terugzetten naar vorige waarde
			$(\'#taakNaam\').val(currentValueNaam);
		}
	} else if ('; $return .= ($taakId != 0) ? 'true' : 'false'; $return .= ')  {
		// update van taakNaam
		$.post(\'process.php\', {soort: \'taakInfo\', save: \'taakNaam\', taakId: \''. $taakId .'\', persoonId: \''. $persoonId .'\', weekId: \''. $weekId .'\', taakNaam: $("#taakNaam").val()},function(data){
			showMelding(data);
			celId = "taakcel_'. $weekId .'_'. $persoonId .'";
			$("#"+ celId +" > a").text($("#taakNaam").val());
		});
		setCurrentValueNaam();
	} else {
		// nieuwe taak aanmaken
		$.post(\'process.php\', {soort: \'taakInfo\', save: \'taakNaam\', taakId: \''. $taakId .'\', persoonId: \''. $persoonId .'\', weekId: \''. $weekId .'\', taakNaam: $("#taakNaam").val()},function(data){
			showMelding(data);
			maakStatistieken();
			$("body").click();
			zetTakenInRooster('. $isRoosterToekomst .');
		});
		setCurrentValueNaam();
	}
}';
if ($schoonmaakmeester && ($taakId != 0)):
$return .= '

function submitWijzigGedaan(value) {
	updateBoete();
	
	$.post(\'process.php\', {soort: \'taakInfo\', save: \'gedaan\', taakId: \''. $taakId .'\', gedaan: value, boete: $("#boete").val(), gedaanOpDag: $("#gedaanOpDag").val(), gedaanOpMaand: $("#gedaanOpMaand").val(), gedaanOpJaar: $("#gedaanOpJaar").val()}, function(data){
		showMelding(data);
		maakStatistieken();
	});
	
	if (value == \'ja\') {
		// gedaanOp weergeven en taak in rooster kleur groen geven
		$("#persoonInfoForm_gedaanOp").attr("style", "visibility:visible");
	} else {
		$("#persoonInfoForm_gedaanOp").attr("style", "visibility:hidden");
	}
}

function updateBoete() {
	var boete = "0,00";
	';
	$dagTeLaat = sqldate2phpdate($taakInfo['deadline']);
	for ($i = 1; $i < 5; $i++) {
		$dagTeLaat = $dagTeLaat + 24*60*60;
		$return .= '
	if (($("#persoonInfoForm_gedaan_ja").attr("checked")) && ($("#gedaanOpDag").val() == \''. date("j", $dagTeLaat) .'\') && ($("#gedaanOpMaand").val() == \''. date("n", $dagTeLaat) .'\') && ($("#gedaanOpJaar").val() == \''. date("Y", $dagTeLaat) .'\'))
		boete = \''. $config['boeteTeLaat'][$i] .'\';';
	}
	$return .= '
	if ($("#persoonInfoForm_gedaan_nooit").attr("checked"))
		boete = \''. $config['boeteTeLaat'][0] .'\';
	
	$("#boete").val(boete);
	
	updateKleur();
	
	mogelijkWeergevenMail();
}

function updateKleur() {
	// kleur link bepalen
	var aStyle = "";
	if (!$("#persoonInfoForm_gedaan_nogNiet").attr("checked")) {
	    if ($("#boete").val() == "0,00")
	    	aStyle = "color:green";
	    else
	    	aStyle = "color:red";
	}
	celId = "taakcel_'. $weekId .'_'. $persoonId .'";
	$("#"+ celId +" > a").attr("style", aStyle);
}

var mogelijkWeergevenHerinneringNaDeadlineMail = '; $return .= (($taakInfo['herinneringNaDeadlineMail'] == '0000-00-00 00:00:00')&&($taakInfo['herinneringNaDeadline'] == '1')&&((sqldate2phpdate($taakInfo['deadline'])+22*60*60) < time())) ? 'true' : 'false'; $return .= '
var mogelijkWeergevenBoeteMeldingMail = '; $return .= ($taakInfo['boeteMeldingMail'] == '0000-00-00 00:00:00') ? 'true' : 'false'; $return .= '

function mogelijkWeergevenMail() {
	if ($("#persoonInfoForm_gedaan_nogNiet").attr("checked")) {
		$("#boeteMeldingMail").attr("style", "display:none");
		// herinnering na deadline wel/niet weergeven
		if (mogelijkWeergevenHerinneringNaDeadlineMail)
			$("#herinneringNaDeadlineMail").attr("style", "display:inline");
		else
			$("#herinneringNaDeadlineMail").attr("style", "display:none");
	} else {
		$("#herinneringNaDeadlineMail").attr("style", "display:none");
		// boete melding wel/niet weergeven
		if (mogelijkWeergevenBoeteMeldingMail && ($("#boete").val() != "0,00"))
			$("#boeteMeldingMail").attr("style", "display:inline");
		else
			$("#boeteMeldingMail").attr("style", "display:none");
	}
}

setTimeout("mogelijkWeergevenMail()", 100); // geen window.onload mogelijkheid, dus dan maar een simpele timer gebruiken

';
endif;
$return .= '
</script>
<table>
	<tr>
		<td>
			Taak
		</td>
		<td>';
	// mogelijkheid om taak te wijzigen niet weergeven als:
	// Geen schoonmaakmeester EN 
	// - Of taak is afgerond / uitgevoerd
	// - Of taak is uit het verleden
	if ((!$schoonmaakmeester) && (($taakInfo['gedaan'] == 'ja') || ($taakInfo['gedaan'] == 'nooit') || (intval(str_replace("-", "", $taakInfo['week'])) < intval(date("oW")))))
		$return .= $taakInfo['taakNaam'];
	else {
		$return .= '
			<select onchange="submitNaamTaak();" id="taakNaam" onFocus="setCurrentValueNaam();" onclick="setCurrentValueNaam();">';
		$selected = ($taakInfo['taakNaam'] == "") ? ' selected="selected"' : '';
		$return .= '<option value="-"'. $selected .'>&nbsp;</option>';
		foreach($config['taken'] as $taak) {
			$selected = ($taak == $taakInfo['taakNaam']) ? ' selected="selected"' : '';
			$return .= '<option value="'. $taak .'"'. $selected .'>'. $taak .'</option>';
		}
		if (($taakInfo['taakNaam'] != '') && (!in_array($taakInfo['taakNaam'], $config['taken'])))
			$return .= '<option value="'. $taakInfo['taakNaam'] .'" selected="selected">'. $taakInfo['taakNaam'] .'</option>';
		$return .= '
				</select>';
	}
	$return .= '
		</td>
	</tr>
	<tr>
		<td>
			Door
		</td>
		<td>
			'. $taakInfo['persoonNaam'] .'
		</td>
	</tr>
	<tr>
		<td>
			Deadline
		</td>
		<td>
			';
	if ($taakInfo['deadline'] == '0000-00-00') {
		echo '&nbsp;';
	} else {
		$deadline = sqldate2phpdate($taakInfo['deadline']);
		$return .= date("j", $deadline) .' '. $maandenTxt[date("n", $deadline)-1] .' '. date("Y", $deadline);
	}
	$return .= '
		</td>
	</tr>
	<tr>
		<td>
			Gedaan
		</td>
		<td>';
	switch ($taakInfo['gedaan']) {
		case '':
			$return .= '&nbsp;';
			break;
		case 'nogNiet':
			$return .= 'Nog niet';
			break;
		case 'nooit';
			$return .= 'Nooit';
			break;
		case 'ja':
			$return .= 'Ja, op ';
			$gedaanOpDag = sqldate2phpdate($taakInfo['gedaanOp']);
			$return .= date("j", $gedaanOpDag) .' '. $maandenTxt[date("n", $gedaanOpDag)-1] .' '. date("Y", $gedaanOpDag);
			break;
	}
	$return .= '
		</td>
	</tr>
	<tr>
		<td>
			Boete
		</td>
		<td>';
	if ($taakInfo['boete'] != '0.00')
		$return .= '&euro; ' .str_replace('.', ',', $taakInfo['boete']);
	else
		$return .= '&nbsp;';
	$return .= '
		</td>
	</tr>';
	if ($schoonmaakmeester && ($taakId != 0)):
	$return .= '
	<tr>
		<td colspan="2">
			&nbsp;
		</td>
	</tr>
	<tr>
		<td>
			Deadline
		</td>
		<td>
			<select id="deadlineDag" onchange="$.post(\'process.php\', {soort: \'taakInfo\', save: \'deadline\', taakId: \''. $taakId .'\', deadlineDag: $(\'#deadlineDag\').val(), deadlineMaand: $(\'#deadlineMaand\').val(), deadlineJaar: $(\'#deadlineJaar\').val()},function(data){showMelding(data);maakStatistieken();$(\'body\').click();});">';
	$deadline = sqldate2phpdate($taakInfo['deadline']);
	
	for ($i=1; $i<=31; $i++) {
		$return .= '<option value="'. $i .'"';
		if ($i == date("j", $deadline))
			$return .= ' selected="selected"';
		$return .= '>'. $i .'</option>';
	}
	$return .= '
			</select>
			 
			<select id="deadlineMaand" onchange="$.post(\'process.php\', {soort: \'taakInfo\', save: \'deadline\', taakId: \''. $taakId .'\', deadlineDag: $(\'#deadlineDag\').val(), deadlineMaand: $(\'#deadlineMaand\').val(), deadlineJaar: $(\'#deadlineJaar\').val()},function(data){showMelding(data);maakStatistieken();$(\'body\').click();});">';
	for ($i=1; $i<=12; $i++) {
		$return .= '<option value="'. $i .'"';
		if ($i == date("n" ,$deadline))
			$return .= ' selected="selected"';
		$return .= '>'. $maandenTxt[$i-1] .'</option>';
	}
	$return .= '
			</select>
			 
			<select id="deadlineJaar" onchange="$.post(\'process.php\', {soort: \'taakInfo\', save: \'deadline\', taakId: \''. $taakId .'\', deadlineDag: $(\'#deadlineDag\').val(), deadlineMaand: $(\'#deadlineMaand\').val(), deadlineJaar: $(\'#deadlineJaar\').val()},function(data){showMelding(data);maakStatistieken();$(\'body\').click();});">';
	for ($i=(date("Y")-2); $i<=(date("Y")+2); $i++) {
		$return .= '<option value="'. $i .'"';
		if ($i == date("Y" ,$deadline))
			$return .= ' selected="selected"';
		$return .= '>'. $i .'</option>';
	}
	$return .= '
			</select>
		</td>
	</tr>
	<tr>
		<td>
			Taak gedaan
		</td>
		<td>
			<label for="persoonInfoForm_gedaan_nogNiet">
				<input type="radio" name="gedaan" id="persoonInfoForm_gedaan_nogNiet" value="nogNiet" '; if ($taakInfo['gedaan'] == 'nogNiet') $return .= 'checked="checked" '; $return .= 'onchange="submitWijzigGedaan(\'nogNiet\');" />Nog niet &nbsp; 
			</label>
			<label for="persoonInfoForm_gedaan_ja">
				<input type="radio" name="gedaan" id="persoonInfoForm_gedaan_ja" value="ja" '; if ($taakInfo['gedaan'] == 'ja') $return .= 'checked="checked" '; $return .= 'onchange="submitWijzigGedaan(\'ja\');" />Ja &nbsp; 
			</label>
			<label for="persoonInfoForm_gedaan_nooit">
				<input type="radio" name="gedaan" id="persoonInfoForm_gedaan_nooit" value="nooit" '; if ($taakInfo['gedaan'] == 'nooit') $return .= 'checked="checked" '; $return .= 'onchange="submitWijzigGedaan(\'nooit\');" />Nooit
			</label>
		</td>
	</tr>
	<tr id="persoonInfoForm_gedaanOp" style="visibility:';
	$return .= ($taakInfo['gedaan'] == 'ja') ? 'visible' : 'hidden';
	$return .= '">
		<td>
			Gedaan op
		</td>
		<td>
			<select id="gedaanOpDag" onchange="updateBoete();$.post(\'process.php\', {soort: \'taakInfo\', save: \'gedaanOp\', taakId: \''. $taakId .'\', gedaanOpDag: $(\'#gedaanOpDag\').val(), gedaanOpMaand: $(\'#gedaanOpMaand\').val(), gedaanOpJaar: $(\'#gedaanOpJaar\').val(), boete: $(\'#boete\').val()},function(data){showMelding(data);maakStatistieken();});">';
	if ($taakInfo['gedaanOp'] == '0000-00-00')
		$gedaanOp = time();
	else
		$gedaanOp = sqldate2phpdate($taakInfo['gedaanOp']);
	
	for ($i=1; $i<=31; $i++) {
		$return .= '<option value="'. $i .'"';
		if ($i == date("j", $gedaanOp))
			$return .= ' selected="selected"';
		$return .= '>'. $i .'</option>';
	}
	$return .= '
			</select>
			 
			<select id="gedaanOpMaand" onchange="updateBoete();$.post(\'process.php\', {soort: \'taakInfo\', save: \'gedaanOp\', taakId: \''. $taakId .'\', gedaanOpDag: $(\'#gedaanOpDag\').val(), gedaanOpMaand: $(\'#gedaanOpMaand\').val(), gedaanOpJaar: $(\'#gedaanOpJaar\').val(), boete: $(\'#boete\').val()},function(data){showMelding(data);maakStatistieken();});">';
	for ($i=1; $i<=12; $i++) {
		$return .= '<option value="'. $i .'"';
		if ($i == date("n" ,$gedaanOp))
			$return .= ' selected="selected"';
		$return .= '>'. $maandenTxt[$i-1] .'</option>';
	}
	$return .= '
			</select>
			 
			<select id="gedaanOpJaar" onchange="updateBoete();$.post(\'process.php\', {soort: \'taakInfo\', save: \'gedaanOp\', taakId: \''. $taakId .'\', gedaanOpDag: $(\'#gedaanOpDag\').val(), gedaanOpMaand: $(\'#gedaanOpMaand\').val(), gedaanOpJaar: $(\'#gedaanOpJaar\').val(), boete: $(\'#boete\').val()},function(data){showMelding(data);maakStatistieken();});">';
	for ($i=(date("Y")-2); $i<=(date("Y")+2); $i++) {
		$return .= '<option value="'. $i .'"';
		if ($i == date("Y" ,$gedaanOp))
			$return .= ' selected="selected"';
		$return .= '>'. $i .'</option>';
	}
	$return .= '
			</select>
		</td>
	</tr>
	<tr>
		<td>
			Boete
		</td>
		<td>
			<input type="text" name="boete" id="boete" size="5" value="'. str_replace('.', ',', $taakInfo['boete']) .'" onchange="$.post(\'process.php\', {soort: \'taakInfo\', save: \'boeteWijzigen\', taakId: \''. $taakId .'\', boete: this.value},function(data){showMelding(data);maakStatistieken();updateKleur();});" />
		</td>
	</tr>
	</tr>
	<tr>
		<td>
			&nbsp;
		</td>
		<td>
			<div id="herinneringNaDeadlineMail" style="display:none">
				<a href="#" class="disableLink" onclick="$.post(\'process.php\', {soort: \'taakInfo\', taakId: \''. $taakId .'\', herinneringNaDeadlineMail: \'true\'},function(data){showMelding(data);});mogelijkWeergevenHerinneringNaDeadlineMail=false;mogelijkWeergevenMail();">Herinnering mail sturen</a>
			</div>
			<div id="boeteMeldingMail" style="display:none">
				<a href="#" class="disableLink" onclick="$.post(\'process.php\', {soort: \'taakInfo\', taakId: \''. $taakId .'\', boeteMeldingMail: \'true\'},function(data){showMelding(data);});mogelijkWeergevenBoeteMeldingMail=false;mogelijkWeergevenMail();">Boete melding mail sturen</a>
			</div>
		</td>
	</tr>';
	endif;
	$return .= '
</table>';
	
} else if (($soort == 'boeteTeBetalenInfo')||($soort == 'boeteVoldaanInfo')) {
	// te betalen of voldane boete informatie
	$persoonId = $_GET['persoonId'];
	
	$weekGroterKleiner = ($soort == 'boeteTeBetalenInfo') ? '>' : '<=';
	$query = "SELECT * FROM taken WHERE persoonId='". $persoonId ."' AND boete > 0.00 AND week ". $weekGroterKleiner ." '". $config['boetesVoldaanGrens'] ."' ORDER BY week DESC";
	$result = $link->query($query);
	if($result->num_rows == 0) {
		$return .= "Er zijn geen ";
		$return .= ($soort == 'boeteTeBetalenInfo') ? "openstaande" : "voldane";
		$return .= " boetes gevonden voor deze persoon.";
	} else {
		$return .= '
<table width="100%" cellspacing="3">
	<tr>
		<td>
			<b>Dienst</b>
		</td>
		<td>
			<b>Deadline</b>
		</td>
		<td>
			<b>Gedaan</b>
		</td>
		<td>
			<b>Boete</b>
		</td>
	</tr>';
		while ($boeteInfo = mysqli_fetch_assoc($result)) {
			$return .= '
	<tr>
		<td>
			'. $boeteInfo['taakNaam'] .'
		</td>
		<td>
			';
			$deadline = sqldate2phpdate($boeteInfo['deadline']);
			$return .= date("j", $deadline) .' '. $maandenTxt[date("n", $deadline)-1] .' '. date("Y", $deadline);
			$return .= '
		</td>
		<td>
			';
			switch ($boeteInfo['gedaan']) {
				case 'nogNiet':
					$return .= 'Nog niet';
					break;
				case 'nooit';
					$return .= 'Nooit';
					break;
				case 'ja':
					$return .= 'Ja, op ';
					$gedaanOp = sqldate2phpdate($boeteInfo['gedaanOp']);
					$return .= date("j", $gedaanOp) .' '. $maandenTxt[date("n", $gedaanOp)-1] .' '. date("Y", $gedaanOp);
					break;
			}
			$return .= '
		</td>
		<td>
			&euro; '. str_replace('.', ',', $boeteInfo['boete']) .'
		</td>
	</tr>';
		}
		
		$return .= '
</table>';
	}
	
} else if ($soort == 'personen') {
	// alle personen teruggeven als XML
	$query = "SELECT persoonId, persoonNaam FROM personen ORDER BY kolomNr ASC";
	$result = $link->query($query);
	$count = $result->num_rows;
	if($result->num_rows == 0) {
		echo "Geen personen gevonden";
		exit;
	}
	
	$returnXML = true;
	$return .= '
<personen>';
	
	while ($persoon = mysqli_fetch_assoc($result)) {
		$return .= '
	<persoon>
		<persoonId>'. $persoon['persoonId'] .'</persoonId>
		<persoonNaam><![CDATA['. html_entity_decode($persoon['persoonNaam'], ENT_QUOTES, 'UTF-8') .']]></persoonNaam>
	</persoon>';
	}
	
	$return .= '
</personen>';
} else if ($soort == 'weken') {
	// alle weken teruggeven als XML
	$antWeken = intval($_GET['antWeken']); // als antWeken negatief is, zijn het weken in het verleden
	$huidigeDag = $_GET['huidigeDag']; // wordt gebruikt om weken in het verleden of toekomst op te vragen
	if (empty($huidigeDag)) {
		$huidigeDag = time();
	} else {
		$huidigeDag = sqldate2phpdate($huidigeDag);
	}
	
	$returnXML = true;	
	$return .= '
<weken>
	';
	
	if ($antWeken < 0) {
		// bij weken in verleden de huidige week niet weergeven
		$huidigeDag -= 7 * 24 * 60 * 60;
	}
	
	for ($i = 0; $i < abs($antWeken); $i++) {
		if ($antWeken >= 0) {
			$date = $huidigeDag + ($i * 7 * 24 * 60 * 60);
		} else {
			$date = $huidigeDag - ($i * 7 * 24 * 60 * 60);
		}
		$deadline = getDeadlineDag($date);
		$deadlineTxt = date("j-n", $deadline);
		if (date("Y", $deadline) != date("Y")) // vorig of volgend jaar -> jaar ook weergeven
			$deadlineTxt = date("j-n-Y", $deadline);
		
		$return .= '<week>
		<weekId>'. date("o-W", $date) .'</weekId>
		<weekNr>'. date("W", $date) .'</weekNr>
		<jaar>'. date("o", $date) .'</jaar>
		<deadline>'. $deadlineTxt .'</deadline>
	</week>';
	}
	$return .= '
</weken>';
} else if ($soort == 'taken') {
	// alle taken in een bepaalde periode teruggeven als XML
	$antWeken = intval($_GET['antWeken']); // als antWeken negatief is, zijn het weken in het verleden
	$huidigeDag = $_GET['huidigeDag']; // wordt gebruikt om weken in het verleden of toekomst op te vragen
	if (empty($huidigeDag)) {
		$huidigeDag = time();
	} else {
		$huidigeDag = sqldate2phpdate($huidigeDag);
	}
	
	$maxDag = $huidigeDag + ($antWeken * 7 * 24 * 60 * 60);
	$query = "SELECT * FROM taken WHERE ";
	if ($antWeken > 0) {
		// taken in toekomst opvragen
		$query .= "week >= '". date('o-W', $huidigeDag) ."' AND week < '". date('o-W', $maxDag) ."'";
	} else {
		// taken in verleden opvragen
		$query .= "week < '". date('o-W', $huidigeDag) ."' AND week >= '". date('o-W', $maxDag) ."'";
	}
	$result = $link->query($query);
	
	$returnXML = true;
	$return .= '
<taken>';
	
	while ($taak = mysqli_fetch_assoc($result)) {
		$return .= '
	<taak>
		<taakId>'. $taak['taakId'] .'</taakId>
		<week>'. $taak['week'] .'</week>
		<taakNaam><![CDATA['. html_entity_decode($taak['taakNaam'], ENT_QUOTES, 'UTF-8') .']]></taakNaam>
		<persoonId>'. $taak['persoonId'] .'</persoonId>
		<gedaan>'. $taak['gedaan'] .'</gedaan>
		<boete>'. $taak['boete'] .'</boete>
	</taak>';
	}
	$return .= '
</taken>';
} else if ($soort == 'statistieken') {
	// alle statistieken teruggeven als XML
	$stats = array();
	
	$query = "SELECT persoonId, persoonNaam FROM personen";
	$result = $link->query($query);
	while ($persoon = mysqli_fetch_assoc($result)) {
		$stats[$persoon['persoonId']] = array(
			"persoonId" => $persoon['persoonId'],
			"persoonNaam" => $persoon['persoonNaam'],
			"boeteTeBetalen" => 0.00,
			"boeteVoldaan" => 0.00,
			"opTijd" => 0,
			"teLaat" => 0,
			"nooitGedaan" => 0
		);
	}
	
	$query = "SELECT * FROM taken";
	$result = $link->query($query);
	while ($taak = mysqli_fetch_assoc($result)) {
		$persoonId = $taak['persoonId'];
		
		if ($taak['boete'] != '0.00') {
			// boete bij stats optellen
			if ($taak['week'] <= $config['boetesVoldaanGrens']) {
				// boete is geincasseerd
				$stats[$persoonId]['boeteVoldaan'] += floatval($taak['boete']);
			} else {
				// boete moet nog betaald worden
				$stats[$persoonId]['boeteTeBetalen'] += floatval($taak['boete']);
			}
		}
		
		if ($taak['gedaan'] == 'ja') {
			if (sqldate2phpdate($taak['gedaanOp']) <=  sqldate2phpdate($taak['deadline'])) {
				// op tijd taak gedaan
				$stats[$persoonId]['opTijd']++;
			} else {
				// te laat taak gedaan
				$stats[$persoonId]['teLaat']++;
			}
		} else if ($taak['gedaan'] == 'nooit') {
			// taak nooit gedaan
			$stats[$persoonId]['nooitGedaan']++;
		}
	}
	
	$returnXML = true;
	$return .= '
<statistieken>';
	
	foreach($stats as $persoon) {
		$antTaken = $persoon['opTijd'] + $persoon['teLaat'] + $persoon['nooitGedaan'];
		
		$deelOpTijd = 0;
		$deelTeLaat = 0;
		$deelNooitGedaan = 0;
		
		if ($antTaken != 0) {
			$deelOpTijd = round($persoon['opTijd'] / $antTaken * 100);
			$deelTeLaat = round($persoon['teLaat'] / $antTaken * 100);
			$deelNooitGedaan = round($persoon['nooitGedaan'] / $antTaken * 100);
		}
		
		if (($persoon['boeteTeBetalen']*100) % 100 == 0)
			$persoon['boeteTeBetalen'] = round($persoon['boeteTeBetalen'], 0);
		else
			$persoon['boeteTeBetalen'] = number_format($persoon['boeteTeBetalen'], 2, ',', '.');
		
		if (($persoon['boeteVoldaan']*100) % 100 == 0)
			$persoon['boeteVoldaan'] = round($persoon['boeteVoldaan'], 0);
		else
			$persoon['boeteVoldaan'] = number_format($persoon['boeteVoldaan'], 2, ',', '.');
		
		$return .= '
	<persoon>
		<persoonId>'. $persoon['persoonId'] .'</persoonId>
		<persoonNaam><![CDATA['. html_entity_decode($persoon['persoonNaam'], ENT_QUOTES, 'UTF-8') .']]></persoonNaam>
		<boeteTeBetalen>'. $persoon['boeteTeBetalen'] .'</boeteTeBetalen>
		<boeteVoldaan>'. $persoon['boeteVoldaan'] .'</boeteVoldaan>
		<opTijd>'. $persoon['opTijd'] .' ('. $deelOpTijd .'%)</opTijd>
		<teLaat>'. $persoon['teLaat'] .' ('. $deelTeLaat .'%)</teLaat>
		<nooitGedaan>'. $persoon['nooitGedaan'] .' ('. $deelNooitGedaan .'%)</nooitGedaan>
	</persoon>';
	}
	
	$return .= '
</statistieken>';
} else if ($soort == 'soortenTaken') {
	// alle verschillende soorten taken teruggeven als XML
	$returnXML = true;
	$return .= '
<taken>';
	
	foreach ($config['taken'] as $taak) {
		$return .= '
	<taak>
		<taakNaam><![CDATA['. html_entity_decode($taak, ENT_QUOTES, 'UTF-8') .']]></taakNaam>
	</taak>';
	}
	$return .= '
</taken>';
}

if ($returnXML) {
	header("content-type:application/xml;charset=utf-8");
	header("Cache-Control: no-cache, must-revalidate");
	echo '<?xml version="1.0" encoding="utf-8"?>
';
}
echo $return;
?>