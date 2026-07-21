<?php

declare(strict_types=1);

require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

require_login('../Auth/login.php');

$programs = $pdo->query('SELECT id, code, naam FROM opleidingen ORDER BY naam')->fetchAll();
$opleidingId = filter_input(INPUT_GET, 'opleiding_id', FILTER_VALIDATE_INT) ?: 0;
$status = (string) ($_GET['status'] ?? '');
$conditions = [];
$parameters = [];

if ($opleidingId > 0) {
    $conditions[] = 'studenten.opleiding_id = ?';
    $parameters[] = $opleidingId;
}

if (in_array($status, ['Actief', 'Afgestudeerd', 'Gestopt'], true)) {
    $conditions[] = 'status = ?';
    $parameters[] = $status;
}

$sql = 'SELECT studenten.*, opleidingen.code AS opleiding_code,
               opleidingen.naam AS opleiding_naam
        FROM studenten JOIN opleidingen ON opleidingen.id = studenten.opleiding_id';
if ($conditions !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY opleidingen.naam, achternaam';

$statement = $pdo->prepare($sql);
$statement->execute($parameters);
$students = $statement->fetchAll();

$total = count($students);
$active = count(array_filter(
    $students,
    fn (array $student): bool => $student['status'] === 'Actief'
));

page_start('Studentenrapport', '', 'Gefilterd overzicht van studenten.', '../');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Examenronde 01 &middot; Volledig admin</p>
            <h1>Studentenrapport</h1>
            <p><?= $total ?> studenten gevonden, waarvan <?= $active ?> actief.</p>
        </div>
        <a class="button button-secondary" href="../index.php">← Administratie</a>
    </div>

    <div class="toolbar">
        <form class="search-form" method="get">
            <select name="opleiding_id" class="filter-select">
                <option value="">Alle opleidingen</option>
                <?php foreach ($programs as $program): ?>
                    <option value="<?= e($program['id']) ?>"
                            <?= $opleidingId === (int) $program['id'] ? 'selected' : '' ?>>
                        <?= e($program['code'] . ' - ' . $program['naam']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="filter-select">
                <option value="">Alle statussen</option>
                <?php foreach (['Actief', 'Afgestudeerd', 'Gestopt'] as $option): ?>
                    <option <?= $status === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button" type="submit">Filteren</button>
            <a class="button button-secondary" href="rapport.php">Wissen</a>
        </form>
    </div>

    <div class="table-wrap">
        <?php if ($students === []): ?>
            <div class="empty">Geen studenten binnen dit filter.</div>
        <?php else: ?>
            <table>
                <thead><tr><th>Student</th><th>Nummer</th><th>Opleiding</th><th>Jaar</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= e($student['voornaam'] . ' ' . $student['achternaam']) ?></td>
                            <td><?= e($student['studentnummer']) ?></td>
                            <td><?= e($student['opleiding_naam']) ?></td>
                            <td><?= e($student['studiejaar']) ?></td>
                            <td><span class="badge"><?= e($student['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<?php page_end(); ?>
