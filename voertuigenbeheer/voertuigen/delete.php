<?php
require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Verwijderen mag alleen via POST.');
}

verify_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$vehicle = find_own_vehicle($pdo, $id);

$statement = $pdo->prepare(
    "SELECT COUNT(*) FROM onderhoud WHERE voertuig_id = ? AND status = 'Gepland'"
);
$statement->execute([$id]);
$planned = (int) $statement->fetchColumn();

if ($planned > 0 && ($_POST['confirm_planned'] ?? '') !== 'yes') {
    flash('warning', 'Dit voertuig heeft gepland onderhoud. Bevestig eerst de waarschuwing.');
    redirect('show.php?id=' . $id);
}

$pdo->beginTransaction();

try {
    audit($pdo, 'verwijderd', 'voertuig', $id, $vehicle['kenteken']);

    $statement = $pdo->prepare('DELETE FROM voertuigen WHERE id = ? AND user_id = ?');
    $statement->execute([$id, user_id()]);
    $pdo->commit();

    flash('success', 'Voertuig en bijbehorend onderhoud verwijderd.');
    redirect('index.php');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash('error', 'Verwijderen mislukt.');
    redirect('show.php?id=' . $id);
}
