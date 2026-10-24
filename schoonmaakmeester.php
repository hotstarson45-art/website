<?php
$requireSchoonmaakmeester = true;
include("include.php");

$peopleResult = $link->query('SELECT persoonId, persoonNaam FROM personen ORDER BY kolomNr ASC');
$people = array();
while ($person = mysqli_fetch_assoc($peopleResult)) {
	$people[] = $person;
}

function adminHtml($value) {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$paidThrough = explode('-', $config['boetesVoldaanGrens']);
$paidYear = isset($paidThrough[0]) ? (int) $paidThrough[0] : (int) date('Y');
$paidWeek = isset($paidThrough[1]) ? (int) $paidThrough[1] : 1;
?>
<!DOCTYPE html>
<html lang="nl">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Schoonmaakrooster - beheer</title>
	<link rel="stylesheet" type="text/css" href="style.css" />
	<script type="text/javascript" src="jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="include.js"></script>
	<script type="text/javascript">
	$(function() {
		hideMelding(0);
	});

	function adminPost(data, reloadOnSuccess) {
		$.ajax({
			type: 'POST',
			url: 'process.php',
			data: data,
			success: function(message) {
				showMelding(message);
				if (reloadOnSuccess) {
					window.setTimeout(function() { window.location.reload(); }, 300);
				}
			},
			error: function(xhr) {
				showMelding(xhr.responseText || 'Opslaan is mislukt.');
			}
		});
		return false;
	}

	function generateSchedule() {
		if (confirm('Vul het rooster voor 12 weken vanaf volgende week aan? Bestaande taken blijven staan.')) {
			adminPost({soort: 'schedule', save: 'generate', weeks: 12}, false);
		}
	}

	function addTask() {
		var name = $('#task-new-name').val().trim();
		if (name) {
			adminPost({
				soort: 'schoonmaakmeester', save: 'taak',
				taakToevoegen: name, taakVerwijderen: '', taakVeranderenVan: '',
				taakVeranderenNaar: '', taakOmdraaien1: '', taakOmdraaien2: ''
			}, true);
		}
		return false;
	}

	function renameTask() {
		var oldName = $('#task-old-name').val();
		var newName = $('#task-rename').val().trim();
		if (oldName && newName) {
			adminPost({
				soort: 'schoonmaakmeester', save: 'taak',
				taakToevoegen: '', taakVerwijderen: '', taakVeranderenVan: oldName,
				taakVeranderenNaar: newName, taakOmdraaien1: '', taakOmdraaien2: ''
			}, true);
		}
		return false;
	}

	function deleteTask() {
		var name = $('#task-delete-name').val();
		if (name && confirm('Taak "' + name + '" verwijderen uit de takenlijst? Bestaande roosterrijen blijven bestaan.')) {
			adminPost({
				soort: 'schoonmaakmeester', save: 'taak',
				taakToevoegen: '', taakVerwijderen: name, taakVeranderenVan: '',
				taakVeranderenNaar: '', taakOmdraaien1: '', taakOmdraaien2: ''
			}, true);
		}
		return false;
	}

	function addPerson() {
		var name = $('#person-new-name').val().trim();
		if (name) {
			adminPost({
				soort: 'schoonmaakmeester', save: 'persoon',
				persoonToevoegen: name, emailadres: $('#person-new-email').val(),
				persoonVerwijderen: '', persoonVeranderenVan: '',
				persoonVeranderenNaar: '', personenOmdraaien1: '', personenOmdraaien2: ''
			}, true);
		}
		return false;
	}

	function renamePerson() {
		var personId = $('#person-edit-id').val();
		var name = $('#person-edit-name').val().trim();
		if (personId && name) {
			adminPost({
				soort: 'schoonmaakmeester', save: 'persoon',
				persoonToevoegen: '', emailadres: '', persoonVerwijderen: '',
				persoonVeranderenVan: personId, persoonVeranderenNaar: name,
				personenOmdraaien1: '', personenOmdraaien2: ''
			}, true);
		}
		return false;
	}

	function deletePerson() {
		var personId = $('#person-delete-id').val();
		if (personId && confirm('Deze persoon verwijderen? Alle bijbehorende taken en boetes worden ook verwijderd.')) {
			adminPost({
				soort: 'schoonmaakmeester', save: 'persoon',
				persoonToevoegen: '', emailadres: '', persoonVerwijderen: personId,
				persoonVeranderenVan: '', persoonVeranderenNaar: '',
				personenOmdraaien1: '', personenOmdraaien2: ''
			}, true);
		}
		return false;
	}

	function saveDeadlineDay(value) {
		adminPost({soort: 'schoonmaakmeester', save: 'deadlineDag', deadlineDag: value}, false);
	}

	function savePaidThrough() {
		adminPost({
			soort: 'schoonmaakmeester', save: 'boetesVoldaanGrens',
			week: $('#paid-through-week').val(), jaar: $('#paid-through-year').val()
		}, false);
	}

	function deleteOldHistory() {
		var week = $('#history-week').val();
		var year = $('#history-year').val();
		if (week && year && confirm('Alle taken en boetes vóór week ' + year + '-' + week + ' definitief verwijderen? Dit kan niet ongedaan worden gemaakt.')) {
			adminPost({
				soort: 'schoonmaakmeester', save: 'takenVerwijderen',
				takenVerwijderenWeek: week, takenVerwijderenJaar: year
			}, false);
		}
		return false;
	}

	function saveMailSetting(key, value) {
		var data = {soort: 'schoonmaakmeester', save: 'mail'};
		data[key] = value;
		adminPost(data, false);
	}
	</script>
</head>
<body class="admin-page">
	<header class="admin-header">
		<div>
			<p class="titel">Schoonmaakrooster</p>
			<p>Beheer</p>
		</div>
		<a href="beer_admin.php">Bierbeheer</a> &nbsp; | &nbsp;
		<a href="./">Terug naar het rooster</a>
	</header>

	<div id="melding" class="admin-message"><div id="meldingTxt">&nbsp;</div></div>

	<main class="admin-layout">
		<section class="admin-card admin-card-primary">
			<h1>Rooster genereren</h1>
			<p>Vul 12 weken vanaf volgende week aan. Bestaande taken blijven staan. Woonkamer krijgt twee verschillende personen; niemand krijgt twee taken in dezelfde week.</p>
			<button type="button" onclick="generateSchedule();">Genereer / vul rooster aan</button>
			<p class="admin-help">Om een persoon, taak, deadline of boete voor een specifieke week te wijzigen, open het rooster en klik op de taak.</p>
		</section>

		<section class="admin-card">
			<h2>Taken beheren</h2>
			<form onsubmit="return addTask();">
				<label for="task-new-name">Nieuwe taak</label>
				<div class="admin-inline">
					<input id="task-new-name" type="text" maxlength="128" required />
					<button type="submit">Toevoegen</button>
				</div>
			</form>
			<form onsubmit="return renameTask();">
				<label for="task-old-name">Taak hernoemen</label>
				<div class="admin-inline">
					<select id="task-old-name" required>
						<option value="">Selecteer taak</option>
						<?php foreach ($config['taken'] as $taskName): $taskLabel = html_entity_decode($taskName, ENT_QUOTES, 'UTF-8'); ?>
						<option value="<?php echo adminHtml($taskLabel); ?>"><?php echo adminHtml($taskLabel); ?></option>
						<?php endforeach; ?>
					</select>
					<input id="task-rename" type="text" maxlength="128" placeholder="Nieuwe naam" required />
					<button type="submit">Hernoemen</button>
				</div>
			</form>
			<form onsubmit="return deleteTask();">
				<label for="task-delete-name">Taak uit keuzelijst verwijderen</label>
				<div class="admin-inline">
					<select id="task-delete-name" required>
						<option value="">Selecteer taak</option>
						<?php foreach ($config['taken'] as $taskName): $taskLabel = html_entity_decode($taskName, ENT_QUOTES, 'UTF-8'); ?>
						<option value="<?php echo adminHtml($taskLabel); ?>"><?php echo adminHtml($taskLabel); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="admin-button-danger">Verwijderen</button>
				</div>
				<p class="admin-help">Bestaande taken in weken blijven behouden.</p>
			</form>
			<label for="deadline-day">Standaard deadline</label>
			<select id="deadline-day" onchange="saveDeadlineDay(this.value);">
				<?php foreach ($dagenTxt as $dayNumber => $dayName): $dayNumber++; ?>
				<option value="<?php echo $dayNumber; ?>"<?php if ((int) $dayNumber === (int) $config['deadlineDag']) echo ' selected="selected"'; ?>><?php echo adminHtml(ucfirst($dayName)); ?></option>
				<?php endforeach; ?>
			</select>
		</section>

		<section class="admin-card">
			<h2>Bewoners beheren</h2>
			<form onsubmit="return addPerson();">
				<label for="person-new-name">Nieuwe bewoner</label>
				<div class="admin-inline">
					<input id="person-new-name" type="text" maxlength="128" placeholder="Naam" required />
					<input id="person-new-email" type="email" maxlength="255" placeholder="E-mailadres (optioneel)" />
					<button type="submit">Toevoegen</button>
				</div>
			</form>
			<form onsubmit="return renamePerson();">
				<label for="person-edit-id">Bewoner hernoemen</label>
				<div class="admin-inline">
					<select id="person-edit-id" required>
						<option value="">Selecteer bewoner</option>
						<?php foreach ($people as $person): ?>
						<option value="<?php echo (int) $person['persoonId']; ?>"><?php echo adminHtml(html_entity_decode($person['persoonNaam'], ENT_QUOTES, 'UTF-8')); ?></option>
						<?php endforeach; ?>
					</select>
					<input id="person-edit-name" type="text" maxlength="128" placeholder="Nieuwe naam" required />
					<button type="submit">Hernoemen</button>
				</div>
			</form>
			<form onsubmit="return deletePerson();">
				<label for="person-delete-id">Bewoner verwijderen</label>
				<div class="admin-inline">
					<select id="person-delete-id" required>
						<option value="">Selecteer bewoner</option>
						<?php foreach ($people as $person): ?>
						<option value="<?php echo (int) $person['persoonId']; ?>"><?php echo adminHtml(html_entity_decode($person['persoonNaam'], ENT_QUOTES, 'UTF-8')); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="admin-button-danger">Verwijderen</button>
				</div>
				<p class="admin-help">Ook de roosterregels en boetes van deze bewoner worden verwijderd.</p>
			</form>
		</section>

		<section class="admin-card">
			<h2>Boetes</h2>
			<p>Voer een boete handmatig in bij de taak in het rooster. Er worden geen boetebedragen meer automatisch berekend.</p>
			<label for="paid-through-week">Boetes t/m week markeren als voldaan</label>
			<div class="admin-inline">
				<select id="paid-through-week">
					<?php for ($weekNumber = 1; $weekNumber <= 53; $weekNumber++): $weekValue = sprintf('%02d', $weekNumber); ?>
					<option value="<?php echo $weekValue; ?>"<?php if ($weekNumber === $paidWeek) echo ' selected="selected"'; ?>>Week <?php echo $weekNumber; ?></option>
					<?php endfor; ?>
				</select>
				<select id="paid-through-year">
					<?php for ($year = $paidYear - 5; $year <= date('Y') + 5; $year++): ?>
					<option value="<?php echo $year; ?>"<?php if ($year === $paidYear) echo ' selected="selected"'; ?>><?php echo $year; ?></option>
					<?php endfor; ?>
				</select>
				<button type="button" onclick="savePaidThrough();">Opslaan</button>
			</div>
			<p class="admin-help">Voldaan markeren bewaart de boetebedragen en geschiedenis.</p>
		</section>

		<section class="admin-card">
			<h2>Oude geschiedenis verwijderen</h2>
			<p>Dit verwijdert oude taken en bijbehorende boetes definitief.</p>
			<div class="admin-inline">
				<select id="history-week">
					<option value="">Week</option>
					<?php for ($weekNumber = 1; $weekNumber <= 53; $weekNumber++): $weekValue = sprintf('%02d', $weekNumber); ?>
					<option value="<?php echo $weekValue; ?>"><?php echo $weekNumber; ?></option>
					<?php endfor; ?>
				</select>
				<select id="history-year">
					<option value="">Jaar</option>
					<?php for ($year = date('Y') - 10; $year <= date('Y') + 5; $year++): ?>
					<option value="<?php echo $year; ?>"><?php echo $year; ?></option>
					<?php endfor; ?>
				</select>
				<button type="button" class="admin-button-danger" onclick="deleteOldHistory();">Verwijder vóór deze week</button>
			</div>
		</section>

		<details class="admin-card admin-card-wide">
			<summary>E-mailherinneringen en berichten</summary>
			<div class="admin-mail-settings">
				<label for="mail-sender">Afzender</label>
				<input id="mail-sender" type="text" value="<?php echo adminHtml($config['mailAfzender']); ?>" onchange="saveMailSetting('mailAfzender', this.value);" />

				<label for="mail-day">Wekelijkse herinnering versturen op</label>
				<select id="mail-day" onchange="saveMailSetting('herinneringMailDag', this.value);">
					<?php foreach ($dagenTxt as $dayNumber => $dayName): $dayNumber++; ?>
					<option value="<?php echo $dayNumber; ?>"<?php if ((int) $dayNumber === (int) $config['herinneringMailDag']) echo ' selected="selected"'; ?>><?php echo adminHtml(ucfirst($dayName)); ?></option>
					<?php endforeach; ?>
				</select>

				<?php
				$mailGroups = array(
					array('Herinnering aan het begin van de week', 'herinneringBeginWeekMailOnderwerp', 'herinneringBeginWeekMailTxt'),
					array('Herinnering na de deadline', 'herinneringNaDeadlineMailOnderwerp', 'herinneringNaDeadlineMailTxt'),
					array('Boetemelding', 'boeteMeldingMailOnderwerp', 'boeteMeldingMailTxt')
				);
				foreach ($mailGroups as $mailGroup):
				?>
				<fieldset>
					<legend><?php echo adminHtml($mailGroup[0]); ?></legend>
					<label>Onderwerp
						<input type="text" value="<?php echo adminHtml($config[$mailGroup[1]]); ?>" onchange="saveMailSetting('<?php echo adminHtml($mailGroup[1]); ?>', this.value);" />
					</label>
					<label>Bericht
						<textarea rows="5" onchange="saveMailSetting('<?php echo adminHtml($mailGroup[2]); ?>', this.value);"><?php echo adminHtml($config[$mailGroup[2]]); ?></textarea>
					</label>
				</fieldset>
				<?php endforeach; ?>
				<p class="admin-help">Beschikbare tags voor herinneringen: [PERSOONNAAM], [PERSOONID], [TAAKNAAM], [DEADLINE]. Boetemeldingen ondersteunen ook [BOETE] en [GEDAAN].</p>
			</div>
		</details>
	</main>
</body>
</html>
