<?php
$aan = "sjoerdsmink@gmail.com";
$onderwerp = "Dit is het onderwerp4";
$bericht = "Dit is het bericht

MvG,
Sjoerd";
$headers = "From: s.k.smink@student.utwente.nl
Reply-To: s.k.smink@student.utwente.nl
X-Mailer: PHP/". phpversion();

echo "Aan = ". $aan ." <br />
Onderwerp = ". $onderwerp ." <br />
Bericht = ". $bericht ." <br />
Headers = ". $headers ." <br />
Mail = ";

echo mail($aan, $onderwerp, $bericht, $headers);

?>