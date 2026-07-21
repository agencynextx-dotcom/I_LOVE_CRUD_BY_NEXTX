<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $appName = preg_replace('/[^a-zA-Z0-9]/', '', basename(dirname(__DIR__)));
    session_name(($appName ?: 'app') . 'Session');
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

function require_login(string $loginPage = 'Auth/login.php'): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $loginPage);
        exit;
    }
}

function require_role(string $role, string $loginPage = 'Auth/login.php'): void
{
    require_login($loginPage);

    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Je hebt geen toegang tot deze pagina.');
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
    $homePage = ($_SESSION['role'] ?? '') === 'gebruiker'
        ? 'public/lenen.php'
        : 'index.php';
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
                <a class="brand" href="<?= e($basePath . $homePage) ?>">
                    <span>CRUD</span> <?= e($title) ?>
                </a>
                <nav class="account-nav" aria-label="Account">
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <span>
                            <?= e($_SESSION['username'] ?? 'Gebruiker') ?>
                            (<?= e($_SESSION['role'] ?? 'gebruiker') ?>)
                        </span>
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
