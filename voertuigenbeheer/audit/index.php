<?php
require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

require_login();

$statement = $pdo->prepare(
    'SELECT * FROM audit_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 100'
);
$statement->execute([user_id()]);
$entries = $statement->fetchAll();

page_start('Auditlog');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Plusfunctie</p>
            <h1>Mijn auditlog</h1>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Moment</th>
                    <th>Actie</th>
                    <th>Entiteit</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?= e($entry['created_at']) ?></td>
                        <td><?= e($entry['actie']) ?></td>
                        <td><?= e($entry['entiteit'] . ' #' . ($entry['entiteit_id'] ?? '-')) ?></td>
                        <td><?= e($entry['details']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<?php page_end(); ?>
