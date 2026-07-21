# Voertuigenbeheer - simpele modulaire cheat sheet

## Installeren

1. Start Apache en MySQL in XAMPP.
2. Open phpMyAdmin.
3. Importeer `database/schema.sql`.
4. Open `http://localhost/Oefenen/voertuigenbeheer/`.
5. Registreer een account.

## Mappenstructuur

```text
Auth/          registreren, inloggen en uitloggen
Includes/      database, beveiliging, validatie en gedeelde functies
voertuigen/    één bestand per CRUD-actie
onderhoud/     één bestand per CRUD-actie
audit/         auditlog bekijken
database/      tabellen en relaties
styles/        vormgeving
index.php      alleen het dashboard
```

## Vast CRUD-patroon

```text
index.php   = lijst en zoeken
show.php    = één record bekijken
create.php  = toevoegen
edit.php    = wijzigen
delete.php  = alleen POST-verwijdering
_form.php   = gedeeld formulier
```

Begin bij `voertuigen/create.php` voor een simpele INSERT en vergelijk die daarna met
`onderhoud/create.php` voor een INSERT met een foreign key.

## Beveiligingspatroon

Iedere beschermde pagina roept eerst `require_login()` aan. Een voertuig wordt niet
alleen op id gezocht, maar altijd met `id AND user_id` via `find_own_vehicle()`. Onderhoud
wordt via een JOIN met het voertuig gecontroleerd. Alle POST-formulieren bevatten een
CSRF-token en alle uitvoer gaat door `e()` (`htmlspecialchars`).
