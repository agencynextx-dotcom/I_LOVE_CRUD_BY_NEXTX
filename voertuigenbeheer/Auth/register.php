<?php

require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

if (user_id() !== 0) {
    redirect('../index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    if ($username === '' || mb_strlen($username) > 50) {
        $errors[] = 'Gebruikersnaam is verplicht (maximaal 50 tekens).';
    }

    if (!$email) {
        $errors[] = 'Vul een geldig e-mailadres in.';
    }

    if (mb_strlen($password) < 8) {
        $errors[] = 'Wachtwoord moet minimaal 8 tekens bevatten.';
    }

    if ($password !== $confirmation) {
        $errors[] = 'De wachtwoorden zijn niet gelijk.';
    }

    if ($errors === []) {
        try {
            $statement = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)'
            );
            $statement->execute([
                $username,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
            ]);

            flash('success', 'Account aangemaakt. Log nu in.');
            redirect('login.php');
        } catch (PDOException $exception) {
            $errors[] = $exception->getCode() === '23000'
                ? 'Gebruikersnaam of e-mail bestaat al.'
                : 'Registratie mislukt.';
        }
    }
}
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registreren | Voertuigenbeheer</title>
    <link rel="stylesheet" href="../styles/styles.css">
</head>
<body>
    <main class="container auth-page">
        <section class="panel auth-panel">
            <p class="eyebrow">Account</p>
            <h1>Registreren</h1>

            <?php if ($errors !== []): ?>
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="post">
                <?= csrf_field() ?>

                <div class="form-field">
                    <label for="username">Gebruikersnaam</label>
                    <input id="username" name="username" value="<?= old('username') ?>" required>
                </div>

                <div class="form-field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= old('email') ?>" required>
                </div>

                <div class="form-field">
                    <label for="password">Wachtwoord</label>
                    <input type="password" id="password" name="password" minlength="8" required>
                </div>

                <div class="form-field">
                    <label for="password_confirmation">Herhaal wachtwoord</label>
                    <input type="password" id="password_confirmation"
                           name="password_confirmation" minlength="8" required>
                </div>

                <button class="button" type="submit">Account maken</button>
                <a class="button button-secondary" href="login.php">Terug</a>
            </form>
        </section>
    </main>
</body>
</html>
