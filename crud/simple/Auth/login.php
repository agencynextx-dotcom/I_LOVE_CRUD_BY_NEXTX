<?php
require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    redirect('../index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');

    if (!$email || $password === '') {
        $error = 'Vul een geldig e-mailadres en wachtwoord in.';
    } else {
        $statement = $pdo->prepare(
            'SELECT id, username, password_hash FROM accounts WHERE email = ?'
        );
        $statement->execute([$email]);
        $account = $statement->fetch();

        if ($account && password_verify($password, $account['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $account['id'];
            $_SESSION['username'] = $account['username'];
            redirect('../index.php');
        }

        $error = 'E-mailadres of wachtwoord is onjuist.';
    }
}

page_start('Inloggen', '../');
?>
<main class="container auth-page">
    <section class="panel auth-panel">
        <p class="eyebrow">Account</p>
        <h1>Inloggen</h1>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">Registratie gelukt. Je kunt nu inloggen.</div>
        <?php endif; ?>

        <form method="post">
            <div class="form-field">
                <label for="email">E-mailadres</label>
                <input type="email" id="email" name="email" value="<?= old('email') ?>" required>
            </div>

            <div class="form-field">
                <label for="password">Wachtwoord</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-actions">
                <button class="button" type="submit">Inloggen</button>
                <a class="button button-secondary" href="register.php">Registreren</a>
            </div>
        </form>
    </section>
</main>
<?php page_end(); ?>
