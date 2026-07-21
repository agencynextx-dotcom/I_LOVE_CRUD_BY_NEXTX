<?php
require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$item = find_student($pdo, $id);
$opleidingen = all_opleidingen($pdo);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item = student_input();
    $errors = validate_student($pdo, $item);

    if ($errors === []) {
        $statement = $pdo->prepare(
            'UPDATE studenten SET naam = ?, opleiding_id = ?, cijfer = ?, status = ? WHERE id = ?'
        );
        $statement->execute([$item['naam'], $item['opleiding_id'], $item['cijfer'], $item['status'], $id]);

        flash('success', 'Student gewijzigd.');
        redirect('index.php');
    }
}

page_start('Wijzigen');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Edit</p>
            <h1>Student wijzigen</h1>
        </div>
    </div>
    <section class="panel"><?php require __DIR__ . '/_form.php'; ?></section>
</main>
<?php page_end(); ?>
