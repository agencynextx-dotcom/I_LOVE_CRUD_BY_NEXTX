<?php

declare(strict_types=1);

require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

require_admin();

$categories = $pdo->query('SELECT id, naam FROM categorieen ORDER BY naam')->fetchAll();

$prioriteiten = ['Laag', 'Normaal', 'Hoog'];
$statussen = ['Open', 'In behandeling', 'Gesloten'];

$action = ($_GET['action'] ?? '') === 'view' ? 'view' : 'list';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$errors = [];

// STATUS WIJZIGEN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'update_status') {
    verify_csrf();

    $ticketId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $newStatus = (string) ($_POST['status'] ?? '');

    if ($ticketId && in_array($newStatus, $statussen, true)) {
        $statement = $pdo->prepare('UPDATE tickets SET status = ? WHERE id = ?');
        $statement->execute([$newStatus, $ticketId]);
        flash('success', 'Status van het ticket is bijgewerkt.');
    } else {
        flash('error', 'Kies een geldige status.');
    }

    redirect('tickets.php?action=view&id=' . $ticketId);
}

if ($action === 'view') {
    $statement = $pdo->prepare(
        'SELECT tickets.*, categorieen.naam AS categorie_naam,
                users.username, users.email
         FROM tickets
         JOIN categorieen ON categorieen.id = tickets.categorie_id
         JOIN users ON users.id = tickets.user_id
         WHERE tickets.id = ?'
    );
    $statement->execute([$id]);
    $ticket = $statement->fetch();

    if (!$ticket) {
        flash('error', 'Ticket niet gevonden.');
        redirect('tickets.php');
    }
}

page_start('Alle tickets', 'admin', 'Beheer alle helpdesktickets.', '../');
?>

<main class="container">
    <?php if ($action === 'view'): ?>
        <div class="page-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1><?= e($ticket['onderwerp']) ?></h1>
                <p>Volledige details van het ticket.</p>
            </div>
            <a class="button button-secondary" href="tickets.php">← Terug</a>
        </div>

        <section class="panel">
            <div class="form-grid">
                <div class="form-field">
                    <label>Melder</label>
                    <p><?= e($ticket['username']) ?></p>
                </div>
                <div class="form-field">
                    <label>E-mailadres</label>
                    <p><?= e($ticket['email']) ?></p>
                </div>
                <div class="form-field">
                    <label>Categorie</label>
                    <p><?= e($ticket['categorie_naam']) ?></p>
                </div>
                <div class="form-field">
                    <label>Prioriteit</label>
                    <p><?= e($ticket['prioriteit']) ?></p>
                </div>
                <div class="form-field">
                    <label>Aangemaakt</label>
                    <p><?= date('d-m-Y H:i', strtotime($ticket['created_at'])) ?></p>
                </div>
                <div class="form-field">
                    <label>Laatst bijgewerkt</label>
                    <p><?= date('d-m-Y H:i', strtotime($ticket['updated_at'])) ?></p>
                </div>
                <div class="form-field full">
                    <label>Beschrijving</label>
                    <p><?= nl2br(e($ticket['beschrijving'])) ?></p>
                </div>
            </div>

            <form method="post" class="form-actions">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="form_action" value="update_status">
                <input type="hidden" name="id" value="<?= e($ticket['id']) ?>">
                <select name="status" style="width:auto">
                    <?php foreach ($statussen as $status): ?>
                        <option <?= $ticket['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button" type="submit">Status bijwerken</button>
            </form>
        </section>
    <?php else: ?>
        <?php
        $search = trim((string) ($_GET['q'] ?? ''));
        $statusFilter = (string) ($_GET['status'] ?? '');
        $priorityFilter = (string) ($_GET['prioriteit'] ?? '');
        $categoryFilter = filter_input(INPUT_GET, 'categorie_id', FILTER_VALIDATE_INT) ?: 0;
        $conditions = [];
        $parameters = [];

        if ($search !== '') {
            $conditions[] = '(tickets.onderwerp LIKE ? OR users.username LIKE ?)';
            $like = '%' . $search . '%';
            array_push($parameters, $like, $like);
        }

        if (in_array($statusFilter, $statussen, true)) {
            $conditions[] = 'tickets.status = ?';
            $parameters[] = $statusFilter;
        }

        if (in_array($priorityFilter, $prioriteiten, true)) {
            $conditions[] = 'tickets.prioriteit = ?';
            $parameters[] = $priorityFilter;
        }

        if ($categoryFilter > 0) {
            $conditions[] = 'tickets.categorie_id = ?';
            $parameters[] = $categoryFilter;
        }

        $sql = 'SELECT tickets.*, categorieen.naam AS categorie_naam,
                       users.username, users.email
                FROM tickets
                JOIN categorieen ON categorieen.id = tickets.categorie_id
                JOIN users ON users.id = tickets.user_id';
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY tickets.created_at DESC';

        $statement = $pdo->prepare($sql);
        $statement->execute($parameters);
        $tickets = $statement->fetchAll();
        ?>

        <div class="page-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>Alle tickets</h1>
                <p>Zoek, filter en behandel binnenkomende tickets.</p>
            </div>
        </div>

        <div class="toolbar">
            <form class="search-form" method="get">
                <input type="search" name="q" value="<?= e($search) ?>"
                       placeholder="Zoek onderwerp of gebruikersnaam">
                <select name="status" class="filter-select">
                    <option value="">Alle statussen</option>
                    <?php foreach ($statussen as $status): ?>
                        <option <?= $statusFilter === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="prioriteit" class="filter-select">
                    <option value="">Alle prioriteiten</option>
                    <?php foreach ($prioriteiten as $prioriteit): ?>
                        <option <?= $priorityFilter === $prioriteit ? 'selected' : '' ?>><?= e($prioriteit) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="categorie_id" class="filter-select">
                    <option value="">Alle categorieën</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['id']) ?>"
                                <?= $categoryFilter === (int) $category['id'] ? 'selected' : '' ?>>
                            <?= e($category['naam']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="button button-secondary" type="submit">Filteren</button>
                <?php if ($search !== '' || $statusFilter !== '' || $priorityFilter !== '' || $categoryFilter > 0): ?>
                    <a class="button button-secondary" href="tickets.php">Wissen</a>
                <?php endif; ?>
            </form>
            <span class="count"><?= count($tickets) ?> ticket(s)</span>
        </div>

        <div class="table-wrap">
            <?php if ($tickets === []): ?>
                <div class="empty">Geen tickets gevonden.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Onderwerp</th><th>Melder</th><th>Categorie</th>
                            <th>Prioriteit</th><th>Status</th><th>Aangemaakt</th><th>Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $row): ?>
                            <?php
                            $statusBadge = match ($row['status']) {
                                'Open' => 'badge-danger',
                                'In behandeling' => 'badge-warning',
                                default => 'badge-success',
                            };
                            $priorityBadge = match ($row['prioriteit']) {
                                'Hoog' => 'badge-danger',
                                'Normaal' => 'badge-warning',
                                default => 'badge-success',
                            };
                            ?>
                            <tr>
                                <td><strong><?= e($row['onderwerp']) ?></strong></td>
                                <td><?= e($row['username']) ?><br><span class="count"><?= e($row['email']) ?></span></td>
                                <td><?= e($row['categorie_naam']) ?></td>
                                <td><span class="badge <?= $priorityBadge ?>"><?= e($row['prioriteit']) ?></span></td>
                                <td><span class="badge <?= $statusBadge ?>"><?= e($row['status']) ?></span></td>
                                <td><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <a class="button button-secondary button-small"
                                       href="?action=view&amp;id=<?= e($row['id']) ?>">
                                        Openen
                                    </a>
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
