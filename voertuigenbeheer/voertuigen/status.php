<?php
require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Status wijzigen mag alleen via POST.');
}

verify_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$vehicle = find_own_vehicle($pdo, $id);
$status = (string) ($_POST['status'] ?? '');

if (!in_array($status, VOERTUIG_STATUSSEN, true)) {
    flash('error', 'Ongeldige status.');
    redirect('index.php');
}

$statement = $pdo->prepare(
    'UPDATE voertuigen SET status = ? WHERE id = ? AND user_id = ?'
);
$statement->execute([$status, $id, user_id()]);

audit($pdo, 'status gewijzigd', 'voertuig', $id, $status);
flash('success', 'Status gewijzigd.');
redirect('index.php');
