<?php
require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Overmaken mag alleen via POST.');
}

// Deze controle staat vóór elke database-wijziging, niet erna.
verify_csrf();

$from = filter_input(INPUT_POST, 'from', FILTER_VALIDATE_INT) ?: 0;
$to = filter_input(INPUT_POST, 'to', FILTER_VALIDATE_INT) ?: 0;
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT) ?: 0.0;

if ($from > 0 && $to > 0 && $amount > 0 && wallet_balance($pdo, $from) >= $amount) {
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE wallets SET balance = balance - ? WHERE id = ?')->execute([$amount, $from]);
    $pdo->prepare('UPDATE wallets SET balance = balance + ? WHERE id = ?')->execute([$amount, $to]);
    $pdo->commit();

    flash('success', 'SRD ' . number_format($amount, 2, ',', '.') . ' veilig overgemaakt.');
} else {
    flash('error', 'Overboeking is mislukt of ongeldig.');
}

redirect('index.php');
