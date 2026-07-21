<?php

declare(strict_types=1);

require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

require_login();

$categories = $pdo->query('SELECT id, naam FROM categorieen ORDER BY naam')->fetchAll();
$action = $_GET['action'] ?? 'list';
$action = in_array($action, ['list', 'create', 'edit'], true) ? $action : 'list';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$errors = [];
$product = [
    'categorie_id' => '',
    'sku' => '',
    'naam' => '',
    'voorraad' => 0,
    'minimumvoorraad' => 0,
    'prijs' => '0.00',
    'locatie' => '',
];

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    $productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($productId) {
        $statement = $pdo->prepare('DELETE FROM producten WHERE id = ?');
        $statement->execute([$productId]);
        flash('success', 'Product is verwijderd.');
    }

    redirect();
}

// CREATE EN UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $product = [
        'categorie_id' => (int) ($_POST['categorie_id'] ?? 0),
        'sku' => strtoupper(trim((string) ($_POST['sku'] ?? ''))),
        'naam' => trim((string) ($_POST['naam'] ?? '')),
        'voorraad' => (int) ($_POST['voorraad'] ?? -1),
        'minimumvoorraad' => (int) ($_POST['minimumvoorraad'] ?? -1),
        'prijs' => str_replace(',', '.', trim((string) ($_POST['prijs'] ?? ''))),
        'locatie' => trim((string) ($_POST['locatie'] ?? '')),
    ];

    if ($product['sku'] === '' || $product['naam'] === '') {
        $errors[] = 'SKU en productnaam zijn verplicht.';
    }

    $categoryIds = array_map('intval', array_column($categories, 'id'));
    if (!in_array($product['categorie_id'], $categoryIds, true)) {
        $errors[] = 'Kies een geldige categorie.';
    }

    if ($product['locatie'] === '') {
        $errors[] = 'Locatie is verplicht.';
    }

    if ($product['voorraad'] < 0 || $product['minimumvoorraad'] < 0) {
        $errors[] = 'Voorraadaantallen mogen niet negatief zijn.';
    }

    if (!is_numeric($product['prijs']) || (float) $product['prijs'] < 0) {
        $errors[] = 'Vul een geldige prijs in.';
    }

    if ($errors === []) {
        $values = [
            $product['categorie_id'],
            $product['sku'],
            $product['naam'],
            $product['voorraad'],
            $product['minimumvoorraad'],
            (float) $product['prijs'],
            $product['locatie'],
        ];

        try {
            if ($action === 'edit' && $id > 0) {
                $statement = $pdo->prepare(
                    'UPDATE producten
                     SET categorie_id = ?, sku = ?, naam = ?, voorraad = ?,
                         minimumvoorraad = ?, prijs = ?, locatie = ?
                     WHERE id = ?'
                );
                $statement->execute([...$values, $id]);
                flash('success', 'Product is bijgewerkt.');
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO producten
                        (categorie_id, sku, naam, voorraad, minimumvoorraad, prijs, locatie)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $statement->execute($values);
                flash('success', 'Product is toegevoegd.');
            }

            redirect();
        } catch (PDOException $exception) {
            $errors[] = $exception->getCode() === '23000'
                ? 'Deze SKU bestaat al.'
                : 'Opslaan is mislukt. Probeer het opnieuw.';
        }
    }
}

// READ ONE
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $statement = $pdo->prepare('SELECT * FROM producten WHERE id = ?');
    $statement->execute([$id]);
    $product = $statement->fetch();

    if (!$product) {
        flash('error', 'Product niet gevonden.');
        redirect();
    }
}

page_start(
    'Inventarisbeheer',
    'inventaris',
    'Beheer producten, voorraad en opslaglocaties.'
);
?>

<main class="container">
    <?php if ($action === 'create' || $action === 'edit'): ?>
        <div class="page-heading">
            <div>
                <p class="eyebrow">Inventaris</p>
                <h1><?= $action === 'edit' ? 'Product wijzigen' : 'Product toevoegen' ?></h1>
                <p>Voorraad en bedragen worden op de server gecontroleerd.</p>
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
                        <label for="sku">SKU / artikelcode *</label>
                        <input id="sku" name="sku" maxlength="30"
                               value="<?= old('sku', $product['sku']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="naam">Productnaam *</label>
                        <input id="naam" name="naam" maxlength="150"
                               value="<?= old('naam', $product['naam']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="categorie_id">Categorie *</label>
                        <select id="categorie_id" name="categorie_id" required>
                            <option value="">Kies een categorie</option>
                            <?php $selectedCategory = (string) ($_POST['categorie_id'] ?? $product['categorie_id']); ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= e($category['id']) ?>"
                                        <?= $selectedCategory === (string) $category['id'] ? 'selected' : '' ?>>
                                    <?= e($category['naam']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="locatie">Opslaglocatie *</label>
                        <input id="locatie" name="locatie" maxlength="80"
                               value="<?= old('locatie', $product['locatie']) ?>"
                               placeholder="Bijvoorbeeld Magazijn A - R2" required>
                    </div>

                    <div class="form-field">
                        <label for="voorraad">Huidige voorraad *</label>
                        <input type="number" id="voorraad" name="voorraad" min="0"
                               value="<?= old('voorraad', $product['voorraad']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="minimumvoorraad">Minimumvoorraad *</label>
                        <input type="number" id="minimumvoorraad" name="minimumvoorraad" min="0"
                               value="<?= old('minimumvoorraad', $product['minimumvoorraad']) ?>" required>
                        <span class="hint">Bij deze waarde verschijnt een waarschuwing.</span>
                    </div>

                    <div class="form-field">
                        <label for="prijs">Prijs (SRD) *</label>
                        <input type="number" step="0.01" id="prijs" name="prijs" min="0"
                               value="<?= old('prijs', $product['prijs']) ?>" required>
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
        // READ ALL MET ZOEKEN EN EEN LAGE-VOORRAADFILTER
        $search = trim((string) ($_GET['q'] ?? ''));
        $categoryFilter = filter_input(INPUT_GET, 'categorie_id', FILTER_VALIDATE_INT) ?: 0;
        $showLowStock = isset($_GET['low']);
        $conditions = [];
        $parameters = [];

        if ($search !== '') {
            $conditions[] = '(producten.sku LIKE ? OR producten.naam LIKE ? OR categorieen.naam LIKE ? OR producten.locatie LIKE ?)';
            $like = '%' . $search . '%';
            array_push($parameters, $like, $like, $like, $like);
        }

        if ($showLowStock) {
            $conditions[] = 'voorraad <= minimumvoorraad';
        }

        if ($categoryFilter > 0) {
            $conditions[] = 'producten.categorie_id = ?';
            $parameters[] = $categoryFilter;
        }

        $sql = 'SELECT producten.*, categorieen.naam AS categorie_naam
                FROM producten JOIN categorieen ON categorieen.id = producten.categorie_id';
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY naam';

        $statement = $pdo->prepare($sql);
        $statement->execute($parameters);
        $products = $statement->fetchAll();
        ?>

        <div class="page-heading">
            <div>
                <p class="eyebrow">Examenronde 03 &middot; Volledig admin</p>
                <h1>Inventarisbeheer</h1>
                <p>Controleer voorraad, waarde en opslaglocatie.</p>
            </div>
            <div class="heading-actions">
                <a class="button button-secondary" href="public/voorraad.php">Voorraad aanpassen</a>
                <a class="button" href="?action=create">+ Product toevoegen</a>
            </div>
        </div>

        <div class="toolbar">
            <form class="search-form" method="get">
                <input type="search" name="q" value="<?= e($search) ?>"
                       placeholder="Zoek product, SKU, categorie of locatie">
                <select name="categorie_id" class="filter-select">
                    <option value="">Alle categorieen</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['id']) ?>"
                                <?= $categoryFilter === (int) $category['id'] ? 'selected' : '' ?>>
                            <?= e($category['naam']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label class="checkbox-label filter-checkbox">
                    <input type="checkbox" name="low" <?= $showLowStock ? 'checked' : '' ?>>
                    Lage voorraad
                </label>
                <button class="button button-secondary" type="submit">Filteren</button>
                <?php if ($search !== '' || $showLowStock || $categoryFilter > 0): ?>
                    <a class="button button-secondary" href="index.php">Wissen</a>
                <?php endif; ?>
            </form>
            <span class="count"><?= count($products) ?> product(en)</span>
        </div>

        <div class="table-wrap">
            <?php if ($products === []): ?>
                <div class="empty">Geen producten gevonden.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th><th>Categorie</th><th>Voorraad</th>
                            <th>Prijs</th><th>Locatie</th><th>Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $row): ?>
                            <?php $hasLowStock = (int) $row['voorraad'] <= (int) $row['minimumvoorraad']; ?>
                            <tr>
                                <td><strong><?= e($row['naam']) ?></strong><br><span class="count"><?= e($row['sku']) ?></span></td>
                                <td><?= e($row['categorie_naam']) ?></td>
                                <td>
                                    <span class="badge <?= $hasLowStock ? 'badge-danger' : 'badge-success' ?>">
                                        <?= e($row['voorraad']) ?> stuks
                                    </span><br>
                                    <span class="count">min. <?= e($row['minimumvoorraad']) ?></span>
                                </td>
                                <td>SRD <?= number_format((float) $row['prijs'], 2, ',', '.') ?></td>
                                <td><?= e($row['locatie']) ?></td>
                                <td>
                                    <div class="actions">
                                        <a class="button button-secondary button-small"
                                           href="?action=edit&amp;id=<?= e($row['id']) ?>">
                                            Wijzigen
                                        </a>
                                        <form method="post" data-confirm="Dit product definitief verwijderen?">
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
