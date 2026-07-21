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
$item = find_own_maintenance($pdo, $id);

$statement = $pdo->prepare(
    'DELETE FROM onderhoud WHERE id = ? AND voertuig_id IN (
        SELECT id FROM voertuigen WHERE user_id = ?
    )'
);
$statement->execute([$item['id'], user_id()]);

audit($pdo, 'verwijderd', 'onderhoud', (int) $item['id'], $item['omschrijving']);
flash('success', 'Onderhoud verwijderd.');
redirect('../voertuigen/show.php?id=' . $item['voertuig_id']);
