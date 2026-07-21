<?php
require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

$content = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $content = trim((string) ($_POST['content'] ?? ''));

    if ($content === '' || mb_strlen($content) > 255) {
        $errors[] = 'Bericht is verplicht (maximaal 255 tekens).';
    }

    if ($errors === []) {
        $statement = $pdo->prepare('INSERT INTO messages (content) VALUES (?)');
        $statement->execute([$content]);

        flash('success', 'Bericht toegevoegd.');
        redirect('index.php');
    }
}

page_start('Toevoegen');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Create</p>
            <h1>Bericht toevoegen</h1>
        </div>
    </div>

    <section class="panel">
        <?php if ($errors !== []): ?>
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>
            <div class="form-field full">
                <label for="content">Bericht *</label>
                <input id="content" name="content" maxlength="255" value="<?= e($content) ?>" required>
            </div>
            <div class="form-actions">
                <button class="button">Opslaan</button>
                <a class="button button-secondary" href="index.php">Annuleren</a>
            </div>
        </form>
    </section>
</main>
<?php page_end(); ?>
