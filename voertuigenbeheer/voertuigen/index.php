<?php
require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

require_login();

$search = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$sort = (string) ($_GET['sort'] ?? 'kenteken');
$direction = strtolower((string) ($_GET['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage = 10;

// Kolomnamen kunnen geen placeholders zijn. Gebruik daarom een whitelist.
$sortableColumns = [
    'kenteken' => 'kenteken',
    'merk' => 'merk',
    'bouwjaar' => 'bouwjaar',
    'kilometerstand' => 'kilometerstand',
];
$sort = array_key_exists($sort, $sortableColumns) ? $sort : 'kenteken';

$parameters = [user_id()];
$where = ' WHERE user_id = ?';

if ($search !== '') {
    $where .= ' AND (kenteken LIKE ? OR merk LIKE ? OR model LIKE ?)';
    $like = '%' . $search . '%';
    array_push($parameters, $like, $like, $like);
}

if (in_array($status, VOERTUIG_STATUSSEN, true)) {
    $where .= ' AND status = ?';
    $parameters[] = $status;
}

$statement = $pdo->prepare('SELECT COUNT(*) FROM voertuigen' . $where);
$statement->execute($parameters);
$total = (int) $statement->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);

$listSql = 'SELECT * FROM voertuigen' . $where
    . ' ORDER BY ' . $sortableColumns[$sort] . ' ' . $direction;

if (($_GET['export'] ?? '') === 'csv') {
    $statement = $pdo->prepare($listSql);
    $statement->execute($parameters);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="voertuigen-' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'wb');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Kenteken', 'Merk', 'Model', 'Bouwjaar', 'Kilometerstand', 'Status'], ';');

    foreach ($statement as $vehicle) {
        fputcsv($output, [
            $vehicle['kenteken'],
            $vehicle['merk'],
            $vehicle['model'],
            $vehicle['bouwjaar'],
            $vehicle['kilometerstand'],
            $vehicle['status'],
        ], ';');
    }

    fclose($output);
    exit;
}

$queryParams = [
    'q' => $search,
    'status' => $status,
    'sort' => $sort,
    'dir' => strtolower($direction),
];

$statement = $pdo->prepare($listSql . ' LIMIT ? OFFSET ?');
$position = 1;

foreach ($parameters as $parameter) {
    $type = is_int($parameter) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $statement->bindValue($position++, $parameter, $type);
}

$statement->bindValue($position++, $perPage, PDO::PARAM_INT);
$statement->bindValue($position, ($page - 1) * $perPage, PDO::PARAM_INT);
$statement->execute();
$vehicles = $statement->fetchAll();

page_start('Voertuigen');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">CRUD-overzicht</p>
            <h1>Mijn voertuigen</h1>
            <p><?= $total ?> resultaat/resultaten</p>
        </div>
        <a class="button" href="create.php">Toevoegen</a>
    </div>

    <form class="toolbar" method="get">
        <input type="search" name="q" value="<?= e($search) ?>"
               placeholder="Kenteken, merk of model">

        <select name="status">
            <option value="">Alle statussen</option>
            <?php foreach (VOERTUIG_STATUSSEN as $option): ?>
                <option <?= $status === $option ? 'selected' : '' ?>><?= e($option) ?></option>
            <?php endforeach; ?>
        </select>

        <button class="button">Zoeken</button>
        <a class="button button-secondary" href="index.php">Reset</a>
        <a class="button button-secondary" href="?<?= e(http_build_query(
            array_merge($queryParams, ['export' => 'csv'])
        )) ?>">CSV</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php foreach ([
                        'kenteken' => 'Kenteken',
                        'merk' => 'Merk / model',
                        'bouwjaar' => 'Bouwjaar',
                        'kilometerstand' => 'Kilometerstand',
                    ] as $key => $label): ?>
                        <?php $nextDirection = $sort === $key && $direction === 'ASC' ? 'desc' : 'asc'; ?>
                        <th>
                            <a href="?<?= e(http_build_query(
                                array_merge($queryParams, ['sort' => $key, 'dir' => $nextDirection])
                            )) ?>"><?= e($label) ?></a>
                        </th>
                    <?php endforeach; ?>
                    <th>Status</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vehicles as $vehicle): ?>
                    <tr>
                        <td><a href="show.php?id=<?= $vehicle['id'] ?>"><?= e($vehicle['kenteken']) ?></a></td>
                        <td><?= e($vehicle['merk'] . ' ' . $vehicle['model']) ?></td>
                        <td><?= e($vehicle['bouwjaar']) ?></td>
                        <td><?= e($vehicle['kilometerstand']) ?> km</td>
                        <td>
                            <form class="status-form" method="post" action="status.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $vehicle['id'] ?>">
                                <select name="status">
                                    <?php foreach (VOERTUIG_STATUSSEN as $option): ?>
                                        <option <?= $vehicle['status'] === $option ? 'selected' : '' ?>>
                                            <?= e($option) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="button button-small">OK</button>
                            </form>
                        </td>
                        <td class="actions">
                            <a class="button button-small button-secondary"
                               href="show.php?id=<?= $vehicle['id'] ?>">Bekijken</a>
                            <a class="button button-small button-secondary"
                               href="edit.php?id=<?= $vehicle['id'] ?>">Wijzigen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if ($vehicles === []): ?>
                    <tr><td colspan="6" class="empty">Geen voertuigen gevonden.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination">
            <?php for ($number = 1; $number <= $totalPages; $number++): ?>
                <a class="button button-small <?= $number === $page ? '' : 'button-secondary' ?>"
                   href="?<?= e(http_build_query(
                       array_merge($queryParams, ['page' => $number])
                   )) ?>"><?= $number ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</main>
<?php page_end(); ?>
