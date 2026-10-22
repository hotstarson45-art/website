<?php
$_SERVER['PHP_AUTH_USER'] = 'stars';
$_SERVER['PHP_AUTH_PW'] = 'goedvoer';

include("include.php");

// herinnering mailtje sturen
$verschilDeadlineHerinnering = $config['deadlineDag'] - $config['herinneringMailDag'];
if ($verschilDeadlineHerinnering < 0)
	$verschilDeadlineHerinnering += 7;
$fromDate = time() + ($verschilDeadlineHerinnering * 24 * 60 * 60);

$query = "
	SELECT
		t.*, p.*
	FROM
		taken AS t, 
		personen AS p 
	WHERE 
		t.persoonId=p.persoonId AND 
		p.herinneringBeginWeek='1' AND 
		t.herinneringBeginWeekMail='0000-00-00 00:00:00' AND 
		t.deadline <= '". date('Y-m-d', $fromDate) ."' AND
		t.deadline >= '". date('Y-m-d', (time()-7*24*60*60)) ."' AND 
		t.gedaan='nogNiet'";

$result = $link->query($query);
if($result->num_rows != 0) {
	// include PEAR bestanden om email(s) te versturen
	include('Mail.php');
	include('Mail/mime.php');
	
	while ($info = mysqli_fetch_assoc($result)) {
		$deadline = sqldate2phpdate($info['deadline']);
		$deadlineTxt = date("j", $deadline) .' '. $maandenTxt[date("n", $deadline)-1] .' '. date("Y", $deadline);
		
		$txtVars = array("[TAAKNAAM]", "[PERSOONNAAM]", "[PERSOONID]", "[DEADLINE]");
		$txtValuesHTML = array(strtolower($info['taakNaam']), $info['persoonNaam'], $info['persoonId'], $deadlineTxt);
		$txtValuesTXT = htmlToPlainTxt($txtValuesHTML);
		
		// herinnering na begin week versturen
		$onderwerp = str_replace($txtVars, $txtValuesTXT, htmlToPlainTxt($config['herinneringBeginWeekMailOnderwerp']));
		$berichtHTML = str_replace($txtVars, $txtValuesHTML, $config['herinneringBeginWeekMailTxt']);
		$berichtTXT = str_replace($txtVars, $txtValuesTXT, htmlToPlainTxt($config['herinneringBeginWeekMailTxt']));
		
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
		
		$query = "UPDATE taken SET herinneringBeginWeekMail=NOW() WHERE taakId='". $info['taakId'] ."'";
		$link->query($query);
	}
}
?>