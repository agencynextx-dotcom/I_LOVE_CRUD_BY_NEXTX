<?php
require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Verwijderen mag alleen via POST.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
find_user($pdo, $id);

$statement = $pdo->prepare('DELETE FROM users WHERE id = ?');
$statement->execute([$id]);

flash('success', 'Gebruiker verwijderd.');
redirect('index.php');
