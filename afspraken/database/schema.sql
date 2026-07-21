-- Examenronde 4: afspraken met diensten en medewerkers.
-- Dit bestand kan handmatig in phpMyAdmin worden geimporteerd.

CREATE DATABASE IF NOT EXISTS examenronde_04_mijn_afspraken
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE examenronde_04_mijn_afspraken;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS diensten (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(120) NOT NULL UNIQUE,
    duur_minuten SMALLINT UNSIGNED NOT NULL,
    prijs DECIMAL(10, 2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS medewerkers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(150) NOT NULL,
    specialisatie VARCHAR(120) NOT NULL,
    actief TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO diensten (id, naam, duur_minuten, prijs) VALUES
    (1, 'Intakegesprek', 30, 150.00),
    (2, 'Adviesgesprek', 60, 300.00),
    (3, 'Controleafspraak', 30, 125.00);

INSERT IGNORE INTO medewerkers (id, naam, specialisatie, actief) VALUES
    (1, 'Alicia Pinas', 'Intake en advies', 1),
    (2, 'Ravi Kanhai', 'Advies en controle', 1),
    (3, 'Maria Cairo', 'Algemene dienstverlening', 1);

CREATE TABLE IF NOT EXISTS afspraken (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    dienst_id INT UNSIGNED NOT NULL,
    medewerker_id INT UNSIGNED NOT NULL,
    klantnaam VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    afspraak_op DATETIME NOT NULL,
    status ENUM('Gepland', 'Voltooid', 'Geannuleerd') NOT NULL DEFAULT 'Gepland',
    notities TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_afspraak_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_afspraak_dienst
        FOREIGN KEY (dienst_id) REFERENCES diensten(id) ON DELETE RESTRICT,
    CONSTRAINT fk_afspraak_medewerker
        FOREIGN KEY (medewerker_id) REFERENCES medewerkers(id) ON DELETE RESTRICT,
    INDEX idx_medewerker_tijd (medewerker_id, afspraak_op)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
