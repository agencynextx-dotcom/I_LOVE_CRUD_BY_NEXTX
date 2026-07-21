<?php
require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

// LET OP: dit bestand is EXPRES onveilig, als lesmateriaal.
// Fouten die hier staan:
//   1. De actie gebruikt GET in plaats van POST voor iets dat data wijzigt.
//   2. Er is geen verify_csrf() aanroep.
// Daardoor kan elke pagina op het internet dit bezoek laten afleggen namens
// een ingelogde gebruiker, bijvoorbeeld via <img src="...">. Vergelijk dit
// met secure-transfer.php.

$from = filter_input(INPUT_GET, 'from', FILTER_VALIDATE_INT) ?: 0;
$to = filter_input(INPUT_GET, 'to', FILTER_VALIDATE_INT) ?: 0;
$amount = filter_input(INPUT_GET, 'amount', FILTER_VALIDATE_FLOAT) ?: 0.0;

if ($from > 0 && $to > 0 && $amount > 0 && wallet_balance($pdo, $from) >= $amount) {
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE wallets SET balance = balance - ? WHERE id = ?')->execute([$amount, $from]);
    $pdo->prepare('UPDATE wallets SET balance = balance + ? WHERE id = ?')->execute([$amount, $to]);
    $pdo->commit();

    flash('error', 'SRD ' . number_format($amount, 2, ',', '.') . ' overgemaakt via een kwetsbare GET-link (geen CSRF-controle!).');
} else {
    flash('error', 'Overboeking is mislukt of ongeldig.');
}

redirect('index.php');
