# Referentiegids PHP-projecten

## Kleinste opzet: crud

`crud` bevat drie minimale, op zichzelf staande voorbeelden zonder login, voor als je snel iets wilt opzoeken.

| Wat wil je bouwen? | Bestand |
| --- | --- |
| CRUD zonder relaties (zoals de User List-schermafbeelding) | `crud/simple/` (database `crud_simple`) |
| CRUD met lookup (foreign key dropdown) en een pass/fail-status | `crud/relations/` (database `crud_relations`) |
| Dashboard dat alleen data ophaalt en toont (COUNT, SUM, AVG, GROUP BY) | `crud/dashboard/index.php` |

Importeer per map eerst het bijbehorende `database/schema.sql`. `crud/dashboard` gebruikt dezelfde database als `crud/relations`. `crud/simple` heeft nu ook een inlogscherm (`Auth/login.php`, `Auth/register.php`, `Auth/logout.php`) met een aparte `accounts`-tabel naast de CRUD-data.

## CSRF-voorbeelden

`crud/csrf` laat zien hoe je CSRF wél en niet afhandelt, met een echte aanvalspagina per voorbeeld.

| Wat wil je zien? | Bestand |
| --- | --- |
| Basis CSRF-token in een verborgen veld, gecontroleerd met `hash_equals()` | `crud/csrf/simple/` (database `crud_csrf_simple`) |
| Een kwetsbaar GET-endpoint naast een veilig POST+token-endpoint, plus veelgemaakte fouten | `crud/csrf/tricky/` (database `crud_csrf_tricky`) |

Open in elke map `attack.html` om een nagemaakte aanvalspagina te simuleren: bij `simple` wordt een vervalst formulier geweigerd (403), bij `tricky` slaagt de aanval op het kwetsbare GET-endpoint via een `<img>`-tag, terwijl het POST+token-endpoint standhoudt.

## Start hier: modulair voertuigenbeheer

`voertuigenbeheer` is de meest specifieke referentie wanneer je niet alles in één bestand wilt zetten.

| Wat wil je bouwen? | Bestand |
| --- | --- |
| Dashboardtotalen en waarschuwingen | `voertuigenbeheer/index.php` |
| Zoeken, filteren, sorteren, pagineren en CSV | `voertuigenbeheer/voertuigen/index.php` |
| Record toevoegen | `voertuigenbeheer/voertuigen/create.php` |
| Eén eigen record ophalen en bekijken | `voertuigenbeheer/voertuigen/show.php` |
| Record wijzigen | `voertuigenbeheer/voertuigen/edit.php` |
| Verwijderen via POST met waarschuwing | `voertuigenbeheer/voertuigen/delete.php` |
| Status direct wijzigen | `voertuigenbeheer/voertuigen/status.php` |
| Child-record toevoegen/wijzigen/verwijderen | `voertuigenbeheer/onderhoud/` |
| Eigenaarschap controleren | `voertuigenbeheer/Includes/functions.php` (`find_own_vehicle`) |
| Validatie en CSRF | `voertuigenbeheer/Includes/functions.php` |
| Tabellen en foreign keys | `voertuigenbeheer/database/schema.sql` |

De vaste route per CRUD is: lijst → detail → formulier → POST-actie. Daardoor heeft ieder bestand één hoofdtaak.

Deze gids is een snelle map van features naar voorbeeldprojecten. Gebruik hem om meteen te zien waar je moet kijken, zonder alle uitleg eromheen.

## Feature -> project

| Feature | Kijk in | Belangrijkste bestanden |
| --- | --- | --- |
| Simpele login en registratie | inventaris | Auth/, Includes/functions.php, Includes/db.php |
| Eenvoudige conventionele PDO-verbinding | alle projecten | Includes/db.php |
| Rollen voor admin en gebruiker | bibliotheek | Auth/, Includes/functions.php, database/schema.sql |
| CSRF-beveiliging op formulieren | studenten | Includes/functions.php, Auth/, index.php |
| Alleen eigen records mogen bewerken | afspraken | index.php, public/dagoverzicht.php |
| CRUD met foreign key | studenten | index.php, database/schema.sql |
| Validatie en foutmeldingen | studenten | index.php, Auth/register.php |
| Zoeken in meerdere kolommen | studenten | index.php |
| Meerdere filters combineren | inventaris | index.php |
| Verwijderen met POST en bevestiging | studenten | index.php, Includes/functions.php |
| Flashmeldingen na POST | alle projecten | Includes/functions.php, index.php |
| Voorraad aanpassen met transactie | inventaris | public/voorraad.php |
| Boeken lenen en retourneren | bibliotheek | public/lenen.php |
| Openbare pagina naast beheer | bibliotheek | public/catalogus.php, index.php |
| Rapporteren en filteren | studenten | public/rapport.php |
| JOIN-query's en relaties | bibliotheek | public/lenen.php, database/schema.sql |
| Bedrijfsregels zoals dubbele afspraken voorkomen | afspraken | index.php |
| Rollen + eigenaar + CSRF samen | Helpdesk-ticketsysteem | index.php, Admin/tickets.php, Includes/functions.php |
| CRUD met ?action= in één bestand | Helpdesk-ticketsysteem | index.php |
| Alleen bepaalde statussen mogen wijzigen | Helpdesk-ticketsysteem | index.php |
| Paginering met LIMIT en OFFSET | studenten | index.php (zoek op `$perPage`) |
| Veilig sorteren op kolom met whitelist | studenten | index.php (zoek op `$sortableColumns`) |
| Status direct vanuit een overzicht wijzigen | studenten | index.php (zoek op `change_status`) |
| Dashboard met totalen | studenten | index.php (zoek op `$dashboard` en `stat-card`) |
| Eenvoudige auditlog | studenten | Includes/functions.php (`audit`), index.php (`action=audit`), database/schema.sql (`audit_log`) |
| CSV-export met actieve zoekopdracht en sortering | studenten | index.php (zoek op `export` en `fputcsv`) |
| Volledig modulair dashboardvoorbeeld | voertuigenbeheer | index.php, voertuigen/, onderhoud/, Includes/ |

## Kort per project

### Aanbevolen bestandsafspraak voor alle nieuwe oefeningen

Gebruik voortaan dezelfde simpele namen als in `voertuigenbeheer`: `index.php` voor de lijst,
`show.php` voor details, `create.php`, `edit.php`, `delete.php` en eventueel `_form.php`.
De oudere projecten blijven bruikbaar als inhoudelijke referentie, maar hun grote `index.php`
is een legacy-voorbeeld. Kopieer voor een nieuwe opdracht liever de structuur van
`voertuigenbeheer` en alleen de relevante query of bedrijfsregel uit het oudere project.

### inventaris
Goed voorbeeld voor de kleinste opzet: login, registratie, filters en een simpele db.php.

### bibliotheek
Handig als je rollen nodig hebt. Hier zie je ook hoe require_role('admin') werkt.

### studenten
De beste referentie voor CSRF, validatie, CRUD en zoeken. Dit project bevat nu ook alle zes plusfuncties: paginering, sortering, snelle statuswijziging, dashboardtotalen, auditlog en CSV-export.

## Plusfuncties uitproberen

Importeer eerst `studenten/database/schema.sql` opnieuw in phpMyAdmin. De statements gebruiken `IF NOT EXISTS`, zodat bestaande tabellen en gegevens blijven bestaan; alleen de nieuwe tabel `audit_log` wordt toegevoegd.

Open daarna `studenten/index.php`:

1. Het dashboard staat boven de zoekbalk.
2. Klik op de kolomkoppen om oplopend of aflopend te sorteren.
3. Bij meer dan tien resultaten verschijnen paginaknoppen onder de tabel.
4. Gebruik de statuskeuze in een rij en klik op **OK**.
5. Klik bovenaan op **Auditlog** om wijzigingen te bekijken.
6. Klik op **CSV exporteren** om de huidige zoekopdracht en sortering te exporteren. De export bevat alle passende records, niet alleen de huidige pagina.

### Veiligheidspunten om als referentie te behouden

- Kolomnamen kunnen geen placeholders gebruiken. Daarom staat sortering in de whitelist `$sortableColumns`.
- Zoekwaarden blijven prepared-statementparameters.
- `LIMIT` en `OFFSET` worden als integers gebonden.
- Status wijzigen gebruikt POST, CSRF-controle en een lijst met toegestane waarden.
- CSV-output wordt vóór HTML verstuurd, zodat headers correct blijven werken.

## Databaseverbinding

Alle projecten gebruiken dezelfde eenvoudige examenopzet in `Includes/db.php`: vier verbindingsvariabelen, `new PDO(...)` in een `try/catch` en daarna direct de variabele `$pdo`. Importeer bij ieder project eerst `database/schema.sql` via phpMyAdmin; de applicaties maken hun database niet automatisch aan.

### afspraken
Gebruik dit als alleen de ingelogde eigenaar zijn eigen records mag zien of wijzigen.

### Helpdesk-ticketsysteem
Gebruik dit voor combinaties van rollen, eigendom en CSRF in één project.

## Waarom is public leeg bij Helpdesk-ticketsysteem?

Omdat dit project geen aparte openbare pagina's nodig heeft. De kern zit in index.php en de adminpagina in Admin/tickets.php. Daarom staat er in public niets wat inhoudelijk nodig is; de map is alleen een plek voor eventuele publieke bestanden als die later worden toegevoegd. De styling staat apart in styles/styles.css.
