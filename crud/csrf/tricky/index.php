<?php
require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

$wallets = $pdo->query('SELECT * FROM wallets ORDER BY id')->fetchAll();

page_start('Overzicht');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">CSRF &middot; lastig</p>
            <h1>Portemonnee</h1>
            <p>Twee versies van dezelfde actie (geld overmaken), zodat je het verschil ziet.</p>
        </div>
    </div>

    <div class="dashboard">
        <?php foreach ($wallets as $wallet): ?>
            <div class="stat-card">
                <span><?= e($wallet['name']) ?></span>
                <strong>SRD <?= number_format((float) $wallet['balance'], 2, ',', '.') ?></strong>
            </div>
        <?php endforeach; ?>
    </div>

    <section class="panel">
        <h2>1. Kwetsbaar: overmaken via GET, zonder token</h2>
        <p>
            Deze link verandert data met een gewone <code>GET</code>-request en controleert helemaal
            geen CSRF-token. Een link, een <code>&lt;img&gt;</code>-tag of een auto-submit formulier
            op een andere site kan dit zonder jouw toestemming laten gebeuren.
        </p>
        <a class="button button-danger"
           href="vulnerable-transfer.php?from=1&amp;to=2&amp;amount=50">
            Maak SRD 50 over (kwetsbare link)
        </a>
        <p class="empty">Probeer ook eens de <a href="attack.html">aanvalspagina</a> te openen zonder op deze link te klikken.</p>
    </section>

    <section class="panel">
        <h2>2. Veilig: overmaken via POST, met token</h2>
        <p>
            Dezelfde actie, maar nu als <code>POST</code>-formulier met een verborgen CSRF-token dat
            met <code>hash_equals()</code> wordt gecontroleerd. Een externe pagina kan dit token niet
            lezen of raden, dus een nagemaakt formulier wordt geweigerd met 403.
        </p>
        <form method="post" action="secure-transfer.php">
            <?= csrf_field() ?>
            <input type="hidden" name="from" value="1">
            <input type="hidden" name="to" value="2">
            <div class="form-grid">
                <div class="form-field">
                    <label for="amount">Bedrag</label>
                    <input type="number" id="amount" name="amount" min="0.01" step="0.01" value="50" required>
                </div>
            </div>
            <button class="button">Maak veilig over</button>
        </form>
    </section>

    <section class="panel">
        <h2>Veelgemaakte fouten bij CSRF</h2>
        <ul>
            <li><strong>State laten veranderen via GET.</strong> GET-requests worden automatisch
                gevolgd door links, afbeeldingen en prefetching &mdash; nooit data mee wijzigen.</li>
            <li><strong>Token vergelijken met <code>==</code> of <code>strcmp()</code>.</strong>
                Dat lekt timinginformatie; gebruik altijd <code>hash_equals()</code>.</li>
            <li><strong>Token uit de querystring of een header lezen die de browser altijd meestuurt.</strong>
                Het token moet uit het formulier (POST-body) komen, niet uit iets wat automatisch meegaat.</li>
            <li><strong>Sessie-id niet vernieuwen na login.</strong> Doe dat met
                <code>session_regenerate_id(true)</code>, anders blijft een oud, eventueel gestolen token geldig.</li>
            <li><strong>Alleen de eerste actie beveiligen.</strong> Iedere route die data wijzigt
                (toevoegen, wijzigen, verwijderen, overmaken) heeft zijn eigen <code>verify_csrf()</code> nodig.</li>
        </ul>
    </section>
</main>
<?php page_end(); ?>
