<?php
require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$item = find_own_maintenance($pdo, $id);
$vehicle = find_own_vehicle($pdo, (int) $item['voertuig_id']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $item = array_merge($item, maintenance_input());
    $errors = validate_maintenance($item);

    if ($errors === []) {
        $statement = $pdo->prepare(
            'UPDATE onderhoud
             SET omschrijving = ?, onderhoudsdatum = ?, kosten = ?, status = ?
             WHERE id = ? AND voertuig_id IN (
                 SELECT id FROM voertuigen WHERE user_id = ?
             )'
        );
        $statement->execute([
            $item['omschrijving'],
            $item['onderhoudsdatum'],
            $item['kosten'],
            $item['status'],
            $item['id'],
            user_id(),
        ]);

        audit($pdo, 'bijgewerkt', 'onderhoud', (int) $item['id'], $item['omschrijving']);
        flash('success', 'Onderhoud bijgewerkt.');
        redirect('../voertuigen/show.php?id=' . $vehicle['id']);
    }
}

page_start('Onderhoud wijzigen');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Update</p>
            <h1>Onderhoud wijzigen</h1>
            <p><?= e($vehicle['kenteken']) ?></p>
        </div>
    </div>
    <section class="panel"><?php require __DIR__ . '/_form.php'; ?></section>
</main>
<?php page_end(); ?>
