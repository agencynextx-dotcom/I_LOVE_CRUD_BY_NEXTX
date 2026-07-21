<?php
require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

require_login();

$vehicleId = filter_input(INPUT_GET, 'voertuig_id', FILTER_VALIDATE_INT) ?: 0;
$vehicle = find_own_vehicle($pdo, $vehicleId);
$item = [
    'omschrijving' => '',
    'onderhoudsdatum' => date('Y-m-d'),
    'kosten' => '0.00',
    'status' => 'Gepland',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $item = maintenance_input();
    $errors = validate_maintenance($item);

    if ($errors === []) {
        $statement = $pdo->prepare(
            'INSERT INTO onderhoud
                (voertuig_id, omschrijving, onderhoudsdatum, kosten, status)
             VALUES (?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $vehicle['id'],
            $item['omschrijving'],
            $item['onderhoudsdatum'],
            $item['kosten'],
            $item['status'],
        ]);

        audit($pdo, 'aangemaakt', 'onderhoud', (int) $pdo->lastInsertId(), $item['omschrijving']);
        flash('success', 'Onderhoud toegevoegd.');
        redirect('../voertuigen/show.php?id=' . $vehicle['id']);
    }
}

page_start('Onderhoud toevoegen');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Create</p>
            <h1>Onderhoud toevoegen</h1>
            <p><?= e($vehicle['kenteken']) ?></p>
        </div>
    </div>
    <section class="panel"><?php require __DIR__ . '/_form.php'; ?></section>
</main>
<?php page_end(); ?>
