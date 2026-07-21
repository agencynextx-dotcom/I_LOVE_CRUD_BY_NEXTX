<?php
require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

require_login();

$vehicle = [
    'kenteken' => '',
    'merk' => '',
    'model' => '',
    'bouwjaar' => date('Y'),
    'kilometerstand' => 0,
    'status' => 'Actief',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $vehicle = vehicle_input();
    $errors = validate_vehicle($vehicle);

    if ($errors === []) {
        try {
            $statement = $pdo->prepare(
                'INSERT INTO voertuigen
                    (user_id, kenteken, merk, model, bouwjaar, kilometerstand, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $statement->execute([
                user_id(),
                $vehicle['kenteken'],
                $vehicle['merk'],
                $vehicle['model'],
                $vehicle['bouwjaar'],
                $vehicle['kilometerstand'],
                $vehicle['status'],
            ]);

            $id = (int) $pdo->lastInsertId();
            audit($pdo, 'aangemaakt', 'voertuig', $id, $vehicle['kenteken']);
            flash('success', 'Voertuig toegevoegd.');
            redirect('show.php?id=' . $id);
        } catch (PDOException $exception) {
            $errors[] = $exception->getCode() === '23000'
                ? 'Dit kenteken bestaat al.'
                : 'Opslaan mislukt.';
        }
    }
}

page_start('Voertuig toevoegen');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Create</p>
            <h1>Voertuig toevoegen</h1>
        </div>
    </div>

    <section class="panel">
        <?php require __DIR__ . '/_form.php'; ?>
    </section>
</main>
<?php page_end(); ?>
