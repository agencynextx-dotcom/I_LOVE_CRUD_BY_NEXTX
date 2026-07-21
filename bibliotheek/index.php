<?php

declare(strict_types=1);

require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

require_role('admin');

$action = $_GET['action'] ?? 'list';
$action = in_array($action, ['list', 'create', 'edit'], true) ? $action : 'list';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$errors = [];
$book = [
    'isbn' => '',
    'titel' => '',
    'auteur' => '',
    'categorie' => '',
    'publicatiejaar' => '',
];

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    $bookId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($bookId) {
        try {
            $statement = $pdo->prepare('DELETE FROM boeken WHERE id = ?');
            $statement->execute([$bookId]);
            flash('success', 'Boek is verwijderd.');
        } catch (PDOException $exception) {
            flash('error', 'Dit boek heeft een uitleenhistoriek en kan niet worden verwijderd.');
        }
    }

    redirect();
}

// CREATE EN UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $book = [
        'isbn' => trim((string) ($_POST['isbn'] ?? '')),
        'titel' => trim((string) ($_POST['titel'] ?? '')),
        'auteur' => trim((string) ($_POST['auteur'] ?? '')),
        'categorie' => trim((string) ($_POST['categorie'] ?? '')),
        'publicatiejaar' => trim((string) ($_POST['publicatiejaar'] ?? '')),
    ];

    if ($book['isbn'] === '') {
        $errors[] = 'ISBN is verplicht.';
    }

    if ($book['titel'] === '' || $book['auteur'] === '') {
        $errors[] = 'Titel en auteur zijn verplicht.';
    }

    if ($book['categorie'] === '') {
        $errors[] = 'Categorie is verplicht.';
    }

    $publicationYear = $book['publicatiejaar'] === ''
        ? null
        : (int) $book['publicatiejaar'];

    if ($publicationYear !== null && ($publicationYear < 1000 || $publicationYear > (int) date('Y'))) {
        $errors[] = 'Vul een geldig publicatiejaar in.';
    }

    if ($errors === []) {
        $values = [
            $book['isbn'],
            $book['titel'],
            $book['auteur'],
            $book['categorie'],
            $publicationYear,
        ];

        try {
            if ($action === 'edit' && $id > 0) {
                $statement = $pdo->prepare(
                    'UPDATE boeken
                     SET isbn = ?, titel = ?, auteur = ?, categorie = ?,
                         publicatiejaar = ?
                     WHERE id = ?'
                );
                $statement->execute([...$values, $id]);
                flash('success', 'Boek is bijgewerkt.');
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO boeken
                        (isbn, titel, auteur, categorie, publicatiejaar)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $statement->execute($values);
                flash('success', 'Boek is toegevoegd.');
            }

            redirect();
        } catch (PDOException $exception) {
            $errors[] = $exception->getCode() === '23000'
                ? 'Dit ISBN bestaat al.'
                : 'Opslaan is mislukt. Probeer het opnieuw.';
        }
    }
}

// READ ONE
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $statement = $pdo->prepare('SELECT * FROM boeken WHERE id = ?');
    $statement->execute([$id]);
    $book = $statement->fetch();

    if (!$book) {
        flash('error', 'Boek niet gevonden.');
        redirect();
    }
}

page_start(
    'Bibliotheekadministratie',
    'bibliotheek',
    'Beheer boeken en hun beschikbaarheid.'
);
?>

<main class="container">
    <?php if ($action === 'create' || $action === 'edit'): ?>
        <div class="page-heading">
            <div>
                <p class="eyebrow">Bibliotheek</p>
                <h1><?= $action === 'edit' ? 'Boek wijzigen' : 'Boek toevoegen' ?></h1>
                <p>Registreer één fysiek exemplaar.</p>
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
                        <label for="isbn">ISBN *</label>
                        <input id="isbn" name="isbn" maxlength="20"
                               value="<?= old('isbn', $book['isbn']) ?>"
                               placeholder="978-0-00-000000-0" required>
                    </div>

                    <div class="form-field">
                        <label for="categorie">Categorie *</label>
                        <input id="categorie" name="categorie" maxlength="80"
                               value="<?= old('categorie', $book['categorie']) ?>"
                               placeholder="Bijvoorbeeld Roman" required>
                    </div>

                    <div class="form-field full">
                        <label for="titel">Titel *</label>
                        <input id="titel" name="titel" maxlength="200"
                               value="<?= old('titel', $book['titel']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="auteur">Auteur *</label>
                        <input id="auteur" name="auteur" maxlength="150"
                               value="<?= old('auteur', $book['auteur']) ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="publicatiejaar">Publicatiejaar</label>
                        <input type="number" id="publicatiejaar" name="publicatiejaar"
                               min="1000" max="<?= date('Y') ?>"
                               value="<?= old('publicatiejaar', $book['publicatiejaar']) ?>">
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
        // READ ALL MET ZOEKEN EN FILTEREN
        $search = trim((string) ($_GET['q'] ?? ''));
        $availability = (string) ($_GET['beschikbaar'] ?? '');
        $conditions = [];
        $parameters = [];

        if ($search !== '') {
            $conditions[] = '(titel LIKE ? OR auteur LIKE ? OR isbn LIKE ? OR categorie LIKE ?)';
            $like = '%' . $search . '%';
            array_push($parameters, $like, $like, $like, $like);
        }

        if ($availability === '1' || $availability === '0') {
            $conditions[] = 'beschikbaar = ?';
            $parameters[] = (int) $availability;
        }

        $sql = 'SELECT * FROM boeken';
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY titel';

        $statement = $pdo->prepare($sql);
        $statement->execute($parameters);
        $books = $statement->fetchAll();
        ?>

        <div class="page-heading">
            <div>
                <p class="eyebrow">Examenronde 02 &middot; Admin</p>
                <h1>Bibliotheekadministratie</h1>
                <p>Beheer de boekencollectie en beschikbaarheid.</p>
            </div>
            <div class="heading-actions">
                <a class="button button-secondary" href="public/catalogus.php">Gebruikerscatalogus</a>
                <a class="button button-secondary" href="public/lenen.php">Lenen en retourneren</a>
                <a class="button" href="?action=create">+ Boek toevoegen</a>
            </div>
        </div>

        <div class="toolbar">
            <form class="search-form" method="get">
                <input type="search" name="q" value="<?= e($search) ?>"
                       placeholder="Zoek titel, auteur, ISBN of categorie">
                <select name="beschikbaar" class="filter-select">
                    <option value="">Alle statussen</option>
                    <option value="1" <?= $availability === '1' ? 'selected' : '' ?>>Beschikbaar</option>
                    <option value="0" <?= $availability === '0' ? 'selected' : '' ?>>Uitgeleend</option>
                </select>
                <button class="button button-secondary" type="submit">Filteren</button>
                <?php if ($search !== '' || $availability !== ''): ?>
                    <a class="button button-secondary" href="index.php">Wissen</a>
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
                        <tr>
                            <th>Boek</th><th>ISBN</th><th>Categorie</th>
                            <th>Jaar</th><th>Status</th><th>Acties</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $row): ?>
                            <tr>
                                <td><strong><?= e($row['titel']) ?></strong><br><span class="count"><?= e($row['auteur']) ?></span></td>
                                <td><?= e($row['isbn']) ?></td>
                                <td><?= e($row['categorie']) ?></td>
                                <td><?= e($row['publicatiejaar'] ?: '—') ?></td>
                                <td>
                                    <span class="badge <?= $row['beschikbaar'] ? 'badge-success' : 'badge-warning' ?>">
                                        <?= $row['beschikbaar'] ? 'Beschikbaar' : 'Uitgeleend' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a class="button button-secondary button-small"
                                           href="?action=edit&amp;id=<?= e($row['id']) ?>">
                                            Wijzigen
                                        </a>
                                        <form method="post" data-confirm="Dit boek definitief verwijderen?">
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
