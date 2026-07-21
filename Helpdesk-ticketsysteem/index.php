<?php

declare(strict_types=1);

require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

require_login();

$categories = $pdo->query('SELECT id, naam FROM categorieen ORDER BY naam')->fetchAll();

$action = $_GET['action'] ?? 'list';
$action = in_array($action, ['list', 'create', 'edit', 'view'], true) ? $action : 'list';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$errors = [];
$ticket = [
    'categorie_id' => '',
    'onderwerp' => '',
    'beschrijving' => '',
    'prioriteit' => 'Normaal',
];

$prioriteiten = ['Laag', 'Normaal', 'Hoog'];
$statussen = ['Open', 'In behandeling', 'Gesloten'];

function find_own_ticket(PDO $pdo, int $id, int $userId): array|false
{
    $statement = $pdo->prepare(
        'SELECT tickets.*, categorieen.naam AS categorie_naam
         FROM tickets
         JOIN categorieen ON categorieen.id = tickets.categorie_id
         WHERE tickets.id = ? AND tickets.user_id = ?'
    );
    $statement->execute([$id, $userId]);

    return $statement->fetch();
}

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    verify_csrf();
    $ticketId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $existing = $ticketId ? find_own_ticket($pdo, $ticketId, (int) $_SESSION['user_id']) : false;

    if ($existing && $existing['status'] === 'Open') {
        $statement = $pdo->prepare('DELETE FROM tickets WHERE id = ? AND user_id = ?');
        $statement->execute([$ticketId, (int) $_SESSION['user_id']]);
        flash('success', 'Je ticket is verwijderd.');
    } else {
        flash('error', 'Alleen open tickets van jezelf kunnen worden verwijderd.');
    }

    redirect();
}

// CREATE EN UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') !== 'delete') {
    verify_csrf();

    $existing = $action === 'edit' && $id > 0
        ? find_own_ticket($pdo, $id, (int) $_SESSION['user_id'])
        : null;

    if ($action === 'edit' && (!$existing || $existing['status'] !== 'Open')) {
        flash('error', 'Alleen open tickets van jezelf kunnen worden gewijzigd.');
        redirect();
    }

    $ticket = [
        'categorie_id' => (int) ($_POST['categorie_id'] ?? 0),
        'onderwerp' => trim((string) ($_POST['onderwerp'] ?? '')),
        'beschrijving' => trim((string) ($_POST['beschrijving'] ?? '')),
        'prioriteit' => (string) ($_POST['prioriteit'] ?? ''),
    ];

    $categoryIds = array_map('intval', array_column($categories, 'id'));
    if (!in_array($ticket['categorie_id'], $categoryIds, true)) {
        $errors[] = 'Kies een geldige categorie.';
    }

    if ($ticket['onderwerp'] === '' || mb_strlen($ticket['onderwerp']) > 255) {
        $errors[] = 'Onderwerp is verplicht en mag maximaal 255 tekens bevatten.';
    }

    if ($ticket['beschrijving'] === '') {
        $errors[] = 'Beschrijving is verplicht.';
    }

    if (!in_array($ticket['prioriteit'], $prioriteiten, true)) {
        $errors[] = 'Kies een geldige prioriteit.';
    }

    if ($errors === []) {
        $values = [
            $ticket['categorie_id'],
            $ticket['onderwerp'],
            $ticket['beschrijving'],
            $ticket['prioriteit'],
        ];

        if ($action === 'edit' && $id > 0) {
            $statement = $pdo->prepare(
                'UPDATE tickets
                 SET categorie_id = ?, onderwerp = ?, beschrijving = ?, prioriteit = ?
                 WHERE id = ? AND user_id = ? AND status = \'Open\''
            );
            $statement->execute([...$values, $id, (int) $_SESSION['user_id']]);
            flash('success', 'Je ticket is bijgewerkt.');
        } else {
            $statement = $pdo->prepare(
                "INSERT INTO tickets (user_id, categorie_id, onderwerp, beschrijving, prioriteit, status)
                 VALUES (?, ?, ?, ?, ?, 'Open')"
            );
            $statement->execute([(int) $_SESSION['user_id'], ...$values]);
            flash('success', 'Je ticket is aangemaakt.');
        }

        redirect();
    }
}

// READ ONE (edit of view)
if (($action === 'edit' || $action === 'view') && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $found = find_own_ticket($pdo, $id, (int) $_SESSION['user_id']);

    if (!$found) {
        flash('error', 'Ticket niet gevonden of niet van jou.');
        redirect();
    }

    if ($action === 'edit' && $found['status'] !== 'Open') {
        flash('error', 'Alleen open tickets kunnen worden gewijzigd.');
        redirect();
    }

    $ticket = $found;
}

page_start('Mijn tickets', 'tickets', 'Meld en beheer je eigen supporttickets.');
?>

<main class="container">
    <?php if ($action === 'view'): ?>
        <div class="page-heading">
            <div>
                <p class="eyebrow">Helpdesk</p>
                <h1><?= e($ticket['onderwerp']) ?></h1>
                <p>Details van je ticket.</p>
            </div>
            <a class="button button-secondary" href="index.php">← Terug</a>
        </div>

        <section class="panel">
            <div class="form-grid">
                <div class="form-field">
                    <label>Categorie</label>
                    <p><?= e($ticket['categorie_naam']) ?></p>
                </div>
                <div class="form-field">
                    <label>Prioriteit</label>
                    <p><?= e($ticket['prioriteit']) ?></p>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <p><?= e($ticket['status']) ?></p>
                </div>
                <div class="form-field">
                    <label>Aangemaakt</label>
                    <p><?= date('d-m-Y H:i', strtotime($ticket['created_at'])) ?></p>
                </div>
                <div class="form-field full">
                    <label>Beschrijving</label>
                    <p><?= nl2br(e($ticket['beschrijving'])) ?></p>
                </div>
            </div>

            <?php if ($ticket['status'] === 'Open'): ?>
                <div class="form-actions">
                    <a class="button" href="?action=edit&amp;id=<?= e($ticket['id']) ?>">Wijzigen</a>
                </div>
            <?php endif; ?>
        </section>
    <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <div class="page-heading">
            <div>
                <p class="eyebrow">Helpdesk</p>
                <h1><?= $action === 'edit' ? 'Ticket wijzigen' : 'Nieuw ticket' ?></h1>
                <p>Beschrijf je probleem zo duidelijk mogelijk.</p>
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
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="form-grid">
                    <div class="form-field">
                        <label for="onderwerp">Onderwerp *</label>
                        <input id="onderwerp" name="onderwerp" maxlength="255"
                               value="<?= old('onderwerp', $ticket['onderwerp']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="categorie_id">Categorie *</label>
                        <select id="categorie_id" name="categorie_id" required>
                            <option value="">Kies een categorie</option>
                            <?php $selectedCategory = (string) ($_POST['categorie_id'] ?? $ticket['categorie_id']); ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= e($category['id']) ?>"
                                        <?= $selectedCategory === (string) $category['id'] ? 'selected' : '' ?>>
                                    <?= e($category['naam']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="prioriteit">Prioriteit *</label>
                        <select id="prioriteit" name="prioriteit" required>
                            <?php foreach ($prioriteiten as $prioriteit): ?>
                                <option <?= (($_POST['prioriteit'] ?? $ticket['prioriteit']) === $prioriteit) ? 'selected' : '' ?>>
                                    <?= e($prioriteit) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field full">
                        <label for="beschrijving">Beschrijving *</label>
                        <textarea id="beschrijving" name="beschrijving"
                                  placeholder="Wat gaat er mis?" required><?= old('beschrijving', $ticket['beschrijving']) ?></textarea>
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
        // READ ALL MET ZOEKEN EN STATUSFILTER
        $search = trim((string) ($_GET['q'] ?? ''));
        $statusFilter = (string) ($_GET['status'] ?? '');
        $conditions = ['tickets.user_id = ?'];
        $parameters = [(int) $_SESSION['user_id']];

        if ($search !== '') {
            $conditions[] = 'tickets.onderwerp LIKE ?';
            $parameters[] = '%' . $search . '%';
        }

        if (in_array($statusFilter, $statussen, true)) {
            $conditions[] = 'tickets.status = ?';
            $parameters[] = $statusFilter;
        }

        $sql = 'SELECT tickets.*, categorieen.naam AS categorie_naam
                FROM tickets
                JOIN categorieen ON categorieen.id = tickets.categorie_id';
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
                <p class="eyebrow">Helpdesk</p>
                <h1>Mijn tickets</h1>
                <p>Meld een probleem en volg je eigen tickets op.</p>
            </div>
            <div class="heading-actions">
                <a class="button" href="?action=create">+ Nieuw ticket</a>
            </div>
        </div>

        <div class="toolbar">
            <form class="search-form" method="get">
                <input type="search" name="q" value="<?= e($search) ?>"
                       placeholder="Zoek op onderwerp">
                <select name="status" class="filter-select">
                    <option value="">Alle statussen</option>
                    <?php foreach ($statussen as $status): ?>
                        <option <?= $statusFilter === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button button-secondary" type="submit">Filteren</button>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                    <a class="button button-secondary" href="index.php">Wissen</a>
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
                            <th>Onderwerp</th><th>Categorie</th><th>Prioriteit</th>
                            <th>Status</th><th>Aangemaakt</th><th>Acties</th>
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
                                <td>
                                    <a href="?action=view&amp;id=<?= e($row['id']) ?>">
                                        <strong><?= e($row['onderwerp']) ?></strong>
                                    </a><br>
                                    <span class="count"><?= e(mb_strimwidth($row['beschrijving'], 0, 45, '…')) ?></span>
                                </td>
                                <td><?= e($row['categorie_naam']) ?></td>
                                <td><span class="badge <?= $priorityBadge ?>"><?= e($row['prioriteit']) ?></span></td>
                                <td><span class="badge <?= $statusBadge ?>"><?= e($row['status']) ?></span></td>
                                <td><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <div class="actions">
                                        <a class="button button-secondary button-small"
                                           href="?action=view&amp;id=<?= e($row['id']) ?>">
                                            Bekijken
                                        </a>
                                        <?php if ($row['status'] === 'Open'): ?>
                                            <a class="button button-secondary button-small"
                                               href="?action=edit&amp;id=<?= e($row['id']) ?>">
                                                Wijzigen
                                            </a>
                                            <form method="post" data-confirm="Dit ticket definitief verwijderen?">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="form_action" value="delete">
                                                <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                                <button class="button button-danger button-small" type="submit">Verwijderen</button>
                                            </form>
                                        <?php endif; ?>
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
