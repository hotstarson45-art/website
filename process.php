<?php
include("include.php");

$soort = $_POST['soort'];

if ($soort == 'persoonInfo') {
	// persoon informatie verwerken
	$persoonId = intval($_POST['persoonId']);
	$emailadres = htmlentities($_POST['emailadres'], ENT_QUOTES, 'UTF-8');
	$herinneringBeginWeek = $_POST['herinneringBeginWeek'];
	$herinneringNaDeadline = $_POST['herinneringNaDeadline'];
	
	if (!empty($emailadres)) {
		// emailadres wordt gewijzigd
		$query = "UPDATE personen SET emailadres='". $emailadres ."' WHERE persoonId='". $persoonId ."'";
		$link->query($query);
		echo "Het emailadres is gewijzigd naar ". stripslashes($emailadres) .".";
	} else if (!empty($herinneringBeginWeek)) {
		// herinnering begin week wordt gewijzigd
		$herinneringBeginWeekTxt = ($herinneringBeginWeek=='ja') ? '1' : '0';
		$query = "UPDATE personen SET herinneringBeginWeek='". $herinneringBeginWeekTxt ."' WHERE persoonId='". $persoonId ."'";
		$link->query($query);
		echo "De herinnering aan het begin van de week is ";
		echo ($herinneringBeginWeek=='ja') ? 'aangezet' : 'uitgezet';
		echo ".";
	} else if (!empty($herinneringNaDeadline)) {
		// herinnering na deadline wordt gewijzigd
		$herinneringNaDeadlineTxt = ($herinneringNaDeadline=='ja') ? '1' : '0';
		$query = "UPDATE personen SET herinneringNaDeadline='". $herinneringNaDeadlineTxt ."' WHERE persoonId='". $persoonId ."'";
		$link->query($query);
		echo "De herinnering na de deadline is ";
		echo ($herinneringNaDeadline=='ja') ? 'aangezet' : 'uitgezet';
		echo ".";
	}
} else if ($soort == 'taakInfo') {
	// taak informatie verwerken
	$save = $_POST['save'];
	$taakId = intval($_POST['taakId']);
	$taakNaam = htmlentities($_POST['taakNaam'], ENT_QUOTES, 'UTF-8');
	$deadline = $_POST['deadline'];
	$deadlineDag = intval($_POST['deadlineDag']);
	$deadlineMaand = intval($_POST['deadlineMaand']);
	$deadlineJaar = intval($_POST['deadlineJaar']);
	$gedaan = $_POST['gedaan'];
	$gedaanOpDag = intval($_POST['gedaanOpDag']);
	$gedaanOpMaand = intval($_POST['gedaanOpMaand']);
	$gedaanOpJaar = intval($_POST['gedaanOpJaar']);
	$boete = $_POST['boete'];
	$boeteMeldingMail = $_POST['boeteMeldingMail'] == 'true';
	$herinneringNaDeadlineMail = $_POST['herinneringNaDeadlineMail'] == 'true';
	
	if ($save == "taakNaam") {
		// naam taak wijzigen en evt. taak aanmaken / verwijderen
		if ($taakId == 0) {
			// taak aanmaken
			$persoonId = intval($_POST['persoonId']);
			$weekId = $_POST['weekId'];
			
			$deadline = week2date($weekId);
			
			$query = "INSERT INTO taken SET week='". $weekId ."', taakNaam='". $taakNaam ."', persoonId='". $persoonId ."', deadline='". date("Y-m-d", $deadline) ."'";
			$link->query($query);
			
			echo "Taak <i>". stripslashes($taakNaam) ."</i> aangemaakt.";
		} else if ($taakNaam == '-') {
			// taak verwijderen
			$query = "DELETE FROM taken WHERE taakId='". $taakId ."'";
			$link->query($query);
			echo "Taak verwijderd.";
		} else {
			// taak naam aanpassen
			$query = "UPDATE taken SET taakNaam='". $taakNaam ."' WHERE taakId='". $taakId ."'";
			$link->query($query);
			echo "Taak naar <i>". stripslashes($taakNaam) ."</i> aangepast.";
		}
	} else if ($save == "gedaan") {
		// gedaan wijzigen
		if (($gedaan == 'nogNiet') || ($gedaan == 'nooit')) {
			$query = "UPDATE taken SET gedaan='". $gedaan ."', gedaanOp='0000-00-00', boete='". str_replace(",", ".", $boete) ."' WHERE taakId='". $taakId ."'";
			$link->query($query);
			if ($gedaan == 'nogNiet')
				echo "Opgeslagen dat taak <i>nog niet</i> is gedaan.";
			else
				echo "Opgeslagen dat taak <i>nooit</i> is gedaan met <i>&euro; ". $boete ."</i> boete.";
		} else {
			// taak is gedaanOp
			$query = "UPDATE taken SET gedaan='". $gedaan ."', gedaanOp='". $gedaanOpJaar ."-". $gedaanOpMaand ."-". $gedaanOpDag ."', boete='". str_replace(",", ".", $boete) ."' WHERE taakId='". $taakId ."'";
			$link->query($query);
			echo "Opgeslagen dat taak is gedaan op ". $gedaanOpDag ." ". $maandenTxt[$gedaanOpMaand-1] ." ". $gedaanOpJaar ." met <i>&euro; ". $boete ."</i> boete.";
		}
	} else if ($save == "deadline") {
		// deadline wijzigen
		$query = "UPDATE taken SET deadline='". $deadlineJaar ."-". $deadlineMaand ."-". $deadlineDag ."' WHERE taakId='". $taakId ."'";
		$link->query($query);
		echo "Opgeslagen dat deadline van taak is ". $deadlineDag ." ". $maandenTxt[$deadlineMaand-1] ." ". $deadlineJaar .".";
	} else if ($save == "gedaanOp") {
		// gedaanOp wijzigen
		$query = "UPDATE taken SET gedaanOp='". $gedaanOpJaar ."-". $gedaanOpMaand ."-". $gedaanOpDag ."', boete='". str_replace(",", ".", $boete) ."' WHERE taakId='". $taakId ."'";
		$link->query($query);
		echo "Opgeslagen dat taak is gedaan op ". $gedaanOpDag ." ". $maandenTxt[$gedaanOpMaand-1] ." ". $gedaanOpJaar ." met <i>&euro; ". $boete ."</i> boete.";
	} else if ($save == "boeteWijzigen") {
		// boete wijzigen
		$query = "UPDATE taken SET boete='". str_replace(",", ".", $boete) ."' WHERE taakId='". $taakId ."'";
		$link->query($query);
		echo "Opgeslagen dat taak <i>&euro; ". $boete ."</i> boete heeft.";
	} else if ($herinneringNaDeadlineMail || $boeteMeldingMail) {
		// herinnering na deadline of boete melding mail versturen
		$query = "SELECT t.*, p.* FROM taken AS t, personen AS p WHERE t.taakId='". $taakId ."' AND t.persoonId=p.persoonId";
		$result = $link->query($query);
		if($result->num_rows != 1) {
			echo "ERROR: geen (of te veel) info gevonden over taak met taakId ". $taakId;
			exit;
		}
		$info = mysqli_fetch_assoc($result);
		
		$deadline = sqldate2phpdate($info['deadline']);
		$deadlineTxt = $dagenTxt[(date("N", $deadline)-1)] .' '. date("j", $deadline) .' '. $maandenTxt[date("n", $deadline)-1] .' '. date("Y", $deadline);
		
		if ($info['gedaan'] == 'nooit')
			$gedaan = 'nooit';
		else {
			$gedaanOp = sqldate2phpdate($info['gedaanOp']);
			$gedaan = 'op '. date("j", $gedaanOp) .' '. $maandenTxt[date("n", $gedaanOp)-1] .' '. date("Y", $gedaanOp);
		}
		
		$txtVars = array("[TAAKNAAM]", "[PERSOONNAAM]", "[PERSOONID]", "[DEADLINE]", "[BOETE]", "[GEDAAN]");
		$txtValuesHTML = array(strtolower($info['taakNaam']), $info['persoonNaam'], $info['persoonId'], $deadlineTxt, str_replace('.', ',', $info['boete']), $gedaan);
		$txtValuesTXT = htmlToPlainTxt($txtValuesHTML);
		
		$onderwerp = '';
		$berichtHTML = '';
		$berichtTXT = '';
		
		if ($herinneringNaDeadlineMail) {
			// herinnering na deadline mail versturen
			$onderwerp = str_replace($txtVars, $txtValuesTXT, htmlToPlainTxt($config['herinneringNaDeadlineMailOnderwerp']));
			$berichtHTML = str_replace($txtVars, $txtValuesHTML, $config['herinneringNaDeadlineMailTxt']);
			$berichtTXT = str_replace($txtVars, $txtValuesTXT, htmlToPlainTxt($config['herinneringNaDeadlineMailTxt']));
			
			$query = "UPDATE taken SET herinneringNaDeadlineMail=NOW() WHERE taakId='". $taakId ."'";
			$link->query($query);
			
			echo "Herinnering mail na de deadline is verstuurd.";
		} else {
			// boete melding mail versturen
			$onderwerp = str_replace($txtVars, $txtValuesTXT, htmlToPlainTxt($config['boeteMeldingMailOnderwerp']));
			$berichtHTML = str_replace($txtVars, $txtValuesHTML, $config['boeteMeldingMailTxt']);
			$berichtTXT = str_replace($txtVars, $txtValuesTXT, htmlToPlainTxt($config['boeteMeldingMailTxt']));
			
			$query = "UPDATE taken SET boeteMeldingMail=NOW() WHERE taakId='". $taakId ."'";
			$link->query($query);
			
			echo "Boetemelding mail is verstuurd.";
		}
		
		// include PEAR bestanden om email(s) te versturen
		include('Mail.php');
		include('Mail/mime.php');
		
		$mailHeaders = array(
			"From" => $config['mailAfzender'],
			"Reply-To" => $config['mailAfzender'],
			"Subject" => $onderwerp,
		);
		
		$mime = new Mail_mime("\n");
		$mime->setTXTBody($berichtTXT);
		$mime->setHTMLBody("<html><body>". $berichtHTML ."</body></html>");
		
		$bodyMail = $mime->get(array("head_charset"=>"UTF-8", "text_charset"=>"UTF-8", "html_charset"=>"UTF-8"));
		$headersMail = $mime->headers($mailHeaders);
		
		$mail =& Mail::factory('mail');
		$mail->send($info['emailadres'], $headersMail, $bodyMail);
	}
} else if ($soort == 'schoonmaakmeester') {
	// schoonmaakmeester informatie verwerken
	$save = $_POST['save'];
	if ($save == "taak") {
		$taakToevoegen = addslashes(htmlentities(stripslashes($_POST['taakToevoegen']), ENT_QUOTES, 'UTF-8'));
		$taakVerwijderen = addslashes(htmlentities(stripslashes($_POST['taakVerwijderen']), ENT_QUOTES, 'UTF-8'));
		$taakVeranderenVan = addslashes(htmlentities(stripslashes($_POST['taakVeranderenVan']), ENT_QUOTES, 'UTF-8'));
		$taakVeranderenNaar = addslashes(htmlentities(stripslashes($_POST['taakVeranderenNaar']), ENT_QUOTES, 'UTF-8'));
		$taakOmdraaien1 = addslashes(htmlentities(stripslashes($_POST['taakOmdraaien1']), ENT_QUOTES, 'UTF-8'));
		$taakOmdraaien2 = addslashes(htmlentities(stripslashes($_POST['taakOmdraaien2']), ENT_QUOTES, 'UTF-8'));
		
		$takenTxt = "array(";
		if (!empty($taakToevoegen)) {
			// taak toevoegen
			foreach ($config['taken'] as $taak) {
				$takenTxt .= '"'. addslashes(addslashes($taak)) .'", ';
			}
			$takenTxt .= '"'. addslashes($taakToevoegen) .'")';
			
			echo "Taak <i>". stripslashes($taakToevoegen) ."</i> is toegevoegd.";
		} else if (!empty($taakVerwijderen)) {
			// taak verwijderen
			foreach ($config['taken'] as $taak) {
				if (addslashes($taak) != $taakVerwijderen)
					$takenTxt .= '"'. addslashes(addslashes($taak)) .'", ';
			}
			$takenTxt = substr($takenTxt, 0, strlen($takenTxt)-2);
			$takenTxt .= ')';
			
			echo "Taak <i>". stripslashes($taakVerwijderen) ."</i> is verwijderd.";
		} else if (!empty($taakVeranderenVan)) {
			// taak veranderen
			foreach ($config['taken'] as $taak) {
				if (addslashes($taak) == $taakVeranderenVan)
					$takenTxt .= '"'. addslashes($taakVeranderenNaar) .'", ';
				else
					$takenTxt .= '"'. addslashes(addslashes($taak)) .'", ';	
			}
			$takenTxt = substr($takenTxt, 0, strlen($takenTxt)-2);
			$takenTxt .= ')';
			
			$query = "UPDATE taken SET `taakNaam`='". $taakVeranderenNaar ."' WHERE `taakNaam`='". $taakVeranderenVan ."' AND `week`>='". date("o-W") ."'";
			$link->query($query);
			
			echo "Taak <i>". stripslashes($taakVeranderenVan) ."</i> is gewijzigd naar <i>". stripslashes($taakVeranderenNaar) ."</i>.";
		} else if (!empty($taakOmdraaien1)) {
			// taken omdraaien
			foreach ($config['taken'] as $taak) {
				if (addslashes($taak) == $taakOmdraaien1)
					$takenTxt .= '"'. addslashes($taakOmdraaien2) .'", ';
				else if (addslashes($taak) == $taakOmdraaien2)
					$takenTxt .= '"'. addslashes($taakOmdraaien1) .'", ';
				else
					$takenTxt .= '"'. addslashes(addslashes($taak)) .'", ';	
			}
			$takenTxt = substr($takenTxt, 0, strlen($takenTxt)-2);
			$takenTxt .= ')';
			
			echo "Taken <i>". stripslashes($taakOmdraaien1) ."</i> en <i>". stripslashes($taakOmdraaien2) ."</i> zijn omgedraaid.";
		}
		
		$query = "UPDATE config SET `value`='". $takenTxt ."' WHERE `key`='taken'";
		$link->query($query);
	} else if ($save == "persoon") {
		$persoonToevoegen = htmlentities($_POST['persoonToevoegen'], ENT_QUOTES, 'UTF-8');
		$emailadres = htmlentities($_POST['emailadres'], ENT_QUOTES, 'UTF-8');
		$persoonVerwijderen = htmlentities($_POST['persoonVerwijderen'], ENT_QUOTES, 'UTF-8');
		$persoonVeranderenVan = htmlentities($_POST['persoonVeranderenVan'], ENT_QUOTES, 'UTF-8');
		$persoonVeranderenNaar = htmlentities($_POST['persoonVeranderenNaar'], ENT_QUOTES, 'UTF-8');
		$personenOmdraaien1 = htmlentities($_POST['personenOmdraaien1'], ENT_QUOTES, 'UTF-8');
		$personenOmdraaien2 = htmlentities($_POST['personenOmdraaien2'], ENT_QUOTES, 'UTF-8');
		
		if (!empty($persoonToevoegen)) {
			// persoon toevoegen
			// eerst achter maximum kolomNr komen
			$query = "SELECT MAX(kolomNr) FROM personen";
			$result = $link->query($query);
			$maxKolomNr = mysql_result($result, 0);
			$kolomNr = $maxKolomNr + 1;
			
			$query = "INSERT INTO personen SET `persoonNaam`='". $persoonToevoegen ."', `kolomNr`='". $kolomNr ."', `emailadres`='". $emailadres ."'";
			$link->query($query);
			
			echo "Persoon <i>". stripslashes($persoonToevoegen) ."</i> is toegevoegd.";
		} else if (!empty($persoonVerwijderen)) {
			// persoon verwijderen
			
			$query = "DELETE FROM personen WHERE `persoonId`='". $persoonVerwijderen ."'";
			$link->query($query);
			
			$query = "DELETE FROM taken WHERE `persoonId`='". $persoonVerwijderen ."'";
			$link->query($query);
			
			echo "De persoon is verwijderd.";
		} else if (!empty($persoonVeranderenVan)) {
			// persoon veranderen
			$query = "UPDATE personen SET `persoonNaam`='". $persoonVeranderenNaar ."' WHERE `persoonId`='". $persoonVeranderenVan ."'";
			$link->query($query);
			
			echo "De naam van de persoon is gewijzigd naar <i>". stripslashes($persoonVeranderenNaar) ."</i>.";
		} else if (!empty($personenOmdraaien1)) {
			// personen omdraaien
			$query = "SELECT kolomNr FROM personen WHERE `persoonId`='". $personenOmdraaien1 ."'";
			$result = $link->query($query);
			$kolomNr1 = mysql_result($result, 0);
			
			$query = "SELECT kolomNr FROM personen WHERE `persoonId`='". $personenOmdraaien2 ."'";
			$result = $link->query($query);
			$kolomNr2 = mysql_result($result, 0);
			
			$query = "UPDATE personen SET `kolomNr`='". $kolomNr2 ."' WHERE `persoonId`='". $personenOmdraaien1 ."'";
			$link->query($query);
			
			$query = "UPDATE personen SET `kolomNr`='". $kolomNr1 ."' WHERE `persoonId`='". $personenOmdraaien2 ."'";
			$link->query($query);
			
			echo "De personen zijn omgedraaid.";
		}
	} else if ($save == "boete") {
		$boete1dagEuro = $_POST['boete1dagEuro'];
		$boete1dagCent = $_POST['boete1dagCent'];
		$boete2dagEuro = $_POST['boete2dagEuro'];
		$boete2dagCent = $_POST['boete2dagCent'];
		$boete3dagEuro = $_POST['boete3dagEuro'];
		$boete3dagCent = $_POST['boete3dagCent'];
		$boete4dagEuro = $_POST['boete4dagEuro'];
		$boete4dagCent = $_POST['boete4dagCent'];
		$boeteNooitEuro = $_POST['boeteNooitEuro'];
		$boeteNooitCent = $_POST['boeteNooitCent'];
		
		if (!empty($boete1dagEuro)) {
			$bedrag = explode(",", $config['boeteTeLaat'][1]);
			$config['boeteTeLaat'][1] = $boete1dagEuro .",". $bedrag[1];
			echo "De boete voor 1 dag te laat is aangepast naar <i>&euro; ". $config['boeteTeLaat'][1] ."</i>.";
		} else if (!empty($boete1dagCent)) {
			$bedrag = explode(",", $config['boeteTeLaat'][1]);
			$config['boeteTeLaat'][1] = $bedrag[0] .",". $boete1dagCent;
			echo "De boete voor 1 dag te laat is aangepast naar <i>&euro; ". $config['boeteTeLaat'][1] ."</i>.";
		} else if (!empty($boete2dagEuro)) {
			$bedrag = explode(",", $config['boeteTeLaat'][2]);
			$config['boeteTeLaat'][2] = $boete2dagEuro .",". $bedrag[1];
			echo "De boete voor 2 dagen te laat is aangepast naar <i>&euro; ". $config['boeteTeLaat'][2] ."</i>.";
		} else if (!empty($boete2dagCent)) {
			$bedrag = explode(",", $config['boeteTeLaat'][2]);
			$config['boeteTeLaat'][2] = $bedrag[0] .",". $boete2dagCent;
			echo "De boete voor 2 dagen te laat is aangepast naar <i>&euro; ". $config['boeteTeLaat'][2] ."</i>.";
		} else if (!empty($boete3dagEuro)) {
			$bedrag = explode(",", $config['boeteTeLaat'][3]);
			$config['boeteTeLaat'][3] = $boete3dagEuro .",". $bedrag[1];
			echo "De boete voor 3 dagen te laat is aangepast naar <i>&euro; ". $config['boeteTeLaat'][3] ."</i>.";
		} else if (!empty($boete3dagCent)) {
			$bedrag = explode(",", $config['boeteTeLaat'][3]);
			$config['boeteTeLaat'][3] = $bedrag[0] .",". $boete3dagCent;
			echo "De boete voor 3 dagen te laat is aangepast naar <i>&euro; ". $config['boeteTeLaat'][3] ."</i>.";
		} else if (!empty($boete4dagEuro)) {
			$bedrag = explode(",", $config['boeteTeLaat'][4]);
			$config['boeteTeLaat'][4] = $boete4dagEuro .",". $bedrag[1];
			echo "De boete voor 4 dagen te laat is aangepast naar <i>&euro; ". $config['boeteTeLaat'][4] ."</i>.";
		} else if (!empty($boete4dagCent)) {
			$bedrag = explode(",", $config['boeteTeLaat'][4]);
			$config['boeteTeLaat'][4] = $bedrag[0] .",". $boete4dagCent;
			echo "De boete voor 4 dagen te laat is aangepast naar <i>&euro; ". $config['boeteTeLaat'][4] ."</i>.";
		} else if (!empty($boeteNooitEuro)) {
			$bedrag = explode(",", $config['boeteTeLaat'][0]);
			$config['boeteTeLaat'][0] = $boeteNooitEuro .",". $bedrag[1];
			echo "De boete voor taak nooit gedaan is aangepast naar <i>&euro; ". $config['boeteTeLaat'][0] ."</i>.";
		} else if (!empty($boeteNooitCent)) {
			$bedrag = explode(",", $config['boeteTeLaat'][0]);
			$config['boeteTeLaat'][0] = $bedrag[0] .",". $boeteNooitCent;
			echo "De boete voor taak nooit gedaan is aangepast naar <i>&euro; ". $config['boeteTeLaat'][0] ."</i>.";
		}
		
		$boetesTxt = "array(";
		foreach ($config['boeteTeLaat'] as $boete) {
			$boetesTxt .= '"'. $boete .'", ';	
		}
		$boetesTxt = substr($boetesTxt, 0, strlen($boetesTxt)-2);
		$boetesTxt .= ')';
			
		$query = "UPDATE config SET `value`='". $boetesTxt ."' WHERE `key`='boeteTeLaat'";
		$link->query($query);
	} else if ($save == "deadlineDag") {
		// deadlineDag aanpassen in config
		$deadlineDag = intval($_POST['deadlineDag']);
		$query = "UPDATE config SET `value`='". $deadlineDag ."' WHERE `key`='deadlineDag'";
		$link->query($query);
		echo "De deadline voor taken is gezet op <i>". $dagenTxt[$deadlineDag-1] ."</i>.";
	} else if ($save == "boetesVoldaanGrens") {
		// boetesVoldaanGrens in config aanpassen
		$week = $_POST['week'];
		$jaar = $_POST['jaar'];
		
		$grens = explode("-", $config['boetesVoldaanGrens']);
		$nieuweBoetesVoldaanGrens = $config['boetesVoldaanGrens'];
		
		if (!empty($week)) {
			// week aanpassen
			$nieuweBoetesVoldaanGrens = $grens[0] .'-'. $week;
		} else if (!empty($jaar)) {
			// jaar aanpassen
			$nieuweBoetesVoldaanGrens = $jaar .'-'. $grens[1];
		}
		
		$query = "UPDATE config SET `value`='". $nieuweBoetesVoldaanGrens ."' WHERE `key`='boetesVoldaanGrens'";
		$link->query($query);
		
		echo "Boetes tot en met week <i>". $nieuweBoetesVoldaanGrens ."</i> worden nu als voldaan beschouwd.";
	} else if ($save == "takenVerwijderen") {
		// taken verwijderen
		$takenVerwijderenWeek = $_POST['takenVerwijderenWeek'];
		$takenVerwijderenJaar = $_POST['takenVerwijderenJaar'];
		
		$weekTxt = $takenVerwijderenJaar .'-'. $takenVerwijderenWeek;
		
		$query = "DELETE FROM taken WHERE `week` < '". $weekTxt ."'";
		$link->query($query);
		
		echo "Alle taken en boetes van voor week <i>". $weekTxt ."</i> zijn verwijderd.";
	} else if ($save == "mail") {
		// mail zaken wijzigen
		$mailAfzender = $_POST['mailAfzender'];
		$herinneringMailDag = intval($_POST['herinneringMailDag']);
		$herinneringBeginWeekMailOnderwerp = $_POST['herinneringBeginWeekMailOnderwerp'];
		$herinneringBeginWeekMailTxt = $_POST['herinneringBeginWeekMailTxt'];
		$herinneringNaDeadlineMailOnderwerp = $_POST['herinneringNaDeadlineMailOnderwerp'];
		$herinneringNaDeadlineMailTxt = $_POST['herinneringNaDeadlineMailTxt'];
		$boeteMeldingMailOnderwerp = $_POST['boeteMeldingMailOnderwerp'];
		$boeteMeldingMailTxt = $_POST['boeteMeldingMailTxt'];
		
		if (!empty($mailAfzender)) {
			// mail afzender wijzigen
			$query = "UPDATE config SET `value`='". $mailAfzender ."' WHERE `key`='mailAfzender'";
			$link->query($query);
			echo "De afzender van emails is gewijzigd.";
		} else if (!empty($herinneringMailDag)) {
			// herinneringMailDag wijzigen
			$query = "UPDATE config SET `value`='". $herinneringMailDag ."' WHERE `key`='herinneringMailDag'";
			$link->query($query);
			echo "Herinneringen worden voortaan verstuurd op <i>". $dagenTxt[$herinneringMailDag-1] ."</i>.";
		} else if (!empty($herinneringBeginWeekMailOnderwerp)) {
			// herinneringBeginWeekMailOnderwerp wijzigen
			$query = "UPDATE config SET `value`='". $herinneringBeginWeekMailOnderwerp ."' WHERE `key`='herinneringBeginWeekMailOnderwerp'";
			$link->query($query);
			echo "Onderwerp herinnering begin week is gewijzigd.";
		} else if (!empty($herinneringBeginWeekMailTxt)) {
			// herinneringBeginWeekMailTxt wijzigen
			$query = "UPDATE config SET `value`='". $herinneringBeginWeekMailTxt ."' WHERE `key`='herinneringBeginWeekMailTxt'";
			$link->query($query);
			echo "Tekst herinnering begin week is gewijzigd.";
		} else if (!empty($herinneringNaDeadlineMailOnderwerp)) {
			// herinneringNaDeadlineMailOnderwerp wijzigen
			$query = "UPDATE config SET `value`='". $herinneringNaDeadlineMailOnderwerp ."' WHERE `key`='herinneringNaDeadlineMailOnderwerp'";
			$link->query($query);
			echo "Onderwerp herinnering na deadline is gewijzigd.";
		} else if (!empty($herinneringNaDeadlineMailTxt)) {
			// herinneringNaDeadlineMailTxt wijzigen
			$query = "UPDATE config SET `value`='". $herinneringNaDeadlineMailTxt ."' WHERE `key`='herinneringNaDeadlineMailTxt'";
			$link->query($query);
			echo "Tekst herinnering na deadline is gewijzigd.";
		}  else if (!empty($boeteMeldingMailOnderwerp)) {
			// boeteMeldingMailOnderwerp wijzigen
			$query = "UPDATE config SET `value`='". $boeteMeldingMailOnderwerp ."' WHERE `key`='boeteMeldingMailOnderwerp'";
			$link->query($query);
			echo "Onderwerp boete melding mail is gewijzigd.";
		} else if (!empty($boeteMeldingMailTxt)) {
			// boeteMeldingMailTxt wijzigen
			$query = "UPDATE config SET `value`='". $boeteMeldingMailTxt ."' WHERE `key`='boeteMeldingMailTxt'";
			$link->query($query);
			echo "Tekst boete melding mail is gewijzigd.";
		}
	} else {
		print_r($_POST);
	}
}
?>