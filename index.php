<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("include.php");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
	<title>Schoonmaakrooster Stars on 45</title>
	<link rel="stylesheet" type="text/css" href="style.css" />
	<!--[if IE]><script type="text/javascript" src="excanvas_compiled.js"></script><![endif]-->
	<script type="text/javascript" src="jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="jquery.bt.min.js"></script>
	<script type="text/javascript" src="include.js"></script>
	<script type="text/javascript">
	<!--
	
	$(document).ready(function(){
		// melding aan het begin niet weergeven
		hideMelding(0);
		
		// tooltips weergeven
		showTooltips();
		
		// roosters maken. Deze functie roept weer andere functies aan, om zo de tijdlijn met asynchroom opvragen van bestanden te verzorgen.
		// Volgorde hiervan is als volgt:
		// 1. kolommen (personen)
		// 2. rijen (weken)
		// 3. inhoud (taken)
		maakRoosters();
		
		// statistieken tabel maken
		statistiekenHTML = $("#statistieken").html();
		maakStatistieken();
	});
	
	// variabelen buiten scope functie declareren, zodat het gebruikt kan worden bij meerdere functies
	var personenIds = new Array();
	var huidigeDate = new Date();
	var huidigeDagToekomst = new Date(huidigeDate.getTime());
	var huidigeDagVerleden = new Date(huidigeDate.getTime());
	var kolomHTML = '';
	var statistiekenHTML = '';
	
	// wijzigt de huidige dag. Oftewel, er wordt op link naar toekomst/verleden geklikt 
	// @param boolean isRoosterToekomst: of het rooster (tabel) in de toekomst is (=true) of verleden is (=false)
	// @param boolean naarToekomst: of het rooster (tabel) verder naar de toekomst gaat (=true) of het verleden (=false)
	function changeHuidigeDag(isRoosterToekomst, naarToekomst) {
		if (isRoosterToekomst) {
			var antWekenSelected = parseInt($('#antWekenToekomst').val());
			var wijzigingDagen = naarToekomst ? antWekenSelected*7 : 0-antWekenSelected*7;
			huidigeDagToekomst.setDate(huidigeDagToekomst.getDate() + wijzigingDagen);
			
			if (huidigeDagToekomst > huidigeDate) {
				// link naar verleden aan
				$('#changeHuidigeDag_roosterToekomst_naarVerleden').attr('style', ' ');
				$('#changeHuidigeDag_roosterToekomst_naarVerleden').unbind('click');
				$('#changeHuidigeDag_roosterToekomst_naarVerleden').bind('click', function() {
					$('body').click();
					changeHuidigeDag(true, false);
				});
			} else {
				// link naar verleden uit
				$('#changeHuidigeDag_roosterToekomst_naarVerleden').attr('style', 'color:grey;cursor:default;');
				$('#changeHuidigeDag_roosterToekomst_naarVerleden').unbind('click');
				huidigeDagToekomst = new Date(huidigeDate.getTime());
			}
		} else {
			var antWekenSelected = parseInt($('#antWekenVerleden').val());
			var wijzigingDagen = naarToekomst ? antWekenSelected*7 : 0-antWekenSelected*7;
			huidigeDagVerleden.setDate(huidigeDagVerleden.getDate() + wijzigingDagen);
			
			if (huidigeDagVerleden < huidigeDate) {
				// link naar toekomst aan
				$('#changeHuidigeDag_roosterVerleden_naarToekomst').attr('style', ' ');
				$('#changeHuidigeDag_roosterVerleden_naarToekomst').unbind('click');
				$('#changeHuidigeDag_roosterVerleden_naarToekomst').bind('click', function() {
					$('body').click();
					changeHuidigeDag(false, true);
				});
			} else {
				// link naar toekomst uit
				$('#changeHuidigeDag_roosterVerleden_naarToekomst').attr('style', 'color:grey;cursor:default;');
				$('#changeHuidigeDag_roosterVerleden_naarToekomst').unbind('click');
				huidigeDagVerleden = new Date(huidigeDate.getTime());
			}
		}
		
		maakRoostersWeken(isRoosterToekomst);
	}
	
	// geeft alle gele ballonnetjes weer
	function showTooltips() {
		// bepaalde links (die class "disableLink" hebben) mogen niet werken, omdat in het adres (=href) de locatie voor de ajax oproep staat. onclick="return false;" was ook een mogelijkheid, maar gaf conflicten met tooltip trigger.
		$(".disableLink").live('click', function(event){
			event.preventDefault();
		});
		
		$('.persoonTooltip').live('click', function() {
			$(this).bt({
				trigger: 'none',
				closeWhenOthersOpen: true,
				width: 300,
				ajaxPath: ["$(this).attr('href')"],
				ajaxLoading: '<p align="center"><img src="loading.gif"/><br /><br /><blink>Loading...</blink><br /><br /></p>',
				ajaxCache: false
			})
			$(this).btOn();
		});
		
		$('.taakTooltip').live('click', function() {
			$(this).bt({
				trigger: 'none',
				closeWhenOthersOpen: true,
				width: 300,
				ajaxPath: ["$(this).attr('alt')"],
				ajaxLoading: '<p align="center"><img src="loading.gif"/><br /><br /><blink>Loading...</blink><br /><br /></p>',
				ajaxCache: false
			});
			$(this).btOn();
		});
		
		$('.boeteTooltip').live('click', function() {
			$(this).bt({
				trigger: 'none',
				closeWhenOthersOpen: true,
				width: 425,
				ajaxPath: ["$(this).attr('href')"],
				ajaxLoading: '<p align="center"><img src="loading.gif"/><br /><br /><blink>Loading...</blink><br /><br /></p>',
				ajaxCache: false
			})
			$(this).btOn();
		});
		
		// toolTips sluiten als op esc(ape) toets wordt gedrukt
		$(window).keydown(function(event){
			if (event.keyCode == 27) {
				$('body').click();
			}
		});
	}

	
	// maakt de roosters, zowel het rooster in de toekomst als het verleden
	function maakRoosters() {
		// personen opvragen en kolommen maken
		$.get("request.php", {soort: 'personen'}, function(xml){
			kolomHTML = "<tr>\n<td>&nbsp;</td>\n";
			
			// voor elke persoon informatie opvragen en kolom voor maken
			$("persoon",xml).each(function(i) {
				persoonId = $(this).find("persoonId").text();
				persoonNaam = $(this).find("persoonNaam").text();
				
				personenIds[personenIds.length] = persoonId;
				
				kolomHTML += "\n<td><a href=\"request.php?soort=persoonInfo&persoonId="+ persoonId +"\" class=\"disableLink persoonTooltip\">"+ persoonNaam +"</a></td>";
			});
			kolomHTML += "\n</tr>";
			
			// colspan juist zetten bij header/footer cel in rooster
			$('.roosterHeaderFooter').attr('colspan', personenIds.length+1);
			
			maakRoostersWeken(true);
			maakRoostersWeken(false);
		});
	}
	
	
	// maakt voor elke week een rij in de roosters
	// @param boolean isRoosterToekomst: of het rooster (tabel) dat gemaakt moet worden toekomst is (=true) of verleden is (=false)
	function maakRoostersWeken(isRoosterToekomst) {
		// periode in rooster achterhalen
		var antWeken;
		var huidigeDagRequest;
		
		if (isRoosterToekomst) {
			antWeken = parseInt($('#antWekenToekomst').val());
			huidigeDagRequest = huidigeDagToekomst.getFullYear() +'-'+ (huidigeDagToekomst.getMonth()+1) +'-'+ huidigeDagToekomst.getDate();
		} else {
			antWeken = 0 - parseInt($('#antWekenVerleden').val());
			huidigeDagRequest = huidigeDagVerleden.getFullYear() +'-'+ (huidigeDagVerleden.getMonth()+1) +'-'+ huidigeDagVerleden.getDate();
		}
		
		// weken opvragen en rijen maken
		$.get("request.php", {soort: 'weken', antWeken: antWeken, huidigeDag: huidigeDagRequest}, function(xml){
			var html = kolomHTML; // oorspronkelijke kolommen(=1e rij tabel) gebruiken
			var rijNr = 0; // wordt gebruikt om de helft van de rijen een achtergrondkleur te geven
			
			// voor elke persoon informatie opvragen en kolom voor maken
			$('week',xml).each(function(i) {
				weekId = $(this).find("weekId").text();
				weekNr = $(this).find("weekNr").text();
				jaar = $(this).find("jaar").text();
				deadline = $(this).find("deadline").text();
				
				rijNr++;
				html += "\n<tr";
				if (rijNr % 2 == 1)
					html += ' class="bgcolor"';
				html += ">\n<td>Wk "+ weekNr +" ("+ deadline +")</td>";
				
				// alle personen (=kolommen) afgaan en elke cel aanmaken
				var i;
				for (i in personenIds) {
					html += "\n<td id=\"taakcel_"+ weekId +"_"+ personenIds[i] +"\" alt=\"request.php?soort=taakInfo&taakId=0&persoonId="+ personenIds[i] +"&weekId="+ weekId +"&isRoosterToekomst="+ isRoosterToekomst +"\" class=\"taakTooltip\">&nbsp;</td>";
				}
				
				html += "\n</tr>";
			});
			
			if (isRoosterToekomst)
				$('#roosterToekomst').html(html);
			else
				$('#roosterVerleden').html(html);
			
			// taken opvragen en in rooster zetten
			zetTakenInRooster(isRoosterToekomst);
		}).fail(function(){
			showMelding("De roosterweken konden niet worden geladen. Laad de pagina opnieuw en controleer de databaseverbinding.");
		});
	}
	
	// taken opvragen en XML bestand in rooster zetten
	// @param boolean isRoosterToekomst: of de taken in het rooster (tabel) in de toekomst is (=true) of verleden is (=false)
	function zetTakenInRooster(isRoosterToekomst) {
		// periode in rooster achterhalen
		var antWeken;
		var huidigeDagRequest;
		
		if (isRoosterToekomst) {
			antWeken = parseInt($('#antWekenToekomst').val());
			huidigeDagRequest = huidigeDagToekomst.getFullYear() +'-'+ (huidigeDagToekomst.getMonth()+1) +'-'+ huidigeDagToekomst.getDate();
		} else {
			antWeken = 0 - parseInt($('#antWekenVerleden').val());
			huidigeDagRequest = huidigeDagVerleden.getFullYear() +'-'+ (huidigeDagVerleden.getMonth()+1) +'-'+ huidigeDagVerleden.getDate();
		}
		
		$.get("request.php", {soort: 'taken', antWeken: antWeken, huidigeDag: huidigeDagRequest}, function(xml){
			// voor elke taak informatie opvragen
			$('taak',xml).each(function(i) {
				taakId = $(this).find("taakId").text();
				week = $(this).find("week").text();
				taakNaam = $(this).find("taakNaam").text();
				persoonId = $(this).find("persoonId").text();
				gedaan = $(this).find("gedaan").text();
				boete = $(this).find("boete").text();
				
				// kleur link bepalen
				var aStyle = '';
				if (gedaan != 'nogNiet') {
					if (boete == '0.00')
						aStyle = ' style="color:green"';
					else
						aStyle = ' style="color:red"';
				}
				
				celId = 'taakcel_' + week + '_' + persoonId;
				$('#'+celId).attr('alt', 'request.php?soort=taakInfo&isRoosterToekomst='+ isRoosterToekomst +'&taakId='+ taakId +'&persoonId='+ persoonId +'&weekId='+ week);
				$('#'+celId).html('<a href="#" class="disableLink taakTooltip" alt="request.php?soort=taakInfo&isRoosterToekomst='+ isRoosterToekomst +'&taakId='+ taakId +'&persoonId='+ persoonId +'&weekId='+ week +'"'+ aStyle +'>'+ taakNaam +'</a>');
			});
		}).fail(function(){
			showMelding("De taken konden niet worden geladen. Controleer de databaseverbinding en laad het rooster opnieuw.");
		});
	}
	
	// maakt de statistieken tabel
	function maakStatistieken() {
		// statistieken opvragen en rijen maken
		var html = statistiekenHTML; // oorspronkelijke kolommen(=1e rij tabel) gebruiken
		
		$.get("request.php", {soort: 'statistieken'}, function(xml){
			// voor elke persoon informatie opvragen en rij maken
			var rijNr = 0; // wordt gebruikt om de helft van de rijen een achtergrondkleur te geven
			$('persoon',xml).each(function(i) {
				persoonId = $(this).find("persoonId").text();
				persoonNaam = $(this).find("persoonNaam").text();
				boeteTeBetalen = $(this).find("boeteTeBetalen").text();
				boeteVoldaan = $(this).find("boeteVoldaan").text();
				opTijd = $(this).find("opTijd").text();
				teLaat = $(this).find("teLaat").text();
				nooitGedaan = $(this).find("nooitGedaan").text();
				
				rijNr++;
				html += "\n\
				<tr";
				if (rijNr % 2 == 1)
					html += ' class="bgcolor"';
				html += ">\n\
					<td>"+ persoonNaam +"</td>\n\
					<td>";
					
				if (boeteTeBetalen != "0")
					html += "<a href=\"request.php?soort=boeteTeBetalenInfo&persoonId="+ persoonId +"\" class=\"disableLink boeteTooltip\">&euro;"+ boeteTeBetalen +"</a>";
				else
					html += "&euro; "+ boeteTeBetalen;
				
				html += "</td>\n\
					<td>";
				
				if (boeteVoldaan != "0")
					html += "<a href=\"request.php?soort=boeteVoldaanInfo&persoonId="+ persoonId +"\" class=\"disableLink boeteTooltip\">&euro;"+ boeteVoldaan +"</a>"
				else
					html += "&euro; "+ boeteVoldaan;
				
				html += "</td>\n\
					<td>"+ opTijd +"</td>\n\
					<td>"+ teLaat +"</td>\n\
					<td>"+ nooitGedaan +"</td>\n\
				</tr>";
			});
			
			$('#statistieken').html(html);
		}); 
	}
	
	// -->
	</script>

</head>
<body>
<table width="100%" cellpadding="10" cellspacing="0">
	<tr>
		<td>
			<p class="titel">Schoonmaakrooster Stars on 45</p>
		</td>
		<td align="right">
			<a href="bier.php">Bierstrepen</a> &nbsp; | &nbsp;
			<a href="schoonmaakmeester.php">Schoonmaakmeester</a>
		</td>
	</tr>
</table>
<table width="700" align="center" id="melding">
	<tr>
		<td align="center" id="meldingTxt">
			&nbsp;
		</td>
	</tr>
</table>
<br />
<table class="tabel tabelHeader" width="100%" cellpadding="4" cellspacing="0" border="0">
	<thead>
		<tr>
			<td class="roosterHeaderFooter">
				Rooster in de toekomst
			</td>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td class="roosterHeaderFooter">
				Aantal weken weergegeven: <select id="antWekenToekomst" onchange="$('body').click();maakRoostersWeken(true);"><option value="5">5</option><option value="10" selected="selected">10</option><option value="15">15</option><option value="20">20</option><option value="25">25</option><option value="30">30</option><option value="40">40</option><option value="50">50</option></select> &nbsp; &nbsp; &nbsp; <a href="#" class="disableLink" id="changeHuidigeDag_roosterToekomst_naarVerleden" style="color:grey;cursor:default;">&lt; verder in verleden</a> &nbsp; | &nbsp; <a href="#" class="disableLink" onclick="$('body').click();changeHuidigeDag(true, true);">verder in toekomst &gt;</a>
			</td>
		</tr>
	</tfoot>
	<tbody id="roosterToekomst">
		<tr>
			<td>&nbsp;</td>
		</tr>
	</tbody>
</table>
<br />
<br />
<table class="tabel tabelHeader" width="100%" cellpadding="4" cellspacing="0" border="0">
	<thead>
		<tr>
			<td class="roosterHeaderFooter">
				Rooster in het verleden
			</td>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td class="roosterHeaderFooter">
				Aantal weken weergegeven: <select id="antWekenVerleden" onchange="$('body').click();maakRoostersWeken(false);"><option value="5" selected="selected">5</option><option value="10">10</option><option value="15">15</option><option value="20">20</option><option value="25">25</option><option value="30">30</option><option value="40">40</option><option value="50">50</option></select> &nbsp; &nbsp; &nbsp; <a href="#" class="disableLink" onclick="$('body').click();changeHuidigeDag(false, false);">&lt; verder in verleden</a> &nbsp; | &nbsp; <a href="#" class="disableLink" id="changeHuidigeDag_roosterVerleden_naarToekomst" style="color:grey;cursor:default;">verder in toekomst &gt;</a>
			</td>
		</tr>
	</tfoot>
	<tbody id="roosterVerleden">
		<tr>
			<td>&nbsp;</td>
		</tr>
	</tbody>
</table>
<br />
<br />
<table class="tabel tabelHeader" width="700" cellpadding="4" cellspacing="0" border="0">
	<thead>
		<tr>
			<td colspan="6">
				Boetes &amp; statistieken
			</td>
		</tr>
	</thead>
	<tbody id="statistieken">
		<tr>
			<td>&nbsp;</td>
			<td>Boetes nog te betalen</td>
			<td>Boetes reeds voldaan</td>
			<td>Op tijd</td>
			<td>Te laat</td>
			<td>Niet gedaan</td>
		</tr>
	</tbody>
</table>

</body>
</html>