<?php
require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$student = find_student($pdo, $id);

page_start('Details');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Show</p>
            <h1><?= e($student['naam']) ?></h1>
        </div>
        <a class="button button-secondary" href="index.php">Terug naar overzicht</a>
    </div>

    <section class="panel">
        <dl>
            <div><dt>Opleiding</dt><dd><?= e($student['opleiding_naam']) ?></dd></div>
            <div><dt>Cijfer</dt><dd><?= e($student['cijfer']) ?></dd></div>
            <div><dt>Status</dt><dd><span class="<?= status_badge_class($student['status']) ?>"><?= e($student['status']) ?></span></dd></div>
        </dl>
        <div class="form-actions">
            <a class="button" href="edit.php?id=<?= $student['id'] ?>">Wijzigen</a>
        </div>
    </section>
</main>
<?php page_end(); ?>
