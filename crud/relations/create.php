<?php
require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

$opleidingen = all_opleidingen($pdo);
$item = ['naam' => '', 'opleiding_id' => '', 'cijfer' => '', 'status' => 'In behandeling'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item = student_input();
    $errors = validate_student($pdo, $item);

    if ($errors === []) {
        $statement = $pdo->prepare(
            'INSERT INTO studenten (naam, opleiding_id, cijfer, status) VALUES (?, ?, ?, ?)'
        );
        $statement->execute([$item['naam'], $item['opleiding_id'], $item['cijfer'], $item['status']]);

        flash('success', 'Student toegevoegd.');
        redirect('index.php');
    }
}

page_start('Toevoegen');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Create</p>
            <h1>Student toevoegen</h1>
        </div>
    </div>
    <section class="panel"><?php require __DIR__ . '/_form.php'; ?></section>
</main>
<?php page_end(); ?>
