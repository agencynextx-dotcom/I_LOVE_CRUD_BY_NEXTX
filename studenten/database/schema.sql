-- Examenronde 1: studentinschrijvingen.
-- Dit bestand kan handmatig in phpMyAdmin worden geimporteerd.

CREATE DATABASE IF NOT EXISTS examenronde_01_studenten
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE examenronde_01_studenten;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS opleidingen (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(12) NOT NULL UNIQUE,
    naam VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO opleidingen (id, code, naam) VALUES
    (1, 'ICT', 'ICT'),
    (2, 'FIN', 'Financieel Management'),
    (3, 'HRM', 'Human Resource Management'),
    (4, 'MER', 'Management, Economie en Recht');

CREATE TABLE IF NOT EXISTS studenten (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opleiding_id INT UNSIGNED NOT NULL,
    studentnummer VARCHAR(20) NOT NULL UNIQUE,
    voornaam VARCHAR(80) NOT NULL,
    achternaam VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    studiejaar TINYINT UNSIGNED NOT NULL,
    status ENUM('Actief', 'Afgestudeerd', 'Gestopt') NOT NULL DEFAULT 'Actief',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_opleiding
        FOREIGN KEY (opleiding_id) REFERENCES opleidingen(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eenvoudige auditlog: bewaart wie welke wijziging uitvoerde.
CREATE TABLE IF NOT EXISTS audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    actie VARCHAR(30) NOT NULL,
    entiteit VARCHAR(50) NOT NULL,
    entiteit_id INT UNSIGNED NULL,
    details VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
