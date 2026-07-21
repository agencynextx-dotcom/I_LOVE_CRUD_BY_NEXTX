<?php

declare(strict_types=1);

require_once __DIR__ . '/../Includes/db.php';
require_once __DIR__ . '/../Includes/functions.php';

require_login('../Auth/login.php');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operation = (string) ($_POST['operation'] ?? '');

    if ($operation === 'create_member') {
        $memberNumber = strtoupper(trim((string) ($_POST['lidnummer'] ?? '')));
        $name = trim((string) ($_POST['naam'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($memberNumber === '' || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Vul een lidnummer, naam en geldig e-mailadres in.';
        } else {
            try {
                $statement = $pdo->prepare(
                    'INSERT INTO leden (lidnummer, naam, email) VALUES (?, ?, ?)'
                );
                $statement->execute([$memberNumber, $name, $email]);
                flash('success', 'Het bibliotheeklid is geregistreerd.');
                redirect('lenen.php');
            } catch (PDOException $exception) {
                $error = $exception->getCode() === '23000'
                    ? 'Dit lidnummer of e-mailadres bestaat al.'
                    : 'Het lid kon niet worden opgeslagen.';
            }
        }
    }

    if ($operation === 'borrow') {
        $bookId = filter_input(INPUT_POST, 'boek_id', FILTER_VALIDATE_INT);
        $memberId = filter_input(INPUT_POST, 'lid_id', FILTER_VALIDATE_INT);

        if (!$bookId || !$memberId) {
            $error = 'Kies een beschikbaar boek en een actief lid.';
        } else {
            $pdo->beginTransaction();
            try {
                $memberCheck = $pdo->prepare('SELECT id FROM leden WHERE id = ? AND actief = 1');
                $memberCheck->execute([$memberId]);
                if (!$memberCheck->fetch()) {
                    throw new RuntimeException('Het gekozen lid is niet actief.');
                }

                $update = $pdo->prepare(
                    'UPDATE boeken SET beschikbaar = 0 WHERE id = ? AND beschikbaar = 1'
                );
                $update->execute([$bookId]);
                if ($update->rowCount() !== 1) {
                    throw new RuntimeException('Het boek is niet meer beschikbaar.');
                }

                $insert = $pdo->prepare(
                    'INSERT INTO leningen
                        (boek_id, lid_id, geleend_op, verwacht_terug_op)
                     VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY))'
                );
                $insert->execute([$bookId, $memberId]);
                $pdo->commit();
                flash('success', 'Het boek is voor veertien dagen uitgeleend.');
                redirect('lenen.php');
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $exception->getMessage();
            }
        }
    }

    if ($operation === 'return') {
        $loanId = filter_input(INPUT_POST, 'lening_id', FILTER_VALIDATE_INT);

        if ($loanId) {
            $pdo->beginTransaction();
            try {
                $loan = $pdo->prepare(
                    'SELECT boek_id FROM leningen WHERE id = ? AND terug_op IS NULL FOR UPDATE'
                );
                $loan->execute([$loanId]);
                $activeLoan = $loan->fetch();
                if (!$activeLoan) {
                    throw new RuntimeException('Deze lening is al afgehandeld.');
                }

                $statement = $pdo->prepare('UPDATE leningen SET terug_op = CURDATE() WHERE id = ?');
                $statement->execute([$loanId]);
                $statement = $pdo->prepare('UPDATE boeken SET beschikbaar = 1 WHERE id = ?');
                $statement->execute([$activeLoan['boek_id']]);
                $pdo->commit();
                flash('success', 'Het boek is geretourneerd.');
                redirect('lenen.php');
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $exception->getMessage();
            }
        }
    }
}

$availableBooks = $pdo->query(
    'SELECT id, titel, auteur FROM boeken WHERE beschikbaar = 1 ORDER BY titel'
)->fetchAll();
$activeMembers = $pdo->query(
    'SELECT id, lidnummer, naam FROM leden WHERE actief = 1 ORDER BY naam'
)->fetchAll();
$loans = $pdo->query(
    'SELECT leningen.*, boeken.titel, leden.lidnummer, leden.naam AS lidnaam,
            (verwacht_terug_op < CURDATE()) AS te_laat
     FROM leningen
     JOIN boeken ON boeken.id = leningen.boek_id
     JOIN leden ON leden.id = leningen.lid_id
     WHERE leningen.terug_op IS NULL
     ORDER BY leningen.verwacht_terug_op, boeken.titel'
)->fetchAll();

page_start('Lenen en retourneren', '', 'Beheer leden en actieve boekleningen.', '../');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Examenronde 02 &middot; Admin</p>
            <h1>Leden en leningen</h1>
            <p>Koppel een beschikbaar boek aan een actief bibliotheeklid.</p>
        </div>
        <a class="button button-secondary" href="../index.php">&larr; Boeken</a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <h2>Nieuw lid registreren</h2>
        <form method="post">
            <input type="hidden" name="operation" value="create_member">
            <div class="form-grid">
                <div class="form-field">
                    <label for="lidnummer">Lidnummer *</label>
                    <input id="lidnummer" name="lidnummer" maxlength="20" required>
                </div>
                <div class="form-field">
                    <label for="naam">Naam *</label>
                    <input id="naam" name="naam" maxlength="150" required>
                </div>
                <div class="form-field">
                    <label for="email">E-mailadres *</label>
                    <input type="email" id="email" name="email" maxlength="190" required>
                </div>
            </div>
            <div class="form-actions">
                <button class="button button-secondary" type="submit">Lid registreren</button>
            </div>
        </form>
    </section>

    <section class="panel section-gap">
        <h2>Boek uitlenen</h2>
        <form method="post">
            <input type="hidden" name="operation" value="borrow">
            <div class="form-grid">
                <div class="form-field">
                    <label for="boek_id">Beschikbaar boek *</label>
                    <select id="boek_id" name="boek_id" required>
                        <option value="">Kies een boek</option>
                        <?php foreach ($availableBooks as $book): ?>
                            <option value="<?= e($book['id']) ?>">
                                <?= e($book['titel'] . ' - ' . $book['auteur']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="lid_id">Actief lid *</label>
                    <select id="lid_id" name="lid_id" required>
                        <option value="">Kies een lid</option>
                        <?php foreach ($activeMembers as $member): ?>
                            <option value="<?= e($member['id']) ?>">
                                <?= e($member['lidnummer'] . ' - ' . $member['naam']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button class="button" type="submit">Uitlenen</button>
            </div>
        </form>
    </section>

    <h2>Actieve leningen</h2>
    <div class="table-wrap">
        <?php if ($loans === []): ?>
            <div class="empty">Er zijn geen actieve leningen.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Boek</th><th>Lid</th><th>Geleend</th><th>Uiterlijk terug</th><th>Actie</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><?= e($loan['titel']) ?></td>
                            <td><?= e($loan['lidnaam']) ?><br><span class="count"><?= e($loan['lidnummer']) ?></span></td>
                            <td><?= date('d-m-Y', strtotime($loan['geleend_op'])) ?></td>
                            <td>
                                <span class="badge <?= $loan['te_laat'] ? 'badge-danger' : 'badge-success' ?>">
                                    <?= date('d-m-Y', strtotime($loan['verwacht_terug_op'])) ?>
                                </span>
                            </td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="operation" value="return">
                                    <input type="hidden" name="lening_id" value="<?= e($loan['id']) ?>">
                                    <button class="button button-small" type="submit">Retourneren</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<?php page_end(); ?>
