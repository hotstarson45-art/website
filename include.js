var timerId = 0;

function showMelding(tekst) {
	$("#melding").stop();
	$('#meldingTxt').html(tekst);
	$("#melding").animate({opacity: 1}, 500, null, function() {
		// wachten voor 10 seconden om dan de melding weer te verbergen
		clearTimeout(timerId); // laatste timeout stoppen en nieuwe opstarten
		timerId = setTimeout("hideMelding()", 10000);
	});
}

function hideMelding(wachtTijd) {
	if (wachtTijd == null)
		wachtTijd = 2000;
	$("#melding").animate({opacity: 0}, wachtTijd);
}