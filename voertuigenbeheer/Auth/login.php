<?php

require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

if (user_id() !== 0) {
    redirect('../index.php');
}

$error = '';
$message = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');

    if (!$email || $password === '') {
        $error = 'Vul een geldig e-mailadres en wachtwoord in.';
    } else {
        $statement = $pdo->prepare(
            'SELECT id, username, password_hash FROM users WHERE email = ?'
        );
        $statement->execute([$email]);
        $user = $statement->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            redirect('../index.php');
        }

        $error = 'E-mailadres of wachtwoord is onjuist.';
    }
}
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inloggen | Voertuigenbeheer</title>
    <link rel="stylesheet" href="../styles/styles.css">
</head>
<body>
    <main class="container auth-page">
        <section class="panel auth-panel">
            <p class="eyebrow">Voertuigenbeheer</p>
            <h1>Inloggen</h1>

            <?php if ($message): ?>
                <div class="alert alert-<?= e($message['type']) ?>">
                    <?= e($message['message']) ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <?= csrf_field() ?>

                <div class="form-field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= old('email') ?>" required>
                </div>

                <div class="form-field">
                    <label for="password">Wachtwoord</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button class="button" type="submit">Inloggen</button>
                <a class="button button-secondary" href="register.php">Registreren</a>
            </form>
        </section>
    </main>
</body>
</html>
