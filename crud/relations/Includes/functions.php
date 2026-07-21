<?php

define('STUDENT_STATUSSEN', ['In behandeling', 'Geslaagd', 'Gezakt']);

// Tekst veilig maken voor in HTML (voorkomt XSS).
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Oude ingevulde waarde terugzetten in een formulier na een fout.
function old($name, $default = '')
{
    if (isset($_POST[$name])) {
        return e($_POST[$name]);
    }

    return e($default);
}

// Doorsturen naar een andere pagina.
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

// Een melding klaarzetten die op de volgende pagina getoond wordt.
function flash($type, $message)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Alle opleidingen ophalen voor de lookup-dropdown.
function all_opleidingen($pdo)
{
    return $pdo->query('SELECT id, naam FROM opleidingen ORDER BY naam')->fetchAll();
}

// De ingevulde studentgegevens uit het formulier halen.
function student_input()
{
    return [
        'naam' => trim((string) ($_POST['naam'] ?? '')),
        'opleiding_id' => filter_var($_POST['opleiding_id'] ?? null, FILTER_VALIDATE_INT),
        'cijfer' => filter_var($_POST['cijfer'] ?? null, FILTER_VALIDATE_FLOAT),
        'status' => (string) ($_POST['status'] ?? ''),
    ];
}

// De studentgegevens controleren op fouten.
function validate_student($pdo, $student)
{
    $errors = [];

    if ($student['naam'] === '' || mb_strlen($student['naam']) > 100) {
        $errors[] = 'Naam is verplicht (maximaal 100 tekens).';
    }

    $opleidingIds = array_column(all_opleidingen($pdo), 'id');
    if (!in_array($student['opleiding_id'], $opleidingIds, true)) {
        $errors[] = 'Kies een geldige opleiding.';
    }

    if ($student['cijfer'] === false || $student['cijfer'] < 0 || $student['cijfer'] > 10) {
        $errors[] = 'Cijfer moet tussen 0 en 10 liggen.';
    }

    if (!in_array($student['status'], STUDENT_STATUSSEN, true)) {
        $errors[] = 'Kies een geldige status.';
    }

    return $errors;
}

// Een student met de bijbehorende opleiding ophalen op id, of 404 tonen.
function find_student($pdo, $id)
{
    $statement = $pdo->prepare(
        'SELECT studenten.*, opleidingen.naam AS opleiding_naam
         FROM studenten
         JOIN opleidingen ON opleidingen.id = studenten.opleiding_id
         WHERE studenten.id = ?'
    );
    $statement->execute([$id]);
    $student = $statement->fetch();

    if (!$student) {
        http_response_code(404);
        exit('Student niet gevonden.');
    }

    return $student;
}

// Badge-klasse bepalen op basis van de status.
function status_badge_class($status)
{
    return match ($status) {
        'Geslaagd' => 'badge badge-success',
        'Gezakt' => 'badge badge-danger',
        default => 'badge badge-warning',
    };
}

// De bovenkant van elke pagina (head, header, eventueel een melding).
function page_start($title)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    ?>
    <!doctype html>
    <html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | CRUD met relatie</title>
        <link rel="stylesheet" href="styles/styles.css">
    </head>
    <body>
        <header class="site-header">
            <div class="nav-wrap">
                <a class="brand" href="index.php"><span>Student</span> Overzicht</a>
                <nav class="account-nav">
                    <a href="index.php">Overzicht</a>
                    <a href="create.php">Toevoegen</a>
                    <a href="../dashboard/index.php">Dashboard</a>
                </nav>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="container flash-wrap">
                <div class="alert alert-<?= e($message['type']) ?>">
                    <?= e($message['message']) ?>
                </div>
            </div>
        <?php endif; ?>
    <?php
}

// De onderkant van elke pagina.
function page_end()
{
    ?>
        <footer class="site-footer">
            <div class="container">Cheat sheet: CRUD met lookup en status &middot; <?= date('Y') ?></div>
        </footer>
    </body>
    </html>
    <?php
}
