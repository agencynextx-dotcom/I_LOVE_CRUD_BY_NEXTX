CREATE DATABASE IF NOT EXISTS crud_csrf_simple;
USE crud_csrf_simple;

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO messages (content) VALUES
    ('Welkom op het prikbord.'),
    ('Verwijderen kan alleen met een geldig CSRF-token.');
