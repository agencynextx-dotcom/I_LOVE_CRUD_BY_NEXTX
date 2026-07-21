<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function old(string $field, mixed $default = ''): string
{
    return e($_POST[$field] ?? $default);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($sessionToken, $submittedToken)) {
        http_response_code(419);
        exit('Ongeldig formulier. Ga terug en probeer opnieuw.');
    }
}

function require_login(string $loginPage = 'Auth/login.php'): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $loginPage);
        exit;
    }
}

function is_admin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

function require_admin(string $redirectTo = 'index.php'): void
{
    require_login();

    if (!is_admin()) {
        http_response_code(403);
        exit('Geen toegang. Deze pagina is alleen voor beheerders.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function redirect(string $location = 'index.php'): never
{
    header('Location: ' . $location);
    exit;
}

function page_start(
    string $title,
    string $section = '',
    string $description = '',
    string $basePath = ''
): void {
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    ?>
    <!DOCTYPE html>
    <html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="<?= e($description) ?>">
        <title><?= e($title) ?></title>
        <link rel="stylesheet" href="<?= e($basePath) ?>styles/styles.css">
    </head>
    <body>
        <header class="site-header">
            <div class="nav-wrap">
                <a class="brand" href="<?= e($basePath) ?>index.php">
                    <span>CRUD</span> <?= e($title) ?>
                </a>
                <nav class="account-nav" aria-label="Account">
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <span><?= e($_SESSION['username'] ?? 'Gebruiker') ?></span>
                        <?php if (is_admin()): ?>
                            <a href="<?= e($basePath) ?>Admin/tickets.php">Admin</a>
                        <?php endif; ?>
                        <a href="<?= e($basePath) ?>Auth/logout.php">Uitloggen</a>
                    <?php else: ?>
                        <a href="<?= e($basePath) ?>Auth/login.php">Inloggen</a>
                        <a href="<?= e($basePath) ?>Auth/register.php">Registreren</a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="container flash-wrap">
                <div class="alert alert-<?= e($message['type']) ?>" role="status">
                    <?= e($message['message']) ?>
                </div>
            </div>
        <?php endif; ?>
    <?php
}

function page_end(): void
{
    ?>
        <footer class="site-footer">
            <div class="container">PHP · PDO · MySQL · <?= date('Y') ?></div>
        </footer>
        <script>
            document.querySelectorAll('[data-confirm]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!confirm(form.dataset.confirm)) {
                        event.preventDefault();
                    }
                });
            });
        </script>
    </body>
    </html>
    <?php
}