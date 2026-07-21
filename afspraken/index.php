<?php

declare(strict_types=1);

require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

require_login();

$services = $pdo->query('SELECT id, naam, duur_minuten, prijs FROM diensten ORDER BY naam')->fetchAll();
$employees = $pdo->query('SELECT id, naam, specialisatie FROM medewerkers WHERE actief = 1 ORDER BY naam')->fetchAll();
$action = $_GET['action'] ?? 'list';
$action = in_array($action, ['list', 'create', 'edit'], true) ? $action : 'list';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$errors = [];
$appointment = [
    'dienst_id' => '',
    'medewerker_id' => '',
    'klantnaam' => '',
    'email' => '',
    'afspraak_op' => '',
    'status' => 'Gepland',
    'notities' => '',
];

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    $appointmentId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($appointmentId) {
        $statement = $pdo->prepare('DELETE FROM afspraken WHERE id = ? AND user_id = ?');
        $statement->execute([$appointmentId, (int) $_SESSION['user_id']]);
        if ($statement->rowCount() === 1) {
            flash('success', 'Je afspraak is verwijderd.');
        }
    }

    redirect();
}

// CREATE EN UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $appointment = [
        'dienst_id' => (int) ($_POST['dienst_id'] ?? 0),
        'medewerker_id' => (int) ($_POST['medewerker_id'] ?? 0),
        'klantnaam' => trim((string) ($_POST['klantnaam'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'afspraak_op' => trim((string) ($_POST['afspraak_op'] ?? '')),
        'status' => (string) ($_POST['status'] ?? ''),
        'notities' => trim((string) ($_POST['notities'] ?? '')),
    ];

    if ($appointment['klantnaam'] === '') {
        $errors[] = 'Klantnaam is verplicht.';
    }

    $serviceIds = array_map('intval', array_column($services, 'id'));
    if (!in_array($appointment['dienst_id'], $serviceIds, true)) {
        $errors[] = 'Kies een geldige dienst.';
    }

    $employeeIds = array_map('intval', array_column($employees, 'id'));
    if (!in_array($appointment['medewerker_id'], $employeeIds, true)) {
        $errors[] = 'Kies een actieve medewerker.';
    }

    if (!filter_var($appointment['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Vul een geldig e-mailadres in.';
    }

    $appointmentDate = DateTime::createFromFormat(
        'Y-m-d\TH:i',
        $appointment['afspraak_op']
    );

    if (!$appointmentDate || $appointmentDate->format('Y-m-d\TH:i') !== $appointment['afspraak_op']) {
        $errors[] = 'Kies een geldige datum en tijd.';
    }

    $validStatuses = ['Gepland', 'Geannuleerd'];
    if (!in_array($appointment['status'], $validStatuses, true)) {
        $errors[] = 'Kies een geldige status.';
    }

    if ($errors === [] && $appointment['status'] === 'Gepland') {
        $statement = $pdo->prepare(
            "SELECT COUNT(*) FROM afspraken
             WHERE medewerker_id = ? AND afspraak_op = ? AND status = 'Gepland' AND id <> ?"
        );
        $statement->execute([
            $appointment['medewerker_id'],
            $appointmentDate->format('Y-m-d H:i:s'),
            $action === 'edit' ? $id : 0,
        ]);
        if ((int) $statement->fetchColumn() > 0) {
            $errors[] = 'Deze medewerker heeft op dit tijdstip al een geplande afspraak.';
        }
    }

    if ($errors === []) {
        $values = [
            $appointment['dienst_id'],
            $appointment['medewerker_id'],
            $appointment['klantnaam'],
            $appointment['email'],
            $appointmentDate->format('Y-m-d H:i:s'),
            $appointment['status'],
            $appointment['notities'] !== '' ? $appointment['notities'] : null,
        ];

        if ($action === 'edit' && $id > 0) {
            $statement = $pdo->prepare(
                'UPDATE afspraken
                 SET dienst_id = ?, medewerker_id = ?, klantnaam = ?, email = ?, afspraak_op = ?,
                     status = ?, notities = ?
                 WHERE id = ? AND user_id = ?'
            );
            $statement->execute([...$values, $id, (int) $_SESSION['user_id']]);
            flash('success', 'Je afspraak is bijgewerkt.');
        } else {
            $statement = $pdo->prepare(
                'INSERT INTO afspraken
                    (user_id, dienst_id, medewerker_id, klantnaam, email, afspraak_op, status, notities)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $statement->execute([(int) $_SESSION['user_id'], ...$values]);
            flash('success', 'Je afspraak is ingepland.');
        }

        redirect();
    }
}

// READ ONE
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $statement = $pdo->prepare('SELECT * FROM afspraken WHERE id = ? AND user_id = ?');
    $statement->execute([$id, (int) $_SESSION['user_id']]);
    $appointment = $statement->fetch();

    if (!$appointment) {
        flash('error', 'Afspraak niet gevonden of niet van jou.');
        redirect();
    }

    // MySQL DATETIME omzetten naar de notatie van input[type=datetime-local].
    $appointment['afspraak_op'] = date(
        'Y-m-d\TH:i',
        strtotime($appointment['afspraak_op'])
    );
}

page_start(
    'Mijn afspraken',
    'afspraken',
    'Plan en beheer je eigen afspraken.'
);
?>

<main class="container">
    <?php if ($action === 'create' || $action === 'edit'): ?>
        <div class="page-heading">
            <div>
                <p class="eyebrow">Examenronde 04 &middot; Gebruikersportaal</p>
                <h1><?= $action === 'edit' ? 'Afspraak wijzigen' : 'Afspraak plannen' ?></h1>
                <p>Combineert tekst, datum en tijd, status en notities.</p>
            </div>
            <a class="button button-secondary" href="index.php">← Terug</a>
        </div>

        <section class="panel">
            <?php if ($errors !== []): ?>
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="post">

                <div class="form-grid">
                    <div class="form-field">
                        <label for="klantnaam">Klantnaam *</label>
                        <input id="klantnaam" name="klantnaam" maxlength="150"
                               value="<?= old('klantnaam', $appointment['klantnaam']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="email">E-mailadres *</label>
                        <input type="email" id="email" name="email" maxlength="190"
                               value="<?= old('email', $appointment['email']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="dienst_id">Dienst *</label>
                        <select id="dienst_id" name="dienst_id" required>
                            <option value="">Kies een dienst</option>
                            <?php $selectedService = (string) ($_POST['dienst_id'] ?? $appointment['dienst_id']); ?>
                            <?php foreach ($services as $service): ?>
                                <option value="<?= e($service['id']) ?>"
                                        <?= $selectedService === (string) $service['id'] ? 'selected' : '' ?>>
                                    <?= e($service['naam']) ?> (<?= e($service['duur_minuten']) ?> min.)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="medewerker_id">Medewerker *</label>
                        <select id="medewerker_id" name="medewerker_id" required>
                            <option value="">Kies een medewerker</option>
                            <?php $selectedEmployee = (string) ($_POST['medewerker_id'] ?? $appointment['medewerker_id']); ?>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?= e($employee['id']) ?>"
                                        <?= $selectedEmployee === (string) $employee['id'] ? 'selected' : '' ?>>
                                    <?= e($employee['naam'] . ' - ' . $employee['specialisatie']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="afspraak_op">Datum en tijd *</label>
                        <input type="datetime-local" id="afspraak_op" name="afspraak_op"
                               value="<?= old('afspraak_op', $appointment['afspraak_op']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <?php foreach (['Gepland', 'Geannuleerd'] as $status): ?>
                                <option <?= (($_POST['status'] ?? $appointment['status']) === $status) ? 'selected' : '' ?>>
                                    <?= e($status) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field full">
                        <label for="notities">Notities</label>
                        <textarea id="notities" name="notities" maxlength="2000"
                                  placeholder="Eventuele bijzonderheden"><?= old('notities', $appointment['notities']) ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="button" type="submit">Opslaan</button>
                    <a class="button button-secondary" href="index.php">Annuleren</a>
                </div>
            </form>
        </section>
    <?php else: ?>
        <?php
        // READ ALL MET ZOEKEN EN STATUSFILTER
        $search = trim((string) ($_GET['q'] ?? ''));
        $statusFilter = (string) ($_GET['status'] ?? '');
        $conditions = ['afspraken.user_id = ?'];
        $parameters = [(int) $_SESSION['user_id']];

        if ($search !== '') {
            $conditions[] = '(afspraken.klantnaam LIKE ? OR afspraken.email LIKE ? OR diensten.naam LIKE ? OR medewerkers.naam LIKE ?)';
            $like = '%' . $search . '%';
            array_push($parameters, $like, $like, $like, $like);
        }

        if (in_array($statusFilter, ['Gepland', 'Voltooid', 'Geannuleerd'], true)) {
            $conditions[] = 'status = ?';
            $parameters[] = $statusFilter;
        }

        $sql = 'SELECT afspraken.*, diensten.naam AS dienst_naam, diensten.duur_minuten,
                       medewerkers.naam AS medewerker_naam
                FROM afspraken
                JOIN diensten ON diensten.id = afspraken.dienst_id
                JOIN medewerkers ON medewerkers.id = afspraken.medewerker_id';
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY afspraak_op DESC';

        $statement = $pdo->prepare($sql);
        $statement->execute($parameters);
        $appointments = $statement->fetchAll();
        ?>

        <div class="page-heading">
            <div>
                <p class="eyebrow">Examenronde 04 &middot; Gebruikersportaal</p>
                <h1>Mijn afspraken</h1>
                <p>Plan een afspraak en beheer alleen je eigen boekingen.</p>
            </div>
            <div class="heading-actions">
                <a class="button button-secondary" href="public/dagoverzicht.php">Mijn dagoverzicht</a>
                <a class="button" href="?action=create">+ Nieuwe afspraak</a>
            </div>
        </div>

        <div class="toolbar">
            <form class="search-form" method="get">
                <input type="search" name="q" value="<?= e($search) ?>"
                       placeholder="Zoek klant, dienst of medewerker">
                <select name="status" class="filter-select">
                    <option value="">Alle statussen</option>
                    <?php foreach (['Gepland', 'Voltooid', 'Geannuleerd'] as $status): ?>
                        <option <?= $statusFilter === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button button-secondary" type="submit">Filteren</button>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                    <a class="button button-secondary" href="index.php">Wissen</a>
                <?php endif; ?>
            </form>
            <span class="count"><?= count($appointments) ?> afspraak/afspraken</span>
        </div>

        <div class="table-wrap">
            <?php if ($appointments === []): ?>
                <div class="empty">Geen afspraken gevonden.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Datum en tijd</th><th>Klant</th><th>Dienst</th><th>Medewerker</th>
                            <th>Status</th><th>Notities</th><th>Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $row): ?>
                            <?php
                            $badgeClass = match ($row['status']) {
                                'Gepland' => 'badge-warning',
                                'Voltooid' => 'badge-success',
                                default => 'badge-danger',
                            };
                            ?>
                            <tr>
                                <td>
                                    <strong><?= date('d-m-Y', strtotime($row['afspraak_op'])) ?></strong><br>
                                    <span class="count">
                                        <?= date('H:i', strtotime($row['afspraak_op'])) ?> uur
                                    </span>
                                </td>
                                <td><?= e($row['klantnaam']) ?><br><span class="count"><?= e($row['email']) ?></span></td>
                                <td><?= e($row['dienst_naam']) ?><br><span class="count"><?= e($row['duur_minuten']) ?> minuten</span></td>
                                <td><?= e($row['medewerker_naam']) ?></td>
                                <td><span class="badge <?= $badgeClass ?>"><?= e($row['status']) ?></span></td>
                                <td><?= e($row['notities'] ? mb_strimwidth($row['notities'], 0, 45, '…') : '—') ?></td>
                                <td>
                                    <div class="actions">
                                        <a class="button button-secondary button-small"
                                           href="?action=edit&amp;id=<?= e($row['id']) ?>">
                                            Wijzigen
                                        </a>
                                        <form method="post" data-confirm="Deze afspraak definitief verwijderen?">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                            <button class="button button-danger button-small" type="submit">Verwijderen</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php page_end(); ?>
