<?php

declare(strict_types=1);

require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

require_login('../Auth/login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($appointmentId) {
        $statement = $pdo->prepare(
            "UPDATE afspraken
             SET status = 'Geannuleerd'
             WHERE id = ? AND user_id = ? AND status = 'Gepland'"
        );
        $statement->execute([$appointmentId, (int) $_SESSION['user_id']]);
        if ($statement->rowCount() === 1) {
            flash('success', 'Je afspraak is geannuleerd.');
        }
    }

    redirect('dagoverzicht.php?datum=' . urlencode((string) ($_POST['datum'] ?? '')));
}

$selectedDate = (string) ($_GET['datum'] ?? date('Y-m-d'));
$date = DateTime::createFromFormat('Y-m-d', $selectedDate);

if (!$date || $date->format('Y-m-d') !== $selectedDate) {
    $selectedDate = date('Y-m-d');
}

$statement = $pdo->prepare(
    'SELECT afspraken.*, diensten.naam AS dienst_naam, diensten.duur_minuten,
            medewerkers.naam AS medewerker_naam
     FROM afspraken
     JOIN diensten ON diensten.id = afspraken.dienst_id
     JOIN medewerkers ON medewerkers.id = afspraken.medewerker_id
     WHERE DATE(afspraak_op) = ? AND afspraken.user_id = ?
     ORDER BY afspraak_op'
);
$statement->execute([$selectedDate, (int) $_SESSION['user_id']]);
$appointments = $statement->fetchAll();

page_start('Mijn dagoverzicht', '', 'Bekijk je eigen afspraken per dag.', '../');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Examenronde 04 &middot; Gebruikersportaal</p>
            <h1>Mijn dagoverzicht</h1>
            <p><?= count($appointments) ?> eigen afspraak/afspraken op deze dag.</p>
        </div>
        <a class="button button-secondary" href="../index.php">← Alle afspraken</a>
    </div>

    <div class="toolbar">
        <form class="search-form" method="get">
            <input type="date" name="datum" value="<?= e($selectedDate) ?>">
            <button class="button" type="submit">Datum tonen</button>
        </form>
    </div>

    <div class="table-wrap">
        <?php if ($appointments === []): ?>
            <div class="empty">Geen afspraken op deze datum.</div>
        <?php else: ?>
            <table>
                <thead><tr><th>Tijd</th><th>Klant</th><th>Dienst</th><th>Medewerker</th><th>Status</th><th>Actie</th></tr></thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td><?= date('H:i', strtotime($appointment['afspraak_op'])) ?></td>
                            <td><?= e($appointment['klantnaam']) ?></td>
                            <td><?= e($appointment['dienst_naam']) ?> (<?= e($appointment['duur_minuten']) ?> min.)</td>
                            <td><?= e($appointment['medewerker_naam']) ?></td>
                            <td><span class="badge"><?= e($appointment['status']) ?></span></td>
                            <td>
                                <?php if ($appointment['status'] === 'Gepland'): ?>
                                    <form method="post">
                                        <input type="hidden" name="id" value="<?= e($appointment['id']) ?>">
                                        <input type="hidden" name="datum" value="<?= e($selectedDate) ?>">
                                        <button class="button button-danger button-small" type="submit">Annuleren</button>
                                    </form>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<?php page_end(); ?>
