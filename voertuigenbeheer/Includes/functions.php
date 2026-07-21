<?php

// Sessie starten als dat nog niet is gebeurd.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('voertuigenbeheerSession');
    session_start();
}

// Toegestane waarden voor de status-velden.
define('VOERTUIG_STATUSSEN', ['Actief', 'In onderhoud', 'Buiten gebruik']);
define('ONDERHOUD_STATUSSEN', ['Gepland', 'Uitgevoerd', 'Geannuleerd']);

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

// Het id van de ingelogde gebruiker, of 0 als niemand is ingelogd.
function user_id()
{
    if (isset($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    return 0;
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
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Alleen ingelogde gebruikers mogen verder, anders naar de inlogpagina.
function require_login($loginPage = '../Auth/login.php')
{
    if (user_id() === 0) {
        redirect($loginPage);
    }
}

// CSRF-token ophalen (en aanmaken als die er nog niet is).
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Verborgen formulierveld met het CSRF-token.
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Controleren of het CSRF-token bij een POST klopt.
function verify_csrf()
{
    $storedToken = '';
    if (isset($_SESSION['csrf_token'])) {
        $storedToken = (string) $_SESSION['csrf_token'];
    }

    $submittedToken = '';
    if (isset($_POST['csrf_token'])) {
        $submittedToken = (string) $_POST['csrf_token'];
    }

    if ($storedToken === '' || !hash_equals($storedToken, $submittedToken)) {
        http_response_code(403);
        exit('Ongeldig CSRF-token. Ga terug en probeer opnieuw.');
    }
}

// Een voertuig ophalen, maar alleen als het van de ingelogde gebruiker is.
function find_own_vehicle($pdo, $id)
{
    $statement = $pdo->prepare(
        'SELECT * FROM voertuigen WHERE id = ? AND user_id = ?'
    );
    $statement->execute([$id, user_id()]);
    $vehicle = $statement->fetch();

    if (!$vehicle) {
        http_response_code(404);
        exit('Voertuig niet gevonden.');
    }

    return $vehicle;
}

// Een onderhoudsrecord ophalen, maar alleen als het bij een eigen voertuig hoort.
function find_own_maintenance($pdo, $id)
{
    $statement = $pdo->prepare(
        'SELECT onderhoud.*
         FROM onderhoud
         JOIN voertuigen ON voertuigen.id = onderhoud.voertuig_id
         WHERE onderhoud.id = ? AND voertuigen.user_id = ?'
    );
    $statement->execute([$id, user_id()]);
    $maintenance = $statement->fetch();

    if (!$maintenance) {
        http_response_code(404);
        exit('Onderhoud niet gevonden.');
    }

    return $maintenance;
}

// Checken of een datum echt bestaat en in het juiste formaat staat.
function valid_date($date)
{
    $parsedDate = DateTime::createFromFormat('Y-m-d', $date);

    if ($parsedDate === false) {
        return false;
    }

    return $parsedDate->format('Y-m-d') === $date;
}

// De ingevulde voertuiggegevens uit het formulier halen.
function vehicle_input()
{
    $kenteken = '';
    if (isset($_POST['kenteken'])) {
        $kenteken = strtoupper(trim((string) $_POST['kenteken']));
    }

    $merk = '';
    if (isset($_POST['merk'])) {
        $merk = trim((string) $_POST['merk']);
    }

    $model = '';
    if (isset($_POST['model'])) {
        $model = trim((string) $_POST['model']);
    }

    $bouwjaar = 0;
    if (isset($_POST['bouwjaar'])) {
        $bouwjaar = (int) $_POST['bouwjaar'];
    }

    $kilometerstand = null;
    if (isset($_POST['kilometerstand'])) {
        $kilometerstand = filter_var($_POST['kilometerstand'], FILTER_VALIDATE_INT);
    }

    $status = '';
    if (isset($_POST['status'])) {
        $status = (string) $_POST['status'];
    }

    return [
        'kenteken' => $kenteken,
        'merk' => $merk,
        'model' => $model,
        'bouwjaar' => $bouwjaar,
        'kilometerstand' => $kilometerstand,
        'status' => $status,
    ];
}

// De voertuiggegevens controleren op fouten.
function validate_vehicle($vehicle)
{
    $errors = [];

    if ($vehicle['kenteken'] === '' || mb_strlen($vehicle['kenteken']) > 20) {
        $errors[] = 'Kenteken is verplicht (maximaal 20 tekens).';
    }

    if ($vehicle['merk'] === '' || $vehicle['model'] === '') {
        $errors[] = 'Merk en model zijn verplicht.';
    }

    $currentYear = (int) date('Y');
    if ($vehicle['bouwjaar'] < 1950 || $vehicle['bouwjaar'] > $currentYear) {
        $errors[] = 'Bouwjaar moet tussen 1950 en het huidige jaar liggen.';
    }

    if ($vehicle['kilometerstand'] === false || $vehicle['kilometerstand'] < 0) {
        $errors[] = 'Kilometerstand moet nul of hoger zijn.';
    }

    if (!in_array($vehicle['status'], VOERTUIG_STATUSSEN, true)) {
        $errors[] = 'Kies een geldige voertuigstatus.';
    }

    return $errors;
}

// De ingevulde onderhoudsgegevens uit het formulier halen.
function maintenance_input()
{
    $omschrijving = '';
    if (isset($_POST['omschrijving'])) {
        $omschrijving = trim((string) $_POST['omschrijving']);
    }

    $onderhoudsdatum = '';
    if (isset($_POST['onderhoudsdatum'])) {
        $onderhoudsdatum = (string) $_POST['onderhoudsdatum'];
    }

    $kosten = null;
    if (isset($_POST['kosten'])) {
        $kosten = filter_var($_POST['kosten'], FILTER_VALIDATE_FLOAT);
    }

    $status = '';
    if (isset($_POST['status'])) {
        $status = (string) $_POST['status'];
    }

    return [
        'omschrijving' => $omschrijving,
        'onderhoudsdatum' => $onderhoudsdatum,
        'kosten' => $kosten,
        'status' => $status,
    ];
}

// De onderhoudsgegevens controleren op fouten.
function validate_maintenance($item)
{
    $errors = [];

    if ($item['omschrijving'] === '' || mb_strlen($item['omschrijving']) > 255) {
        $errors[] = 'Omschrijving is verplicht (maximaal 255 tekens).';
    }

    if (!valid_date($item['onderhoudsdatum'])) {
        $errors[] = 'Vul een geldige onderhoudsdatum in.';
    }

    if ($item['kosten'] === false || $item['kosten'] < 0) {
        $errors[] = 'Kosten mogen niet negatief zijn.';
    }

    if (!in_array($item['status'], ONDERHOUD_STATUSSEN, true)) {
        $errors[] = 'Kies een geldige onderhoudsstatus.';
    }

    return $errors;
}

// Een regel wegschrijven in de auditlog.
function audit($pdo, $action, $entity, $entityId, $details)
{
    $statement = $pdo->prepare(
        'INSERT INTO audit_log (user_id, actie, entiteit, entiteit_id, details)
         VALUES (?, ?, ?, ?, ?)'
    );
    $statement->execute([user_id(), $action, $entity, $entityId, $details]);
}

// De bovenkant van elke pagina (head, header, eventueel een melding).
function page_start($title, $base = '../')
{
    $message = null;
    if (isset($_SESSION['flash'])) {
        $message = $_SESSION['flash'];
    }
    unset($_SESSION['flash']);
    ?>
    <!doctype html>
    <html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | Voertuigenbeheer</title>
        <link rel="stylesheet" href="<?= e($base) ?>styles/styles.css">
    </head>
    <body>
        <header class="site-header">
            <div class="nav-wrap">
                <a class="brand" href="<?= e($base) ?>index.php">
                    <span>Fleet</span> Dashboard
                </a>
                <nav class="account-nav">
                    <a href="<?= e($base) ?>index.php">Dashboard</a>
                    <a href="<?= e($base) ?>voertuigen/index.php">Voertuigen</a>
                    <a href="<?= e($base) ?>audit/index.php">Auditlog</a>
                    <span><?= e($_SESSION['username'] ?? '') ?></span>
                    <a href="<?= e($base) ?>Auth/logout.php">Uitloggen</a>
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
            <div class="container">Cheat sheet: PHP · PDO · MySQL · <?= date('Y') ?></div>
        </footer>
    </body>
    </html>
    <?php
}
