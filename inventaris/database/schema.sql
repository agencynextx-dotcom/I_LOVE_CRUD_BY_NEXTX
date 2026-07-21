-- Examenronde 3: inventaris en voorraadmutaties.
-- Dit bestand kan handmatig in phpMyAdmin worden geimporteerd.

CREATE DATABASE IF NOT EXISTS examenronde_03_inventaris
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE examenronde_03_inventaris;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categorieen (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO categorieen (id, naam) VALUES
    (1, 'Kantoorartikelen'),
    (2, 'Elektronica'),
    (3, 'Schoonmaak'),
    (4, 'Verpakking');

CREATE TABLE IF NOT EXISTS producten (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT UNSIGNED NOT NULL,
    sku VARCHAR(30) NOT NULL UNIQUE,
    naam VARCHAR(150) NOT NULL,
    voorraad INT UNSIGNED NOT NULL DEFAULT 0,
    minimumvoorraad INT UNSIGNED NOT NULL DEFAULT 0,
    prijs DECIMAL(10, 2) NOT NULL DEFAULT 0,
    locatie VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_categorie
        FOREIGN KEY (categorie_id) REFERENCES categorieen(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS voorraadmutaties (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    wijziging INT NOT NULL,
    reden VARCHAR(200) NOT NULL,
    gemuteerd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mutatie_product
        FOREIGN KEY (product_id) REFERENCES producten(id) ON DELETE CASCADE,
    CONSTRAINT fk_mutatie_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
