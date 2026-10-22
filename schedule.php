<?php
function requireTransactionalScheduleTable($link)
{
	$result = $link->query("SHOW TABLE STATUS WHERE Name IN ('taken', 'personen', 'config')");
	$transactionalTables = array();
	while ($table = $result->fetch_assoc()) {
		if (isset($table['Engine']) && strcasecmp($table['Engine'], 'InnoDB') === 0) {
			$transactionalTables[$table['Name']] = true;
		}
	}
	if (!isset($transactionalTables['taken'], $transactionalTables['personen'], $transactionalTables['config'])) {
		throw new DomainException('The taken, personen, and config tables must use InnoDB before schedule changes can be saved safely.');
	}
}

function lockScheduleResidents($link)
{
	$result = $link->query('SELECT persoonId FROM personen ORDER BY persoonId ASC FOR UPDATE');
	while ($result->fetch_assoc()) {
	}
}

function generateWeeklySchedule($link, $config, $weeks)
{
	requireTransactionalScheduleTable($link);
	if ($weeks < 1 || $weeks > 52) {
		throw new InvalidArgumentException('Choose a schedule length between 1 and 52 weeks.');
	}

	$tasks = array_values($config['taken']);
	$woonkamerIndex = false;
	$taskNames = array();
	foreach ($tasks as $index => $taskName) {
		$normalizedName = strtolower(trim($taskName));
		if ($normalizedName === '') {
			throw new DomainException('The task list contains an empty task name.');
		}
		if (isset($taskNames[$normalizedName])) {
			throw new DomainException('The task list contains duplicate task names.');
		}
		$taskNames[$normalizedName] = true;
		if ($normalizedName === 'woonkamer') {
			$woonkamerIndex = $index;
		}
	}
	if ($woonkamerIndex === false) {
		throw new DomainException('Add a task named "woonkamer" before generating the schedule.');
	}

	$slotsPerWeek = count($tasks) + 1;
	$start = new DateTimeImmutable('next monday');
	$inserted = 0;
	$link->begin_transaction();
	try {
		lockScheduleResidents($link);
		$personResult = $link->query('SELECT persoonId FROM personen ORDER BY kolomNr ASC');
		$personIds = array();
		while ($person = $personResult->fetch_assoc()) {
			$personIds[] = (int) $person['persoonId'];
		}
		if (count($personIds) < $slotsPerWeek) {
			throw new DomainException(
				'This schedule needs at least ' . $slotsPerWeek . ' residents: one per task plus a second person for woonkamer.'
			);
		}

		$selectAssignments = $link->prepare('SELECT taakNaam, persoonId FROM taken WHERE `week` = ? ORDER BY taakId ASC');
		$insertAssignment = $link->prepare(
			'INSERT INTO taken (`week`, taakNaam, persoonId, deadline) VALUES (?, ?, ?, ?)'
		);
		for ($weekOffset = 0; $weekOffset < $weeks; $weekOffset++) {
			$weekDate = $start->modify('+' . $weekOffset . ' weeks');
			$weekId = $weekDate->format('o-W');
			$deadline = date('Y-m-d', getDeadlineDag($weekDate->getTimestamp()));

			$selectAssignments->bind_param('s', $weekId);
			$selectAssignments->execute();
			$existingResult = $selectAssignments->get_result();

			$assignedPeople = array();
			$taskCounts = array_fill(0, count($tasks), 0);
			while ($assignment = $existingResult->fetch_assoc()) {
				$personId = (int) $assignment['persoonId'];
				if (!in_array($personId, $personIds, true)) {
					throw new DomainException('Week ' . $weekId . ' contains an assignment to a resident who is no longer active.');
				}
				if (isset($assignedPeople[$personId])) {
					throw new DomainException(
						'Week ' . $weekId . ' already assigns more than one task to the same resident. Correct it before generating.'
					);
				}
				$assignedPeople[$personId] = true;

				$taskIndex = array_search($assignment['taakNaam'], $tasks, true);
				if ($taskIndex !== false) {
					$taskCounts[$taskIndex]++;
				}
			}

			foreach ($taskCounts as $taskIndex => $taskCount) {
				$requiredCount = ($taskIndex === $woonkamerIndex) ? 2 : 1;
				if ($taskCount > $requiredCount) {
					throw new DomainException(
						'Week ' . $weekId . ' already has too many assignments for "' . $tasks[$taskIndex] . '".'
					);
				}
			}

			$personCount = count($personIds);
			$personCursor = ($weekOffset * $slotsPerWeek) % $personCount;
			foreach ($tasks as $taskIndex => $taskName) {
				$requiredCount = ($taskIndex === $woonkamerIndex) ? 2 : 1;
				$missingCount = $requiredCount - $taskCounts[$taskIndex];
				for ($missing = 0; $missing < $missingCount; $missing++) {
					$personId = null;
					for ($attempt = 0; $attempt < $personCount; $attempt++) {
						$candidate = $personIds[$personCursor % $personCount];
						$personCursor = ($personCursor + 1) % $personCount;
						if (!isset($assignedPeople[$candidate])) {
							$personId = $candidate;
							break;
						}
					}
					if ($personId === null) {
						throw new DomainException('Week ' . $weekId . ' does not have enough unassigned residents.');
					}

					$assignedPeople[$personId] = true;
					$insertAssignment->bind_param('ssis', $weekId, $taskName, $personId, $deadline);
					$insertAssignment->execute();
					$inserted++;
				}
			}
		}
		$link->commit();
	} catch (Throwable $error) {
		$link->rollback();
		throw $error;
	}

	return $inserted;
}

function moveTaskAssignment($link, $taskId, $personId)
{
	requireTransactionalScheduleTable($link);
	if ($taskId < 1 || $personId < 1) {
		throw new InvalidArgumentException('Select a valid task and resident.');
	}

	$link->begin_transaction();
	try {
		lockScheduleResidents($link);
		$taskQuery = $link->prepare('SELECT `week` FROM taken WHERE taakId = ?');
		$taskQuery->bind_param('i', $taskId);
		$taskQuery->execute();
		$task = $taskQuery->get_result()->fetch_assoc();
		if (!$task) {
			throw new DomainException('The selected task no longer exists.');
		}

		$personQuery = $link->prepare('SELECT persoonId FROM personen WHERE persoonId = ?');
		$personQuery->bind_param('i', $personId);
		$personQuery->execute();
		if (!$personQuery->get_result()->fetch_assoc()) {
			throw new DomainException('The selected resident no longer exists.');
		}

		$week = $task['week'];
		$assignmentQuery = $link->prepare(
			'SELECT taakId FROM taken WHERE `week` = ? AND persoonId = ? AND taakId <> ? LIMIT 1'
		);
		$assignmentQuery->bind_param('sii', $week, $personId, $taskId);
		$assignmentQuery->execute();
		if ($assignmentQuery->get_result()->fetch_assoc()) {
			throw new DomainException('That resident already has a task in this week.');
		}

		$updateQuery = $link->prepare('UPDATE taken SET persoonId = ? WHERE taakId = ?');
		$updateQuery->bind_param('ii', $personId, $taskId);
		$updateQuery->execute();
		$link->commit();
	} catch (Throwable $error) {
		$link->rollback();
		throw $error;
	}
}

function saveTaskAssignment($link, $config, $taskId, $week, $personId, $taskName, $addTaskToCatalog = false)
{
	requireTransactionalScheduleTable($link);
	$taskName = trim($taskName);
	if ($taskName === '' || strlen($taskName) > 128 || preg_match('/[\x00-\x1F\x7F]/', $taskName)) {
		throw new InvalidArgumentException('Enter a task name up to 128 bytes long.');
	}
	if (!in_array($taskName, $config['taken'], true) && !$addTaskToCatalog) {
		throw new DomainException('Select a task from the configured task list.');
	}
	if ($personId < 1 || !preg_match('/^\d{4}-\d{2}$/', $week)) {
		throw new InvalidArgumentException('Select a valid week and resident.');
	}

	$requiredTaskCount = (strtolower(trim($taskName)) === 'woonkamer') ? 2 : 1;
	$link->begin_transaction();
	try {
		lockScheduleResidents($link);
		if (!in_array($taskName, $config['taken'], true)) {
			$taskList = $config['taken'];
			foreach ($taskList as $existingTask) {
				if (strcasecmp(trim($existingTask), $taskName) === 0) {
					$taskName = $existingTask;
					break;
				}
			}
			if (!in_array($taskName, $taskList, true)) {
				$taskList[] = $taskName;
				$serializedTasks = 'array(' . implode(', ', array_map(function ($task) {
					return '"' . addcslashes($task, "\\\"") . '"';
				}, $taskList)) . ')';
				$configQuery = $link->prepare("UPDATE config SET `value` = ? WHERE `key` = 'taken'");
				$configQuery->bind_param('s', $serializedTasks);
				$configQuery->execute();
				if ($configQuery->affected_rows !== 1) {
					throw new DomainException('The task list could not be updated. Check the config table and try again.');
				}
			}
		}

		$personQuery = $link->prepare('SELECT persoonId FROM personen WHERE persoonId = ?');
		$personQuery->bind_param('i', $personId);
		$personQuery->execute();
		if (!$personQuery->get_result()->fetch_assoc()) {
			throw new DomainException('The selected resident no longer exists.');
		}

		if ($taskId === 0) {
			$personAssignmentQuery = $link->prepare(
				'SELECT taakId FROM taken WHERE `week` = ? AND persoonId = ? LIMIT 1'
			);
			$personAssignmentQuery->bind_param('si', $week, $personId);
		} else {
			$personAssignmentQuery = $link->prepare(
				'SELECT taakId FROM taken WHERE `week` = ? AND persoonId = ? AND taakId <> ? LIMIT 1'
			);
			$personAssignmentQuery->bind_param('sii', $week, $personId, $taskId);
		}
		$personAssignmentQuery->execute();
		if ($personAssignmentQuery->get_result()->fetch_assoc()) {
			throw new DomainException('That resident already has a task in this week.');
		}

		if ($taskId === 0) {
			$countQuery = $link->prepare(
				'SELECT COUNT(*) AS taskCount FROM taken WHERE `week` = ? AND taakNaam = ?'
			);
			$countQuery->bind_param('ss', $week, $taskName);
		} else {
			$countQuery = $link->prepare(
				'SELECT COUNT(*) AS taskCount FROM taken WHERE `week` = ? AND taakNaam = ? AND taakId <> ?'
			);
			$countQuery->bind_param('ssi', $week, $taskName, $taskId);
		}
		$countQuery->execute();
		$count = (int) $countQuery->get_result()->fetch_assoc()['taskCount'];
		if ($count >= $requiredTaskCount) {
			throw new DomainException('Week ' . $week . ' already has all assignments for "' . $taskName . '".');
		}

		if ($taskId === 0) {
			$deadline = date('Y-m-d', week2date($week));
			$saveQuery = $link->prepare(
				'INSERT INTO taken (`week`, taakNaam, persoonId, deadline) VALUES (?, ?, ?, ?)'
			);
			$saveQuery->bind_param('ssis', $week, $taskName, $personId, $deadline);
		} else {
			$taskQuery = $link->prepare('SELECT `week`, persoonId FROM taken WHERE taakId = ?');
			$taskQuery->bind_param('i', $taskId);
			$taskQuery->execute();
			$existingTask = $taskQuery->get_result()->fetch_assoc();
			if (!$existingTask || $existingTask['week'] !== $week || (int) $existingTask['persoonId'] !== $personId) {
				throw new DomainException('The selected assignment has changed. Reload the schedule and try again.');
			}

			$saveQuery = $link->prepare('UPDATE taken SET taakNaam = ? WHERE taakId = ?');
			$saveQuery->bind_param('si', $taskName, $taskId);
		}
		$saveQuery->execute();
		$link->commit();
	} catch (Throwable $error) {
		$link->rollback();
		throw $error;
	}
}
?>
