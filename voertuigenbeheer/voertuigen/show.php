<?php
require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$vehicle = find_own_vehicle($pdo, $id);

$statement = $pdo->prepare(
    'SELECT * FROM onderhoud WHERE voertuig_id = ? ORDER BY onderhoudsdatum DESC'
);
$statement->execute([$id]);
$maintenanceItems = $statement->fetchAll();

$plannedCount = 0;
foreach ($maintenanceItems as $item) {
    if ($item['status'] === 'Gepland') {
        $plannedCount++;
    }
}

page_start('Voertuig bekijken');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Read</p>
            <h1><?= e($vehicle['kenteken']) ?></h1>
            <p>
                <?= e($vehicle['merk'] . ' ' . $vehicle['model']) ?> ·
                <?= e($vehicle['bouwjaar']) ?> ·
                <?= e($vehicle['kilometerstand']) ?> km
            </p>
        </div>
        <div class="heading-actions">
            <a class="button button-secondary" href="index.php">Terug</a>
            <a class="button" href="edit.php?id=<?= $id ?>">Wijzigen</a>
        </div>
    </div>

    <section class="panel">
        <div class="page-heading">
            <h2>Onderhoud</h2>
            <a class="button" href="../onderhoud/create.php?voertuig_id=<?= $id ?>">
                Onderhoud toevoegen
            </a>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Omschrijving</th>
                        <th>Kosten</th>
                        <th>Status</th>
                        <th>Acties</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maintenanceItems as $item): ?>
                        <tr>
                            <td><?= e($item['onderhoudsdatum']) ?></td>
                            <td><?= e($item['omschrijving']) ?></td>
                            <td>SRD <?= number_format((float) $item['kosten'], 2, ',', '.') ?></td>
                            <td><?= e($item['status']) ?></td>
                            <td class="actions">
                                <a class="button button-small button-secondary"
                                   href="../onderhoud/edit.php?id=<?= $item['id'] ?>">Wijzigen</a>

                                <form method="post" action="../onderhoud/delete.php"
                                      onsubmit="return confirm('Onderhoud verwijderen?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <button class="button button-small button-danger">Verwijderen</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if ($maintenanceItems === []): ?>
                        <tr><td colspan="5" class="empty">Nog geen onderhoud.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="danger-zone">
        <h2>Voertuig verwijderen</h2>

        <?php if ($plannedCount > 0): ?>
            <p class="alert alert-warning">
                Waarschuwing: dit voertuig heeft <?= $plannedCount ?> geplande onderhoudsbeurt(en).
            </p>
        <?php endif; ?>

        <form method="post" action="delete.php"
              onsubmit="return confirm('Voertuig en alle onderhoud definitief verwijderen?')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">

            <?php if ($plannedCount > 0): ?>
                <label>
                    <input type="checkbox" name="confirm_planned" value="yes" required>
                    Ik begrijp dat gepland onderhoud ook wordt verwijderd.
                </label>
            <?php endif; ?>

            <button class="button button-danger">Voertuig verwijderen</button>
        </form>
    </section>
</main>
<?php page_end(); ?>
