<?php
require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

$statement = $pdo->query('SELECT * FROM messages ORDER BY id');
$messages = $statement->fetchAll();

page_start('Overzicht');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">CSRF &middot; simpel</p>
            <h1>Prikbord</h1>
            <p>Eén token per sessie, meegestuurd als verborgen veld en gecontroleerd met <code>hash_equals()</code>.</p>
        </div>
        <a class="button" href="create.php">Bericht toevoegen</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Bericht</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $index => $message): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= e($message['content']) ?></td>
                        <td class="actions">
                            <form method="post" action="delete.php" onsubmit="return confirm('Bericht verwijderen?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $message['id'] ?>">
                                <button class="button button-small button-danger">Verwijderen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if ($messages === []): ?>
                    <tr><td colspan="3" class="empty">Geen berichten gevonden.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <section class="panel">
        <h2>Waarom dit veilig is</h2>
        <p>
            Verwijderen gebeurt alleen via POST met een verborgen <code>csrf_token</code>-veld.
            Een kwaadwillende pagina kan dat token niet raden of uitlezen (same-origin policy),
            dus een nagemaakt formulier van elders wordt met 403 geweigerd.
            Probeer het zelf op de <a href="attack.html">aanvalspagina</a>.
        </p>
    </section>
</main>
<?php page_end(); ?>
