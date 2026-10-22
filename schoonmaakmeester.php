<?php
$requireSchoonmaakmeester = true;
include("include.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
	<title>Schoonmaakrooster Stars on 45 - instellingen</title>
	<link rel="stylesheet" type="text/css" href="style.css" />
	<script type="text/javascript" src="jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="include.js"></script>
	<script type="text/javascript">
	<!--
	
	$(document).ready(function(){
		// melding aan het begin niet weergeven
		hideMelding(0);
		
		// taken in selectie forms
		updateTaken();
		
		// personen in selectie forms
		updatePersonen();
		
		// alle mail onderwerpen en berichten verbergen
		changeSelectMail();
	});
	
	function updateTaken() {
		$.get("request.php", {soort: 'soortenTaken'}, function(xml){
			// options verwijderen
			document.form.taakVerwijderen.options.length = 0;
			document.form.taakVeranderenVan.options.length = 0;
			document.form.taakOmdraaien1.options.length = 0;
			document.form.taakOmdraaien2.options.length = 0;
			
			document.form.taakVerwijderen.options[0] = new Option('- Selecteer taak -', '', true, true);
			document.form.taakVeranderenVan.options[0] = new Option('- Selecteer taak -', '', true, true);
			document.form.taakOmdraaien1.options[0] = new Option('- Selecteer taak -', '', true, true);
			document.form.taakOmdraaien2.options[0] = new Option('- Selecteer taak -', '', true, true);
			
			// voor elke taak informatie opvragen
			$('taak',xml).each(function(i) {
				taakNaam = $(this).find("taakNaam").text();
				
				document.form.taakVerwijderen.options[document.form.taakVerwijderen.length] = new Option(taakNaam, taakNaam);
				document.form.taakVeranderenVan.options[document.form.taakVeranderenVan.length] = new Option(taakNaam, taakNaam);
				document.form.taakOmdraaien1.options[document.form.taakOmdraaien1.length] = new Option(taakNaam, taakNaam);
				document.form.taakOmdraaien2.options[document.form.taakOmdraaien2.length] = new Option(taakNaam, taakNaam);
			});
		});
	};
	
	function updatePersonen() {
		$.get("request.php", {soort: 'personen'}, function(xml){
			// options verwijderen
			document.form.persoonVerwijderen.options.length = 0;
			document.form.persoonVeranderenVan.options.length = 0;
			document.form.personenOmdraaien1.options.length = 0;
			document.form.personenOmdraaien2.options.length = 0;
			
			document.form.persoonVerwijderen.options[0] = new Option('- Selecteer persoon -', '', true, true);
			document.form.persoonVeranderenVan.options[0] = new Option('- Selecteer persoon -', '', true, true);
			document.form.personenOmdraaien1.options[0] = new Option('- Selecteer persoon -', '', true, true);
			document.form.personenOmdraaien2.options[0] = new Option('- Selecteer persoon -', '', true, true);
			
			// voor elke taak informatie opvragen
			$('persoon',xml).each(function(i) {
				persoonId = $(this).find("persoonId").text();
				persoonNaam = $(this).find("persoonNaam").text();
				
				document.form.persoonVerwijderen.options[document.form.persoonVerwijderen.length] = new Option(persoonNaam, persoonId);
				document.form.persoonVeranderenVan.options[document.form.persoonVeranderenVan.length] = new Option(persoonNaam, persoonId);
				document.form.personenOmdraaien1.options[document.form.personenOmdraaien1.length] = new Option(persoonNaam, persoonId);
				document.form.personenOmdraaien2.options[document.form.personenOmdraaien2.length] = new Option(persoonNaam, persoonId);
			});
		});
	};
	
	function taakVeranderen() {
		if (($('#taakVeranderenVan').val() != '') && ($('#taakVeranderenNaar').val() != '')) {
			$.post('process.php', {
				soort: 'schoonmaakmeester', 
				save: 'taak', 
				taakVeranderenVan: $('#taakVeranderenVan').val(), 
				taakVeranderenNaar:  $('#taakVeranderenNaar').val()
			},function(data){
				showMelding(data);
				updateTaken();
				$('#taakVeranderenNaar').val('');
			});
		}
	}
	
	function persoonVeranderen() {
		if (($('#persoonVeranderenVan').val() != '') && ($('#persoonVeranderenNaar').val() != '')) {
			$.post('process.php', {
				soort: 'schoonmaakmeester', 
				save: 'persoon', 
				persoonVeranderenVan: $('#persoonVeranderenVan').val(), 
				persoonVeranderenNaar:  $('#persoonVeranderenNaar').val()
			},function(data){
				showMelding(data);
				updatePersonen();
				$('#persoonVeranderenNaar').val('');
			});
		}
	}
	
	function takenVerwijderen() {
		if (($('#takenVerwijderenWeek').val() != '') && ($('#takenVerwijderenJaar').val() != '')) {
			if (confirm('Weet je zeker dat je alle taken en boetes die voor deze week hebben plaatsgevonden definitief wilt verwijderen? \nDit kan niet meer ongedaan worden gemaakt!')) {
				$.post('process.php', {
					soort: 'schoonmaakmeester', 
					save: 'takenVerwijderen', 
					takenVerwijderenWeek: $('#takenVerwijderenWeek').val(),
					takenVerwijderenJaar: $('#takenVerwijderenJaar').val()
				},function(data){
					showMelding(data);
					$('#takenVerwijderenWeek').val('');
					$('#takenVerwijderenJaar').val('');
				});
			} else {
				$('#takenVerwijderenWeek').val('');
				$('#takenVerwijderenJaar').val('');
			}
		}
	}
	
	function exportExcel() {
		if (($('#exportExcelVanWeek').val() != '') && ($('#exportExcelVanJaar').val() != '') && ($('#exportExcelTotWeek').val() != '') && ($('#exportExcelTotJaar').val() != '')) {
			window.location='exportExcel.php?vanWeek='+ $("#exportExcelVanWeek").val() +'&vanJaar='+ $("#exportExcelVanJaar").val() +'&totWeek='+ $("#exportExcelTotWeek").val() +'&totJaar='+ $("#exportExcelTotJaar").val();
			$('#exportExcelVanWeek').val('');
			$('#exportExcelVanJaar').val('');
			$('#exportExcelTotWeek').val('');
			$('#exportExcelTotJaar').val('');
		}
	}
	
	function persoonToevoegenEmail() {
		var email = prompt('Wat is het emailadres van '+ document.form.persoonToevoegen.value +'?');
		if (email != null) {
			$.post('process.php', {
				soort: 'schoonmaakmeester', 
				save: 'persoon', 
				persoonToevoegen: document.form.persoonToevoegen.value,
				emailadres: email
			},function(data){
				showMelding(data);
				updatePersonen();
			});
		}
		document.form.persoonToevoegen.value='';
	}
	
	function changeSelectMail() {
		var geselecteerd = $("#selectMail").val();
		if (geselecteerd == '') {
			$(".herinneringBeginWeekMail").hide();
			$(".herinneringNaDeadlineMail").hide();
			$(".boeteMeldingMail").hide();
		} else if (geselecteerd == 'herinneringBeginWeekMail') {
			$(".herinneringBeginWeekMail").show();
			$(".herinneringNaDeadlineMail").hide();
			$(".boeteMeldingMail").hide();
		} else if (geselecteerd == 'herinneringNaDeadlineMail') {
			$(".herinneringBeginWeekMail").hide();
			$(".herinneringNaDeadlineMail").show();
			$(".boeteMeldingMail").hide();
		} else if (geselecteerd == 'boeteMeldingMail') {
			$(".herinneringBeginWeekMail").hide();
			$(".herinneringNaDeadlineMail").hide();
			$(".boeteMeldingMail").show();
		}
	}
	
	// -->
	</script>

</head>
<body>
<table width="100%" cellpadding="10" cellspacing="0">
	<tr>
		<td>
			<p class="titel">Schoonmaakrooster Stars on 45 - instellingen</p>
		</td>
		<td align="right">
			<a href="./">Overzicht</a>
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
<form action="#" method="post" name="form" onsubmit="return false;">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
	<tr valign="top">
		<td align="left" width="45%">
			<table class="tabel" width="100%" cellpadding="4" cellspacing="0" border="0">
				<thead>
					<tr>
						<td colspan="2">
							Taken
						</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td width="40%">
							Taak toevoegen
						</td>
						<td width="60%">
							<input type="text" name="taakToevoegen" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'taak', taakToevoegen: this.value},function(data){showMelding(data);updateTaken();});this.value=''" />
						</td>
					</tr>
					<tr class="bgcolor">
						<td>
							Taak verwijderen
						</td>
						<td>
							<select name="taakVerwijderen" onchange="confirm('Weet je zeker dat je deze taak wilt verwijderen?\nHiermee worden bestaande taken (zowel uit het verleden als in de toekomst) niet aangepast noch verwijderd.') ? $.post('process.php', {soort: 'schoonmaakmeester', save: 'taak', taakVerwijderen: this.value},function(data){showMelding(data);updateTaken();}) : this.selectedIndex=0;" >
								<option value=""> </option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							Verander taak
						</td>
						<td>
							<select name="taakVeranderenVan" id="taakVeranderenVan" onchange="taakVeranderen();">
								<option value=""> </option>
							</select>
							 naar 
							<input type="text" name="taakVeranderenNaar" id="taakVeranderenNaar" onchange="taakVeranderen();" />
						</td>
					</tr>
					<tr class="bgcolor">
						<td>
							Taken omdraaien
						</td>
						<td>
							<select name="taakOmdraaien1" id="taakOmdraaien1" onchange="if ($('#taakOmdraaien2').val() != '') $.post('process.php', {soort: 'schoonmaakmeester', save: 'taak', taakOmdraaien1: this.value, taakOmdraaien2: $('#taakOmdraaien2').val()},function(data){showMelding(data);updateTaken();});">
								<option value=""> </option>
							</select>
							 en
							<select name="taakOmdraaien2" id="taakOmdraaien2" onchange="if ($('#taakOmdraaien1').val() != '') $.post('process.php', {soort: 'schoonmaakmeester', save: 'taak', taakOmdraaien1: $('#taakOmdraaien1').val(), taakOmdraaien2: this.value},function(data){showMelding(data);updateTaken();});">
								<option value=""> </option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							Deadline taken
						</td>
						<td>
							<select name="deadlineDag" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'deadlineDag', deadlineDag: this.value},function(data){showMelding(data);});" >
							<?php
							foreach ($dagenTxt as $dagNr=>$dagTxt) {
								$dagNr++;
								echo '<option value="'. $dagNr .'"';
								if ($dagNr == $config['deadlineDag']) echo ' selected="selected"';
								echo '>'. $dagTxt .'</option>';
							}
							?>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
		</td>
		<td width="10%">
			&nbsp;
		</td>
		<td width="45%" align="right">
			<table class="tabel" width="100%" cellpadding="4" cellspacing="0" border="0">
				<thead>
					<tr>
						<td colspan="2">
							Boetes
						</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td width="40%">
							1 dag te laat
						</td>
						<td width="60%">
							&euro; 
							<select name="boete1dagEuro" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'boete', boete1dagEuro: this.value},function(data){showMelding(data);});">
								<?php
								$bedrag = explode(",", $config['boeteTeLaat'][1]);
								for($i = 0; $i < 100; $i++) {
									echo '<option value="'. $i .'"';
									if ($i == $bedrag[0]) echo ' selected="selected"';
									echo '>'. $i .'</option>';
								}
								?>
							</select>
							, 
							<select name="boete1dagCent" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'boete', boete1dagCent: this.value},function(data){showMelding(data);});">
								<?php
								for($i = 0; $i < 100; $i++) {
									$iTxt = ($i < 10) ? '0'. $i : $i;
									echo '<option value="'. $iTxt .'"';
									if ($i == $bedrag[1]) echo ' selected="selected"';
									echo '>'. $iTxt .'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr class="bgcolor">
						<td>
							2 dagen te laat
						</td>
						<td>
							&euro; 
							<select name="boete2dagEuro" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'boete', boete2dagEuro: this.value},function(data){showMelding(data);});">
								<?php
								$bedrag = explode(",", $config['boeteTeLaat'][2]);
								for($i = 0; $i < 100; $i++) {
									echo '<option value="'. $i .'"';
									if ($i == $bedrag[0]) echo ' selected="selected"';
									echo '>'. $i .'</option>';
								}
								?>
							</select>
							, 
							<select name="boete2dagCent" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'boete', boete2dagCent: this.value},function(data){showMelding(data);});">
								<?php
								for($i = 0; $i < 100; $i++) {
									$iTxt = ($i < 10) ? '0'. $i : $i;
									echo '<option value="'. $iTxt .'"';
									if ($i == $bedrag[1]) echo ' selected="selected"';
									echo '>'. $iTxt .'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							3 dagen te laat
						</td>
						<td>
							&euro; 
							<select name="boete3dagEuro" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'boete', boete3dagEuro: this.value},function(data){showMelding(data);});">
								<?php
								$bedrag = explode(",", $config['boeteTeLaat'][3]);
								for($i = 0; $i < 100; $i++) {
									echo '<option value="'. $i .'"';
									if ($i == $bedrag[0]) echo ' selected="selected"';
									echo '>'. $i .'</option>';
								}
								?>
							</select>
							, 
							<select name="boete3dagCent" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'boete', boete3dagCent: this.value},function(data){showMelding(data);});">
								<?php
								for($i = 0; $i < 100; $i++) {
									$iTxt = ($i < 10) ? '0'. $i : $i;
									echo '<option value="'. $iTxt .'"';
									if ($i == $bedrag[1]) echo ' selected="selected"';
									echo '>'. $iTxt .'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr class="bgcolor">
						<td>
							4 dagen te laat
						</td>
						<td>
							&euro; 
							<select name="boete4dagEuro" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'boete', boete4dagEuro: this.value},function(data){showMelding(data);});">
								<?php
								$bedrag = explode(",", $config['boeteTeLaat'][4]);
								for($i = 0; $i < 100; $i++) {
									echo '<option value="'. $i .'"';
									if ($i == $bedrag[0]) echo ' selected="selected"';
									echo '>'. $i .'</option>';
								}
								?>
							</select>
							, 
							<select name="boete4dagCent" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'boete', boete4dagCent: this.value},function(data){showMelding(data);});">
								<?php
								for($i = 0; $i < 100; $i++) {
									$iTxt = ($i < 10) ? '0'. $i : $i;
									echo '<option value="'. $iTxt .'"';
									if ($i == $bedrag[1]) echo ' selected="selected"';
									echo '>'. $iTxt .'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							Taak nooit gedaan
						</td>
						<td>
							&euro; 
							<select name="boeteNooitEuro" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'boete', boeteNooitEuro: this.value},function(data){showMelding(data);});">
								<?php
								$bedrag = explode(",", $config['boeteTeLaat'][0]);
								for($i = 0; $i < 100; $i++) {
									echo '<option value="'. $i .'"';
									if ($i == $bedrag[0]) echo ' selected="selected"';
									echo '>'. $i .'</option>';
								}
								?>
							</select>
							, 
							<select name="boeteNooitCent" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'boete', boeteNooitCent: this.value},function(data){showMelding(data);});">
								<?php
								for($i = 0; $i < 100; $i++) {
									$iTxt = ($i < 10) ? '0'. $i : $i;
									echo '<option value="'. $iTxt .'"';
									if ($i == $bedrag[1]) echo ' selected="selected"';
									echo '>'. $iTxt .'</option>';
								}
								?>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
		</td>
	</tr>
	<tr>
		<td width="45%">&nbsp;</td>
		<td width="10%">&nbsp;</td>
		<td width="45%">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td align="left" width="45%">
			<table class="tabel" width="100%" cellpadding="4" cellspacing="0" border="0">
				<thead>
					<tr>
						<td colspan="2">
							Personen
						</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td width="40%">
							Persoon toevoegen
						</td>
						<td width="60%">
							<input type="text" name="persoonToevoegen" onchange="persoonToevoegenEmail();" />
						</td>
					</tr>
					<tr class="bgcolor">
						<td>
							Persoon verwijderen
						</td>
						<td>
							<select name="persoonVerwijderen" onchange="confirm('Weet je zeker dat je deze persoon wilt verwijderen?\nHiermee worden alle taken en boetes voor deze persoon ook verwijderd!') ? $.post('process.php', {soort: 'schoonmaakmeester', save: 'persoon', persoonVerwijderen: this.value},function(data){showMelding(data);updatePersonen();}) : this.selectedIndex=0;" >
								<option value=""> </option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							Verander persoon
						</td>
						<td>
							<select name="persoonVeranderenVan" id="persoonVeranderenVan" onchange="persoonVeranderen();">
								<option value=""> </option>
							</select>
							 naar 
							<input type="text" name="persoonVeranderenNaar" id="persoonVeranderenNaar" onchange="persoonVeranderen();" />
						</td>
					</tr>
					<tr class="bgcolor">
						<td>
							Kolommen omdraaien in rooster
						</td>
						<td>
							<select name="personenOmdraaien1" id="personenOmdraaien1" onchange="if ($('#personenOmdraaien2').val() != '') $.post('process.php', {soort: 'schoonmaakmeester', save: 'persoon', personenOmdraaien1: this.value, personenOmdraaien2: $('#personenOmdraaien2').val()},function(data){showMelding(data);updatePersonen();});">
								<option value=""> </option>
							</select>
							 en
							<select name="personenOmdraaien2" id="personenOmdraaien2" onchange="if ($('#personenOmdraaien1').val() != '') $.post('process.php', {soort: 'schoonmaakmeester', save: 'persoon', personenOmdraaien1: $('#personenOmdraaien1').val(), personenOmdraaien2: this.value},function(data){showMelding(data);updatePersonen();});">
								<option value=""> </option>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
		</td>
		<td width="10%">
			&nbsp;
		</td>
		<td width="45%" align="right">
			<table class="tabel" width="100%" cellpadding="4" cellspacing="0" border="0">
				<thead>
					<tr>
						<td>
							Uitlezen &amp; bijwerken
						</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							Alle taken exporteren naar Excel: van week 
							<select name="exportExcelVanWeek" id="exportExcelVanWeek" onchange="exportExcel();">
								<option value=''>- Selecteer week -</option>
								<?php
								for ($i = 1; $i <= 53; $i++) {
									$iTxt = ($i < 10) ? '0'. $i : $i;
									echo '<option value="'. $iTxt .'">'. $i .'</option>';
								}
								?>
							</select>
							<select name="exportExcelVanJaar" id="exportExcelVanJaar" onchange="exportExcel();">
								<option value=''>- Selecteer jaar -</option>
								<?php
								for ($i = date("Y")-10; $i <= date("Y")+5; $i++) {
									echo '<option value="'. $i .'">'. $i .'</option>';
								}
								?>
							</select>
							tot en met week
							<select name="exportExcelTotWeek" id="exportExcelTotWeek" onchange="exportExcel();">
								<option value=''>- Selecteer week -</option>
								<?php
								for ($i = 1; $i <= 53; $i++) {
									$iTxt = ($i < 10) ? '0'. $i : $i;
									echo '<option value="'. $iTxt .'">'. $i .'</option>';
								}
								?>
							</select>
							<select name="exportExcelTotJaar" id="exportExcelTotJaar" onchange="exportExcel();">
								<option value=''>- Selecteer jaar -</option>
								<?php
								for ($i = date("Y")-10; $i <= date("Y")+5; $i++) {
									echo '<option value="'. $i .'">'. $i .'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr class="bgcolor">
						<td>
							Alle boetes van week tot en met week
							<select name="boetesVoldaanGrensWeek" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'boetesVoldaanGrens', week: this.value},function(data){showMelding(data);});">
								<?php
								$grens = explode("-", $config['boetesVoldaanGrens']);
								for($i = 1; $i <= 53; $i++) {
									$iTxt = ($i < 10) ? '0'. $i : $i;
									echo '<option value="'. $iTxt .'"';
									if ($i == $grens[1]) echo ' selected="selected"';
									echo '>'. $i .'</option>';
								}
								?>
							</select>
							<select name="boetesVoldaanGrensJaar" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'boetesVoldaanGrens', jaar: this.value},function(data){showMelding(data);});">
								<?php
								$grens = explode("-", $config['boetesVoldaanGrens']);
								for($i = $grens[0]-5; $i <= date("Y")+5; $i++) {
									echo '<option value="'. $i .'"';
									if ($i == $grens[0]) echo ' selected="selected"';
									echo '>'. $i .'</option>';
								}
								?>
							</select>
							als voldaan beschouwen
						</td>
					</tr>
					<tr>
						<td>
							Alle taken en boetes van voor week 
							<select name="takenVerwijderenWeek" id="takenVerwijderenWeek" onchange="takenVerwijderen();">
								<option value=''>- Selecteer week -</option>
								<?php
								for ($i = 1; $i <= 53; $i++) {
									$iTxt = ($i < 10) ? '0'. $i : $i;
									echo '<option value="'. $iTxt .'">'. $i .'</option>';
								}
								?>
							</select>
							<select name="takenVerwijderenJaar" id="takenVerwijderenJaar" onchange="takenVerwijderen();">
								<option value=''>- Selecteer jaar -</option>
								<?php
								for ($i = date("Y")-10; $i <= date("Y")+5; $i++) {
									echo '<option value="'. $i .'">'. $i .'</option>';
								}
								?>
							</select>
							verwijderen
						</td>
					</tr>
				</tbody>
			</table>
		</td>
	</tr>
	<tr>
		<td width="45%">&nbsp;</td>
		<td width="10%">&nbsp;</td>
		<td width="45%">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td align="left" width="45%">
			<table class="tabel" width="100%" cellpadding="4" cellspacing="0" border="0">
				<thead>
					<tr>
						<td colspan="2">
							Mail
						</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td width="40%">
							Afzender
						</td>
						<td width="60%">
							<input type="text" name="mailAfzender" value="<?php echo htmlspecialchars($config['mailAfzender'], ENT_QUOTES); ?>" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'mail', mailAfzender: this.value},function(data){showMelding(data);});" />
						</td>
					</tr>
					<tr class="bgcolor">
						<td>
							Herinnering begin week versturen op
						</td>
						<td>
							<select name="herinneringMailDag" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'mail', herinneringMailDag: this.value},function(data){showMelding(data);});" >
							<?php
							foreach ($dagenTxt as $dagNr=>$dagTxt) {
								$dagNr++;
								echo '<option value="'. $dagNr .'"';
								if ($dagNr == $config['herinneringMailDag']) echo ' selected="selected"';
								echo '>'. $dagTxt .'</option>';
							}
							?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							E-mail bericht dat gewijzigd moet worden
						</td>
						<td>
							<select name="selectMail" id="selectMail" onchange="changeSelectMail();">
								<option value="">- Selecteer email bericht -</option>
								<option value="herinneringBeginWeekMail">Herinnering begin week</option>
								<option value="herinneringNaDeadlineMail">Herinnering na deadline</option>
								<option value="boeteMeldingMail">Boete melding mail</option>
							</select>
						</td>
					</tr>
					<tr class="bgcolor herinneringBeginWeekMail">
						<td colspan="2">
							Onderwerp herinnering begin week
							<br />
							<input type="text" name="herinneringBeginWeekMailOnderwerp" size="60" value="<?php echo $config['herinneringBeginWeekMailOnderwerp']; ?>" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'mail', herinneringBeginWeekMailOnderwerp: this.value},function(data){showMelding(data);});" />
						</td>
					</tr>
					<tr class="herinneringBeginWeekMail">
						<td colspan="2">
							Tekst herinnering begin week
							<br />
							<textarea rows="10" cols="50" name="herinneringBeginWeekMailTxt" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'mail', herinneringBeginWeekMailTxt: this.value},function(data){showMelding(data);});" ><?php echo $config['herinneringBeginWeekMailTxt']; ?></textarea>
							<br /><div class="tags">Mogelijk tags: [PERSOONNAAM], [PERSOONID], [TAAKNAAM] en [DEADLINE]</div>
						</td>
					</tr>
					<tr class="bgcolor herinneringNaDeadlineMail">
						<td colspan="2">
							Onderwerp herinnering na deadline
							<br />
							<input type="text" name="herinneringNaDeadlineMailOnderwerp" size="60" value="<?php echo $config['herinneringNaDeadlineMailOnderwerp']; ?>" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'mail', herinneringNaDeadlineMailOnderwerp: this.value},function(data){showMelding(data);});" />
						</td>
					</tr>
					<tr class="herinneringNaDeadlineMail">
						<td colspan="2">
							Tekst herinnering na deadline
							<br />
							<textarea rows="10" cols="50" name="herinneringNaDeadlineMailTxt" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'mail', herinneringNaDeadlineMailTxt: this.value},function(data){showMelding(data);});" ><?php echo $config['herinneringNaDeadlineMailTxt']; ?></textarea>
							<br /><div class="tags">Mogelijk tags: [PERSOONNAAM], [PERSOONID], [TAAKNAAM] en [DEADLINE]</div>
						</td>
					</tr>
					<tr class="bgcolor boeteMeldingMail">
						<td colspan="2">
							Onderwerp boete melding mail
							<br />
							<input type="text" name="boeteMeldingMailOnderwerp" size="60" value="<?php echo $config['boeteMeldingMailOnderwerp']; ?>" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'mail', boeteMeldingMailOnderwerp: this.value},function(data){showMelding(data);});" />
						</td>
					</tr>
					<tr class="boeteMeldingMail">
						<td colspan="2">
							Tekst boete melding mail
							<br />
							<textarea rows="10" cols="50" name="boeteMeldingMailTxt" onchange="$.post('process.php', {soort: 'schoonmaakmeester', save: 'mail', boeteMeldingMailTxt: this.value},function(data){showMelding(data);});" ><?php echo $config['boeteMeldingMailTxt']; ?></textarea>
							<br /><div class="tags">Mogelijk tags: [PERSOONNAAM], [PERSOONID], [TAAKNAAM], [DEADLINE], [GEDAAN] en [BOETE]</div>
						</td>
					</tr>
				</tbody>
			</table>
		</td>
		<td width="10%">
			&nbsp;
		</td>
		<td width="45%" align="right">
			&nbsp;
		</td>
	</tr>
</table>
</form>
</body>
</html>