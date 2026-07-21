<?php
require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$vehicle = find_own_vehicle($pdo, $id);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $vehicle = array_merge($vehicle, vehicle_input());
    $errors = validate_vehicle($vehicle);

    if ($errors === []) {
        try {
            $statement = $pdo->prepare(
                'UPDATE voertuigen
                 SET kenteken = ?, merk = ?, model = ?, bouwjaar = ?, kilometerstand = ?, status = ?
                 WHERE id = ? AND user_id = ?'
            );
            $statement->execute([
                $vehicle['kenteken'],
                $vehicle['merk'],
                $vehicle['model'],
                $vehicle['bouwjaar'],
                $vehicle['kilometerstand'],
                $vehicle['status'],
                $id,
                user_id(),
            ]);

            audit($pdo, 'bijgewerkt', 'voertuig', $id, $vehicle['kenteken']);
            flash('success', 'Voertuig bijgewerkt.');
            redirect('show.php?id=' . $id);
        } catch (PDOException $exception) {
            $errors[] = $exception->getCode() === '23000'
                ? 'Dit kenteken bestaat al.'
                : 'Opslaan mislukt.';
        }
    }
}

page_start('Voertuig wijzigen');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Update</p>
            <h1>Voertuig wijzigen</h1>
        </div>
    </div>

    <section class="panel">
        <?php require __DIR__ . '/_form.php'; ?>
    </section>
</main>
<?php page_end(); ?>
