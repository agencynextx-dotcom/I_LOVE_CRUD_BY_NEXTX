<?php

declare(strict_types=1);

require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

$search = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['categorie'] ?? ''));
$availability = (string) ($_GET['beschikbaar'] ?? '');
$conditions = [];
$parameters = [];

if ($search !== '') {
    $conditions[] = '(titel LIKE ? OR auteur LIKE ? OR isbn LIKE ?)';
    $like = '%' . $search . '%';
    array_push($parameters, $like, $like, $like);
}

if ($category !== '') {
    $conditions[] = 'categorie = ?';
    $parameters[] = $category;
}

if ($availability === '1' || $availability === '0') {
    $conditions[] = 'beschikbaar = ?';
    $parameters[] = (int) $availability;
}

$sql = 'SELECT id, isbn, titel, auteur, categorie, publicatiejaar, beschikbaar FROM boeken';
if ($conditions !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY titel';

$statement = $pdo->prepare($sql);
$statement->execute($parameters);
$books = $statement->fetchAll();
$categories = $pdo->query('SELECT DISTINCT categorie FROM boeken ORDER BY categorie')->fetchAll();

page_start(
    'Bibliotheekcatalogus',
    '',
    'Zoek boeken en bekijk hun actuele beschikbaarheid.',
    '../'
);
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Examenronde 02 &middot; Gebruikersoverzicht</p>
            <h1>Bibliotheekcatalogus</h1>
            <p>Zoek in de collectie en controleer of een boek beschikbaar is.</p>
        </div>
        <a class="button button-secondary" href="../index.php">Naar administratie</a>
    </div>

    <div class="toolbar">
        <form class="search-form" method="get">
            <input type="search" name="q" value="<?= e($search) ?>"
                   placeholder="Zoek titel, auteur of ISBN">
            <select name="categorie" class="filter-select">
                <option value="">Alle categorieen</option>
                <?php foreach ($categories as $row): ?>
                    <option value="<?= e($row['categorie']) ?>"
                            <?= $category === $row['categorie'] ? 'selected' : '' ?>>
                        <?= e($row['categorie']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="beschikbaar" class="filter-select">
                <option value="">Alle statussen</option>
                <option value="1" <?= $availability === '1' ? 'selected' : '' ?>>Beschikbaar</option>
                <option value="0" <?= $availability === '0' ? 'selected' : '' ?>>Uitgeleend</option>
            </select>
            <button class="button" type="submit">Zoeken</button>
            <?php if ($search !== '' || $category !== '' || $availability !== ''): ?>
                <a class="button button-secondary" href="catalogus.php">Wissen</a>
            <?php endif; ?>
        </form>
        <span class="count"><?= count($books) ?> boek(en)</span>
    </div>

    <div class="table-wrap">
        <?php if ($books === []): ?>
            <div class="empty">Geen boeken gevonden.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Titel</th><th>Auteur</th><th>Categorie</th><th>Publicatiejaar</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td><strong><?= e($book['titel']) ?></strong><br><span class="count"><?= e($book['isbn']) ?></span></td>
                            <td><?= e($book['auteur']) ?></td>
                            <td><?= e($book['categorie']) ?></td>
                            <td><?= e($book['publicatiejaar'] ?: '-') ?></td>
                            <td>
                                <span class="badge <?= $book['beschikbaar'] ? 'badge-success' : 'badge-warning' ?>">
                                    <?= $book['beschikbaar'] ? 'Beschikbaar' : 'Uitgeleend' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<?php page_end(); ?>
