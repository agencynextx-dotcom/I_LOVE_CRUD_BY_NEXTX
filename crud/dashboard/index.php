<?php
require_once __DIR__ . '/../relations/Includes/db.php';
require_once __DIR__ . '/../relations/Includes/functions.php';

// e() en status_badge_class() komen uit relations/Includes/functions.php.
// Deze pagina heeft alleen leesqueries nodig, dus geen losse Includes-map.

// Aantallen per status in één query (COUNT + SUM van een voorwaarde).
$statement = $pdo->query(
    "SELECT COUNT(*) AS totaal,
            SUM(status = 'Geslaagd') AS geslaagd,
            SUM(status = 'Gezakt') AS gezakt,
            SUM(status = 'In behandeling') AS in_behandeling,
            AVG(cijfer) AS gemiddeld_cijfer
     FROM studenten"
);
$stats = $statement->fetch();

// Aantal studenten per opleiding (JOIN + GROUP BY), voor een klein staafoverzicht.
$statement = $pdo->query(
    "SELECT opleidingen.naam, COUNT(studenten.id) AS aantal
     FROM opleidingen
     LEFT JOIN studenten ON studenten.opleiding_id = opleidingen.id
     GROUP BY opleidingen.id, opleidingen.naam
     ORDER BY opleidingen.naam"
);
$perOpleiding = $statement->fetchAll();

// De vijf laagste cijfers, zodat je meteen ziet wie aandacht nodig heeft.
$statement = $pdo->query(
    "SELECT studenten.naam, studenten.cijfer, studenten.status, opleidingen.naam AS opleiding_naam
     FROM studenten
     JOIN opleidingen ON opleidingen.id = studenten.opleiding_id
     ORDER BY studenten.cijfer ASC
     LIMIT 5"
);
$aandacht = $statement->fetchAll();

?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Fetch-and-display</title>
    <link rel="stylesheet" href="../relations/styles/styles.css">
</head>
<body>
    <header class="site-header">
        <div class="nav-wrap">
            <a class="brand" href="index.php"><span>Studenten</span> Dashboard</a>
            <nav class="account-nav">
                <a href="index.php">Dashboard</a>
                <a href="../relations/index.php">Studentenoverzicht</a>
            </nav>
        </div>
    </header>

<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Fetch-and-display</p>
            <h1>Dashboard</h1>
            <p>Totalen en aandachtspunten, direct opgehaald met een paar queries.</p>
        </div>
        <a class="button button-secondary" href="../relations/index.php">Naar studentenoverzicht</a>
    </div>

    <div class="dashboard">
        <div class="stat-card">
            <span>Studenten</span>
            <strong><?= e($stats['totaal']) ?></strong>
        </div>
        <div class="stat-card">
            <span>Geslaagd</span>
            <strong><?= e($stats['geslaagd'] ?? 0) ?></strong>
        </div>
        <div class="stat-card">
            <span>Gezakt</span>
            <strong><?= e($stats['gezakt'] ?? 0) ?></strong>
        </div>
        <div class="stat-card">
            <span>Gemiddeld cijfer</span>
            <strong><?= number_format((float) $stats['gemiddeld_cijfer'], 1, ',', '.') ?></strong>
        </div>
    </div>

    <section class="panel">
        <h2>Studenten per opleiding</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Opleiding</th>
                        <th>Aantal studenten</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($perOpleiding as $row): ?>
                        <tr>
                            <td><?= e($row['naam']) ?></td>
                            <td><?= e($row['aantal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2>Laagste cijfers (aandacht nodig)</h2>

        <?php if ($aandacht === []): ?>
            <p class="empty">Nog geen studenten om te tonen.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Naam</th>
                            <th>Opleiding</th>
                            <th>Cijfer</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aandacht as $student): ?>
                            <tr>
                                <td><?= e($student['naam']) ?></td>
                                <td><?= e($student['opleiding_naam']) ?></td>
                                <td><?= e($student['cijfer']) ?></td>
                                <td><span class="<?= status_badge_class($student['status']) ?>"><?= e($student['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

    <footer class="site-footer">
        <div class="container">Cheat sheet: dashboard met aggregatiequeries &middot; <?= date('Y') ?></div>
    </footer>
</body>
</html>
