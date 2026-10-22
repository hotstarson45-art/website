<?php
include("include.php");

function normalizeFineAmount($amount) {
	$amount = str_replace(',', '.', trim((string) $amount));
	if (!preg_match('/^\d{1,6}(?:\.\d{1,2})?$/', $amount)) {
		throw new InvalidArgumentException('Voer een geldig boetebedrag in, bijvoorbeeld 5,00.');
	}
	return number_format((float) $amount, 2, '.', '');
}

function validTaskDate($year, $month, $day) {
	return checkdate((int) $month, (int) $day, (int) $year);
}

$soort = isset($_POST['soort']) ? $_POST['soort'] : '';

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
	$save = isset($_POST['save']) ? $_POST['save'] : '';
	if (in_array($save, array('gedaan', 'deadline', 'gedaanOp', 'boeteWijzigen'), true) && !$schoonmaakmeester) {
		http_response_code(403);
		exit('Alleen de beheerder mag taakstatus, deadlines en boetes wijzigen.');
	}
	$taakId = isset($_POST['taakId']) ? intval($_POST['taakId']) : 0;
	$taakNaam = isset($_POST['taakNaam']) ? htmlentities($_POST['taakNaam'], ENT_QUOTES, 'UTF-8') : '';
	$deadline = isset($_POST['deadline']) ? $_POST['deadline'] : '';
	$deadlineDag = isset($_POST['deadlineDag']) ? intval($_POST['deadlineDag']) : 0;
	$deadlineMaand = isset($_POST['deadlineMaand']) ? intval($_POST['deadlineMaand']) : 0;
	$deadlineJaar = isset($_POST['deadlineJaar']) ? intval($_POST['deadlineJaar']) : 0;
	$gedaan = isset($_POST['gedaan']) ? $_POST['gedaan'] : '';
	$gedaanOpDag = isset($_POST['gedaanOpDag']) ? intval($_POST['gedaanOpDag']) : 0;
	$gedaanOpMaand = isset($_POST['gedaanOpMaand']) ? intval($_POST['gedaanOpMaand']) : 0;
	$gedaanOpJaar = isset($_POST['gedaanOpJaar']) ? intval($_POST['gedaanOpJaar']) : 0;
	$boete = isset($_POST['boete']) ? $_POST['boete'] : '0,00';
	$addTaskToCatalog = isset($_POST['addTaskToCatalog']) && $_POST['addTaskToCatalog'] === 'true';
	$boeteMeldingMail = isset($_POST['boeteMeldingMail']) && $_POST['boeteMeldingMail'] == 'true';
	$herinneringNaDeadlineMail = isset($_POST['herinneringNaDeadlineMail']) && $_POST['herinneringNaDeadlineMail'] == 'true';
	if (($boeteMeldingMail || $herinneringNaDeadlineMail) && !$schoonmaakmeester) {
		http_response_code(403);
		exit('Alleen de beheerder mag herinneringen en boetemeldingen versturen.');
	}
	
	if ($save == "taakNaam") {
		if (!$schoonmaakmeester) {
			http_response_code(403);
			exit('Alleen de beheerder mag taken toewijzen.');
		}
		// naam taak wijzigen en evt. taak aanmaken / verwijderen
		require_once __DIR__ . '/schedule.php';
		if ($taakId == 0) {
			// taak aanmaken
			$persoonId = intval($_POST['persoonId']);
			$weekId = $_POST['weekId'];
			try {
				saveTaskAssignment($link, $config, 0, $weekId, $persoonId, stripslashes($taakNaam), $addTaskToCatalog);
				echo "Taak <i>". stripslashes($taakNaam) ."</i> aangemaakt.";
			} catch (DomainException $exception) {
				http_response_code(400);
				echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
			} catch (InvalidArgumentException $exception) {
				http_response_code(400);
				echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
			}
		} else if ($taakNaam == '-') {
			// taak verwijderen
			$query = "DELETE FROM taken WHERE taakId='". $taakId ."'";
			$link->query($query);
			echo "Taak verwijderd.";
		} else {
			// taak naam aanpassen
			$weekId = isset($_POST['weekId']) ? $_POST['weekId'] : '';
			$persoonId = isset($_POST['persoonId']) ? intval($_POST['persoonId']) : 0;
			try {
				saveTaskAssignment($link, $config, $taakId, $weekId, $persoonId, stripslashes($taakNaam), $addTaskToCatalog);
				echo "Taak naar <i>". stripslashes($taakNaam) ."</i> aangepast.";
			} catch (DomainException $exception) {
				http_response_code(400);
				echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
			} catch (InvalidArgumentException $exception) {
				http_response_code(400);
				echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
			}
		}
	} else if ($save == "persoon") {
		if (!$schoonmaakmeester) {
			http_response_code(403);
			exit('Alleen de beheerder mag taken opnieuw toewijzen.');
		}
		require_once __DIR__ . '/schedule.php';
		try {
			moveTaskAssignment($link, $taakId, intval($_POST['persoonId']));
			echo 'De taak is opnieuw toegewezen.';
		} catch (DomainException $exception) {
			http_response_code(400);
			echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
		} catch (InvalidArgumentException $exception) {
			http_response_code(400);
			echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
		}
	} else if ($save == "gedaan") {
		try {
			if (!in_array($gedaan, array('nogNiet', 'nooit', 'ja'), true)) {
				throw new InvalidArgumentException('Kies een geldige taakstatus.');
			}
			$boete = normalizeFineAmount($boete);
			if ($gedaan !== 'ja') {
				$query = $link->prepare("UPDATE taken SET gedaan = ?, gedaanOp = '0000-00-00', boete = ? WHERE taakId = ?");
				$query->bind_param('ssi', $gedaan, $boete, $taakId);
				$query->execute();
				if ($gedaan == 'nogNiet')
					echo "Opgeslagen dat taak <i>nog niet</i> is gedaan.";
				else
					echo "Opgeslagen dat taak <i>nooit</i> is gedaan met <i>&euro; ". $boete ."</i> boete.";
			} else {
				if (!validTaskDate($gedaanOpJaar, $gedaanOpMaand, $gedaanOpDag)) {
					throw new InvalidArgumentException('Kies een geldige datum waarop de taak is gedaan.');
				}
				$gedaanOp = sprintf('%04d-%02d-%02d', $gedaanOpJaar, $gedaanOpMaand, $gedaanOpDag);
				$query = $link->prepare('UPDATE taken SET gedaan = ?, gedaanOp = ?, boete = ? WHERE taakId = ?');
				$query->bind_param('sssi', $gedaan, $gedaanOp, $boete, $taakId);
				$query->execute();
				echo "Opgeslagen dat taak is gedaan op ". $gedaanOpDag ." ". $maandenTxt[$gedaanOpMaand-1] ." ". $gedaanOpJaar ." met <i>&euro; ". $boete ."</i> boete.";
			}
		} catch (InvalidArgumentException $exception) {
			http_response_code(400);
			echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
		}
	} else if ($save == "deadline") {
		if (validTaskDate($deadlineJaar, $deadlineMaand, $deadlineDag)) {
			$deadline = sprintf('%04d-%02d-%02d', $deadlineJaar, $deadlineMaand, $deadlineDag);
			$query = $link->prepare('UPDATE taken SET deadline = ? WHERE taakId = ?');
			$query->bind_param('si', $deadline, $taakId);
			$query->execute();
			echo "Opgeslagen dat deadline van taak is ". $deadlineDag ." ". $maandenTxt[$deadlineMaand-1] ." ". $deadlineJaar .".";
		} else {
			http_response_code(400);
			echo 'Kies een geldige deadline.';
		}
	} else if ($save == "gedaanOp") {
		try {
			if (!validTaskDate($gedaanOpJaar, $gedaanOpMaand, $gedaanOpDag)) {
				throw new InvalidArgumentException('Kies een geldige datum waarop de taak is gedaan.');
			}
			$boete = normalizeFineAmount($boete);
			$gedaanOp = sprintf('%04d-%02d-%02d', $gedaanOpJaar, $gedaanOpMaand, $gedaanOpDag);
			$query = $link->prepare('UPDATE taken SET gedaanOp = ?, boete = ? WHERE taakId = ?');
			$query->bind_param('ssi', $gedaanOp, $boete, $taakId);
			$query->execute();
			echo "Opgeslagen dat taak is gedaan op ". $gedaanOpDag ." ". $maandenTxt[$gedaanOpMaand-1] ." ". $gedaanOpJaar ." met <i>&euro; ". $boete ."</i> boete.";
		} catch (InvalidArgumentException $exception) {
			http_response_code(400);
			echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
		}
	} else if ($save == "boeteWijzigen") {
		try {
			$boete = normalizeFineAmount($boete);
			$query = $link->prepare('UPDATE taken SET boete = ? WHERE taakId = ?');
			$query->bind_param('si', $boete, $taakId);
			$query->execute();
			echo "Opgeslagen dat taak <i>&euro; ". $boete ."</i> boete heeft.";
		} catch (InvalidArgumentException $exception) {
			http_response_code(400);
			echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
		}
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
	if (!$schoonmaakmeester) {
		http_response_code(403);
		exit('Alleen de beheerder mag deze instellingen wijzigen.');
	}
	// schoonmaakmeester informatie verwerken
	$save = isset($_POST['save']) ? $_POST['save'] : '';
	if ($save == "taak") {
		$taakToevoegen = addslashes(htmlentities(stripslashes($_POST['taakToevoegen']), ENT_QUOTES, 'UTF-8'));
		$taakVerwijderen = addslashes(htmlentities(stripslashes($_POST['taakVerwijderen']), ENT_QUOTES, 'UTF-8'));
		$taakVeranderenVan = addslashes(htmlentities(stripslashes($_POST['taakVeranderenVan']), ENT_QUOTES, 'UTF-8'));
		$taakVeranderenNaar = addslashes(htmlentities(stripslashes($_POST['taakVeranderenNaar']), ENT_QUOTES, 'UTF-8'));
		$taakOmdraaien1 = addslashes(htmlentities(stripslashes($_POST['taakOmdraaien1']), ENT_QUOTES, 'UTF-8'));
		$taakOmdraaien2 = addslashes(htmlentities(stripslashes($_POST['taakOmdraaien2']), ENT_QUOTES, 'UTF-8'));

		$taskToAdd = strtolower(trim(stripslashes($taakToevoegen)));
		$taskToRemove = strtolower(trim(stripslashes($taakVerwijderen)));
		$taskToRename = strtolower(trim(stripslashes($taakVeranderenVan)));
		$newTaskName = strtolower(trim(stripslashes($taakVeranderenNaar)));
		if (!empty($taakToevoegen) || !empty($taakVeranderenNaar)) {
			$checkName = !empty($taakToevoegen) ? $taskToAdd : $newTaskName;
			foreach ($config['taken'] as $existingTask) {
				if (strtolower(trim($existingTask)) === $checkName
					&& (!empty($taakToevoegen) || strtolower(trim($existingTask)) !== $taskToRename)) {
					http_response_code(400);
					exit('Deze taaknaam bestaat al.');
				}
			}
		}
		if (!empty($taakVerwijderen) && ($taskToRemove === 'woonkamer' || count($config['taken']) <= 1)) {
			http_response_code(400);
			exit('Woonkamer moet in de takenlijst blijven en de lijst mag niet leeg zijn.');
		}
		if (!empty($taakVeranderenVan) && $taskToRename === 'woonkamer') {
			http_response_code(400);
			exit('De taak woonkamer moet deze naam houden zodat de dubbele wekelijkse toewijzing blijft werken.');
		}
		
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
			$row = mysqli_fetch_row($result);
			$maxKolomNr = $row[0];
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
			$row = mysqli_fetch_row($result);
			$kolomNr1 = $row[0];
			
			$query = "SELECT kolomNr FROM personen WHERE `persoonId`='". $personenOmdraaien2 ."'";
			$result = $link->query($query);
			$row = mysqli_fetch_row($result);
			$kolomNr2 = $row[0];
			
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
		$grens = explode("-", $config['boetesVoldaanGrens']);
		$week = isset($_POST['week']) && $_POST['week'] !== '' ? (int) $_POST['week'] : (int) $grens[1];
		$jaar = isset($_POST['jaar']) && $_POST['jaar'] !== '' ? (int) $_POST['jaar'] : (int) $grens[0];
		if ($week < 1 || $week > 53 || $jaar < 2000 || $jaar > 9999) {
			http_response_code(400);
			exit('Kies een geldige week en een geldig jaar.');
		}
		$nieuweBoetesVoldaanGrens = sprintf('%04d-%02d', $jaar, $week);
		
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
		$mailSettingKeys = array(
			'mailAfzender',
			'herinneringMailDag',
			'herinneringBeginWeekMailOnderwerp',
			'herinneringBeginWeekMailTxt',
			'herinneringNaDeadlineMailOnderwerp',
			'herinneringNaDeadlineMailTxt',
			'boeteMeldingMailOnderwerp',
			'boeteMeldingMailTxt'
		);
		$updatedKey = null;
		foreach ($mailSettingKeys as $settingKey) {
			if (array_key_exists($settingKey, $_POST)) {
				$updatedKey = $settingKey;
				break;
			}
		}
		if ($updatedKey === null) {
			http_response_code(400);
			exit('Selecteer een e-mailinstelling om op te slaan.');
		}

		$settingValue = stripslashes((string) $_POST[$updatedKey]);
		if ($updatedKey === 'herinneringMailDag') {
			$dayNumber = (int) $settingValue;
			if ($dayNumber < 1 || $dayNumber > 7) {
				http_response_code(400);
				exit('Kies een geldige dag voor de wekelijkse herinnering.');
			}
			$settingValue = (string) $dayNumber;
		}

		$query = $link->prepare('UPDATE config SET `value` = ? WHERE `key` = ?');
		$query->bind_param('ss', $settingValue, $updatedKey);
		$query->execute();
		echo 'E-mailinstelling opgeslagen.';
	} else {
		print_r($_POST);
	}
} else if ($soort == 'schedule') {
	if (!$schoonmaakmeester) {
		http_response_code(403);
		exit('Alleen de beheerder mag het rooster genereren.');
	}
	if (!isset($_POST['save']) || $_POST['save'] !== 'generate') {
		http_response_code(400);
		exit('Ongeldige roosteractie.');
	}

	require_once __DIR__ . '/schedule.php';
	try {
		$weeks = isset($_POST['weeks']) ? (int) $_POST['weeks'] : 12;
		$inserted = generateWeeklySchedule($link, $config, $weeks);
		echo 'Rooster bijgewerkt voor ' . $weeks . ' weken vanaf volgende week. ' . $inserted . ' taaktoewijzingen toegevoegd; bestaande toewijzingen zijn behouden.';
	} catch (DomainException $exception) {
		http_response_code(400);
		echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
	} catch (InvalidArgumentException $exception) {
		http_response_code(400);
		echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
	}
}
?>