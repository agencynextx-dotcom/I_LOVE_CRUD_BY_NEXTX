<?php

require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

require_login('Auth/login.php');

$statement = $pdo->prepare(
    "SELECT COUNT(*) AS totaal,
            SUM(status = 'Actief') AS actief,
            SUM(status = 'In onderhoud') AS in_onderhoud
     FROM voertuigen
     WHERE user_id = ?"
);
$statement->execute([user_id()]);
$stats = $statement->fetch();

$statement = $pdo->prepare(
    "SELECT COALESCE(SUM(onderhoud.kosten), 0)
     FROM onderhoud
     JOIN voertuigen ON voertuigen.id = onderhoud.voertuig_id
     WHERE voertuigen.user_id = ? AND onderhoud.status = 'Uitgevoerd'"
);
$statement->execute([user_id()]);
$totalCosts = (float) $statement->fetchColumn();

$statement = $pdo->prepare(
    "SELECT onderhoud.*, voertuigen.kenteken, voertuigen.merk, voertuigen.model,
            DATEDIFF(onderhoud.onderhoudsdatum, CURDATE()) AS dagen
     FROM onderhoud
     JOIN voertuigen ON voertuigen.id = onderhoud.voertuig_id
     WHERE voertuigen.user_id = ?
       AND onderhoud.status = 'Gepland'
       AND onderhoud.onderhoudsdatum <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY onderhoud.onderhoudsdatum
     LIMIT 8"
);
$statement->execute([user_id()]);
$warnings = $statement->fetchAll();

page_start('Dashboard', '');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Mijn wagenpark</p>
            <h1>Dashboard</h1>
            <p>Overzicht van voertuigen en aankomend onderhoud.</p>
        </div>
        <a class="button" href="voertuigen/create.php">Voertuig toevoegen</a>
    </div>

    <div class="dashboard">
        <div class="stat-card">
            <span>Voertuigen</span>
            <strong><?= e($stats['totaal']) ?></strong>
        </div>
        <div class="stat-card">
            <span>Actief</span>
            <strong><?= e($stats['actief'] ?? 0) ?></strong>
        </div>
        <div class="stat-card">
            <span>In onderhoud</span>
            <strong><?= e($stats['in_onderhoud'] ?? 0) ?></strong>
        </div>
        <div class="stat-card">
            <span>Uitgegeven onderhoud</span>
            <strong>SRD <?= number_format($totalCosts, 2, ',', '.') ?></strong>
        </div>
    </div>

    <section class="panel">
        <h2>Onderhoudswaarschuwingen</h2>

        <?php if ($warnings === []): ?>
            <p class="empty">Geen gepland of achterstallig onderhoud binnen 30 dagen.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Voertuig</th>
                            <th>Omschrijving</th>
                            <th>Datum</th>
                            <th>Waarschuwing</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($warnings as $item): ?>
                            <?php $isOverdue = (int) $item['dagen'] < 0; ?>
                            <tr>
                                <td>
                                    <a href="voertuigen/show.php?id=<?= e($item['voertuig_id']) ?>">
                                        <?= e($item['kenteken'] . ' · ' . $item['merk'] . ' ' . $item['model']) ?>
                                    </a>
                                </td>
                                <td><?= e($item['omschrijving']) ?></td>
                                <td><?= e($item['onderhoudsdatum']) ?></td>
                                <td>
                                    <span class="badge <?= $isOverdue ? 'badge-danger' : 'badge-warning' ?>">
                                        <?= $isOverdue ? 'Achterstallig' : e($item['dagen']) . ' dagen' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php page_end(); ?>
