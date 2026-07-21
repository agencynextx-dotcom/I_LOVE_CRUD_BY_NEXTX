<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('crudCsrfTrickySession');
    session_start();
}

// Tekst veilig maken voor in HTML (voorkomt XSS).
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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

// Eén token per sessie, net als in de simpele variant.
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// De GOEDE manier: hash_equals() vergelijkt in constante tijd, en het token
// mag alleen uit POST komen (nooit uit de querystring of een header die de
// browser automatisch meestuurt).
function verify_csrf()
{
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');

    if ($sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        http_response_code(403);
        exit('Ongeldig of ontbrekend CSRF-token. Ga terug en probeer opnieuw.');
    }
}

// Saldo ophalen voor de weergave.
function wallet_balance($pdo, $id)
{
    $statement = $pdo->prepare('SELECT balance FROM wallets WHERE id = ?');
    $statement->execute([$id]);

    return (float) $statement->fetchColumn();
}

// De bovenkant van elke pagina.
function page_start($title)
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    ?>
    <!doctype html>
    <html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | CSRF (lastig)</title>
        <link rel="stylesheet" href="styles/styles.css">
    </head>
    <body>
        <header class="site-header">
            <div class="nav-wrap">
                <a class="brand" href="index.php"><span>CSRF</span> Portemonnee</a>
                <nav class="account-nav">
                    <a href="index.php">Overzicht</a>
                    <a href="attack.html">Aanvalspagina</a>
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
            <div class="container">Cheat sheet: lastige CSRF-valkuilen &middot; <?= date('Y') ?></div>
        </footer>
    </body>
    </html>
    <?php
}
