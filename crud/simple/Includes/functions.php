<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('crudSimpleSession');
    session_start();
}

// Alleen ingelogde gebruikers mogen verder, anders naar de inlogpagina.
function require_login($loginPage = 'Auth/login.php')
{
    if (empty($_SESSION['user_id'])) {
        redirect($loginPage);
    }
}

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

// De ingevulde gebruikersgegevens uit het formulier halen.
function user_input()
{
    return [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'address' => trim((string) ($_POST['address'] ?? '')),
    ];
}

// De gebruikersgegevens controleren op fouten.
function validate_user($user)
{
    $errors = [];

    if ($user['name'] === '' || mb_strlen($user['name']) > 100) {
        $errors[] = 'Naam is verplicht (maximaal 100 tekens).';
    }

    if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Vul een geldig e-mailadres in.';
    }

    if ($user['phone'] === '') {
        $errors[] = 'Telefoonnummer is verplicht.';
    }

    if ($user['address'] === '') {
        $errors[] = 'Adres is verplicht.';
    }

    return $errors;
}

// Een gebruiker ophalen op id, of 404 tonen.
function find_user($pdo, $id)
{
    $statement = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $statement->execute([$id]);
    $user = $statement->fetch();

    if (!$user) {
        http_response_code(404);
        exit('Gebruiker niet gevonden.');
    }

    return $user;
}

// De bovenkant van elke pagina (head, header, eventueel een melding).
function page_start($title, $base = '')
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    ?>
    <!doctype html>
    <html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | Simpele CRUD</title>
        <link rel="stylesheet" href="<?= e($base) ?>styles/styles.css">
    </head>
    <body>
        <header class="site-header">
            <div class="nav-wrap">
                <a class="brand" href="<?= e($base) ?>index.php"><span>User</span> List</a>
                <nav class="account-nav">
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <a href="<?= e($base) ?>index.php">Overzicht</a>
                        <a href="<?= e($base) ?>create.php">Toevoegen</a>
                        <span><?= e($_SESSION['username'] ?? '') ?></span>
                        <a href="<?= e($base) ?>Auth/logout.php">Uitloggen</a>
                    <?php else: ?>
                        <a href="<?= e($base) ?>Auth/login.php">Inloggen</a>
                        <a href="<?= e($base) ?>Auth/register.php">Registreren</a>
                    <?php endif; ?>
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
            <div class="container">Cheat sheet: eenvoudige CRUD zonder relaties &middot; <?= date('Y') ?></div>
        </footer>
    </body>
    </html>
    <?php
}
