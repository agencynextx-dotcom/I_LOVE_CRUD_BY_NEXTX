<?php

declare(strict_types=1);

require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    redirect('../index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim((string) ($_POST['username'] ?? ''));
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    if ($username === '' || mb_strlen($username) > 50) {
        $errors[] = 'Gebruikersnaam is verplicht en mag maximaal 50 tekens bevatten.';
    }

    if (!$email) {
        $errors[] = 'Vul een geldig e-mailadres in.';
    }

    if (mb_strlen($password) < 8) {
        $errors[] = 'Het wachtwoord moet minimaal 8 tekens bevatten.';
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
            redirect('login.php?registered=1');
        } catch (PDOException $exception) {
            $errors[] = $exception->getCode() === '23000'
                ? 'Gebruikersnaam of e-mailadres bestaat al.'
                : 'Registreren is mislukt.';
        }
    }
}

page_start('Registreren', '', 'Maak een account aan.', '../');
?>
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

            <div class="form-field">
                <label for="username">Gebruikersnaam</label>
                <input id="username" name="username" maxlength="50"
                       value="<?= old('username') ?>" required>
            </div>

            <div class="form-field">
                <label for="email">E-mailadres</label>
                <input type="email" id="email" name="email"
                       value="<?= old('email') ?>" required>
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

            <button class="button" type="submit">Account aanmaken</button>
            <a class="button button-secondary" href="login.php">Naar inloggen</a>
        </form>
    </section>
</main>
<?php page_end(); ?>
