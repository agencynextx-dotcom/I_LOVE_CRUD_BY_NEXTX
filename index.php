<?php

declare(strict_types=1);

$rounds = [
    [
        'number' => '00',
        'title' => 'Voertuigenbeheer',
        'folder' => 'voertuigenbeheer',
        'tables' => 'users, voertuigen, onderhoud, audit_log',
        'focus' => 'Modulaire CRUD, eigenaarschap en alle plusfuncties',
        'level' => 'Volledig voorbeeld',
        'access' => 'Alleen eigen gegevens',
        'links' => [
            ['label' => 'Open dashboard', 'href' => 'voertuigenbeheer/', 'secondary' => false],
        ],
    ],
    [
        'number' => '01',
        'title' => 'Studentinschrijvingen',
        'folder' => 'studenten',
        'tables' => 'users, opleidingen, studenten',
        'focus' => 'CRUD, foreign key, JOIN en rapportfilter',
        'level' => 'Basis',
        'access' => 'Volledig admin',
        'links' => [
            ['label' => 'Open administratie', 'href' => 'studenten/', 'secondary' => false],
        ],
    ],
    [
        'number' => '02',
        'title' => 'Bibliotheek',
        'folder' => 'bibliotheek',
        'tables' => 'users, leden, boeken, leningen',
        'focus' => 'Relatietabel, transacties en retourproces',
        'level' => 'Basis +',
        'access' => 'Gebruiker + admin',
        'links' => [
            ['label' => 'Open catalogus', 'href' => 'bibliotheek/public/catalogus.php', 'secondary' => false],
            ['label' => 'Open admin', 'href' => 'bibliotheek/', 'secondary' => true],
        ],
    ],
    [
        'number' => '03',
        'title' => 'Inventaris',
        'folder' => 'inventaris',
        'tables' => 'users, categorieen, producten, voorraadmutaties',
        'focus' => 'Mutatiehistoriek, JOINs en voorraadregel',
        'level' => 'Gemiddeld',
        'access' => 'Volledig admin',
        'links' => [
            ['label' => 'Open administratie', 'href' => 'inventaris/', 'secondary' => false],
        ],
    ],
    [
        'number' => '04',
        'title' => 'Afsprakenplanner',
        'folder' => 'afspraken',
        'tables' => 'users, diensten, medewerkers, afspraken',
        'focus' => 'Meerdere relaties, planning en dubbele-boekingcontrole',
        'level' => 'Gemiddeld +',
        'access' => 'Gebruikersportaal',
        'links' => [
            ['label' => 'Open mijn afspraken', 'href' => 'afspraken/', 'secondary' => false],
        ],
    ],
];

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Vier PHP en MySQL oefenrondes voor een examen van zes uur.">
    <title>PHP-examenrondes</title>
    <link rel="stylesheet" href="overview.css">
</head>
<body>
    <header class="hero">
        <div class="container">
            <p class="eyebrow">PHP &middot; PDO &middot; MySQL</p>
            <h1>Modulaire PHP-cheat sheets</h1>
            <p class="intro">
                Ieder onderdeel heeft een duidelijke eigen pagina. Gebruik de projecten
                als eenvoudige referentie voor CRUD, authenticatie en beveiliging.
            </p>
        </div>
    </header>

    <main class="container">
        <div class="notice">
            <strong>Voor je begint:</strong> start Apache en MySQL in XAMPP.
            Iedere ronde heeft een eigen database en vereist een apart account.
        </div>

        <div class="round-grid">
            <?php foreach ($rounds as $round): ?>
                <article class="round-card">
                    <div class="round-topline">
                        <span class="round-number">Ronde <?= escape($round['number']) ?></span>
                        <span class="level"><?= escape($round['level']) ?></span>
                    </div>
                    <h2><?= escape($round['title']) ?></h2>
                    <dl>
                        <div>
                            <dt>Tabellen</dt>
                            <dd><?= escape($round['tables']) ?></dd>
                        </div>
                        <div>
                            <dt>Focus</dt>
                            <dd><?= escape($round['focus']) ?></dd>
                        </div>
                        <div>
                            <dt>Toegang</dt>
                            <dd><?= escape($round['access']) ?></dd>
                        </div>
                    </dl>
                    <div class="card-actions">
                        <?php foreach ($round['links'] as $link): ?>
                            <a class="button <?= $link['secondary'] ? 'button-secondary' : '' ?>"
                               href="<?= escape($link['href']) ?>">
                                <?= escape($link['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <section class="scope">
            <h2>Gelijke basis, verschillende referenties</h2>
            <p>
                Alle rondes bevatten authenticatie, prepared statements,
                servervalidatie en CRUD. Studenten toont CSRF, bibliotheek toont
                rollen en afspraken toont autorisatie op de eigenaar van een record.
            </p>
            <a class="button button-secondary" href="REFERENTIES.md">
                Open referentiegids
            </a>
        </section>
    </main>

    <footer>
        <div class="container">Oefenomgeving &middot; <?= date('Y') ?></div>
    </footer>
</body>
</html>
