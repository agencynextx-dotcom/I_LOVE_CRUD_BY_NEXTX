<?php
require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

$statement = $pdo->query(
    'SELECT studenten.*, opleidingen.naam AS opleiding_naam
     FROM studenten
     JOIN opleidingen ON opleidingen.id = studenten.opleiding_id
     ORDER BY studenten.id'
);
$students = $statement->fetchAll();

page_start('Overzicht');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">CRUD met lookup en status</p>
            <h1>Studenten</h1>
            <p>Iedere student hoort bij één opleiding (foreign key) en heeft een resultaatstatus.</p>
        </div>
        <a class="button" href="create.php">Toevoegen</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Naam</th>
                    <th>Opleiding</th>
                    <th>Cijfer</th>
                    <th>Status</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $index => $student): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><a href="show.php?id=<?= $student['id'] ?>"><?= e($student['naam']) ?></a></td>
                        <td><?= e($student['opleiding_naam']) ?></td>
                        <td><?= e($student['cijfer']) ?></td>
                        <td><span class="<?= status_badge_class($student['status']) ?>"><?= e($student['status']) ?></span></td>
                        <td class="actions">
                            <a class="button button-small button-secondary" href="show.php?id=<?= $student['id'] ?>">Bekijken</a>
                            <a class="button button-small" href="edit.php?id=<?= $student['id'] ?>">Wijzigen</a>
                            <form method="post" action="delete.php" onsubmit="return confirm('Student verwijderen?');">
                                <input type="hidden" name="id" value="<?= $student['id'] ?>">
                                <button class="button button-small button-danger">Verwijderen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if ($students === []): ?>
                    <tr><td colspan="6" class="empty">Geen studenten gevonden.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
<?php page_end(); ?>
