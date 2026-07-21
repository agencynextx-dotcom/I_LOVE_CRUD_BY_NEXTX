CREATE DATABASE IF NOT EXISTS crud_relations;
USE crud_relations;

CREATE TABLE IF NOT EXISTS opleidingen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS studenten (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(100) NOT NULL,
    opleiding_id INT NOT NULL,
    cijfer DECIMAL(4,2) NOT NULL,
    status ENUM('In behandeling', 'Geslaagd', 'Gezakt') NOT NULL DEFAULT 'In behandeling',
    FOREIGN KEY (opleiding_id) REFERENCES opleidingen(id)
);

INSERT INTO opleidingen (naam) VALUES
    ('Software Engineering'),
    ('Data Analyse'),
    ('Netwerkbeheer');

INSERT INTO studenten (naam, opleiding_id, cijfer, status) VALUES
    ('Codehal', 1, 7.50, 'Geslaagd'),
    ('Amina Soekhoe', 2, 4.80, 'Gezakt'),
    ('Ravi Sitaram', 3, 6.20, 'In behandeling');
