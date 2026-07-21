<?php
require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Verwijderen mag alleen via POST.');
}

// Deze controle moet vóór elke schrijfactie staan, niet erna.
verify_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;

$statement = $pdo->prepare('DELETE FROM messages WHERE id = ?');
$statement->execute([$id]);

flash('success', 'Bericht verwijderd.');
redirect('index.php');
