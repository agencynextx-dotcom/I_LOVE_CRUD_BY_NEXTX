<?php

declare(strict_types=1);

require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

require_login('../Auth/login.php');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $change = filter_input(INPUT_POST, 'change', FILTER_VALIDATE_INT);
    $reason = trim((string) ($_POST['reden'] ?? ''));

    if (!$productId || $change === false || $change === null || $change === 0) {
        $error = 'Kies een product en vul een wijziging anders dan nul in.';
    } elseif ($reason === '') {
        $error = 'Vul een reden voor de voorraadmutatie in.';
    } else {
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                'UPDATE producten
                 SET voorraad = voorraad + ?
                 WHERE id = ? AND voorraad + ? >= 0'
            );
            $statement->execute([$change, $productId, $change]);

            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('De voorraad kan niet negatief worden.');
            }

            $statement = $pdo->prepare(
                'INSERT INTO voorraadmutaties (product_id, user_id, wijziging, reden)
                 VALUES (?, ?, ?, ?)'
            );
            $statement->execute([
                $productId,
                (int) $_SESSION['user_id'],
                $change,
                $reason,
            ]);
            $pdo->commit();
            flash('success', 'De voorraad en mutatiehistoriek zijn bijgewerkt.');
            redirect('voorraad.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception->getMessage();
        }
    }
}

$products = $pdo->query(
    'SELECT producten.*, categorieen.naam AS categorie_naam
     FROM producten JOIN categorieen ON categorieen.id = producten.categorie_id
     ORDER BY producten.naam'
)->fetchAll();
$mutations = $pdo->query(
    'SELECT voorraadmutaties.*, producten.naam AS productnaam,
            COALESCE(users.username, "verwijderde gebruiker") AS gebruikersnaam
     FROM voorraadmutaties
     JOIN producten ON producten.id = voorraadmutaties.product_id
     LEFT JOIN users ON users.id = voorraadmutaties.user_id
     ORDER BY voorraadmutaties.gemuteerd_op DESC
     LIMIT 20'
)->fetchAll();

page_start('Voorraad aanpassen', '', 'Boek en controleer voorraadmutaties.', '../');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Examenronde 03 &middot; Volledig admin</p>
            <h1>Voorraadmutatie boeken</h1>
            <p>Gebruik een positief getal voor ontvangst en een negatief getal voor uitgifte.</p>
        </div>
        <a class="button button-secondary" href="../index.php">&larr; Inventaris</a>
    </div>

    <section class="panel">
        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-grid">
                <div class="form-field">
                    <label for="product_id">Product *</label>
                    <select id="product_id" name="product_id" required>
                        <option value="">Kies een product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= e($product['id']) ?>">
                                <?= e($product['naam']) ?> - voorraad: <?= e($product['voorraad']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="change">Wijziging *</label>
                    <input type="number" id="change" name="change"
                           placeholder="Bijvoorbeeld 5 of -2" required>
                </div>

                <div class="form-field full">
                    <label for="reden">Reden *</label>
                    <input id="reden" name="reden" maxlength="200"
                           placeholder="Bijvoorbeeld levering of verkoop" required>
                </div>
            </div>
            <div class="form-actions">
                <button class="button" type="submit">Mutatie boeken</button>
            </div>
        </form>
    </section>

    <h2>Actuele voorraad</h2>
    <div class="table-wrap">
        <?php if ($products === []): ?>
            <div class="empty">Voeg eerst producten toe.</div>
        <?php else: ?>
            <table>
                <thead><tr><th>Product</th><th>Categorie</th><th>SKU</th><th>Voorraad</th><th>Minimum</th></tr></thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= e($product['naam']) ?></td>
                            <td><?= e($product['categorie_naam']) ?></td>
                            <td><?= e($product['sku']) ?></td>
                            <td><?= e($product['voorraad']) ?></td>
                            <td><?= e($product['minimumvoorraad']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <h2>Laatste twintig mutaties</h2>
    <div class="table-wrap">
        <?php if ($mutations === []): ?>
            <div class="empty">Er zijn nog geen voorraadmutaties geboekt.</div>
        <?php else: ?>
            <table>
                <thead><tr><th>Moment</th><th>Product</th><th>Wijziging</th><th>Reden</th><th>Gebruiker</th></tr></thead>
                <tbody>
                    <?php foreach ($mutations as $mutation): ?>
                        <tr>
                            <td><?= date('d-m-Y H:i', strtotime($mutation['gemuteerd_op'])) ?></td>
                            <td><?= e($mutation['productnaam']) ?></td>
                            <td>
                                <span class="badge <?= (int) $mutation['wijziging'] > 0 ? 'badge-success' : 'badge-warning' ?>">
                                    <?= (int) $mutation['wijziging'] > 0 ? '+' : '' ?><?= e($mutation['wijziging']) ?>
                                </span>
                            </td>
                            <td><?= e($mutation['reden']) ?></td>
                            <td><?= e($mutation['gebruikersnaam']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<?php page_end(); ?>
