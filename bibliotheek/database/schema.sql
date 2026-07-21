-- Examenronde 2: bibliotheek en uitleningen.
-- Dit bestand kan handmatig in phpMyAdmin worden geimporteerd.

CREATE DATABASE IF NOT EXISTS examenronde_02_bibliotheek
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE examenronde_02_bibliotheek;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'gebruiker') NOT NULL DEFAULT 'gebruiker',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leden (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lidnummer VARCHAR(20) NOT NULL UNIQUE,
    naam VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    actief TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS boeken (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) NOT NULL UNIQUE,
    titel VARCHAR(200) NOT NULL,
    auteur VARCHAR(150) NOT NULL,
    categorie VARCHAR(80) NOT NULL,
    publicatiejaar SMALLINT UNSIGNED NULL,
    beschikbaar TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leningen (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    boek_id INT UNSIGNED NOT NULL,
    lid_id INT UNSIGNED NOT NULL,
    geleend_op DATE NOT NULL,
    verwacht_terug_op DATE NOT NULL,
    terug_op DATE NULL,
    CONSTRAINT fk_lening_boek
        FOREIGN KEY (boek_id) REFERENCES boeken(id) ON DELETE RESTRICT,
    CONSTRAINT fk_lening_lid
        FOREIGN KEY (lid_id) REFERENCES leden(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
