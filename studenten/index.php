<?php

declare(strict_types=1);

require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

require_login();

$programs = $pdo->query('SELECT id, code, naam FROM opleidingen ORDER BY naam')->fetchAll();
$action = $_GET['action'] ?? 'list';
$allowedActions = ['list', 'create', 'edit', 'audit'];

if (!in_array($action, $allowedActions, true)) {
    $action = 'list';
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$errors = [];
$student = [
    'opleiding_id' => '',
    'studentnummer' => '',
    'voornaam' => '',
    'achternaam' => '',
    'email' => '',
    'studiejaar' => 1,
    'status' => 'Actief',
];

// DELETE: verwijderen gebeurt bewust met POST en niet via een gewone link.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    verify_csrf();

    $studentId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($studentId) {
        $lookup = $pdo->prepare('SELECT studentnummer FROM studenten WHERE id = ?');
        $lookup->execute([$studentId]);
        $studentNumber = $lookup->fetchColumn();
        $statement = $pdo->prepare('DELETE FROM studenten WHERE id = ?');
        $statement->execute([$studentId]);
        if ($statement->rowCount() === 1) {
            audit($pdo, 'verwijderd', 'student', $studentId, 'Student ' . $studentNumber . ' verwijderd');
            flash('success', 'Student is verwijderd.');
        }
    }

    redirect();
}

// STATUS WIJZIGEN: een kleine, afzonderlijke actie vanuit de tabel.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'change_status') {
    verify_csrf();
    $studentId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $newStatus = (string) ($_POST['status'] ?? '');
    $validStatuses = ['Actief', 'Afgestudeerd', 'Gestopt'];

    if ($studentId && in_array($newStatus, $validStatuses, true)) {
        $statement = $pdo->prepare('UPDATE studenten SET status = ? WHERE id = ?');
        $statement->execute([$newStatus, $studentId]);
        audit($pdo, 'status gewijzigd', 'student', $studentId, 'Nieuwe status: ' . $newStatus);
        flash('success', 'Status is gewijzigd.');
    } else {
        flash('error', 'Ongeldige statuswijziging.');
    }

    redirect();
}

// CREATE en UPDATE gebruiken hetzelfde formulier en dezelfde validatie.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $student = [
        'opleiding_id' => (int) ($_POST['opleiding_id'] ?? 0),
        'studentnummer' => strtoupper(trim((string) ($_POST['studentnummer'] ?? ''))),
        'voornaam' => trim((string) ($_POST['voornaam'] ?? '')),
        'achternaam' => trim((string) ($_POST['achternaam'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'studiejaar' => (int) ($_POST['studiejaar'] ?? 0),
        'status' => (string) ($_POST['status'] ?? ''),
    ];

    if ($student['studentnummer'] === '') {
        $errors[] = 'Studentnummer is verplicht.';
    }

    if ($student['voornaam'] === '' || $student['achternaam'] === '') {
        $errors[] = 'Voornaam en achternaam zijn verplicht.';
    }

    if (!filter_var($student['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Vul een geldig e-mailadres in.';
    }

    $programIds = array_map('intval', array_column($programs, 'id'));
    if (!in_array($student['opleiding_id'], $programIds, true)) {
        $errors[] = 'Kies een geldige opleiding.';
    }

    if ($student['studiejaar'] < 1 || $student['studiejaar'] > 6) {
        $errors[] = 'Studiejaar moet tussen 1 en 6 liggen.';
    }

    $validStatuses = ['Actief', 'Afgestudeerd', 'Gestopt'];
    if (!in_array($student['status'], $validStatuses, true)) {
        $errors[] = 'Kies een geldige status.';
    }

    if ($errors === []) {
        try {
            $values = [
                $student['opleiding_id'],
                $student['studentnummer'],
                $student['voornaam'],
                $student['achternaam'],
                $student['email'],
                $student['studiejaar'],
                $student['status'],
            ];

            if ($action === 'edit' && $id > 0) {
                $statement = $pdo->prepare(
                    'UPDATE studenten
                     SET opleiding_id = ?, studentnummer = ?, voornaam = ?, achternaam = ?,
                         email = ?, studiejaar = ?, status = ?
                     WHERE id = ?'
                );
                $statement->execute([...$values, $id]);
                audit($pdo, 'bijgewerkt', 'student', $id, 'Studentgegevens bijgewerkt');
                flash('success', 'Studentgegevens zijn bijgewerkt.');
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO studenten
                        (opleiding_id, studentnummer, voornaam, achternaam, email, studiejaar, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $statement->execute($values);
                $newId = (int) $pdo->lastInsertId();
                audit($pdo, 'aangemaakt', 'student', $newId, 'Student ' . $student['studentnummer'] . ' toegevoegd');
                flash('success', 'Student is toegevoegd.');
            }

            redirect();
        } catch (PDOException $exception) {
            $errors[] = $exception->getCode() === '23000'
                ? 'Studentnummer of e-mailadres bestaat al.'
                : 'Opslaan is mislukt. Probeer het opnieuw.';
        }
    }
}

// READ ONE: haal bij wijzigen eerst het bestaande record op.
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $statement = $pdo->prepare('SELECT * FROM studenten WHERE id = ?');
    $statement->execute([$id]);
    $student = $statement->fetch();

    if (!$student) {
        flash('error', 'Student niet gevonden.');
        redirect();
    }
}

// Veilige sortering gebruikt een whitelist; waarden blijven queryparameters.
$students = [];
$totalStudents = 0;
$totalPages = 1;
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage = 10;
$search = trim((string) ($_GET['q'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'naam');
$direction = strtolower((string) ($_GET['direction'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
$sortableColumns = [
    'naam' => 'studenten.achternaam',
    'nummer' => 'studenten.studentnummer',
    'opleiding' => 'opleidingen.naam',
    'jaar' => 'studenten.studiejaar',
    'status' => 'studenten.status',
];
$sortColumn = $sortableColumns[$sort] ?? $sortableColumns['naam'];
$sort = array_key_exists($sort, $sortableColumns) ? $sort : 'naam';
$parameters = [];
$where = '';

if ($search !== '') {
    $where = ' WHERE studentnummer LIKE ? OR voornaam LIKE ? OR achternaam LIKE ? OR opleidingen.naam LIKE ?';
    $like = '%' . $search . '%';
    $parameters = [$like, $like, $like, $like];
}

if ($action === 'list') {
    $countStatement = $pdo->prepare(
        'SELECT COUNT(*) FROM studenten JOIN opleidingen ON opleidingen.id = studenten.opleiding_id' . $where
    );
    $countStatement->execute($parameters);
    $totalStudents = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($totalStudents / $perPage));
    $page = min($page, $totalPages);
    $listSql = 'SELECT studenten.*, opleidingen.naam AS opleiding_naam
                FROM studenten JOIN opleidingen ON opleidingen.id = studenten.opleiding_id' .
               $where . ' ORDER BY ' . $sortColumn . ' ' . $direction;

    if (($_GET['export'] ?? '') === 'csv') {
        $statement = $pdo->prepare($listSql);
        $statement->execute($parameters);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="studenten-' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Studentnummer', 'Voornaam', 'Achternaam', 'E-mail', 'Opleiding', 'Studiejaar', 'Status'], ';');
        foreach ($statement as $row) {
            fputcsv($output, [$row['studentnummer'], $row['voornaam'], $row['achternaam'], $row['email'], $row['opleiding_naam'], $row['studiejaar'], $row['status']], ';');
        }
        fclose($output);
        exit;
    }

    $statement = $pdo->prepare($listSql . ' LIMIT ? OFFSET ?');
    $position = 1;
    foreach ($parameters as $parameter) {
        $statement->bindValue($position++, $parameter, PDO::PARAM_STR);
    }
    $statement->bindValue($position++, $perPage, PDO::PARAM_INT);
    $statement->bindValue($position, ($page - 1) * $perPage, PDO::PARAM_INT);
    $statement->execute();
    $students = $statement->fetchAll();
}

$dashboard = $pdo->query(
    "SELECT COUNT(*) AS totaal, SUM(status = 'Actief') AS actief,
            SUM(status = 'Afgestudeerd') AS afgestudeerd, SUM(status = 'Gestopt') AS gestopt
     FROM studenten"
)->fetch();

$auditEntries = $action === 'audit'
    ? $pdo->query(
        'SELECT audit_log.*, users.username FROM audit_log
         LEFT JOIN users ON users.id = audit_log.user_id
         ORDER BY audit_log.created_at DESC LIMIT 100'
    )->fetchAll()
    : [];

page_start(
    'Studentenvolgsysteem',
    'studenten',
    'Beheer studenten, opleidingen en studievoortgang.'
);
?>

<main class="container">
    <?php if ($action === 'audit'): ?>
        <div class="page-heading">
            <div><p class="eyebrow">Controle</p><h1>Auditlog</h1><p>De 100 meest recente wijzigingen.</p></div>
            <a class="button button-secondary" href="index.php">Terug</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Moment</th><th>Gebruiker</th><th>Actie</th><th>Record</th><th>Details</th></tr></thead>
                <tbody>
                <?php foreach ($auditEntries as $entry): ?>
                    <tr>
                        <td><?= e($entry['created_at']) ?></td>
                        <td><?= e($entry['username'] ?? 'Onbekend') ?></td>
                        <td><?= e($entry['actie']) ?></td>
                        <td><?= e($entry['entiteit'] . ' #' . ($entry['entiteit_id'] ?? '-')) ?></td>
                        <td><?= e($entry['details']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <div class="page-heading">
            <div>
                <p class="eyebrow">Studenten</p>
                <h1><?= $action === 'edit' ? 'Student wijzigen' : 'Student toevoegen' ?></h1>
                <p>Velden met een * zijn verplicht.</p>
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
                <?= csrf_field() ?>

                <div class="form-grid">
                    <div class="form-field">
                        <label for="studentnummer">Studentnummer *</label>
                        <input id="studentnummer" name="studentnummer" maxlength="20"
                               value="<?= old('studentnummer', $student['studentnummer']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="email">E-mailadres *</label>
                        <input type="email" id="email" name="email" maxlength="190"
                               value="<?= old('email', $student['email']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="voornaam">Voornaam *</label>
                        <input id="voornaam" name="voornaam" maxlength="80"
                               value="<?= old('voornaam', $student['voornaam']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="achternaam">Achternaam *</label>
                        <input id="achternaam" name="achternaam" maxlength="100"
                               value="<?= old('achternaam', $student['achternaam']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="opleiding_id">Opleiding *</label>
                        <select id="opleiding_id" name="opleiding_id" required>
                            <option value="">Kies een opleiding</option>
                            <?php $selectedProgram = (string) ($_POST['opleiding_id'] ?? $student['opleiding_id']); ?>
                            <?php foreach ($programs as $program): ?>
                                <option value="<?= e($program['id']) ?>"
                                        <?= $selectedProgram === (string) $program['id'] ? 'selected' : '' ?>>
                                    <?= e($program['code'] . ' - ' . $program['naam']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="studiejaar">Studiejaar *</label>
                        <input type="number" id="studiejaar" name="studiejaar" min="1" max="6"
                               value="<?= old('studiejaar', $student['studiejaar']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <?php foreach (['Actief', 'Afgestudeerd', 'Gestopt'] as $status): ?>
                                <option <?= (($_POST['status'] ?? $student['status']) === $status) ? 'selected' : '' ?>>
                                    <?= e($status) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
        // READ ALL: zoek op meerdere kolommen met één zoekterm.
        ?>

        <div class="page-heading">
            <div>
                <p class="eyebrow">Examenronde 01 &middot; Volledig admin</p>
                <h1>Studentenvolgsysteem</h1>
                <p>Beheer inschrijvingen en studievoortgang.</p>
            </div>
            <div class="heading-actions">
                <a class="button button-secondary" href="?action=audit">Auditlog</a>
                <a class="button button-secondary" href="?<?= e(http_build_query(['q' => $search, 'sort' => $sort, 'direction' => strtolower($direction), 'export' => 'csv'])) ?>">CSV exporteren</a>
                <a class="button button-secondary" href="public/rapport.php">Rapport bekijken</a>
                <a class="button" href="?action=create">+ Student toevoegen</a>
            </div>
        </div>

        <section class="dashboard" aria-label="Dashboard met totalen">
            <div class="stat-card"><span>Totaal</span><strong><?= e($dashboard['totaal'] ?? 0) ?></strong></div>
            <div class="stat-card"><span>Actief</span><strong><?= e($dashboard['actief'] ?? 0) ?></strong></div>
            <div class="stat-card"><span>Afgestudeerd</span><strong><?= e($dashboard['afgestudeerd'] ?? 0) ?></strong></div>
            <div class="stat-card"><span>Gestopt</span><strong><?= e($dashboard['gestopt'] ?? 0) ?></strong></div>
        </section>

        <div class="toolbar">
            <form class="search-form" method="get">
                <input type="search" name="q" value="<?= e($search) ?>"
                       placeholder="Zoek op naam, nummer of opleiding">
                <button class="button button-secondary" type="submit">Zoeken</button>
                <?php if ($search !== ''): ?>
                    <a class="button button-secondary" href="index.php">Wissen</a>
                <?php endif; ?>
            </form>
            <span class="count"><?= e($totalStudents) ?> resultaat/resultaten</span>
        </div>

        <div class="table-wrap">
            <?php if ($students === []): ?>
                <div class="empty">Geen studenten gevonden.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <?php
                            $sortLink = static function (string $column) use ($search, $sort, $direction): string {
                                $next = $sort === $column && $direction === 'ASC' ? 'desc' : 'asc';
                                return '?' . http_build_query(['q' => $search, 'sort' => $column, 'direction' => $next]);
                            };
                            ?>
                            <th><a href="<?= e($sortLink('naam')) ?>">Student</a></th>
                            <th><a href="<?= e($sortLink('nummer')) ?>">Nummer</a></th>
                            <th><a href="<?= e($sortLink('opleiding')) ?>">Opleiding</a></th>
                            <th><a href="<?= e($sortLink('jaar')) ?>">Jaar</a></th>
                            <th><a href="<?= e($sortLink('status')) ?>">Status</a></th>
                            <th>Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $row): ?>
                            <tr>
                                <td>
                                    <strong><?= e($row['voornaam'] . ' ' . $row['achternaam']) ?></strong><br>
                                    <span class="count"><?= e($row['email']) ?></span>
                                </td>
                                <td><?= e($row['studentnummer']) ?></td>
                                <td><?= e($row['opleiding_naam']) ?></td>
                                <td><?= e($row['studiejaar']) ?></td>
                                <td>
                                    <?php
                                    $statusClass = match ($row['status']) {
                                        'Actief' => 'badge-success',
                                        'Gestopt' => 'badge-danger',
                                        default => '',
                                    };
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= e($row['status']) ?></span>
                                    <form class="status-form" method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="form_action" value="change_status">
                                        <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                        <select name="status" aria-label="Status van <?= e($row['studentnummer']) ?>">
                                            <?php foreach (['Actief', 'Afgestudeerd', 'Gestopt'] as $option): ?>
                                                <option <?= $row['status'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="button button-secondary button-small" type="submit">OK</button>
                                    </form>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a class="button button-secondary button-small"
                                           href="?action=edit&amp;id=<?= e($row['id']) ?>">Wijzigen</a>

                                        <form method="post" data-confirm="Deze student definitief verwijderen?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                            <button class="button button-danger button-small" type="submit">
                                                Verwijderen
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php if ($totalPages > 1): ?>
            <nav class="pagination" aria-label="Paginering">
                <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                    <a class="button button-small <?= $pageNumber === $page ? '' : 'button-secondary' ?>"
                       href="?<?= e(http_build_query(['q' => $search, 'sort' => $sort, 'direction' => strtolower($direction), 'page' => $pageNumber])) ?>">
                        <?= $pageNumber ?>
                    </a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php page_end(); ?>
